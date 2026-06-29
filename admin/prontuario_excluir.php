<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: prontuarios.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: prontuarios.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    header('Location: prontuarios.php');
    exit;
}

$stmt = $pdo->prepare("
    UPDATE prontuarios
    SET ativo = 0,
        atualizado_em = NOW()
    WHERE id = :id
");

$stmt->execute([
    ':id' => $id
]);

registrarAuditoria(
    $pdo,
    $_SESSION['usuario_id'],
    'PRONTUARIO_INATIVADO',
    'prontuarios',
    $id,
    'Prontuário inativado pelo administrador.'
);

header('Location: prontuarios.php?msg=excluido');
exit;