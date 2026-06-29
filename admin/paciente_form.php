<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$erro = $_GET['erro'] ?? '';

$paciente = [
    'id' => '',
    'nome' => '',
    'cpf' => '',
    'data_nascimento' => '',
    'telefone' => '',
    'email' => '',
    'endereco' => '',
    'contato_emergencia' => '',
    'telefone_emergencia' => '',
    'alergias' => '',
    'observacoes' => '',
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
$pageSubtitle = 'Preencha os dados cadastrais e informações clínicas básicas';
$menuAtivo = 'pacientes';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2><?= $id > 0 ? 'Editar Paciente' : 'Cadastrar Paciente' ?></h2>
        <p>Os dados abaixo serão utilizados no atendimento e histórico clínico.</p>
    </div>

    <a href="<?= BASE_URL ?>admin/pacientes.php" class="btn btn-light">
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
    <form method="post" action="<?= BASE_URL ?>admin/paciente_salvar.php" class="form-card">
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

            <div class="form-group">
                <label for="contato_emergencia">Contato de emergência</label>
                <input 
                    type="text" 
                    id="contato_emergencia" 
                    name="contato_emergencia" 
                    value="<?= e($paciente['contato_emergencia']) ?>"
                >
            </div>

            <div class="form-group">
                <label for="telefone_emergencia">Telefone de emergência</label>
                <input 
                    type="text"
                    id="telefone_emergencia"
                    name="telefone_emergencia"
                    value="<?= e($paciente['telefone_emergencia']) ?>"
                    placeholder="(00) 00000-0000"
                    maxlength="15"
                    inputmode="numeric"
                >
            </div>

            <div class="form-group form-group-full">
                <label for="endereco">Endereço</label>
                <input 
                    type="text" 
                    id="endereco" 
                    name="endereco" 
                    value="<?= e($paciente['endereco']) ?>"
                >
            </div>

            <div class="form-group form-group-full">
                <label for="alergias">Alergias</label>
                <textarea 
                    id="alergias" 
                    name="alergias" 
                    rows="3"
                    placeholder="Informe alergias conhecidas, se houver"
                ><?= e($paciente['alergias']) ?></textarea>
            </div>

            <div class="form-group form-group-full">
                <label for="observacoes">Observações</label>
                <textarea 
                    id="observacoes" 
                    name="observacoes" 
                    rows="4"
                    placeholder="Observações gerais sobre o paciente"
                ><?= e($paciente['observacoes']) ?></textarea>
            </div>

            <div class="form-group form-group-full">
                <label class="checkbox-label">
                    <input 
                        type="checkbox" 
                        name="consentimento_lgpd" 
                        value="1"
                        <?= (int)$paciente['consentimento_lgpd'] === 1 ? 'checked' : '' ?>
                    >
                    Paciente autorizou o armazenamento e uso dos dados para fins de atendimento, conforme LGPD.
                </label>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>admin/pacientes.php" class="btn btn-light">
                Cancelar
            </a>

            <button type="submit" class="btn btn-primary-small">
                Salvar Paciente
            </button>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>