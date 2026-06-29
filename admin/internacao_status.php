<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: internacoes.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: internacoes.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$acao = trim($_POST['acao'] ?? '');

if ($id <= 0) {
    header('Location: internacoes.php');
    exit;
}

if ($acao === 'alta') {
    $novoStatus = 'Alta';
    $msg = 'alta';
    $acaoAuditoria = 'INTERNACAO_ALTA';
    $detalhes = 'Alta registrada e leito liberado.';
} elseif ($acao === 'cancelar') {
    $novoStatus = 'Cancelada';
    $msg = 'cancelada';
    $acaoAuditoria = 'INTERNACAO_CANCELADA';
    $detalhes = 'Internação cancelada e leito liberado.';
} else {
    header('Location: internacoes.php');
    exit;
}

$stmtInternacao = $pdo->prepare("
    SELECT id, leito_id, status
    FROM internacoes
    WHERE id = :id
    AND ativo = 1
    LIMIT 1
");

$stmtInternacao->execute([
    ':id' => $id
]);

$internacao = $stmtInternacao->fetch();

if (!$internacao || $internacao['status'] !== 'Ativa') {
    header('Location: internacoes.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE internacoes
        SET status = :status,
            data_alta = NOW(),
            atualizado_em = NOW()
        WHERE id = :id
        AND ativo = 1
    ");

    $stmt->execute([
        ':status' => $novoStatus,
        ':id' => $id
    ]);

    $stmtLeito = $pdo->prepare("
        UPDATE leitos
        SET status = 'Disponivel',
            atualizado_em = NOW()
        WHERE id = :id
    ");

    $stmtLeito->execute([
        ':id' => $internacao['leito_id']
    ]);

    registrarAuditoria(
        $pdo,
        $_SESSION['usuario_id'],
        $acaoAuditoria,
        'internacoes',
        $id,
        $detalhes
    );

    $pdo->commit();

    header("Location: internacoes.php?msg={$msg}");
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());
    header('Location: internacoes.php');
    exit;
}