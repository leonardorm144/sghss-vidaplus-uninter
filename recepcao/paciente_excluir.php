<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('recepcao');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pacientes.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: pacientes.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    header('Location: pacientes.php');
    exit;
}

$stmt = $pdo->prepare("
    UPDATE pacientes
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
    'PACIENTE_INATIVADO_RECEPCAO',
    'pacientes',
    $id,
    'Paciente inativado pela recepção.'
);

header('Location: pacientes.php?msg=excluido');
exit;