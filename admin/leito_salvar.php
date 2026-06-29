<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: leitos.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: leito_form.php?erro=csrf');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$unidadeId = isset($_POST['unidade_id']) ? (int)$_POST['unidade_id'] : 0;
$numero = trim($_POST['numero'] ?? '');
$setor = trim($_POST['setor'] ?? '');
$status = trim($_POST['status'] ?? '');

$statusPermitidos = ['Disponivel', 'Ocupado', 'Manutencao'];

if ($unidadeId <= 0) {
    $url = $id > 0 ? "leito_form.php?id={$id}&erro=unidade" : "leito_form.php?erro=unidade";
    header("Location: {$url}");
    exit;
}

if ($numero === '') {
    $url = $id > 0 ? "leito_form.php?id={$id}&erro=numero" : "leito_form.php?erro=numero";
    header("Location: {$url}");
    exit;
}

if (!in_array($status, $statusPermitidos)) {
    $url = $id > 0 ? "leito_form.php?id={$id}&erro=status" : "leito_form.php?erro=status";
    header("Location: {$url}");
    exit;
}

if ($setor === '') {
    $setor = null;
}

$stmtUnidade = $pdo->prepare("
    SELECT id
    FROM unidades
    WHERE id = :id
    AND ativo = 1
    LIMIT 1
");

$stmtUnidade->execute([
    ':id' => $unidadeId
]);

if (!$stmtUnidade->fetch()) {
    $url = $id > 0 ? "leito_form.php?id={$id}&erro=unidade" : "leito_form.php?erro=unidade";
    header("Location: {$url}");
    exit;
}

$stmtDuplicado = $pdo->prepare("
    SELECT id
    FROM leitos
    WHERE unidade_id = :unidade_id
    AND numero = :numero
    AND id <> :id
    AND ativo = 1
    LIMIT 1
");

$stmtDuplicado->execute([
    ':unidade_id' => $unidadeId,
    ':numero' => $numero,
    ':id' => $id
]);

if ($stmtDuplicado->fetch()) {
    $url = $id > 0 ? "leito_form.php?id={$id}&erro=duplicado" : "leito_form.php?erro=duplicado";
    header("Location: {$url}");
    exit;
}

if ($id > 0) {
    $stmt = $pdo->prepare("
        UPDATE leitos
        SET
            unidade_id = :unidade_id,
            numero = :numero,
            setor = :setor,
            status = :status,
            atualizado_em = NOW()
        WHERE id = :id
        AND ativo = 1
    ");

    $stmt->execute([
        ':unidade_id' => $unidadeId,
        ':numero' => $numero,
        ':setor' => $setor,
        ':status' => $status,
        ':id' => $id
    ]);

    registrarAuditoria(
        $pdo,
        $_SESSION['usuario_id'],
        'LEITO_ATUALIZADO',
        'leitos',
        $id,
        'Leito atualizado: ' . $numero
    );

    header('Location: leitos.php?msg=atualizado');
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO leitos
    (
        unidade_id,
        numero,
        setor,
        status,
        ativo
    )
    VALUES
    (
        :unidade_id,
        :numero,
        :setor,
        :status,
        1
    )
");

$stmt->execute([
    ':unidade_id' => $unidadeId,
    ':numero' => $numero,
    ':setor' => $setor,
    ':status' => $status
]);

$novoId = $pdo->lastInsertId();

registrarAuditoria(
    $pdo,
    $_SESSION['usuario_id'],
    'LEITO_CRIADO',
    'leitos',
    $novoId,
    'Leito cadastrado: ' . $numero
);

header('Location: leitos.php?msg=criado');
exit;