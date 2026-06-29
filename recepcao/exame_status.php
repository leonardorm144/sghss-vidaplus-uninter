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
$acao = trim($_POST['acao'] ?? '');

if ($id <= 0) {
    header('Location: exames.php');
    exit;
}

if ($acao === 'realizar') {
    $novoStatus = 'Realizado';
    $acaoAuditoria = 'EXAME_REALIZADO_RECEPCAO';
} elseif ($acao === 'cancelar') {
    $novoStatus = 'Cancelado';
    $acaoAuditoria = 'EXAME_CANCELADO_RECEPCAO';
} else {
    header('Location: exames.php');
    exit;
}

$stmtExame = $pdo->prepare("
    SELECT id, status
    FROM exames
    WHERE id = :id
    AND ativo = 1
    LIMIT 1
");

$stmtExame->execute([
    ':id' => $id
]);

$exame = $stmtExame->fetch();

if (!$exame) {
    header('Location: exames.php');
    exit;
}

if (in_array($exame['status'], ['Realizado', 'Cancelado'])) {
    header('Location: exames.php');
    exit;
}

$stmt = $pdo->prepare("
    UPDATE exames
    SET status = :status,
        atualizado_em = NOW()
    WHERE id = :id
    AND ativo = 1
");

$stmt->execute([
    ':status' => $novoStatus,
    ':id' => $id
]);

registrarAuditoria(
    $pdo,
    $_SESSION['usuario_id'],
    $acaoAuditoria,
    'exames',
    $id,
    'Status do exame alterado pela recepção para: ' . $novoStatus
);

header('Location: exames.php?msg=status');
exit;