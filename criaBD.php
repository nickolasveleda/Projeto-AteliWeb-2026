<?php

// Dados do banco de dados
$bdnome = "bd_ateliweb";
$conexao = mysqli_connect('localhost', 'root', '') or die("Erro de conexão");
mysqli_set_charset($conexao, "utf8mb4");

$tbNome1 = "tbl_produto";
$tbNome2 = "tbl_compras";
$tbNome3 = "tbl_itens_compra";

// Cria banco de dados
$criabd = mysqli_query($conexao, "CREATE DATABASE IF NOT EXISTS $bdnome CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

// Usa conexão
$abre = mysqli_query($conexao, "USE $bdnome");

mysqli_query($conexao, "ALTER DATABASE $bdnome CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

// Primeiro apaga-se a tabela filha. Ela possui FOREIGN KEYs apontando para tbl_compras e tbl_produto
mysqli_query($conexao, "DROP TABLE IF EXISTS $tbNome3");

// Apenas depois pode-se apagar as tabelas pai
mysqli_query($conexao, "DROP TABLE IF EXISTS $tbNome2");
mysqli_query($conexao, "DROP TABLE IF EXISTS $tbNome1");

// Cria tbl_produto
$sqlProdutos = "CREATE TABLE IF NOT EXISTS $tbNome1 (
    pro_id INT(10) AUTO_INCREMENT PRIMARY KEY,
    pro_nome VARCHAR(200) NOT NULL,
    pro_descricao VARCHAR(50000) NOT NULL,
    pro_preco DECIMAL(10,2) NOT NULL,
    pro_categoria VARCHAR(100) NOT NULL,
    pro_imagem VARCHAR(500) NOT NULL,
    pro_estoque INT(5) NOT NULL DEFAULT 0
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

if (mysqli_query($conexao, $sqlProdutos)) {
    echo "Tabela $tbNome1 criada com sucesso<br>";
} else {
    echo "Erro ao criar $tbNome1: " . mysqli_error($conexao) . "<br>";
}

// Cadastra produtos de exemplo
$inserirProdutos = "INSERT INTO $tbNome1(pro_nome, pro_descricao, pro_preco, pro_categoria, pro_imagem, pro_estoque) VALUES('Vaso rústico', 'Vaso artesanal de cerâmica.', 89.90, 'Vasos', 'vaso_rustico.jpg', 10), ('Caneca artesanal', 'Caneca artesanal de cerâmica.', 49.90, 'Canecas', 'caneca_artesanal.jpg', 10), ('Prato decorativo', 'Prato decorativo de cerâmica.', 69.90, 'Pratos', 'prato_decorativo.jpg', 10), ('Conjunto de tigelas', 'Conjunto de tigelas artesanais.', 119.90, 'Tigelas', 'conjunto_tigelas.jpg', 10), ('Escultura artesanal', 'Escultura artesanal de cerâmica.', 149.90, 'Esculturas', 'escultura_artesanal.jpg', 10)";

$resProdutos = mysqli_query($conexao, $inserirProdutos);

if ($resProdutos) {
    echo "Produtos cadastrados com sucesso<br>";
} else {
    echo "Erro ao cadastrar produtos: " . mysqli_error($conexao) . "<br>";
}

// Cria tbl_compras
$sqlCompras = "CREATE TABLE $tbNome2 (
    compra_id INT(10) AUTO_INCREMENT PRIMARY KEY,
    compra_nome VARCHAR(200) NOT NULL,
    compra_email VARCHAR(150) NOT NULL,
    compra_telefone VARCHAR(30) NOT NULL,
    compra_endereco VARCHAR(500) NOT NULL,
    compra_itens JSON NOT NULL,
    compra_total DECIMAL(10,2) NOT NULL,
    compra_data DATETIME DEFAULT CURRENT_TIMESTAMP
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

if (mysqli_query($conexao, $sqlCompras)) {
    echo "Tabela $tbNome2 criada com sucesso<br>";
} else {
    echo "Erro ao criar $tbNome2: " . mysqli_error($conexao) . "<br>";
}

// Cria tbl_itens_compra
$sqlItens = "CREATE TABLE $tbNome3 (
    itens_id INT(10) AUTO_INCREMENT PRIMARY KEY,
    compra_id INT(10) NOT NULL,
    pro_id INT(10) NOT NULL,
    itens_produto VARCHAR(200) NOT NULL,
    itens_preco DECIMAL(10,2) NOT NULL,
    itens_quantidade INT NOT NULL,
    itens_subtotal DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_itens_compra FOREIGN KEY(compra_id) REFERENCES $tbNome2(compra_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_itens_produto FOREIGN KEY(pro_id) REFERENCES $tbNome1(pro_id) ON UPDATE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

if (mysqli_query($conexao, $sqlItens)) {
    echo "Tabela $tbNome3 criada com sucesso<br>";
} else {
    echo "Erro ao criar $tbNome3: " . mysqli_error($conexao) . "<br>";
}

mysqli_close($conexao);

?>