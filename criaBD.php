<?php

$dbnome = "bd_ateliweb";
$conexao = mysqli_connect('localhost', 'root', '') or die("Erro de conexão");
$criadb = mysqli_query($conexao, "CREATE DATABASE IF NOT EXISTS $dbnome");
$abre = mysqli_query($conexao, "USE $dbnome");

$tbNome1 = "tbl_produto";
$tbNome2 = "tbl_compras";
$tbNome3 = "tbl_itens_compra";

// Primeiro apaga-se a tabela filha. Ela possui FOREIGN KEYs apontando para tbl_compras e tbl_produto
mysqli_query($conexao, "DROP TABLE IF EXISTS $tbNome3");

// Apenas depois pode apagar as tabelas pai
mysqli_query($conexao, "DROP TABLE $tbNome2");
mysqli_query($conexao, "DROP TABLE $tbNome1");

// Cria tbl_produto
$sqlProdutos = "CREATE TABLE IF NOT EXISTS $tbNome1 (
    pro_id INT(10) AUTO_INCREMENT PRIMARY KEY,
    pro_nome VARCHAR(200) NOT NULL,
    pro_descricao VARCHAR(50000) NOT NULL,
    pro_preco DECIMAL(10,2) NOT NULL,
    pro_categoria VARCHAR(100) NOT NULL,
    pro_imagem VARCHAR(500) NOT NULL,
    pro_estoque INT(5) NOT NULL DEFAULT 0
)";

if (mysqli_query($conexao, $sqlProdutos)) {
    echo "Tabela $tbNome1 criada com sucesso<br>";
} else {
    echo "Erro ao criar $tbNome1: " . mysqli_error($conexao) . "<br>";
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
)";

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
)";

if (mysqli_query($conexao, $sqlItens)) {
    echo "Tabela $tbNome3 criada com sucesso<br>";
} else {
    echo "Erro ao criar $tbNome3: " . mysqli_error($conexao) . "<br>";
}

mysqli_close($conexao);

?>