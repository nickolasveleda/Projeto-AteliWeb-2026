<?php

header('Content-Type: application/json; charset=utf-8');

require_once("conecta.php");

$pesquisa = $_GET['pesquisa'] ?? '';

$sql = "SELECT pro_id, pro_nome, pro_descricao, pro_preco, pro_categoria, pro_imagem, pro_estoque FROM tbl_produto WHERE pro_nome LIKE ? ORDER BY pro_nome ASC";

$stmt = mysqli_prepare($conexao, $sql);

if (!$stmt) {
    http_response_code(500);

    echo json_encode(['erro' => 'Erro ao preparar a consulta: ' . mysqli_error($conexao)], JSON_UNESCAPED_UNICODE);

    exit;
}

$termo = '%' . $pesquisa . '%';

if (!mysqli_stmt_bind_param($stmt, 's', $termo)) {
    http_response_code(500);

    echo json_encode(['erro' => "Erro ao associar o parâmetro: " . mysqli_stmt_error($stmt)], JSON_UNESCAPED_UNICODE);

    exit;
}

if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);

    echo json_encode(['erro' => "Erro ao executar a consulta: " . mysqli_stmt_error($stmt)], JSON_UNESCAPED_UNICODE);

    exit;
}

mysqli_stmt_bind_result($stmt, $pro_id, $pro_nome, $pro_descricao, $pro_preco, $pro_categoria, $pro_imagem, $pro_estoque);

$produtos = [];

while (mysqli_stmt_fetch($stmt)) {

    $produtos[] = ['pro_id' => $pro_id, 'pro_nome' => $pro_nome, 'pro_descricao' => $pro_descricao, 'pro_preco' => $pro_preco, 'pro_categoria' => $pro_categoria, 'pro_imagem' => $pro_imagem, 'pro_estoque' => $pro_estoque];
}

echo json_encode($produtos, JSON_UNESCAPED_UNICODE);

mysqli_stmt_close($stmt);
mysqli_close($conexao);

?>