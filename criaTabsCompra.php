<?php

$dbnome = "bd_ateliweb";
$conexao = mysqli_connect('localhost', 'root', '') or die("Erro de conexão");
$criadb = mysqli_query($conexao, "CREATE DATABASE IF NOT EXISTS $dbnome");
$abre = mysqli_query($conexao, "USE $dbnome");

$tbNome1 = "tbl_compras";
$tbNome2 = "tbl_itens_compra";

// Primeiro apaga-se a tabela filha. Ela possui uma FOREIGN KEY apontando para tbl_compras
mysqli_query($conexao, "DROP TABLE IF EXISTS $tbNome2");

// Apenas depois pode apagar a tabela pai
mysqli_query($conexao, "DROP TABLE IF EXISTS $tbNome1");

// Cria tbl_compras
$sqlCompras = "CREATE TABLE $tbNome1 (
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
    echo "Tabela $tbNome1 criada com sucesso<br>";
} else {
    echo "Erro ao criar $tbNome1: " . mysqli_error($conexao) . "<br>";
}


// Cria tbl_itens_compra
$sqlItens = "CREATE TABLE $tbNome2 (
    itens_id INT(10) AUTO_INCREMENT PRIMARY KEY,
    compra_id INT(10) NOT NULL,
    itens_produto VARCHAR(200) NOT NULL,
    itens_preco DECIMAL(10,2) NOT NULL,
    itens_quantidade INT NOT NULL,
    itens_subtotal DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_itens_compra FOREIGN KEY(compra_id) REFERENCES $tbNome1(compra_id) ON DELETE CASCADE ON UPDATE CASCADE
    CONSTRAINT fk_itens_produto FOREIGN KEY(pro_id) REFERENCES tbl_produto(pro_id) ON DELETE CASCADE ON UPDATE CASCADE
)";

if (mysqli_query($conexao, $sqlItens)) {
    echo "Tabela $tbNome2 criada com sucesso<br>";
} else {
    echo "Erro ao criar $tbNome2: " . mysqli_error($conexao) . "<br>";
}

mysqli_close($conexao);

?>