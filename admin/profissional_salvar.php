<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profissionais.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: profissional_form.php?erro=csrf');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$nome = trim($_POST['nome'] ?? '');
$tipo = trim($_POST['tipo'] ?? '');
$especialidade = trim($_POST['especialidade'] ?? '');
$registroProfissional = trim($_POST['registro_profissional'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$email = trim($_POST['email'] ?? '');
$unidadeId = isset($_POST['unidade_id']) && $_POST['unidade_id'] !== '' ? (int)$_POST['unidade_id'] : null;

$tiposPermitidos = ['Medico', 'Enfermeiro', 'Tecnico', 'Outro'];

if ($nome === '') {
    $url = $id > 0 ? "profissional_form.php?id={$id}&erro=nome" : "profissional_form.php?erro=nome";
    header("Location: {$url}");
    exit;
}

if (!in_array($tipo, $tiposPermitidos)) {
    $url = $id > 0 ? "profissional_form.php?id={$id}&erro=tipo" : "profissional_form.php?erro=tipo";
    header("Location: {$url}");
    exit;
}

if ($especialidade === '') {
    $especialidade = null;
}

if ($registroProfissional === '') {
    $registroProfissional = null;
}

if ($telefone === '') {
    $telefone = null;
}

if ($email === '') {
    $email = null;
}

if ($id > 0) {
    $stmt = $pdo->prepare("
        UPDATE profissionais
        SET
            unidade_id = :unidade_id,
            nome = :nome,
            tipo = :tipo,
            especialidade = :especialidade,
            registro_profissional = :registro_profissional,
            telefone = :telefone,
            email = :email,
            atualizado_em = NOW()
        WHERE id = :id
        AND ativo = 1
    ");

    $stmt->execute([
        ':unidade_id' => $unidadeId,
        ':nome' => $nome,
        ':tipo' => $tipo,
        ':especialidade' => $especialidade,
        ':registro_profissional' => $registroProfissional,
        ':telefone' => $telefone,
        ':email' => $email,
        ':id' => $id
    ]);

    registrarAuditoria(
        $pdo,
        $_SESSION['usuario_id'],
        'PROFISSIONAL_ATUALIZADO',
        'profissionais',
        $id,
        'Profissional atualizado: ' . $nome
    );

    header('Location: profissionais.php?msg=atualizado');
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO profissionais
    (
        unidade_id,
        nome,
        tipo,
        especialidade,
        registro_profissional,
        telefone,
        email,
        ativo
    )
    VALUES
    (
        :unidade_id,
        :nome,
        :tipo,
        :especialidade,
        :registro_profissional,
        :telefone,
        :email,
        1
    )
");

$stmt->execute([
    ':unidade_id' => $unidadeId,
    ':nome' => $nome,
    ':tipo' => $tipo,
    ':especialidade' => $especialidade,
    ':registro_profissional' => $registroProfissional,
    ':telefone' => $telefone,
    ':email' => $email
]);

$novoId = $pdo->lastInsertId();

registrarAuditoria(
    $pdo,
    $_SESSION['usuario_id'],
    'PROFISSIONAL_CRIADO',
    'profissionais',
    $novoId,
    'Profissional cadastrado: ' . $nome
);

header('Location: profissionais.php?msg=criado');
exit;