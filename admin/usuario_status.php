<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: usuarios.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: usuarios.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$acao = trim($_POST['acao'] ?? '');

if ($id <= 0) {
    header('Location: usuarios.php');
    exit;
}

if ($id === (int)$_SESSION['usuario_id'] && $acao === 'inativar') {
    header('Location: usuarios.php?erro=proprio_usuario');
    exit;
}

if ($acao === 'inativar') {
    $novoStatus = 0;
    $msg = 'inativado';
    $acaoAuditoria = 'USUARIO_INATIVADO';
} elseif ($acao === 'reativar') {
    $novoStatus = 1;
    $msg = 'reativado';
    $acaoAuditoria = 'USUARIO_REATIVADO';
} else {
    header('Location: usuarios.php');
    exit;
}

$stmt = $pdo->prepare("
    UPDATE usuarios
    SET ativo = :ativo,
        atualizado_em = NOW()
    WHERE id = :id
");

$stmt->execute([
    ':ativo' => $novoStatus,
    ':id' => $id
]);

registrarAuditoria(
    $pdo,
    $_SESSION['usuario_id'],
    $acaoAuditoria,
    'usuarios',
    $id,
    'Status do usuário alterado.'
);

header("Location: usuarios.php?msg={$msg}");
exit;