<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('recepcao');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: agendamentos.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    $url = $id > 0 ? "agendamento_form.php?id={$id}&erro=csrf" : "agendamento_form.php?erro=csrf";
    header("Location: {$url}");
    exit;
}

$pacienteId = isset($_POST['paciente_id']) ? (int)$_POST['paciente_id'] : 0;
$profissionalId = isset($_POST['profissional_id']) ? (int)$_POST['profissional_id'] : 0;
$unidadeId = isset($_POST['unidade_id']) && $_POST['unidade_id'] !== '' ? (int)$_POST['unidade_id'] : null;

$dataConsultaInput = trim($_POST['data_consulta'] ?? '');
$tipo = trim($_POST['tipo'] ?? '');
$status = trim($_POST['status'] ?? '');
$motivo = trim($_POST['motivo'] ?? '');

$tiposPermitidos = ['Presencial', 'Telemedicina'];
$statusPermitidos = ['Agendada', 'Confirmada', 'Cancelada', 'Concluida'];

function voltarAgendamentoRecepcaoComErro($id, $erro)
{
    $url = $id > 0 ? "agendamento_form.php?id={$id}&erro={$erro}" : "agendamento_form.php?erro={$erro}";
    header("Location: {$url}");
    exit;
}

if ($pacienteId <= 0) {
    voltarAgendamentoRecepcaoComErro($id, 'paciente');
}

if ($profissionalId <= 0) {
    voltarAgendamentoRecepcaoComErro($id, 'profissional');
}

if ($dataConsultaInput === '') {
    voltarAgendamentoRecepcaoComErro($id, 'data');
}

if (!in_array($tipo, $tiposPermitidos)) {
    voltarAgendamentoRecepcaoComErro($id, 'tipo');
}

if (!in_array($status, $statusPermitidos)) {
    voltarAgendamentoRecepcaoComErro($id, 'status');
}

$dataConsulta = date('Y-m-d H:i:s', strtotime($dataConsultaInput));

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
    voltarAgendamentoRecepcaoComErro($id, 'paciente');
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
    voltarAgendamentoRecepcaoComErro($id, 'profissional');
}

if ($unidadeId !== null) {
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
        voltarAgendamentoRecepcaoComErro($id, 'unidade');
    }
}

if ($motivo === '') {
    $motivo = null;
}

$linkTeleconsulta = null;

if ($tipo === 'Telemedicina') {
    if ($id > 0) {
        $stmtLink = $pdo->prepare("
            SELECT link_teleconsulta
            FROM consultas
            WHERE id = :id
            LIMIT 1
        ");

        $stmtLink->execute([
            ':id' => $id
        ]);

        $linkAtual = $stmtLink->fetchColumn();

        if (!empty($linkAtual)) {
            $linkTeleconsulta = $linkAtual;
        }
    }

    if (empty($linkTeleconsulta)) {
        $linkTeleconsulta = BASE_URL . 'teleconsulta.php?sala=' . bin2hex(random_bytes(12));
    }
}

if ($id > 0) {
    $stmtVerifica = $pdo->prepare("
        SELECT id
        FROM consultas
        WHERE id = :id
        LIMIT 1
    ");

    $stmtVerifica->execute([
        ':id' => $id
    ]);

    if (!$stmtVerifica->fetch()) {
        header('Location: agendamentos.php');
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE consultas
        SET
            paciente_id = :paciente_id,
            profissional_id = :profissional_id,
            unidade_id = :unidade_id,
            data_consulta = :data_consulta,
            tipo = :tipo,
            status = :status,
            motivo = :motivo,
            link_teleconsulta = :link_teleconsulta,
            atualizado_em = NOW()
        WHERE id = :id
    ");

    $stmt->execute([
        ':paciente_id' => $pacienteId,
        ':profissional_id' => $profissionalId,
        ':unidade_id' => $unidadeId,
        ':data_consulta' => $dataConsulta,
        ':tipo' => $tipo,
        ':status' => $status,
        ':motivo' => $motivo,
        ':link_teleconsulta' => $linkTeleconsulta,
        ':id' => $id
    ]);

    registrarAuditoria(
        $pdo,
        $_SESSION['usuario_id'],
        'AGENDAMENTO_ATUALIZADO_RECEPCAO',
        'consultas',
        $id,
        'Agendamento atualizado pela recepção.'
    );

    header('Location: agendamentos.php?msg=atualizado');
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO consultas
    (
        paciente_id,
        profissional_id,
        unidade_id,
        data_consulta,
        tipo,
        status,
        motivo,
        link_teleconsulta
    )
    VALUES
    (
        :paciente_id,
        :profissional_id,
        :unidade_id,
        :data_consulta,
        :tipo,
        :status,
        :motivo,
        :link_teleconsulta
    )
");

$stmt->execute([
    ':paciente_id' => $pacienteId,
    ':profissional_id' => $profissionalId,
    ':unidade_id' => $unidadeId,
    ':data_consulta' => $dataConsulta,
    ':tipo' => $tipo,
    ':status' => $status,
    ':motivo' => $motivo,
    ':link_teleconsulta' => $linkTeleconsulta
]);

$novoId = $pdo->lastInsertId();

registrarAuditoria(
    $pdo,
    $_SESSION['usuario_id'],
    'AGENDAMENTO_CRIADO_RECEPCAO',
    'consultas',
    $novoId,
    'Agendamento criado pela recepção.'
);

header('Location: agendamentos.php?msg=criado');
exit;