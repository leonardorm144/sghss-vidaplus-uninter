<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/auth/valida_sessao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: trocar_senha.php');
    exit;
}

if (
    ($_SESSION['usuario_perfil'] ?? '') !== 'paciente' ||
    (int)($_SESSION['trocar_senha_primeiro_acesso'] ?? 0) !== 1
) {
    header('Location: dashboard.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: trocar_senha.php?erro=csrf');
    exit;
}

$novaSenha = trim($_POST['nova_senha'] ?? '');
$confirmarSenha = trim($_POST['confirmar_senha'] ?? '');

if ($novaSenha === '' || $confirmarSenha === '') {
    header('Location: trocar_senha.php?erro=vazia');
    exit;
}

if ($novaSenha !== $confirmarSenha) {
    header('Location: trocar_senha.php?erro=diferente');
    exit;
}

$temTamanhoMinimo = strlen($novaSenha) >= 8;
$temLetra = preg_match('/[a-zA-Z]/', $novaSenha);
$temNumero = preg_match('/[0-9]/', $novaSenha);

if (!$temTamanhoMinimo || !$temLetra || !$temNumero) {
    header('Location: trocar_senha.php?erro=fraca');
    exit;
}

$usuarioId = (int)$_SESSION['usuario_id'];

$stmtPaciente = $pdo->prepare("
    SELECT data_nascimento
    FROM pacientes
    WHERE usuario_id = :usuario_id
    AND ativo = 1
    LIMIT 1
");

$stmtPaciente->execute([
    ':usuario_id' => $usuarioId
]);

$paciente = $stmtPaciente->fetch();

if (!empty($paciente['data_nascimento'])) {
    $data = DateTime::createFromFormat('Y-m-d', $paciente['data_nascimento']);

    if ($data && $novaSenha === $data->format('dmY')) {
        header('Location: trocar_senha.php?erro=nascimento');
        exit;
    }
}

$senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    UPDATE usuarios
    SET senha = :senha,
        trocar_senha_primeiro_acesso = 0,
        atualizado_em = NOW()
    WHERE id = :id
    AND perfil = 'paciente'
");

$stmt->execute([
    ':senha' => $senhaHash,
    ':id' => $usuarioId
]);

$_SESSION['trocar_senha_primeiro_acesso'] = 0;

registrarAuditoria(
    $pdo,
    $usuarioId,
    'SENHA_ALTERADA_PRIMEIRO_ACESSO',
    'usuarios',
    $usuarioId,
    'Paciente alterou a senha obrigatória no primeiro acesso.'
);

header('Location: dashboard.php');
exit;