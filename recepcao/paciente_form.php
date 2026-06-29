<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('recepcao');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$erro = $_GET['erro'] ?? '';

$paciente = [
    'id' => '',
    'nome' => '',
    'cpf' => '',
    'data_nascimento' => '',
    'sexo' => '',
    'telefone' => '',
    'email' => '',
    'endereco' => '',
    'consentimento_lgpd' => 0
];

if ($id > 0) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM pacientes
        WHERE id = :id
        AND ativo = 1
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $pacienteEncontrado = $stmt->fetch();

    if (!$pacienteEncontrado) {
        header('Location: pacientes.php');
        exit;
    }

    $paciente = $pacienteEncontrado;
}

$pageTitle = $id > 0 ? 'Editar Paciente' : 'Novo Paciente';
$pageSubtitle = 'Cadastro do paciente pela recepção';
$menuAtivo = 'pacientes';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2><?= $id > 0 ? 'Editar Paciente' : 'Cadastrar Paciente' ?></h2>
        <p>Informe os dados pessoais e de contato do paciente.</p>
    </div>

    <a href="<?= BASE_URL ?>recepcao/pacientes.php" class="btn btn-light">
        Voltar
    </a>
</section>

<?php if ($erro === 'nome'): ?>
    <div class="alert-error">Informe o nome do paciente.</div>
<?php elseif ($erro === 'cpf_obrigatorio'): ?>
    <div class="alert-error">Informe o CPF do paciente.</div>
<?php elseif ($erro === 'cpf_invalido'): ?>
    <div class="alert-error">CPF inválido. Verifique os números digitados.</div>
<?php elseif ($erro === 'cpf_duplicado'): ?>
    <div class="alert-error">Já existe um paciente ativo cadastrado com este CPF.</div>
<?php elseif ($erro === 'nascimento_obrigatorio'): ?>
    <div class="alert-error">Informe a data de nascimento do paciente.</div>
<?php elseif ($erro === 'email'): ?>
    <div class="alert-error">Informe um e-mail válido.</div>
<?php elseif ($erro === 'csrf'): ?>
    <div class="alert-error">Sessão expirada. Atualize a página e tente novamente.</div>
<?php endif; ?>

<section class="panel">
    <form method="post" action="<?= BASE_URL ?>recepcao/paciente_salvar.php" class="form-card">
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <input type="hidden" name="id" value="<?= e($paciente['id']) ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="nome">Nome completo</label>
                <input 
                    type="text" 
                    id="nome" 
                    name="nome" 
                    value="<?= e($paciente['nome']) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="cpf">CPF</label>
                <input 
                    type="text"
                    id="cpf"
                    name="cpf"
                    value="<?= e($paciente['cpf']) ?>"
                    placeholder="000.000.000-00"
                    maxlength="14"
                    inputmode="numeric"
                    required
                >
            </div>

            <div class="form-group">
                <label for="data_nascimento">Data de nascimento</label>
                <input 
                    type="date" 
                    id="data_nascimento" 
                    name="data_nascimento" 
                    value="<?= e($paciente['data_nascimento']) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="sexo">Sexo</label>
                <select id="sexo" name="sexo">
                    <option value="">Não informado</option>
                    <option value="Masculino" <?= $paciente['sexo'] === 'Masculino' ? 'selected' : '' ?>>
                        Masculino
                    </option>
                    <option value="Feminino" <?= $paciente['sexo'] === 'Feminino' ? 'selected' : '' ?>>
                        Feminino
                    </option>
                    <option value="Outro" <?= $paciente['sexo'] === 'Outro' ? 'selected' : '' ?>>
                        Outro
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="telefone">Telefone</label>
                <input 
                    type="text"
                    id="telefone"
                    name="telefone"
                    value="<?= e($paciente['telefone']) ?>"
                    placeholder="(00) 00000-0000"
                    maxlength="15"
                    inputmode="numeric"
                >
            </div>

            <div class="form-group">
                <label for="email">E-mail</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="<?= e($paciente['email']) ?>"
                >
            </div>

            <div class="form-group form-group-full">
                <label for="endereco">Endereço</label>
                <textarea 
                    id="endereco" 
                    name="endereco" 
                    rows="3"
                    placeholder="Informe endereço, número, bairro, cidade e estado"
                ><?= e($paciente['endereco']) ?></textarea>
            </div>

            <div class="form-group form-group-full">
                <label class="checkbox-label">
                    <input 
                        type="checkbox" 
                        name="consentimento_lgpd" 
                        value="1"
                        <?= (int)$paciente['consentimento_lgpd'] === 1 ? 'checked' : '' ?>
                    >
                    Paciente autorizou o uso dos dados para fins de atendimento, conforme LGPD.
                </label>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>recepcao/pacientes.php" class="btn btn-light">
                Cancelar
            </a>

            <button type="submit" class="btn btn-primary-small">
                Salvar Paciente
            </button>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>