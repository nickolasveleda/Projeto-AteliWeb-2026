<?php

$dbnome = "bd_ateliweb";
$conexao = mysqli_connect('localhost', 'root', '') or die("Erro de conexão");
$criadb = mysqli_query($conexao, "CREATE DATABASE IF NOT EXISTS $dbnome");
$abre = mysqli_query($conexao, "USE $dbnome");

$tbNome1 = "tbl_produto";

// Apaga a tabela existente
$elimina = "DROP TABLE IF EXISTS tbl_produto";

$criacao1 = "CREATE TABLE IF NOT EXISTS $tbNome1 (
    pro_id INT(10) AUTO_INCREMENT PRIMARY KEY,
    pro_nome VARCHAR(200) NOT NULL,
    pro_descricao VARCHAR NOT NULL,
    pro_preco DECIMAL(10,2) NOT NULL,
    pro_categoria VARCHAR(100) NOT NULL,
    pro_imagem VARCHAR(300) NOT NULL,
    pro_estoque INT(5) NOT NULL DEFAULT 0
)";

// Exclui a tabela
$resDrop = mysqli_query($conexao, $elimina);

if ($resDrop > 0) {
    echo "Tabela $tbNome1 eliminada<br>";
} else {
    echo "Tabela $tbNome1 não existe<br>";
}

// Cria a tabela
$resCria1 = mysqli_query($conexao, $criacao1);

if ($resCria1 > 0) {
    echo "Tabela $tbNome1 criada<br>";
} else {
    echo "Tabela $tbNome1 não pode ser criada<br>";
}


// Cadastra produtos
$inserirProdutos = "INSERT INTO tbl_produto(pro_nome, pro_descricao, pro_preco, pro_categoria, pro_imagem, pro_estoque) VALUES('Vaso rústico', 'Vaso artesanal de cerâmica.', 89.90, 'Vasos', 'vaso_rustico.jpg', 10), ('Caneca artesanal', 'Caneca artesanal de cerâmica.', 49.90, 'Canecas', 'caneca_artesanal.jpg', 10), ('Prato decorativo', 'Prato decorativo de cerâmica.', 69.90, 'Pratos', 'prato_decorativo.jpg', 10), ('Conjunto de tigelas', 'Conjunto de tigelas artesanais.', 119.90, 'Tigelas', 'conjunto_tigelas.jpg', 10), ('Escultura artesanal', 'Escultura artesanal de cerâmica.', 149.90, 'Esculturas', 'escultura_artesanal.jpg', 10)";

$resProdutos = mysqli_query($conexao, $inserirProdutos);

if ($resProdutos) {
    echo "Produtos cadastrados com sucesso<br>";
} else {
    echo "Erro ao cadastrar produtos: " . mysqli_error($conexao) . "<br>";
}

mysqli_close($conexao);

?>