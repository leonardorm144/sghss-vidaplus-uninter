<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('recepcao');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: exames.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: exames.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    header('Location: exames.php');
    exit;
}

$stmt = $pdo->prepare("
    UPDATE exames
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
    'EXAME_INATIVADO_RECEPCAO',
    'exames',
    $id,
    'Exame inativado pela recepção.'
);

header('Location: exames.php?msg=excluido');
exit;