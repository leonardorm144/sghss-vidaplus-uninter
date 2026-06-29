<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: prontuarios.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: prontuario_form.php?erro=csrf');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$pacienteId = isset($_POST['paciente_id']) ? (int)$_POST['paciente_id'] : 0;
$profissionalId = isset($_POST['profissional_id']) ? (int)$_POST['profissional_id'] : 0;
$consultaId = isset($_POST['consulta_id']) && $_POST['consulta_id'] !== '' ? (int)$_POST['consulta_id'] : null;

$descricao = trim($_POST['descricao'] ?? '');
$diagnostico = trim($_POST['diagnostico'] ?? '');
$conduta = trim($_POST['conduta'] ?? '');

function voltarProntuarioComErro($id, $erro)
{
    $url = $id > 0 ? "prontuario_form.php?id={$id}&erro={$erro}" : "prontuario_form.php?erro={$erro}";
    header("Location: {$url}");
    exit;
}

if ($pacienteId <= 0) {
    voltarProntuarioComErro($id, 'paciente');
}

if ($profissionalId <= 0) {
    voltarProntuarioComErro($id, 'profissional');
}

if ($descricao === '') {
    voltarProntuarioComErro($id, 'descricao');
}

if ($diagnostico === '') {
    $diagnostico = null;
}

if ($conduta === '') {
    $conduta = null;
}

$stmtPaciente = $pdo->prepare("
    SELECT id
    FROM pacientes
    WHERE id = :id
    AND ativo = 1
    LIMIT 1
");

$stmtPaciente->execute([
    ':id' => $pacienteId
]);

if (!$stmtPaciente->fetch()) {
    voltarProntuarioComErro($id, 'paciente');
}

$stmtProfissional = $pdo->prepare("
    SELECT id
    FROM profissionais
    WHERE id = :id
    AND ativo = 1
    LIMIT 1
");

$stmtProfissional->execute([
    ':id' => $profissionalId
]);

if (!$stmtProfissional->fetch()) {
    voltarProntuarioComErro($id, 'profissional');
}

if ($consultaId !== null) {
    $stmtConsulta = $pdo->prepare("
        SELECT id
        FROM consultas
        WHERE id = :id
        AND status <> 'Cancelada'
        LIMIT 1
    ");

    $stmtConsulta->execute([
        ':id' => $consultaId
    ]);

    if (!$stmtConsulta->fetch()) {
        voltarProntuarioComErro($id, 'consulta');
    }
}

if ($id > 0) {
    $stmt = $pdo->prepare("
        UPDATE prontuarios
        SET
            paciente_id = :paciente_id,
            profissional_id = :profissional_id,
            consulta_id = :consulta_id,
            descricao = :descricao,
            diagnostico = :diagnostico,
            conduta = :conduta,
            atualizado_em = NOW()
        WHERE id = :id
        AND ativo = 1
    ");

    $stmt->execute([
        ':paciente_id' => $pacienteId,
        ':profissional_id' => $profissionalId,
        ':consulta_id' => $consultaId,
        ':descricao' => $descricao,
        ':diagnostico' => $diagnostico,
        ':conduta' => $conduta,
        ':id' => $id
    ]);

    registrarAuditoria(
        $pdo,
        $_SESSION['usuario_id'],
        'PRONTUARIO_ATUALIZADO',
        'prontuarios',
        $id,
        'Prontuário atualizado.'
    );

    header('Location: prontuarios.php?msg=atualizado');
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO prontuarios
    (
        paciente_id,
        profissional_id,
        consulta_id,
        descricao,
        diagnostico,
        conduta,
        ativo
    )
    VALUES
    (
        :paciente_id,
        :profissional_id,
        :consulta_id,
        :descricao,
        :diagnostico,
        :conduta,
        1
    )
");

$stmt->execute([
    ':paciente_id' => $pacienteId,
    ':profissional_id' => $profissionalId,
    ':consulta_id' => $consultaId,
    ':descricao' => $descricao,
    ':diagnostico' => $diagnostico,
    ':conduta' => $conduta
]);

$novoId = $pdo->lastInsertId();

registrarAuditoria(
    $pdo,
    $_SESSION['usuario_id'],
    'PRONTUARIO_CRIADO',
    'prontuarios',
    $novoId,
    'Prontuário cadastrado.'
);

header('Location: prontuarios.php?msg=criado');
exit;