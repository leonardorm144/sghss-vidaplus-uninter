<?php

require_once __DIR__ . '/funcoes_auth.php';

iniciarSessaoSegura();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /login.php');
    exit;
}

$paginaAtual = basename($_SERVER['PHP_SELF']);

$paginasPermitidasTrocaSenha = [
    'trocar_senha.php',
    'trocar_senha_salvar.php',
    'logout.php'
];

if (
    ($_SESSION['usuario_perfil'] ?? '') === 'paciente' &&
    (int)($_SESSION['trocar_senha_primeiro_acesso'] ?? 0) === 1 &&
    !in_array($paginaAtual, $paginasPermitidasTrocaSenha)
) {
    header('Location: /trocar_senha.php');
    exit;
}