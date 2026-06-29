<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('recepcao');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pacientes.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: pacientes.php');
    exit;
}

$pacienteId = isset($_POST['paciente_id']) ? (int)$_POST['paciente_id'] : 0;
$acao = $_POST['acao'] ?? '';

if ($pacienteId <= 0 || !in_array($acao, ['criar', 'resetar'])) {
    header('Location: pacientes.php');
    exit;
}

function somenteNumerosAcessoSalvar($valor)
{
    return preg_replace('/\D/', '', (string)$valor);
}

function gerarLoginAcessoSalvar($cpf)
{
    $cpfNumeros = somenteNumerosAcessoSalvar($cpf);

    if (strlen($cpfNumeros) !== 11) {
        return null;
    }

    return $cpfNumeros . '@vidaplus.com';
}

function gerarSenhaInicialAcessoSalvar($dataNascimento)
{
    if (empty($dataNascimento)) {
        return null;
    }

    $data = DateTime::createFromFormat('Y-m-d', $dataNascimento);

    if (!$data) {
        return null;
    }

    return $data->format('dmY');
}

$stmtPaciente = $pdo->prepare("
    SELECT *
    FROM pacientes
    WHERE id = :id
    AND ativo = 1
    LIMIT 1
");

$stmtPaciente->execute([
    ':id' => $pacienteId
]);

$paciente = $stmtPaciente->fetch();

if (!$paciente) {
    header('Location: pacientes.php');
    exit;
}

$login = gerarLoginAcessoSalvar($paciente['cpf'] ?? '');
$senhaInicial = gerarSenhaInicialAcessoSalvar($paciente['data_nascimento'] ?? '');

if (!$login || !$senhaInicial) {
    header('Location: paciente_acesso.php?id=' . $pacienteId . '&msg=erro_dados');
    exit;
}

$senhaHash = password_hash($senhaInicial, PASSWORD_DEFAULT);

if ($acao === 'criar') {
    $stmtUsuarioExistente = $pdo->prepare("
        SELECT id
        FROM usuarios
        WHERE email = :email
        LIMIT 1
    ");

    $stmtUsuarioExistente->execute([
        ':email' => $login
    ]);

    $usuarioExistente = $stmtUsuarioExistente->fetch();

    if ($usuarioExistente) {
        $usuarioId = $usuarioExistente['id'];

        $stmtAtualizarUsuario = $pdo->prepare("
            UPDATE usuarios
            SET nome = :nome,
                senha = :senha,
                perfil = 'paciente',
                ativo = 1,
                trocar_senha_primeiro_acesso = 1,
                atualizado_em = NOW()
            WHERE id = :id
        ");

        $stmtAtualizarUsuario->execute([
            ':nome' => $paciente['nome'],
            ':senha' => $senhaHash,
            ':id' => $usuarioId
        ]);
    } else {
        $stmtCriarUsuario = $pdo->prepare("
            INSERT INTO usuarios (
                nome,
                email,
                senha,
                perfil,
                ativo,
                trocar_senha_primeiro_acesso,
                criado_em
            ) VALUES (
                :nome,
                :email,
                :senha,
                'paciente',
                1,
                1,
                NOW()
            )
        ");

        $stmtCriarUsuario->execute([
            ':nome' => $paciente['nome'],
            ':email' => $login,
            ':senha' => $senhaHash
        ]);

        $usuarioId = $pdo->lastInsertId();
    }

    $stmtVincular = $pdo->prepare("
        UPDATE pacientes
        SET usuario_id = :usuario_id,
            atualizado_em = NOW()
        WHERE id = :paciente_id
    ");

    $stmtVincular->execute([
        ':usuario_id' => $usuarioId,
        ':paciente_id' => $pacienteId
    ]);

    registrarAuditoria(
        $pdo,
        $_SESSION['usuario_id'],
        'ACESSO_PACIENTE_CRIADO_RECEPCAO',
        'usuarios',
        $usuarioId,
        'Acesso criado/recriado pela recepção para o paciente: ' . $paciente['nome']
    );

    header('Location: paciente_acesso.php?id=' . $pacienteId . '&msg=criado');
    exit;
}

if ($acao === 'resetar') {
    $usuarioId = (int)($paciente['usuario_id'] ?? 0);

    if ($usuarioId <= 0) {
        $stmtUsuario = $pdo->prepare("
            SELECT id
            FROM usuarios
            WHERE email = :email
            LIMIT 1
        ");

        $stmtUsuario->execute([
            ':email' => $login
        ]);

        $usuario = $stmtUsuario->fetch();

        if (!$usuario) {
            header('Location: paciente_acesso.php?id=' . $pacienteId . '&msg=erro_dados');
            exit;
        }

        $usuarioId = (int)$usuario['id'];
    }

    $stmtResetar = $pdo->prepare("
        UPDATE usuarios
        SET senha = :senha,
            ativo = 1,
            trocar_senha_primeiro_acesso = 1,
            atualizado_em = NOW()
        WHERE id = :id
        AND perfil = 'paciente'
    ");

    $stmtResetar->execute([
        ':senha' => $senhaHash,
        ':id' => $usuarioId
    ]);

    $stmtVincular = $pdo->prepare("
        UPDATE pacientes
        SET usuario_id = :usuario_id,
            atualizado_em = NOW()
        WHERE id = :paciente_id
    ");

    $stmtVincular->execute([
        ':usuario_id' => $usuarioId,
        ':paciente_id' => $pacienteId
    ]);

    registrarAuditoria(
        $pdo,
        $_SESSION['usuario_id'],
        'ACESSO_PACIENTE_SENHA_RESETADA_RECEPCAO',
        'usuarios',
        $usuarioId,
        'Senha inicial redefinida pela recepção para o paciente: ' . $paciente['nome']
    );

    header('Location: paciente_acesso.php?id=' . $pacienteId . '&msg=resetado');
    exit;
}

header('Location: pacientes.php');
exit;