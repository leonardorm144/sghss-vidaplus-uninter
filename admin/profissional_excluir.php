<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profissionais.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: profissionais.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    header('Location: profissionais.php');
    exit;
}

$stmt = $pdo->prepare("
    UPDATE profissionais
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
    'PROFISSIONAL_INATIVADO',
    'profissionais',
    $id,
    'Profissional inativado pelo administrador.'
);

header('Location: profissionais.php?msg=excluido');
exit;