<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('profissional');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: agenda.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: agenda.php');
    exit;
}

$usuarioId = $_SESSION['usuario_id'] ?? 0;

$stmtProfissional = $pdo->prepare("
    SELECT id
    FROM profissionais
    WHERE usuario_id = :usuario_id
    AND ativo = 1
    LIMIT 1
");

$stmtProfissional->execute([
    ':usuario_id' => $usuarioId
]);

$profissional = $stmtProfissional->fetch();

if (!$profissional) {
    header('Location: agenda.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$acao = trim($_POST['acao'] ?? '');

if ($id <= 0) {
    header('Location: agenda.php');
    exit;
}

if ($acao === 'confirmar') {
    $novoStatus = 'Confirmada';
    $msg = 'confirmada';
    $acaoAuditoria = 'CONSULTA_CONFIRMADA_PROFISSIONAL';
} elseif ($acao === 'concluir') {
    $novoStatus = 'Concluida';
    $msg = 'concluida';
    $acaoAuditoria = 'CONSULTA_CONCLUIDA_PROFISSIONAL';
} else {
    header('Location: agenda.php');
    exit;
}

$stmtConsulta = $pdo->prepare("
    SELECT id, status
    FROM consultas
    WHERE id = :id
    AND profissional_id = :profissional_id
    LIMIT 1
");

$stmtConsulta->execute([
    ':id' => $id,
    ':profissional_id' => $profissional['id']
]);

$consulta = $stmtConsulta->fetch();

if (!$consulta) {
    header('Location: agenda.php');
    exit;
}

if (in_array($consulta['status'], ['Cancelada', 'Concluida'])) {
    header('Location: agenda.php');
    exit;
}

$stmt = $pdo->prepare("
    UPDATE consultas
    SET status = :status,
        atualizado_em = NOW()
    WHERE id = :id
    AND profissional_id = :profissional_id
");

$stmt->execute([
    ':status' => $novoStatus,
    ':id' => $id,
    ':profissional_id' => $profissional['id']
]);

registrarAuditoria(
    $pdo,
    $_SESSION['usuario_id'],
    $acaoAuditoria,
    'consultas',
    $id,
    'Status da consulta alterado pelo profissional para: ' . $novoStatus
);

header("Location: agenda.php?msg={$msg}");
exit;