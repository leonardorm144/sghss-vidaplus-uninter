<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$erro = $_GET['erro'] ?? '';

$prescricao = [
    'id' => '',
    'paciente_id' => '',
    'profissional_id' => '',
    'consulta_id' => '',
    'medicamento' => '',
    'dosagem' => '',
    'orientacoes' => ''
];

if ($id > 0) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM prescricoes
        WHERE id = :id
        AND ativo = 1
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $prescricaoEncontrada = $stmt->fetch();

    if (!$prescricaoEncontrada) {
        header('Location: prescricoes.php');
        exit;
    }

    $prescricao = $prescricaoEncontrada;
}

$stmtPacientes = $pdo->query("
    SELECT id, nome, cpf
    FROM pacientes
    WHERE ativo = 1
    ORDER BY nome ASC
");

$pacientes = $stmtPacientes->fetchAll();

$stmtProfissionais = $pdo->query("
    SELECT id, nome, especialidade, tipo, registro_profissional
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

$pageTitle = $id > 0 ? 'Editar Prescrição' : 'Nova Prescrição';
$pageSubtitle = 'Emissão de receita digital vinculada ao atendimento';
$menuAtivo = 'prescricoes';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2><?= $id > 0 ? 'Editar Prescrição' : 'Emitir Prescrição' ?></h2>
        <p>Informe medicamento, dosagem e orientações para o paciente.</p>
    </div>

    <a href="<?= BASE_URL ?>admin/prescricoes.php" class="btn btn-light">
        Voltar
    </a>
</section>

<?php if ($erro === 'paciente'): ?>
    <div class="alert-error">Selecione o paciente.</div>
<?php elseif ($erro === 'profissional'): ?>
    <div class="alert-error">Selecione o profissional de saúde.</div>
<?php elseif ($erro === 'medicamento'): ?>
    <div class="alert-error">Informe o medicamento.</div>
<?php elseif ($erro === 'consulta'): ?>
    <div class="alert-error">Consulta inválida ou cancelada.</div>
<?php elseif ($erro === 'csrf'): ?>
    <div class="alert-error">Sessão expirada. Atualize a página e tente novamente.</div>
<?php endif; ?>

<?php if (empty($pacientes) || empty($profissionais)): ?>
    <div class="alert-error">
        Para emitir uma prescrição, é necessário ter pelo menos um paciente e um profissional cadastrados.
    </div>
<?php endif; ?>

<section class="panel">
    <form method="post" action="<?= BASE_URL ?>admin/prescricao_salvar.php" class="form-card">
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <input type="hidden" name="id" value="<?= e($prescricao['id']) ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="paciente_id">Paciente</label>
                <select id="paciente_id" name="paciente_id" required>
                    <option value="">Selecione o paciente</option>

                    <?php foreach ($pacientes as $paciente): ?>
                        <option 
                            value="<?= (int)$paciente['id'] ?>"
                            <?= (int)$prescricao['paciente_id'] === (int)$paciente['id'] ? 'selected' : '' ?>
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
                            <?= (int)$prescricao['profissional_id'] === (int)$profissional['id'] ? 'selected' : '' ?>
                        >
                            <?= e($profissional['nome']) ?> - <?= e($profissional['tipo']) ?>
                            <?= !empty($profissional['especialidade']) ? ' / ' . e($profissional['especialidade']) : '' ?>
                            <?= !empty($profissional['registro_profissional']) ? ' / ' . e($profissional['registro_profissional']) : '' ?>
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
                            <?= (int)$prescricao['consulta_id'] === (int)$consulta['id'] ? 'selected' : '' ?>
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
                    Opcional. Use para relacionar esta prescrição a uma consulta cadastrada no sistema.
                </small>
            </div>

            <div class="form-group">
                <label for="medicamento">Medicamento</label>
                <input 
                    type="text" 
                    id="medicamento" 
                    name="medicamento" 
                    value="<?= e($prescricao['medicamento']) ?>"
                    placeholder="Ex: Dipirona 500mg"
                    required
                >
            </div>

            <div class="form-group">
                <label for="dosagem">Dosagem</label>
                <input 
                    type="text" 
                    id="dosagem" 
                    name="dosagem" 
                    value="<?= e($prescricao['dosagem']) ?>"
                    placeholder="Ex: 1 comprimido de 8 em 8 horas"
                >
            </div>

            <div class="form-group form-group-full">
                <label for="orientacoes">Orientações</label>
                <textarea 
                    id="orientacoes" 
                    name="orientacoes" 
                    rows="5"
                    placeholder="Informe orientações de uso, duração do tratamento e cuidados adicionais"
                ><?= e($prescricao['orientacoes']) ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>admin/prescricoes.php" class="btn btn-light">
                Cancelar
            </a>

            <button 
                type="submit" 
                class="btn btn-primary-small"
                <?= empty($pacientes) || empty($profissionais) ? 'disabled' : '' ?>
            >
                Salvar Prescrição
            </button>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>