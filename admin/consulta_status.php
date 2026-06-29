<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: consultas.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: consultas.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$acao = trim($_POST['acao'] ?? '');

if ($id <= 0) {
    header('Location: consultas.php');
    exit;
}

$novoStatus = null;
$msg = null;
$acaoAuditoria = null;

if ($acao === 'confirmar') {
    $novoStatus = 'Confirmada';
    $msg = 'confirmada';
    $acaoAuditoria = 'CONSULTA_CONFIRMADA';
} elseif ($acao === 'cancelar') {
    $novoStatus = 'Cancelada';
    $msg = 'cancelada';
    $acaoAuditoria = 'CONSULTA_CANCELADA';
} elseif ($acao === 'concluir') {
    $novoStatus = 'Concluida';
    $msg = 'concluida';
    $acaoAuditoria = 'CONSULTA_CONCLUIDA';
} else {
    header('Location: consultas.php');
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
    header('Location: consultas.php');
    exit;
}

if (in_array($consulta['status'], ['Cancelada', 'Concluida'])) {
    header('Location: consultas.php');
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
    'Status da consulta alterado para: ' . $novoStatus
);

header("Location: consultas.php?msg={$msg}");
exit;