<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: usuarios.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: usuario_form.php?erro=csrf');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$perfil = trim($_POST['perfil'] ?? '');
$senha = $_POST['senha'] ?? '';
$confirmarSenha = $_POST['confirmar_senha'] ?? '';

$pacienteId = isset($_POST['paciente_id']) && $_POST['paciente_id'] !== '' ? (int)$_POST['paciente_id'] : null;
$profissionalId = isset($_POST['profissional_id']) && $_POST['profissional_id'] !== '' ? (int)$_POST['profissional_id'] : null;

$perfisPermitidos = ['admin', 'profissional', 'paciente', 'recepcao'];

function voltarUsuarioComErro($id, $erro)
{
    $url = $id > 0 ? "usuario_form.php?id={$id}&erro={$erro}" : "usuario_form.php?erro={$erro}";
    header("Location: {$url}");
    exit;
}

if ($nome === '') {
    voltarUsuarioComErro($id, 'nome');
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    voltarUsuarioComErro($id, 'email');
}

if (!in_array($perfil, $perfisPermitidos)) {
    voltarUsuarioComErro($id, 'perfil');
}

$stmtEmail = $pdo->prepare("
    SELECT id
    FROM usuarios
    WHERE email = :email
    AND id <> :id
    LIMIT 1
");

$stmtEmail->execute([
    ':email' => $email,
    ':id' => $id
]);

if ($stmtEmail->fetch()) {
    voltarUsuarioComErro($id, 'email_duplicado');
}

if ($id <= 0 || $senha !== '') {
    if (strlen($senha) < 6) {
        voltarUsuarioComErro($id, 'senha');
    }

    if ($senha !== $confirmarSenha) {
        voltarUsuarioComErro($id, 'confirmacao');
    }
}

if ($perfil !== 'paciente') {
    $pacienteId = null;
}

if ($perfil !== 'profissional') {
    $profissionalId = null;
}

if ($pacienteId !== null) {
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
        $pacienteId = null;
    }
}

if ($profissionalId !== null) {
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
        $profissionalId = null;
    }
}

if ($id > 0) {
    if ($senha !== '') {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            UPDATE usuarios
            SET
                nome = :nome,
                email = :email,
                perfil = :perfil,
                senha = :senha,
                atualizado_em = NOW()
            WHERE id = :id
        ");

        $stmt->execute([
            ':nome' => $nome,
            ':email' => $email,
            ':perfil' => $perfil,
            ':senha' => $senhaHash,
            ':id' => $id
        ]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE usuarios
            SET
                nome = :nome,
                email = :email,
                perfil = :perfil,
                atualizado_em = NOW()
            WHERE id = :id
        ");

        $stmt->execute([
            ':nome' => $nome,
            ':email' => $email,
            ':perfil' => $perfil,
            ':id' => $id
        ]);
    }

    $usuarioId = $id;

    registrarAuditoria(
        $pdo,
        $_SESSION['usuario_id'],
        'USUARIO_ATUALIZADO',
        'usuarios',
        $usuarioId,
        'Usuário atualizado: ' . $nome
    );

    $msg = 'atualizado';
} else {
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO usuarios
        (
            nome,
            email,
            senha,
            perfil,
            ativo
        )
        VALUES
        (
            :nome,
            :email,
            :senha,
            :perfil,
            1
        )
    ");

    $stmt->execute([
        ':nome' => $nome,
        ':email' => $email,
        ':senha' => $senhaHash,
        ':perfil' => $perfil
    ]);

    $usuarioId = $pdo->lastInsertId();

    registrarAuditoria(
        $pdo,
        $_SESSION['usuario_id'],
        'USUARIO_CRIADO',
        'usuarios',
        $usuarioId,
        'Usuário cadastrado: ' . $nome
    );

    $msg = 'criado';
}

$pdo->prepare("
    UPDATE pacientes
    SET usuario_id = NULL
    WHERE usuario_id = :usuario_id
")->execute([
    ':usuario_id' => $usuarioId
]);

$pdo->prepare("
    UPDATE profissionais
    SET usuario_id = NULL
    WHERE usuario_id = :usuario_id
")->execute([
    ':usuario_id' => $usuarioId
]);

if ($pacienteId !== null) {
    $pdo->prepare("
        UPDATE pacientes
        SET usuario_id = :usuario_id
        WHERE id = :paciente_id
    ")->execute([
        ':usuario_id' => $usuarioId,
        ':paciente_id' => $pacienteId
    ]);
}

if ($profissionalId !== null) {
    $pdo->prepare("
        UPDATE profissionais
        SET usuario_id = :usuario_id
        WHERE id = :profissional_id
    ")->execute([
        ':usuario_id' => $usuarioId,
        ':profissional_id' => $profissionalId
    ]);
}

header("Location: usuarios.php?msg={$msg}");
exit;