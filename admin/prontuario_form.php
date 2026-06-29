<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$erro = $_GET['erro'] ?? '';

$prontuario = [
    'id' => '',
    'paciente_id' => '',
    'profissional_id' => '',
    'consulta_id' => '',
    'descricao' => '',
    'diagnostico' => '',
    'conduta' => ''
];

if ($id > 0) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM prontuarios
        WHERE id = :id
        AND ativo = 1
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $prontuarioEncontrado = $stmt->fetch();

    if (!$prontuarioEncontrado) {
        header('Location: prontuarios.php');
        exit;
    }

    $prontuario = $prontuarioEncontrado;
}

$stmtPacientes = $pdo->query("
    SELECT id, nome, cpf
    FROM pacientes
    WHERE ativo = 1
    ORDER BY nome ASC
");

$pacientes = $stmtPacientes->fetchAll();

$stmtProfissionais = $pdo->query("
    SELECT id, nome, especialidade, tipo
    FROM profissionais
    WHERE ativo = 1
    ORDER BY nome ASC
");

$profissionais = $stmtProfissionais->fetchAll();

$stmtConsultas = $pdo->query("
    SELECT 
        c.id,
        c.data_consulta,
        c.tipo,
        c.status,
        p.nome AS paciente_nome,
        pr.nome AS profissional_nome
    FROM consultas c
    INNER JOIN pacientes p ON p.id = c.paciente_id
    INNER JOIN profissionais pr ON pr.id = c.profissional_id
    WHERE c.status <> 'Cancelada'
    AND p.ativo = 1
    AND pr.ativo = 1
    ORDER BY c.data_consulta DESC
");

$consultas = $stmtConsultas->fetchAll();

$pageTitle = $id > 0 ? 'Editar Prontuário' : 'Novo Prontuário';
$pageSubtitle = 'Registro clínico do atendimento do paciente';
$menuAtivo = 'prontuarios';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2><?= $id > 0 ? 'Editar Prontuário' : 'Cadastrar Prontuário' ?></h2>
        <p>Registre descrição do atendimento, diagnóstico e conduta clínica.</p>
    </div>

    <a href="<?= BASE_URL ?>admin/prontuarios.php" class="btn btn-light">
        Voltar
    </a>
</section>

<?php if ($erro === 'paciente'): ?>
    <div class="alert-error">Selecione o paciente.</div>
<?php elseif ($erro === 'profissional'): ?>
    <div class="alert-error">Selecione o profissional de saúde.</div>
<?php elseif ($erro === 'descricao'): ?>
    <div class="alert-error">Informe a descrição do atendimento.</div>
<?php elseif ($erro === 'consulta'): ?>
    <div class="alert-error">Consulta inválida ou cancelada.</div>
<?php elseif ($erro === 'csrf'): ?>
    <div class="alert-error">Sessão expirada. Atualize a página e tente novamente.</div>
<?php endif; ?>

<?php if (empty($pacientes) || empty($profissionais)): ?>
    <div class="alert-error">
        Para cadastrar um prontuário, é necessário ter pelo menos um paciente e um profissional cadastrados.
    </div>
<?php endif; ?>

<section class="panel">
    <form method="post" action="<?= BASE_URL ?>admin/prontuario_salvar.php" class="form-card">
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <input type="hidden" name="id" value="<?= e($prontuario['id']) ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="paciente_id">Paciente</label>
                <select id="paciente_id" name="paciente_id" required>
                    <option value="">Selecione o paciente</option>

                    <?php foreach ($pacientes as $paciente): ?>
                        <option 
                            value="<?= (int)$paciente['id'] ?>"
                            <?= (int)$prontuario['paciente_id'] === (int)$paciente['id'] ? 'selected' : '' ?>
                        >
                            <?= e($paciente['nome']) ?>
                            <?= !empty($paciente['cpf']) ? ' - CPF: ' . e($paciente['cpf']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="profissional_id">Profissional</label>
                <select id="profissional_id" name="profissional_id" required>
                    <option value="">Selecione o profissional</option>

                    <?php foreach ($profissionais as $profissional): ?>
                        <option 
                            value="<?= (int)$profissional['id'] ?>"
                            <?= (int)$prontuario['profissional_id'] === (int)$profissional['id'] ? 'selected' : '' ?>
                        >
                            <?= e($profissional['nome']) ?> - <?= e($profissional['tipo']) ?>
                            <?= !empty($profissional['especialidade']) ? ' / ' . e($profissional['especialidade']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group form-group-full">
                <label for="consulta_id">Consulta vinculada</label>
                <select id="consulta_id" name="consulta_id">
                    <option value="">Sem consulta vinculada</option>

                    <?php foreach ($consultas as $consulta): ?>
                        <option 
                            value="<?= (int)$consulta['id'] ?>"
                            <?= (int)$prontuario['consulta_id'] === (int)$consulta['id'] ? 'selected' : '' ?>
                        >
                            <?= date('d/m/Y H:i', strtotime($consulta['data_consulta'])) ?>
                            - <?= e($consulta['paciente_nome']) ?>
                            com <?= e($consulta['profissional_nome']) ?>
                            / <?= e($consulta['tipo']) ?>
                            / <?= e($consulta['status']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <small class="form-help">
                    Opcional. Use para relacionar este prontuário a uma consulta agendada no sistema.
                </small>
            </div>

            <div class="form-group form-group-full">
                <label for="descricao">Descrição do atendimento</label>
                <textarea 
                    id="descricao" 
                    name="descricao" 
                    rows="5"
                    placeholder="Descreva o atendimento realizado, queixas relatadas e observações clínicas"
                    required
                ><?= e($prontuario['descricao']) ?></textarea>
            </div>

            <div class="form-group form-group-full">
                <label for="diagnostico">Diagnóstico</label>
                <textarea 
                    id="diagnostico" 
                    name="diagnostico" 
                    rows="4"
                    placeholder="Informe o diagnóstico, se houver"
                ><?= e($prontuario['diagnostico']) ?></textarea>
            </div>

            <div class="form-group form-group-full">
                <label for="conduta">Conduta</label>
                <textarea 
                    id="conduta" 
                    name="conduta" 
                    rows="4"
                    placeholder="Informe a conduta, orientações, solicitações ou encaminhamentos"
                ><?= e($prontuario['conduta']) ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>admin/prontuarios.php" class="btn btn-light">
                Cancelar
            </a>

            <button 
                type="submit" 
                class="btn btn-primary-small"
                <?= empty($pacientes) || empty($profissionais) ? 'disabled' : '' ?>
            >
                Salvar Prontuário
            </button>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>