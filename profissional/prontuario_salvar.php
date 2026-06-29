<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('profissional');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: prontuarios.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: prontuario_form.php?erro=csrf');
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
    header('Location: prontuarios.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$pacienteId = isset($_POST['paciente_id']) ? (int)$_POST['paciente_id'] : 0;
$consultaId = isset($_POST['consulta_id']) && $_POST['consulta_id'] !== '' ? (int)$_POST['consulta_id'] : null;

$descricao = trim($_POST['descricao'] ?? '');
$diagnostico = trim($_POST['diagnostico'] ?? '');
$conduta = trim($_POST['conduta'] ?? '');

function voltarProntuarioProfissionalComErro($id, $erro)
{
    $url = $id > 0 ? "prontuario_form.php?id={$id}&erro={$erro}" : "prontuario_form.php?erro={$erro}";
    header("Location: {$url}");
    exit;
}

if ($pacienteId <= 0) {
    voltarProntuarioProfissionalComErro($id, 'paciente');
}

if ($descricao === '') {
    voltarProntuarioProfissionalComErro($id, 'descricao');
}

if ($diagnostico === '') {
    $diagnostico = null;
}

if ($conduta === '') {
    $conduta = null;
}

$stmtPaciente = $pdo->prepare("
    SELECT DISTINCT p.id
    FROM pacientes p
    INNER JOIN consultas c ON c.paciente_id = p.id
    WHERE p.id = :paciente_id
    AND c.profissional_id = :profissional_id
    AND p.ativo = 1
    LIMIT 1
");

$stmtPaciente->execute([
    ':paciente_id' => $pacienteId,
    ':profissional_id' => $profissional['id']
]);

if (!$stmtPaciente->fetch()) {
    voltarProntuarioProfissionalComErro($id, 'paciente');
}

if ($consultaId !== null) {
    $stmtConsulta = $pdo->prepare("
        SELECT id
        FROM consultas
        WHERE id = :id
        AND profissional_id = :profissional_id
        AND paciente_id = :paciente_id
        AND status <> 'Cancelada'
        LIMIT 1
    ");

    $stmtConsulta->execute([
        ':id' => $consultaId,
        ':profissional_id' => $profissional['id'],
        ':paciente_id' => $pacienteId
    ]);

    if (!$stmtConsulta->fetch()) {
        voltarProntuarioProfissionalComErro($id, 'consulta');
    }
}

if ($id > 0) {
    $stmtVerifica = $pdo->prepare("
        SELECT id
        FROM prontuarios
        WHERE id = :id
        AND profissional_id = :profissional_id
        AND ativo = 1
        LIMIT 1
    ");

    $stmtVerifica->execute([
        ':id' => $id,
        ':profissional_id' => $profissional['id']
    ]);

    if (!$stmtVerifica->fetch()) {
        header('Location: prontuarios.php');
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE prontuarios
        SET
            paciente_id = :paciente_id,
            consulta_id = :consulta_id,
            descricao = :descricao,
            diagnostico = :diagnostico,
            conduta = :conduta,
            atualizado_em = NOW()
        WHERE id = :id
        AND profissional_id = :profissional_id
        AND ativo = 1
    ");

    $stmt->execute([
        ':paciente_id' => $pacienteId,
        ':consulta_id' => $consultaId,
        ':descricao' => $descricao,
        ':diagnostico' => $diagnostico,
        ':conduta' => $conduta,
        ':id' => $id,
        ':profissional_id' => $profissional['id']
    ]);

    registrarAuditoria(
        $pdo,
        $_SESSION['usuario_id'],
        'PRONTUARIO_ATUALIZADO_PROFISSIONAL',
        'prontuarios',
        $id,
        'Prontuário atualizado pelo profissional.'
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
    ':profissional_id' => $profissional['id'],
    ':consulta_id' => $consultaId,
    ':descricao' => $descricao,
    ':diagnostico' => $diagnostico,
    ':conduta' => $conduta
]);

$novoId = $pdo->lastInsertId();

registrarAuditoria(
    $pdo,
    $_SESSION['usuario_id'],
    'PRONTUARIO_CRIADO_PROFISSIONAL',
    'prontuarios',
    $novoId,
    'Prontuário criado pelo profissional.'
);

header('Location: prontuarios.php?msg=criado');
exit;