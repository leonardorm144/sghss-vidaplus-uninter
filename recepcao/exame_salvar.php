<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('recepcao');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: exames.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    $url = $id > 0 ? "exame_form.php?id={$id}&erro=csrf" : "exame_form.php?erro=csrf";
    header("Location: {$url}");
    exit;
}

$pacienteId = isset($_POST['paciente_id']) ? (int)$_POST['paciente_id'] : 0;
$unidadeId = isset($_POST['unidade_id']) && $_POST['unidade_id'] !== '' ? (int)$_POST['unidade_id'] : null;
$nomeExame = trim($_POST['nome_exame'] ?? '');
$dataExameInput = trim($_POST['data_exame'] ?? '');
$status = trim($_POST['status'] ?? '');
$resultado = trim($_POST['resultado'] ?? '');
$observacoes = trim($_POST['observacoes'] ?? '');

$statusPermitidos = ['Solicitado', 'Agendado', 'Realizado', 'Cancelado'];

function voltarExameRecepcaoComErro($id, $erro)
{
    $url = $id > 0 ? "exame_form.php?id={$id}&erro={$erro}" : "exame_form.php?erro={$erro}";
    header("Location: {$url}");
    exit;
}

if ($pacienteId <= 0) {
    voltarExameRecepcaoComErro($id, 'paciente');
}

if ($nomeExame === '') {
    voltarExameRecepcaoComErro($id, 'nome_exame');
}

if (!in_array($status, $statusPermitidos)) {
    voltarExameRecepcaoComErro($id, 'status');
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
    voltarExameRecepcaoComErro($id, 'paciente');
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
        voltarExameRecepcaoComErro($id, 'unidade');
    }
}

$dataExame = null;

if ($dataExameInput !== '') {
    $dataExame = date('Y-m-d H:i:s', strtotime($dataExameInput));
}

if ($resultado === '') {
    $resultado = null;
}

if ($observacoes === '') {
    $observacoes = null;
}

if ($id > 0) {
    $stmtVerifica = $pdo->prepare("
        SELECT id
        FROM exames
        WHERE id = :id
        AND ativo = 1
        LIMIT 1
    ");

    $stmtVerifica->execute([
        ':id' => $id
    ]);

    if (!$stmtVerifica->fetch()) {
        header('Location: exames.php');
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE exames
        SET
            paciente_id = :paciente_id,
            unidade_id = :unidade_id,
            nome_exame = :nome_exame,
            data_exame = :data_exame,
            status = :status,
            resultado = :resultado,
            observacoes = :observacoes,
            atualizado_em = NOW()
        WHERE id = :id
        AND ativo = 1
    ");

    $stmt->execute([
        ':paciente_id' => $pacienteId,
        ':unidade_id' => $unidadeId,
        ':nome_exame' => $nomeExame,
        ':data_exame' => $dataExame,
        ':status' => $status,
        ':resultado' => $resultado,
        ':observacoes' => $observacoes,
        ':id' => $id
    ]);

    registrarAuditoria(
        $pdo,
        $_SESSION['usuario_id'],
        'EXAME_ATUALIZADO_RECEPCAO',
        'exames',
        $id,
        'Exame atualizado pela recepção: ' . $nomeExame
    );

    header('Location: exames.php?msg=atualizado');
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO exames
    (
        paciente_id,
        unidade_id,
        nome_exame,
        data_exame,
        status,
        resultado,
        observacoes,
        ativo
    )
    VALUES
    (
        :paciente_id,
        :unidade_id,
        :nome_exame,
        :data_exame,
        :status,
        :resultado,
        :observacoes,
        1
    )
");

$stmt->execute([
    ':paciente_id' => $pacienteId,
    ':unidade_id' => $unidadeId,
    ':nome_exame' => $nomeExame,
    ':data_exame' => $dataExame,
    ':status' => $status,
    ':resultado' => $resultado,
    ':observacoes' => $observacoes
]);

$novoId = $pdo->lastInsertId();

registrarAuditoria(
    $pdo,
    $_SESSION['usuario_id'],
    'EXAME_CRIADO_RECEPCAO',
    'exames',
    $novoId,
    'Exame criado pela recepção: ' . $nomeExame
);

header('Location: exames.php?msg=criado');
exit;