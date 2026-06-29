<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: leitos.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: leitos.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    header('Location: leitos.php');
    exit;
}

$stmtLeito = $pdo->prepare("
    SELECT status
    FROM leitos
    WHERE id = :id
    AND ativo = 1
    LIMIT 1
");

$stmtLeito->execute([
    ':id' => $id
]);

$leito = $stmtLeito->fetch();

if (!$leito) {
    header('Location: leitos.php');
    exit;
}

if ($leito['status'] === 'Ocupado') {
    header('Location: leitos.php?erro=ocupado');
    exit;
}

$stmt = $pdo->prepare("
    UPDATE leitos
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
    'LEITO_INATIVADO',
    'leitos',
    $id,
    'Leito inativado pelo administrador.'
);

header('Location: leitos.php?msg=excluido');
exit;