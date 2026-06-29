<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: internacoes.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    $url = $id > 0 ? "internacao_form.php?id={$id}&erro=csrf" : "internacao_form.php?erro=csrf";
    header("Location: {$url}");
    exit;
}

$pacienteId = isset($_POST['paciente_id']) ? (int)$_POST['paciente_id'] : 0;
$leitoId = isset($_POST['leito_id']) ? (int)$_POST['leito_id'] : 0;
$dataEntradaInput = trim($_POST['data_entrada'] ?? '');
$motivo = trim($_POST['motivo'] ?? '');
$observacoes = trim($_POST['observacoes'] ?? '');

function voltarInternacaoComErro($id, $erro)
{
    $url = $id > 0 ? "internacao_form.php?id={$id}&erro={$erro}" : "internacao_form.php?erro={$erro}";
    header("Location: {$url}");
    exit;
}

if ($pacienteId <= 0) {
    voltarInternacaoComErro($id, 'paciente');
}

if ($leitoId <= 0) {
    voltarInternacaoComErro($id, 'leito');
}

if ($dataEntradaInput === '') {
    voltarInternacaoComErro($id, 'data');
}

$dataEntrada = date('Y-m-d H:i:s', strtotime($dataEntradaInput));

if ($motivo === '') {
    $motivo = null;
}

if ($observacoes === '') {
    $observacoes = null;
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
    voltarInternacaoComErro($id, 'paciente');
}

$stmtLeito = $pdo->prepare("
    SELECT id, status
    FROM leitos
    WHERE id = :id
    AND ativo = 1
    LIMIT 1
");

$stmtLeito->execute([
    ':id' => $leitoId
]);

$leito = $stmtLeito->fetch();

if (!$leito) {
    voltarInternacaoComErro($id, 'leito');
}

$stmtOcupacao = $pdo->prepare("
    SELECT id
    FROM internacoes
    WHERE leito_id = :leito_id
    AND status = 'Ativa'
    AND ativo = 1
    AND id <> :id
    LIMIT 1
");

$stmtOcupacao->execute([
    ':leito_id' => $leitoId,
    ':id' => $id
]);

if ($stmtOcupacao->fetch()) {
    voltarInternacaoComErro($id, 'leito_ocupado');
}

try {
    $pdo->beginTransaction();

    if ($id > 0) {
        $stmtInternacaoAtual = $pdo->prepare("
            SELECT id, leito_id
            FROM internacoes
            WHERE id = :id
            AND status = 'Ativa'
            AND ativo = 1
            LIMIT 1
        ");

        $stmtInternacaoAtual->execute([
            ':id' => $id
        ]);

        $internacaoAtual = $stmtInternacaoAtual->fetch();

        if (!$internacaoAtual) {
            $pdo->rollBack();
            header('Location: internacoes.php');
            exit;
        }

        $leitoAnteriorId = (int)$internacaoAtual['leito_id'];

        $stmt = $pdo->prepare("
            UPDATE internacoes
            SET
                paciente_id = :paciente_id,
                leito_id = :leito_id,
                data_entrada = :data_entrada,
                motivo = :motivo,
                observacoes = :observacoes,
                atualizado_em = NOW()
            WHERE id = :id
            AND status = 'Ativa'
            AND ativo = 1
        ");

        $stmt->execute([
            ':paciente_id' => $pacienteId,
            ':leito_id' => $leitoId,
            ':data_entrada' => $dataEntrada,
            ':motivo' => $motivo,
            ':observacoes' => $observacoes,
            ':id' => $id
        ]);

        if ($leitoAnteriorId !== $leitoId) {
            $stmtLiberaLeito = $pdo->prepare("
                UPDATE leitos
                SET status = 'Disponivel',
                    atualizado_em = NOW()
                WHERE id = :id
            ");

            $stmtLiberaLeito->execute([
                ':id' => $leitoAnteriorId
            ]);
        }

        $stmtOcupaLeito = $pdo->prepare("
            UPDATE leitos
            SET status = 'Ocupado',
                atualizado_em = NOW()
            WHERE id = :id
        ");

        $stmtOcupaLeito->execute([
            ':id' => $leitoId
        ]);

        registrarAuditoria(
            $pdo,
            $_SESSION['usuario_id'],
            'INTERNACAO_ATUALIZADA',
            'internacoes',
            $id,
            'Internação atualizada.'
        );

        $pdo->commit();

        header('Location: internacoes.php?msg=atualizada');
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO internacoes
        (
            paciente_id,
            leito_id,
            data_entrada,
            status,
            motivo,
            observacoes,
            ativo
        )
        VALUES
        (
            :paciente_id,
            :leito_id,
            :data_entrada,
            'Ativa',
            :motivo,
            :observacoes,
            1
        )
    ");

    $stmt->execute([
        ':paciente_id' => $pacienteId,
        ':leito_id' => $leitoId,
        ':data_entrada' => $dataEntrada,
        ':motivo' => $motivo,
        ':observacoes' => $observacoes
    ]);

    $novoId = $pdo->lastInsertId();

    $stmtOcupaLeito = $pdo->prepare("
        UPDATE leitos
        SET status = 'Ocupado',
            atualizado_em = NOW()
        WHERE id = :id
    ");

    $stmtOcupaLeito->execute([
        ':id' => $leitoId
    ]);

    registrarAuditoria(
        $pdo,
        $_SESSION['usuario_id'],
        'INTERNACAO_CRIADA',
        'internacoes',
        $novoId,
        'Internação criada e leito ocupado.'
    );

    $pdo->commit();

    header('Location: internacoes.php?msg=criada');
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());
    header('Location: internacao_form.php?erro=leito');
    exit;
}