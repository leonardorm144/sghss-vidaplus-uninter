<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('profissional');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: prescricoes.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: prescricao_form.php?erro=csrf');
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
$pacienteId = isset($_POST['paciente_id']) ? (int)$_POST['paciente_id'] : 0;
$consultaId = isset($_POST['consulta_id']) && $_POST['consulta_id'] !== '' ? (int)$_POST['consulta_id'] : null;

$medicamento = trim($_POST['medicamento'] ?? '');
$dosagem = trim($_POST['dosagem'] ?? '');
$orientacoes = trim($_POST['orientacoes'] ?? '');

function voltarPrescricaoProfissionalComErro($id, $erro)
{
    $url = $id > 0 ? "prescricao_form.php?id={$id}&erro={$erro}" : "prescricao_form.php?erro={$erro}";
    header("Location: {$url}");
    exit;
}

if ($pacienteId <= 0) {
    voltarPrescricaoProfissionalComErro($id, 'paciente');
}

if ($medicamento === '') {
    voltarPrescricaoProfissionalComErro($id, 'medicamento');
}

if ($dosagem === '') {
    $dosagem = null;
}

if ($orientacoes === '') {
    $orientacoes = null;
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
    voltarPrescricaoProfissionalComErro($id, 'paciente');
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
        voltarPrescricaoProfissionalComErro($id, 'consulta');
    }
}

if ($id > 0) {
    $stmtVerifica = $pdo->prepare("
        SELECT id
        FROM prescricoes
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
        header('Location: prescricoes.php');
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE prescricoes
        SET
            paciente_id = :paciente_id,
            consulta_id = :consulta_id,
            medicamento = :medicamento,
            dosagem = :dosagem,
            orientacoes = :orientacoes,
            atualizado_em = NOW()
        WHERE id = :id
        AND profissional_id = :profissional_id
        AND ativo = 1
    ");

    $stmt->execute([
        ':paciente_id' => $pacienteId,
        ':consulta_id' => $consultaId,
        ':medicamento' => $medicamento,
        ':dosagem' => $dosagem,
        ':orientacoes' => $orientacoes,
        ':id' => $id,
        ':profissional_id' => $profissional['id']
    ]);

    registrarAuditoria(
        $pdo,
        $_SESSION['usuario_id'],
        'PRESCRICAO_ATUALIZADA_PROFISSIONAL',
        'prescricoes',
        $id,
        'Prescrição atualizada pelo profissional.'
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
    ':profissional_id' => $profissional['id'],
    ':consulta_id' => $consultaId,
    ':medicamento' => $medicamento,
    ':dosagem' => $dosagem,
    ':orientacoes' => $orientacoes
]);

$novoId = $pdo->lastInsertId();

registrarAuditoria(
    $pdo,
    $_SESSION['usuario_id'],
    'PRESCRICAO_CRIADA_PROFISSIONAL',
    'prescricoes',
    $novoId,
    'Prescrição criada pelo profissional.'
);

header('Location: prescricoes.php?msg=criado');
exit;