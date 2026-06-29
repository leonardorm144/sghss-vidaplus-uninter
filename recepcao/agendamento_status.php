<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('recepcao');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: agendamentos.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: agendamentos.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$acao = trim($_POST['acao'] ?? '');

if ($id <= 0) {
    header('Location: agendamentos.php');
    exit;
}

if ($acao === 'confirmar') {
    $novoStatus = 'Confirmada';
    $msg = 'confirmado';
    $acaoAuditoria = 'AGENDAMENTO_CONFIRMADO_RECEPCAO';
} elseif ($acao === 'cancelar') {
    $novoStatus = 'Cancelada';
    $msg = 'cancelado';
    $acaoAuditoria = 'AGENDAMENTO_CANCELADO_RECEPCAO';
} else {
    header('Location: agendamentos.php');
    exit;
}

$stmtConsulta = $pdo->prepare("
    SELECT id, status
    FROM consultas
    WHERE id = :id
    LIMIT 1
");

$stmtConsulta->execute([
    ':id' => $id
]);

$consulta = $stmtConsulta->fetch();

if (!$consulta) {
    header('Location: agendamentos.php');
    exit;
}

if (in_array($consulta['status'], ['Cancelada', 'Concluida'])) {
    header('Location: agendamentos.php');
    exit;
}

$stmt = $pdo->prepare("
    UPDATE consultas
    SET status = :status,
        atualizado_em = NOW()
    WHERE id = :id
");

$stmt->execute([
    ':status' => $novoStatus,
    ':id' => $id
]);

registrarAuditoria(
    $pdo,
    $_SESSION['usuario_id'],
    $acaoAuditoria,
    'consultas',
    $id,
    'Status do agendamento alterado pela recepção para: ' . $novoStatus
);

header("Location: agendamentos.php?msg={$msg}");
exit;