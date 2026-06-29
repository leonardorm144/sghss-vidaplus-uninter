<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: consultas.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: consulta_form.php?erro=csrf');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$pacienteId = isset($_POST['paciente_id']) ? (int)$_POST['paciente_id'] : 0;
$profissionalId = isset($_POST['profissional_id']) ? (int)$_POST['profissional_id'] : 0;
$unidadeId = isset($_POST['unidade_id']) ? (int)$_POST['unidade_id'] : 0;
$tipo = trim($_POST['tipo'] ?? '');
$status = trim($_POST['status'] ?? '');
$dataConsulta = trim($_POST['data_consulta'] ?? '');
$motivo = trim($_POST['motivo'] ?? '');

$tiposPermitidos = ['Presencial', 'Telemedicina'];
$statusPermitidos = ['Agendada', 'Confirmada'];

function voltarConsultaComErro($id, $erro)
{
    $url = $id > 0 ? "consulta_form.php?id={$id}&erro={$erro}" : "consulta_form.php?erro={$erro}";
    header("Location: {$url}");
    exit;
}

if ($pacienteId <= 0) {
    voltarConsultaComErro($id, 'paciente');
}

if ($profissionalId <= 0) {
    voltarConsultaComErro($id, 'profissional');
}

if ($unidadeId <= 0) {
    voltarConsultaComErro($id, 'unidade');
}

if ($dataConsulta === '') {
    voltarConsultaComErro($id, 'data');
}

if (!in_array($tipo, $tiposPermitidos)) {
    voltarConsultaComErro($id, 'tipo');
}

if (!in_array($status, $statusPermitidos)) {
    voltarConsultaComErro($id, 'status');
}

$dataConsulta = str_replace('T', ' ', $dataConsulta);

if (strlen($dataConsulta) === 16) {
    $dataConsulta .= ':00';
}

if ($motivo === '') {
    $motivo = null;
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
    voltarConsultaComErro($id, 'paciente');
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
    voltarConsultaComErro($id, 'profissional');
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
    voltarConsultaComErro($id, 'unidade');
}

$linkTeleconsulta = null;

if ($id > 0) {
    $stmtConsultaAtual = $pdo->prepare("
        SELECT link_teleconsulta
        FROM consultas
        WHERE id = :id
        LIMIT 1
    ");

    $stmtConsultaAtual->execute([
        ':id' => $id
    ]);

    $consultaAtual = $stmtConsultaAtual->fetch();

    if (!$consultaAtual) {
        header('Location: consultas.php');
        exit;
    }

    if ($tipo === 'Telemedicina') {
        $linkTeleconsulta = $consultaAtual['link_teleconsulta'];

        if (empty($linkTeleconsulta)) {
            $linkTeleconsulta = BASE_URL . 'teleconsulta.php?sala=' . bin2hex(random_bytes(12));
        }
    }

    $stmt = $pdo->prepare("
        UPDATE consultas
        SET
            paciente_id = :paciente_id,
            profissional_id = :profissional_id,
            unidade_id = :unidade_id,
            tipo = :tipo,
            status = :status,
            data_consulta = :data_consulta,
            motivo = :motivo,
            link_teleconsulta = :link_teleconsulta,
            atualizado_em = NOW()
        WHERE id = :id
    ");

    $stmt->execute([
        ':paciente_id' => $pacienteId,
        ':profissional_id' => $profissionalId,
        ':unidade_id' => $unidadeId,
        ':tipo' => $tipo,
        ':status' => $status,
        ':data_consulta' => $dataConsulta,
        ':motivo' => $motivo,
        ':link_teleconsulta' => $linkTeleconsulta,
        ':id' => $id
    ]);

    registrarAuditoria(
        $pdo,
        $_SESSION['usuario_id'],
        'CONSULTA_ATUALIZADA',
        'consultas',
        $id,
        'Consulta atualizada.'
    );

    header('Location: consultas.php?msg=atualizado');
    exit;
}

if ($tipo === 'Telemedicina') {
    $linkTeleconsulta = BASE_URL . 'teleconsulta.php?sala=' . bin2hex(random_bytes(12));
}

$stmt = $pdo->prepare("
    INSERT INTO consultas
    (
        paciente_id,
        profissional_id,
        unidade_id,
        tipo,
        status,
        data_consulta,
        motivo,
        link_teleconsulta
    )
    VALUES
    (
        :paciente_id,
        :profissional_id,
        :unidade_id,
        :tipo,
        :status,
        :data_consulta,
        :motivo,
        :link_teleconsulta
    )
");

$stmt->execute([
    ':paciente_id' => $pacienteId,
    ':profissional_id' => $profissionalId,
    ':unidade_id' => $unidadeId,
    ':tipo' => $tipo,
    ':status' => $status,
    ':data_consulta' => $dataConsulta,
    ':motivo' => $motivo,
    ':link_teleconsulta' => $linkTeleconsulta
]);

$novoId = $pdo->lastInsertId();

registrarAuditoria(
    $pdo,
    $_SESSION['usuario_id'],
    'CONSULTA_CRIADA',
    'consultas',
    $novoId,
    'Consulta agendada.'
);

header('Location: consultas.php?msg=criado');
exit;