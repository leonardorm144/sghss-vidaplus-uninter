<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$erro = $_GET['erro'] ?? '';

$consulta = [
    'id' => '',
    'paciente_id' => '',
    'profissional_id' => '',
    'unidade_id' => '',
    'tipo' => 'Presencial',
    'status' => 'Agendada',
    'data_consulta' => '',
    'motivo' => '',
    'link_teleconsulta' => ''
];

if ($id > 0) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM consultas
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $consultaEncontrada = $stmt->fetch();

    if (!$consultaEncontrada || in_array($consultaEncontrada['status'], ['Cancelada', 'Concluida'])) {
        header('Location: consultas.php');
        exit;
    }

    $consulta = $consultaEncontrada;
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

$stmtUnidades = $pdo->query("
    SELECT id, nome, tipo
    FROM unidades
    WHERE ativo = 1
    ORDER BY nome ASC
");

$unidades = $stmtUnidades->fetchAll();

$pageTitle = $id > 0 ? 'Editar Consulta' : 'Nova Consulta';
$pageSubtitle = 'Agende consultas presenciais ou por telemedicina';
$menuAtivo = 'consultas';

require_once __DIR__ . '/../includes/header.php';

$dataConsultaValor = '';

if (!empty($consulta['data_consulta'])) {
    $dataConsultaValor = date('Y-m-d\TH:i', strtotime($consulta['data_consulta']));
}
?>

<section class="page-actions">
    <div>
        <h2><?= $id > 0 ? 'Editar Consulta' : 'Agendar Consulta' ?></h2>
        <p>Selecione paciente, profissional, unidade, tipo e horário da consulta.</p>
    </div>

    <a href="<?= BASE_URL ?>admin/consultas.php" class="btn btn-light">
        Voltar
    </a>
</section>

<?php if ($erro === 'paciente'): ?>
    <div class="alert-error">Selecione o paciente.</div>
<?php elseif ($erro === 'profissional'): ?>
    <div class="alert-error">Selecione o profissional de saúde.</div>
<?php elseif ($erro === 'unidade'): ?>
    <div class="alert-error">Selecione a unidade de atendimento.</div>
<?php elseif ($erro === 'data'): ?>
    <div class="alert-error">Informe a data e hora da consulta.</div>
<?php elseif ($erro === 'tipo'): ?>
    <div class="alert-error">Tipo de consulta inválido.</div>
<?php elseif ($erro === 'status'): ?>
    <div class="alert-error">Status da consulta inválido.</div>
<?php elseif ($erro === 'csrf'): ?>
    <div class="alert-error">Sessão expirada. Atualize a página e tente novamente.</div>
<?php endif; ?>

<?php if (empty($pacientes) || empty($profissionais) || empty($unidades)): ?>
    <div class="alert-error">
        Para agendar uma consulta, é necessário ter pelo menos um paciente, um profissional e uma unidade cadastrados.
    </div>
<?php endif; ?>

<section class="panel">
    <form method="post" action="<?= BASE_URL ?>admin/consulta_salvar.php" class="form-card">
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <input type="hidden" name="id" value="<?= e($consulta['id']) ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="paciente_id">Paciente</label>
                <select id="paciente_id" name="paciente_id" required>
                    <option value="">Selecione o paciente</option>

                    <?php foreach ($pacientes as $paciente): ?>
                        <option 
                            value="<?= (int)$paciente['id'] ?>"
                            <?= (int)$consulta['paciente_id'] === (int)$paciente['id'] ? 'selected' : '' ?>
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
                            <?= (int)$consulta['profissional_id'] === (int)$profissional['id'] ? 'selected' : '' ?>
                        >
                            <?= e($profissional['nome']) ?> - <?= e($profissional['tipo']) ?>
                            <?= !empty($profissional['especialidade']) ? ' / ' . e($profissional['especialidade']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="unidade_id">Unidade</label>
                <select id="unidade_id" name="unidade_id" required>
                    <option value="">Selecione a unidade</option>

                    <?php foreach ($unidades as $unidade): ?>
                        <option 
                            value="<?= (int)$unidade['id'] ?>"
                            <?= (int)$consulta['unidade_id'] === (int)$unidade['id'] ? 'selected' : '' ?>
                        >
                            <?= e($unidade['nome']) ?> - <?= e($unidade['tipo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="data_consulta">Data e hora</label>
                <input 
                    type="datetime-local" 
                    id="data_consulta" 
                    name="data_consulta" 
                    value="<?= e($dataConsultaValor) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="tipo">Tipo</label>
                <select id="tipo" name="tipo" required>
                    <option value="Presencial" <?= $consulta['tipo'] === 'Presencial' ? 'selected' : '' ?>>
                        Presencial
                    </option>

                    <option value="Telemedicina" <?= $consulta['tipo'] === 'Telemedicina' ? 'selected' : '' ?>>
                        Telemedicina
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="Agendada" <?= $consulta['status'] === 'Agendada' ? 'selected' : '' ?>>
                        Agendada
                    </option>

                    <option value="Confirmada" <?= $consulta['status'] === 'Confirmada' ? 'selected' : '' ?>>
                        Confirmada
                    </option>
                </select>
            </div>

            <div class="form-group form-group-full">
                <label for="motivo">Motivo da consulta</label>
                <textarea 
                    id="motivo" 
                    name="motivo" 
                    rows="4"
                    placeholder="Descreva o motivo, sintomas ou observações iniciais"
                ><?= e($consulta['motivo']) ?></textarea>
            </div>

            <?php if ($id > 0 && $consulta['tipo'] === 'Telemedicina' && !empty($consulta['link_teleconsulta'])): ?>
                <div class="form-group form-group-full">
                    <label>Link da teleconsulta</label>
                    <a href="<?= e($consulta['link_teleconsulta']) ?>" target="_blank" class="btn btn-secondary">
                        Abrir sala da teleconsulta
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>admin/consultas.php" class="btn btn-light">
                Cancelar
            </a>

            <button 
                type="submit" 
                class="btn btn-primary-small"
                <?= empty($pacientes) || empty($profissionais) || empty($unidades) ? 'disabled' : '' ?>
            >
                Salvar Consulta
            </button>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>