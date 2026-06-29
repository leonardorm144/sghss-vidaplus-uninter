<?php

$host = 'localhost';
$dbname = 'nome_do_banco';
$user = 'usuario_do_banco';
$pass = 'senha_do_banco';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Erro ao conectar ao banco de dados.');
}