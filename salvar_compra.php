<?php

declare(strict_types = 1);

header('Content-Type: application/json; charset=utf-8');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once(__DIR__ . "conecta.php");

$transacaoIniciada = false;

// Retorna uma resposta JSON e encerra o processamento
function responder(int $status, array $dados): never {
    http_response_code($status);

    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

    exit;
}

// Valida um inteiro positivo recebido como número ou string decimal
function inteiroPositivo(mixed $valor): ?int {
    if (is_int($valor)) {
        return $valor > 0 ? $valor : null;
    }

    if (!is_string($valor) || !preg_match('/^[1-9][0-9]*$/', $valor)) {
        return null;
    }

    $numero = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $numero === false ? null : $numero;
}

// Converte um preço decimal com duas casas para centavos, aceitando somente a representação decimal convencional
function precoEmCentavos(mixed $valor): int {
    if (!is_string($valor) && !is_int($valor)) {
        throw new RuntimeException('Preço inválido no banco');
    }

    $valor = (string) $valor;

    if (!preg_match('/^([0-9]+)\.([0-9]{2})$/', $valor, $partes)) {
        throw new RuntimeException('Preço inválido no banco');
    }

    $inteiro = (int) $partes[1];

    if ((string) $inteiro !== ltrim($partes[1], '0') && !preg_match('/^0+$/', $partes[1])) {
        throw new RuntimeException('Preço fora do limite permitido');
    }

    if ($inteiro > 10000000) {
        throw new RuntimeException('Preço fora do limite permitido');
    }

    return ($inteiro * 100) + (int) $partes[2];
}

// Formata centavos como decimal com duas casas
function formatarDinheiro(int $centavos): string {
    return intdiv($centavos, 100) . '.' . str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
}

try {
    // 1. Aceita somente POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responder(405, ['sucesso' => false, 'mensagem' => 'Método de requisição inválido']);
    }

    mysqli_set_charset($conexao, 'utf8mb4');

    // 2. Recebe e valida os dados do cliente
    $nome = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['phone'] ?? '');
    $endereco = trim($_POST['address'] ?? '');
    $jsonCarrinho = $_POST['cart'] ?? '';

    if (!is_string($nome) || !is_string($email) || !is_string($telefone) || !is_string($endereco) || !is_string($jsonCarrinho)) {
        responder(400, ['sucesso' => false, 'mensagem' => 'Dados enviados em formato inválido']);
    }

    if ($nome === '' || $email === '' || $telefone === '' || $endereco === '') {
        responder(400, ['sucesso' => false, 'mensagem' => 'Preencha todos os campos obrigatórios']);
    }

    if (mb_strlen($nome) > 200 || mb_strlen($email) > 150 || mb_strlen($telefone) > 30 || mb_strlen($endereco) > 500) {
        responder(400, ['sucesso' => false, 'mensagem' => 'Um ou mais campos excedem o tamanho permitido']);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        responder(400, ['sucesso' => false, 'mensagem' => 'Informe um e-mail válido']);
    }

    if ($jsonCarrinho === '' || strlen($jsonCarrinho) > 100000) {
        responder(400, ['sucesso' => false, 'mensagem' => 'Carrinho vazio ou muito grande']);
    }

    // 3. Decodifica o JSON
    $itensRecebidos = json_decode($jsonCarrinho, true, 32, JSON_THROW_ON_ERROR);

    if (!is_array($itensRecebidos) || !array_is_list($itensRecebidos) || count($itensRecebidos) === 0 || count($itensRecebidos) > 100) {
        responder(400, ['sucesso' => false, 'mensagem' => 'Carrinho inválido']);
    }

    // 4. Valida os IDs e as quantidades e agrupa produtos repetidos
    $quantidades = [];

    foreach ($itensRecebidos as $item) {
        if (!is_array($item)) {
            throw new InvalidArgumentException('Item inválido no carrinho');
        }

        $id = inteiroPositivo($item['id'] ?? null);
        $quantidade = inteiroPositivo($item['quantity'] ?? null);

        if ($id === null || $quantidade === null) {
            throw new InvalidArgumentException('Produto ou quantidade inválidos');
        }

        if ($quantidade > 10000) {
            throw new InvalidArgumentException('Quantidade acima do limite permitido');
        }

        $anterior = $quantidades[$id] ?? 0;

        if ($anterior > 10000 - $quantidade) {
            throw new InvalidArgumentException('Quantidade acima do limite permitido');
        }

        $quantidades[$id] = $anterior + $quantidade;
    }

    // Ordena os IDs para reduzir o risco de deadlocks
    ksort($quantidades, SORT_NUMERIC);

    // 5. Inicia a transação
    mysqli_begin_transaction($conexao);
    $transacaoIniciada = true;

    $produtos = [];
    $totalCentavos = 0;

    // 6. Busca cada produto no banco e bloqueia sua linha
    $sqlProduto = "SELECT pro_id, pro_nome, pro_preco, pro_estoque, pro_imagem FROM tbl_produto WHERE pro_id = ? FOR UPDATE";

    $stmtProduto = mysqli_prepare($conexao, $sqlProduto);

    foreach ($quantidades as $id => $quantidade) {
        mysqli_stmt_bind_param($stmtProduto, 'i', $id);
        mysqli_stmt_execute($stmtProduto);

        mysqli_stmt_bind_result($stmtProduto, $produtoId, $produtoNome, $produtoPreco, $estoqueDisponivel, $produtoImagem);

        $encontrado = mysqli_stmt_fetch($stmtProduto);

        mysqli_stmt_free_result($stmtProduto);

        if (!$encontrado) {
            throw new InvalidArgumentException("Um dos produtos não existe mais");
        }

        $estoqueDisponivel = (int) $estoqueDisponivel;

        if ($estoqueDisponivel < $quantidade) {
            throw new InvalidArgumentException("Estoque insuficiente para " . $produtoNome);
        }

        $precoCentavos = precoEmCentavos($produtoPreco);

        // O limite de DECIMAL(10,2) é R$ 10000000,00
        if ($precoCentavos > 1000000099 || $precoCentavos > intdiv(1000000000, $quantidade)) {
            throw new InvalidArgumentException("Valor do pedido acima do limite permitido");
        }

        $subtotalCentavos = $precoCentavos * $quantidade;

        if ($totalCentavos > 1000000099 - $subtotalCentavos) {
            throw new InvalidArgumentException("Valor total acima do limite permitido");
        }

        $totalCentavos += $subtotalCentavos;

        $produtos[] = [
            'id' => (int) $produtoId,
            'name' => $produtoNome,
            'price' => formatarDinheiro($precoCentavos),
            'quantity' => $quantidade,
            'image' => $produtoImagem,
            'subtotal' => formatarDinheiro($subtotalCentavos)
        ];
    }

    mysqli_stmt_close($stmtProduto);

    // 7. Atualiza o estoque dentro da mesma transação
    $sqlEstoque = "UPDATE tbl_produto SET pro_estoque = pro_estoque - ? WHERE pro_id = ? AND pro_estoque >= ?";

    $stmtEstoque = mysqli_prepare($conexao, $sqlEstoque);

    foreach ($produtos as $produto) {
        $id = $produto['id'];
        $quantidade = $produto['quantity'];

        mysqli_stmt_bind_param($stmtEstoque, 'iii', $quantidade, $id, $quantidade);

        mysqli_stmt_execute($stmtEstoque);

        if (mysqli_stmt_affected_rows($stmtEstoque) !== 1) {
            throw new RuntimeException("Não foi possível reservar o estoque");
        }
    }

    mysqli_stmt_close($stmtEstoque);

    // 8. Gera o JSON canônico usando os dados do banco
    $itensOficiais = json_encode($produtos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);

    $total = formatarDinheiro($totalCentavos);

    // 9. Insere a compra
    $sqlCompra = 'INSERT INTO tbl_compras(nome, email, telefone, endereco, itens, total) VALUES(?, ?, ?, ?, ?, ?)';

    $stmtCompra = mysqli_prepare($conexao, $sqlCompra);

    mysqli_stmt_bind_param($stmtCompra, 'ssssss', $nome, $email, $telefone, $endereco, $itensOficiais, $total);

    mysqli_stmt_execute($stmtCompra);

    $compraId = mysqli_insert_id($conexao);

    mysqli_stmt_close($stmtCompra);

    // 10. Insere os itens da compra.
    $sqlItem = 'INSERT INTO tbl_itens_compra(compra_id, produto, preco, quantidade, subtotal) VALUES(?, ?, ?, ?, ?)';

    $stmtItem = mysqli_prepare($conexao, $sqlItem);

    foreach ($produtos as $produto) {
        $nomeProduto = $produto['name'];
        $preco = $produto['price'];
        $quantidade = $produto['quantity'];
        $subtotal = $produto['subtotal'];

        mysqli_stmt_bind_param($stmtItem, 'issis', $compraId, $nomeProduto, $preco, $quantidade, $subtotal);

        mysqli_stmt_execute($stmtItem);
    }

    mysqli_stmt_close($stmtItem);

    // 11. Confirma a compra e a baixa do estoque
    mysqli_commit($conexao);
    $transacaoIniciada = false;

    // 12. Responde ao JavaScript
    responder(200, ['sucesso' => true, 'mensagem' => "Pedido enviado com sucesso!", 'compra_id' => $compraId]);

} catch (JsonException $e) {
    responder(400, ['sucesso' => false, 'mensagem' => "O carrinho contém JSON inválido"]);

} catch (InvalidArgumentException $e) {
    if ($transacaoIniciada) {
        mysqli_rollback($conexao);
    }

    responder(400, ['sucesso' => false, 'mensagem' => $e->getMessage()]);
} catch (Throwable $e) {
    if ($transacaoIniciada) {
        try {
            mysqli_rollback($conexao);
        } catch (Throwable $rollbackError) {
            error_log("Falha no rollback da compra");
        }
    }

    error_log(
        "Erro ao processar compra: " . $e->getMessage()
    );

    responder(500, ['sucesso' => false, 'mensagem' => "Não foi possível processar o pedido. Tente novamente"
    ]);
}

?>