<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('recepcao');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$erro = $_GET['erro'] ?? '';

$consulta = [
    'id' => '',
    'paciente_id' => '',
    'profissional_id' => '',
    'unidade_id' => '',
    'data_consulta' => '',
    'tipo' => 'Presencial',
    'status' => 'Agendada',
    'motivo' => ''
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

    if (!$consultaEncontrada) {
        header('Location: agendamentos.php');
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
    SELECT id, nome, especialidade, registro_profissional
    FROM profissionais
    WHERE ativo = 1
    ORDER BY nome ASC
");

$profissionais = $stmtProfissionais->fetchAll();

$stmtUnidades = $pdo->query("
    SELECT id, nome, tipo, cidade, estado
    FROM unidades
    WHERE ativo = 1
    ORDER BY nome ASC
");

$unidades = $stmtUnidades->fetchAll();

$pageTitle = $id > 0 ? 'Editar Agendamento' : 'Novo Agendamento';
$pageSubtitle = 'Cadastro de consulta presencial ou telemedicina';
$menuAtivo = 'agendamentos';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2><?= $id > 0 ? 'Editar Agendamento' : 'Novo Agendamento' ?></h2>
        <p>Informe paciente, profissional, data, tipo e motivo da consulta.</p>
    </div>

    <a href="<?= BASE_URL ?>recepcao/agendamentos.php" class="btn btn-light">
        Voltar
    </a>
</section>

<?php if ($erro === 'paciente'): ?>
    <div class="alert-error">Selecione um paciente válido.</div>
<?php elseif ($erro === 'profissional'): ?>
    <div class="alert-error">Selecione um profissional válido.</div>
<?php elseif ($erro === 'data'): ?>
    <div class="alert-error">Informe a data e hora da consulta.</div>
<?php elseif ($erro === 'tipo'): ?>
    <div class="alert-error">Tipo de consulta inválido.</div>
<?php elseif ($erro === 'status'): ?>
    <div class="alert-error">Status inválido.</div>
<?php elseif ($erro === 'unidade'): ?>
    <div class="alert-error">Unidade inválida.</div>
<?php elseif ($erro === 'csrf'): ?>
    <div class="alert-error">Sessão expirada. Atualize a página e tente novamente.</div>
<?php endif; ?>

<section class="panel">
    <form method="post" action="<?= BASE_URL ?>recepcao/agendamento_salvar.php" class="form-card">
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <input type="hidden" name="id" value="<?= e($consulta['id']) ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="paciente_id">Paciente</label>
                <select id="paciente_id" name="paciente_id" required>
                    <option value="">Selecione</option>

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
                    <option value="">Selecione</option>

                    <?php foreach ($profissionais as $profissional): ?>
                        <option 
                            value="<?= (int)$profissional['id'] ?>"
                            <?= (int)$consulta['profissional_id'] === (int)$profissional['id'] ? 'selected' : '' ?>
                        >
                            <?= e($profissional['nome']) ?>
                            <?= !empty($profissional['especialidade']) ? ' - ' . e($profissional['especialidade']) : '' ?>
                            <?= !empty($profissional['registro_profissional']) ? ' / ' . e($profissional['registro_profissional']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="unidade_id">Unidade</label>
                <select id="unidade_id" name="unidade_id">
                    <option value="">Sem unidade definida</option>

                    <?php foreach ($unidades as $unidade): ?>
                        <option 
                            value="<?= (int)$unidade['id'] ?>"
                            <?= (int)$consulta['unidade_id'] === (int)$unidade['id'] ? 'selected' : '' ?>
                        >
                            <?= e($unidade['nome']) ?>
                            - <?= e($unidade['tipo']) ?>
                            <?= !empty($unidade['cidade']) ? ' / ' . e($unidade['cidade']) : '' ?>
                            <?= !empty($unidade['estado']) ? ' - ' . e($unidade['estado']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="data_consulta">Data/Hora</label>
                <input 
                    type="datetime-local" 
                    id="data_consulta" 
                    name="data_consulta" 
                    value="<?= !empty($consulta['data_consulta']) ? date('Y-m-d\TH:i', strtotime($consulta['data_consulta'])) : '' ?>"
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

                    <option value="Cancelada" <?= $consulta['status'] === 'Cancelada' ? 'selected' : '' ?>>
                        Cancelada
                    </option>

                    <option value="Concluida" <?= $consulta['status'] === 'Concluida' ? 'selected' : '' ?>>
                        Concluída
                    </option>
                </select>
            </div>

            <div class="form-group form-group-full">
                <label for="motivo">Motivo da consulta</label>
                <textarea 
                    id="motivo" 
                    name="motivo" 
                    rows="4"
                    placeholder="Informe o motivo do atendimento"
                ><?= e($consulta['motivo']) ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>recepcao/agendamentos.php" class="btn btn-light">
                Cancelar
            </a>

            <button type="submit" class="btn btn-primary-small">
                Salvar Agendamento
            </button>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>