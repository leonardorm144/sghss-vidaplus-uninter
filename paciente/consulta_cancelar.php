<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('paciente');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: consultas.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: consultas.php');
    exit;
}

$usuarioId = $_SESSION['usuario_id'] ?? 0;

$stmtPaciente = $pdo->prepare("
    SELECT id
    FROM pacientes
    WHERE usuario_id = :usuario_id
    AND ativo = 1
    LIMIT 1
");

$stmtPaciente->execute([
    ':usuario_id' => $usuarioId
]);

$paciente = $stmtPaciente->fetch();

if (!$paciente) {
    header('Location: consultas.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    header('Location: consultas.php');
    exit;
}

$stmtConsulta = $pdo->prepare("
    SELECT id, status
    FROM consultas
    WHERE id = :id
    AND paciente_id = :paciente_id
    LIMIT 1
");

$stmtConsulta->execute([
    ':id' => $id,
    ':paciente_id' => $paciente['id']
]);

$consulta = $stmtConsulta->fetch();

if (!$consulta) {
    header('Location: consultas.php');
    exit;
}

if (!in_array($consulta['status'], ['Agendada', 'Confirmada'])) {
    header('Location: consultas.php');
    exit;
}

$stmt = $pdo->prepare("
    UPDATE consultas
    SET status = 'Cancelada',
        atualizado_em = NOW()
    WHERE id = :id
    AND paciente_id = :paciente_id
");

$stmt->execute([
    ':id' => $id,
    ':paciente_id' => $paciente['id']
]);

registrarAuditoria(
    $pdo,
    $_SESSION['usuario_id'],
    'CONSULTA_CANCELADA_PACIENTE',
    'consultas',
    $id,
    'Consulta cancelada pelo paciente.'
);

header('Location: consultas.php?msg=cancelada');
exit;