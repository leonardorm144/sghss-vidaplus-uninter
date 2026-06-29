<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('profissional');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: prescricoes.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: prescricoes.php');
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
    header('Location: prescricoes.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    header('Location: prescricoes.php');
    exit;
}

$stmt = $pdo->prepare("
    UPDATE prescricoes
    SET ativo = 0,
        atualizado_em = NOW()
    WHERE id = :id
    AND profissional_id = :profissional_id
");

$stmt->execute([
    ':id' => $id,
    ':profissional_id' => $profissional['id']
]);

registrarAuditoria(
    $pdo,
    $_SESSION['usuario_id'],
    'PRESCRICAO_INATIVADA_PROFISSIONAL',
    'prescricoes',
    $id,
    'Prescrição inativada pelo profissional.'
);

header('Location: prescricoes.php?msg=excluido');
exit;