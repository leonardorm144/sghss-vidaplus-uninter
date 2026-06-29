<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: prescricoes.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: prescricao_form.php?erro=csrf');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$pacienteId = isset($_POST['paciente_id']) ? (int)$_POST['paciente_id'] : 0;
$profissionalId = isset($_POST['profissional_id']) ? (int)$_POST['profissional_id'] : 0;
$consultaId = isset($_POST['consulta_id']) && $_POST['consulta_id'] !== '' ? (int)$_POST['consulta_id'] : null;

$medicamento = trim($_POST['medicamento'] ?? '');
$dosagem = trim($_POST['dosagem'] ?? '');
$orientacoes = trim($_POST['orientacoes'] ?? '');

function voltarPrescricaoComErro($id, $erro)
{
    $url = $id > 0 ? "prescricao_form.php?id={$id}&erro={$erro}" : "prescricao_form.php?erro={$erro}";
    header("Location: {$url}");
    exit;
}

if ($pacienteId <= 0) {
    voltarPrescricaoComErro($id, 'paciente');
}

if ($profissionalId <= 0) {
    voltarPrescricaoComErro($id, 'profissional');
}

if ($medicamento === '') {
    voltarPrescricaoComErro($id, 'medicamento');
}

if ($dosagem === '') {
    $dosagem = null;
}

if ($orientacoes === '') {
    $orientacoes = null;
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
    voltarPrescricaoComErro($id, 'paciente');
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
    voltarPrescricaoComErro($id, 'profissional');
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
        voltarPrescricaoComErro($id, 'consulta');
    }
}

if ($id > 0) {
    $stmt = $pdo->prepare("
        UPDATE prescricoes
        SET
            paciente_id = :paciente_id,
            profissional_id = :profissional_id,
            consulta_id = :consulta_id,
            medicamento = :medicamento,
            dosagem = :dosagem,
            orientacoes = :orientacoes,
            atualizado_em = NOW()
        WHERE id = :id
        AND ativo = 1
    ");

    $stmt->execute([
        ':paciente_id' => $pacienteId,
        ':profissional_id' => $profissionalId,
        ':consulta_id' => $consultaId,
        ':medicamento' => $medicamento,
        ':dosagem' => $dosagem,
        ':orientacoes' => $orientacoes,
        ':id' => $id
    ]);

    registrarAuditoria(
        $pdo,
        $_SESSION['usuario_id'],
        'PRESCRICAO_ATUALIZADA',
        'prescricoes',
        $id,
        'Prescrição atualizada.'
    );

    header('Location: prescricoes.php?msg=atualizado');
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO prescricoes
    (
        paciente_id,
        profissional_id,
        consulta_id,
        medicamento,
        dosagem,
        orientacoes,
        ativo
    )
    VALUES
    (
        :paciente_id,
        :profissional_id,
        :consulta_id,
        :medicamento,
        :dosagem,
        :orientacoes,
        1
    )
");

$stmt->execute([
    ':paciente_id' => $pacienteId,
    ':profissional_id' => $profissionalId,
    ':consulta_id' => $consultaId,
    ':medicamento' => $medicamento,
    ':dosagem' => $dosagem,
    ':orientacoes' => $orientacoes
]);

$novoId = $pdo->lastInsertId();

registrarAuditoria(
    $pdo,
    $_SESSION['usuario_id'],
    'PRESCRICAO_CRIADA',
    'prescricoes',
    $novoId,
    'Prescrição cadastrada.'
);

header('Location: prescricoes.php?msg=criado');
exit;