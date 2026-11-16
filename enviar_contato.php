<?php

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: contato.html");
    exit;
}

$nome = trim($_POST["nome"] ?? "");
$email = trim($_POST["email"] ?? "");
$assunto = trim($_POST["assunto"] ?? "");
$mensagem = trim($_POST["mensagem"] ?? "");

// Verifica se os campos foram preenchidos
if ($nome === "" || $email === "" || $assunto === "" || $mensagem === "") {
    die("Preencha todos os campos");
}

// Verifica se o e-mail é válido
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Por favor digite um endereço de e-mail válido");
}

// E-mail que receberá as mensagens
$destinatario = "projetoateliweb2026@gmail.com";

// Monta a mensagem
$corpo = "Nova mensagem recebida!\n\n";
$corpo .= "Nome: " . $nome . "\n";
$corpo .= "E-mail: " . $email . "\n";
$corpo .= "Assunto: " . $assunto . "\n\n";
$corpo .= "Mensagem:\n";
$corpo .= $mensagem;

// Cabeçalhos
$headers = "From: AteliWeb <projetoateliweb2026@gmail.com>\r\n";
$headers .= "Reply-To: " . $email . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

//Nome seguro
$nomeSeguro = htmlspecialchars($nome, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// Envia o e-mail
if (mail($destinatario, $assunto, $corpo, $headers)) {
    echo "
    <!DOCTYPE html>
    <html lang='pt-br'>
    <head>
        <meta charset='utf-8'>
		<link rel="stylesheet" href="estilo.css">
        <title>Mensagem Enviada</title>
    </head>

    <body>
        <p style = 'margin:20px;'>
            Obrigado pelo contato, <?= $nomeSeguro ?>. Retornaremos assim que possível!
        </p>

        <a
            href = 'contato.html'
            style = 'style.css'
        >
            Voltar
        </a>
    </body>
    </html>
    ";
} else {
    echo "
    <h1>Não foi possível enviar a mensagem</h1>
    <p>Por favor tente novamente mais tarde</p>
    <a href='contato.html'>Voltar</a>
    ";
}
?>