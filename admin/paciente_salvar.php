<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

function somenteNumerosPaciente($valor)
{
    return preg_replace('/\D/', '', (string)$valor);
}

function validarCpfPaciente($cpf)
{
    $cpf = somenteNumerosPaciente($cpf);

    if (strlen($cpf) !== 11) {
        return false;
    }

    if (preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    for ($t = 9; $t < 11; $t++) {
        $soma = 0;

        for ($i = 0; $i < $t; $i++) {
            $soma += (int)$cpf[$i] * (($t + 1) - $i);
        }

        $digito = ((10 * $soma) % 11) % 10;

        if ((int)$cpf[$t] !== $digito) {
            return false;
        }
    }

    return true;
}

function formatarCpfPaciente($cpf)
{
    $cpf = somenteNumerosPaciente($cpf);

    if (strlen($cpf) !== 11) {
        return $cpf;
    }

    return substr($cpf, 0, 3) . '.' .
           substr($cpf, 3, 3) . '.' .
           substr($cpf, 6, 3) . '-' .
           substr($cpf, 9, 2);
}

function gerarSenhaNascimentoPaciente($dataNascimento)
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

function criarUsuarioPacienteAutomatico($pdo, $pacienteId, $nome, $cpf, $dataNascimento)
{
    $cpfNumeros = somenteNumerosPaciente($cpf);

    if (strlen($cpfNumeros) !== 11) {
        return null;
    }

    $senhaInicial = gerarSenhaNascimentoPaciente($dataNascimento);

    if (!$senhaInicial) {
        return null;
    }

    $emailAutomatico = $cpfNumeros . '@vidaplus.com';

    $stmtUsuarioExistente = $pdo->prepare("
        SELECT id
        FROM usuarios
        WHERE email = :email
        LIMIT 1
    ");

    $stmtUsuarioExistente->execute([
        ':email' => $emailAutomatico
    ]);

    $usuarioExistente = $stmtUsuarioExistente->fetch();

    if ($usuarioExistente) {
        $usuarioId = $usuarioExistente['id'];
    } else {
        $senhaHash = password_hash($senhaInicial, PASSWORD_DEFAULT);

        $stmtUsuario = $pdo->prepare("
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

        $stmtUsuario->execute([
            ':nome' => $nome,
            ':email' => $emailAutomatico,
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

    return [
        'usuario_id' => $usuarioId,
        'email' => $emailAutomatico,
        'senha_inicial' => $senhaInicial
    ];
}

function voltarPacienteComErro($id, $erro)
{
    $url = $id > 0 ? "paciente_form.php?id={$id}&erro={$erro}" : "paciente_form.php?erro={$erro}";
    header("Location: {$url}");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pacientes.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if (!validarTokenCsrf($_POST['csrf_token'] ?? '')) {
    voltarPacienteComErro($id, 'csrf');
}

$nome = trim($_POST['nome'] ?? '');
$cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$dataNascimento = trim($_POST['data_nascimento'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$email = trim($_POST['email'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');
$contatoEmergencia = trim($_POST['contato_emergencia'] ?? '');
$telefoneEmergencia = trim($_POST['telefone_emergencia'] ?? '');
$alergias = trim($_POST['alergias'] ?? '');
$observacoes = trim($_POST['observacoes'] ?? '');
$consentimentoLgpd = isset($_POST['consentimento_lgpd']) ? 1 : 0;

if ($nome === '') {
    $url = $id > 0 ? "paciente_form.php?id={$id}&erro=nome" : "paciente_form.php?erro=nome";
    header("Location: {$url}");
    exit;
}

if ($dataNascimento === '') {
    voltarPacienteComErro($id, 'nascimento_obrigatorio');
}

if ($email === '') {
    $email = null;
}

if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    voltarPacienteComErro($id, 'email');
}

$cpfNumeros = somenteNumerosPaciente($cpf);

if ($cpfNumeros === '') {
    voltarPacienteComErro($id, 'cpf_obrigatorio');
}

if (!validarCpfPaciente($cpfNumeros)) {
    voltarPacienteComErro($id, 'cpf_invalido');
}

$stmtCpf = $pdo->prepare("
    SELECT id
    FROM pacientes
    WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = :cpf_numeros
    AND ativo = 1
    AND id <> :id
    LIMIT 1
");

$stmtCpf->execute([
    ':cpf_numeros' => $cpfNumeros,
    ':id' => $id
]);

if ($stmtCpf->fetch()) {
    voltarPacienteComErro($id, 'cpf_duplicado');
}

$cpf = formatarCpfPaciente($cpfNumeros);

if ($id > 0) {
    $stmt = $pdo->prepare("
        UPDATE pacientes
        SET
            nome = :nome,
            cpf = :cpf,
            data_nascimento = :data_nascimento,
            telefone = :telefone,
            email = :email,
            endereco = :endereco,
            contato_emergencia = :contato_emergencia,
            telefone_emergencia = :telefone_emergencia,
            alergias = :alergias,
            observacoes = :observacoes,
            consentimento_lgpd = :consentimento_lgpd,
            atualizado_em = NOW()
        WHERE id = :id
        AND ativo = 1
    ");

    $stmt->execute([
        ':nome' => $nome,
        ':cpf' => $cpf,
        ':data_nascimento' => $dataNascimento,
        ':telefone' => $telefone,
        ':email' => $email,
        ':endereco' => $endereco,
        ':contato_emergencia' => $contatoEmergencia,
        ':telefone_emergencia' => $telefoneEmergencia,
        ':alergias' => $alergias,
        ':observacoes' => $observacoes,
        ':consentimento_lgpd' => $consentimentoLgpd,
        ':id' => $id
    ]);

    registrarAuditoria(
        $pdo,
        $_SESSION['usuario_id'],
        'PACIENTE_ATUALIZADO',
        'pacientes',
        $id,
        'Paciente atualizado: ' . $nome
    );

    header('Location: pacientes.php?msg=atualizado');
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO pacientes
    (
        nome,
        cpf,
        data_nascimento,
        telefone,
        email,
        endereco,
        contato_emergencia,
        telefone_emergencia,
        alergias,
        observacoes,
        consentimento_lgpd,
        ativo
    )
    VALUES
    (
        :nome,
        :cpf,
        :data_nascimento,
        :telefone,
        :email,
        :endereco,
        :contato_emergencia,
        :telefone_emergencia,
        :alergias,
        :observacoes,
        :consentimento_lgpd,
        1
    )
");

$stmt->execute([
    ':nome' => $nome,
    ':cpf' => $cpf,
    ':data_nascimento' => $dataNascimento,
    ':telefone' => $telefone,
    ':email' => $email,
    ':endereco' => $endereco,
    ':contato_emergencia' => $contatoEmergencia,
    ':telefone_emergencia' => $telefoneEmergencia,
    ':alergias' => $alergias,
    ':observacoes' => $observacoes,
    ':consentimento_lgpd' => $consentimentoLgpd
]);

$novoId = $pdo->lastInsertId();

$usuarioPaciente = criarUsuarioPacienteAutomatico(
    $pdo,
    $novoId,
    $nome,
    $cpf,
    $dataNascimento
);

registrarAuditoria(
    $pdo,
    $_SESSION['usuario_id'],
    'PACIENTE_CRIADO',
    'pacientes',
    $novoId,
    'Paciente cadastrado pelo administrador: ' . $nome
);

if ($usuarioPaciente) {
    registrarAuditoria(
        $pdo,
        $_SESSION['usuario_id'],
        'USUARIO_PACIENTE_CRIADO_AUTOMATICAMENTE_ADMIN',
        'usuarios',
        $usuarioPaciente['usuario_id'],
        'Usuário criado automaticamente pelo administrador para o paciente. Login: ' . $usuarioPaciente['email']
    );

    header(
        'Location: pacientes.php?msg=criado_usuario&login=' . urlencode($usuarioPaciente['email']) .
        '&senha=' . urlencode($usuarioPaciente['senha_inicial'])
    );
    exit;
}

header('Location: pacientes.php?msg=criado');
exit;