<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: unidades.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: unidade_form.php?erro=csrf');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$nome = trim($_POST['nome'] ?? '');
$tipo = trim($_POST['tipo'] ?? '');
$cidade = trim($_POST['cidade'] ?? '');
$estado = trim($_POST['estado'] ?? '');

$tiposPermitidos = ['Hospital', 'Clinica', 'Laboratorio', 'Home Care'];

if ($nome === '') {
    $url = $id > 0 ? "unidade_form.php?id={$id}&erro=nome" : "unidade_form.php?erro=nome";
    header("Location: {$url}");
    exit;
}

if (!in_array($tipo, $tiposPermitidos)) {
    $url = $id > 0 ? "unidade_form.php?id={$id}&erro=tipo" : "unidade_form.php?erro=tipo";
    header("Location: {$url}");
    exit;
}

if ($cidade === '') {
    $cidade = null;
}

if ($estado === '') {
    $estado = null;
}

if ($id > 0) {
    $stmt = $pdo->prepare("
        UPDATE unidades
        SET
            nome = :nome,
            tipo = :tipo,
            cidade = :cidade,
            estado = :estado,
            atualizado_em = NOW()
        WHERE id = :id
        AND ativo = 1
    ");

    $stmt->execute([
        ':nome' => $nome,
        ':tipo' => $tipo,
        ':cidade' => $cidade,
        ':estado' => $estado,
        ':id' => $id
    ]);

    registrarAuditoria(
        $pdo,
        $_SESSION['usuario_id'],
        'UNIDADE_ATUALIZADA',
        'unidades',
        $id,
        'Unidade atualizada: ' . $nome
    );

    header('Location: unidades.php?msg=atualizado');
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO unidades
    (
        nome,
        tipo,
        cidade,
        estado,
        ativo
    )
    VALUES
    (
        :nome,
        :tipo,
        :cidade,
        :estado,
        1
    )
");

$stmt->execute([
    ':nome' => $nome,
    ':tipo' => $tipo,
    ':cidade' => $cidade,
    ':estado' => $estado
]);

$novoId = $pdo->lastInsertId();

registrarAuditoria(
    $pdo,
    $_SESSION['usuario_id'],
    'UNIDADE_CRIADA',
    'unidades',
    $novoId,
    'Unidade cadastrada: ' . $nome
);

header('Location: unidades.php?msg=criado');
exit;