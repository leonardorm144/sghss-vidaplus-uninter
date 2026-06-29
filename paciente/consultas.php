<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('paciente');

$pageTitle = 'Minhas Consultas';
$pageSubtitle = 'Acompanhamento das consultas agendadas';
$menuAtivo = 'minhas_consultas';

$usuarioId = $_SESSION['usuario_id'] ?? 0;

$stmtPaciente = $pdo->prepare("
    SELECT *
    FROM pacientes
    WHERE usuario_id = :usuario_id
    AND ativo = 1
    LIMIT 1
");

$stmtPaciente->execute([
    ':usuario_id' => $usuarioId
]);

$paciente = $stmtPaciente->fetch();

$statusFiltro = trim($_GET['status'] ?? '');
$dataInicio = trim($_GET['data_inicio'] ?? date('Y-m-01'));
$dataFim = trim($_GET['data_fim'] ?? date('Y-m-d', strtotime('+30 days')));
$msg = $_GET['msg'] ?? '';

$consultas = [];

if ($paciente) {
    $where = [
        "c.paciente_id = :paciente_id",
        "DATE(c.data_consulta) BETWEEN :data_inicio AND :data_fim"
    ];

    $params = [
        ':paciente_id' => $paciente['id'],
        ':data_inicio' => $dataInicio,
        ':data_fim' => $dataFim
    ];

    if ($statusFiltro !== '') {
        $where[] = "c.status = :status";
        $params[':status'] = $statusFiltro;
    }

    $whereSql = implode(' AND ', $where);

    $stmtConsultas = $pdo->prepare("
        SELECT 
            c.*,
            pr.nome AS profissional_nome,
            pr.especialidade AS profissional_especialidade,
            pr.registro_profissional,
            u.nome AS unidade_nome,
            u.tipo AS unidade_tipo
        FROM consultas c
        INNER JOIN profissionais pr ON pr.id = c.profissional_id
        LEFT JOIN unidades u ON u.id = c.unidade_id
        WHERE {$whereSql}
        ORDER BY c.data_consulta ASC
    ");

    $stmtConsultas->execute($params);
    $consultas = $stmtConsultas->fetchAll();
}

function labelStatusPacienteConsulta($status)
{
    switch ($status) {
        case 'Agendada':
            return 'Agendada';
        case 'Confirmada':
            return 'Confirmada';
        case 'Cancelada':
            return 'Cancelada';
        case 'Concluida':
            return 'Concluída';
        default:
            return $status;
    }
}

function classeStatusPacienteConsulta($status)
{
    switch ($status) {
        case 'Agendada':
            return 'badge-info';
        case 'Confirmada':
            return 'badge-success';
        case 'Cancelada':
            return 'badge-danger';
        case 'Concluida':
            return 'badge-neutral';
        default:
            return 'badge-warning';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!$paciente): ?>
    <section class="panel">
        <h2>Paciente não vinculado</h2>
        <p>
            Seu usuário ainda não está vinculado a um cadastro de paciente.
            Peça para o administrador acessar <strong>Usuários</strong> e vincular este login ao paciente correto.
        </p>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    <?php exit; ?>
<?php endif; ?>

<section class="page-actions">
    <div>
        <h2>Consultas de <?= e($paciente['nome']) ?></h2>
        <p>Acompanhe seus agendamentos presenciais e online.</p>
    </div>
</section>

<?php if ($msg === 'cancelada'): ?>
    <div class="alert-success">Consulta cancelada com sucesso.</div>
<?php endif; ?>

<section class="panel">
    <form method="get" class="agenda-filter-form">
        <div class="agenda-filter-grid">
            <div class="form-group">
                <label for="data_inicio">Data inicial</label>
                <input 
                    type="date" 
                    id="data_inicio" 
                    name="data_inicio" 
                    value="<?= e($dataInicio) ?>"
                >
            </div>

            <div class="form-group">
                <label for="data_fim">Data final</label>
                <input 
                    type="date" 
                    id="data_fim" 
                    name="data_fim" 
                    value="<?= e($dataFim) ?>"
                >
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Todos</option>
                    <option value="Agendada" <?= $statusFiltro === 'Agendada' ? 'selected' : '' ?>>Agendada</option>
                    <option value="Confirmada" <?= $statusFiltro === 'Confirmada' ? 'selected' : '' ?>>Confirmada</option>
                    <option value="Cancelada" <?= $statusFiltro === 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
                    <option value="Concluida" <?= $statusFiltro === 'Concluida' ? 'selected' : '' ?>>Concluída</option>
                </select>
            </div>
        </div>

        <div class="form-actions agenda-actions">
            <a href="<?= BASE_URL ?>paciente/consultas.php" class="btn btn-light">
                Limpar
            </a>

            <button type="submit" class="btn btn-secondary">
                Filtrar
            </button>
        </div>
    </form>
</section>

<section class="panel">
    <div class="audit-summary">
        <div>
            <h2>Consultas Encontradas</h2>
            <p>Exibindo apenas consultas vinculadas ao paciente logado.</p>
        </div>

        <span class="audit-count">
            <?= count($consultas) ?>
        </span>
    </div>

    <div class="agenda-list">
        <?php if (empty($consultas)): ?>
            <div class="empty-state agenda-empty">
                Nenhuma consulta encontrada para o período selecionado.
            </div>
        <?php endif; ?>

        <?php foreach ($consultas as $consulta): ?>
            <article class="agenda-card">
                <div class="agenda-card-header">
                    <div>
                        <span class="agenda-date">
                            <?= date('d/m/Y H:i', strtotime($consulta['data_consulta'])) ?>
                        </span>

                        <h3><?= e($consulta['profissional_nome']) ?></h3>

                        <?php if (!empty($consulta['profissional_especialidade'])): ?>
                            <p>Especialidade: <?= e($consulta['profissional_especialidade']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($consulta['registro_profissional'])): ?>
                            <p>Registro: <?= e($consulta['registro_profissional']) ?></p>
                        <?php endif; ?>
                    </div>

                    <span class="badge <?= classeStatusPacienteConsulta($consulta['status']) ?>">
                        <?= e(labelStatusPacienteConsulta($consulta['status'])) ?>
                    </span>
                </div>

                <div class="agenda-info-grid">
                    <div>
                        <span>Tipo</span>
                        <strong><?= e($consulta['tipo']) ?></strong>
                    </div>

                    <div>
                        <span>Unidade</span>
                        <strong><?= e($consulta['unidade_nome'] ?: '-') ?></strong>
                    </div>

                    <div>
                        <span>Motivo</span>
                        <strong><?= e($consulta['motivo'] ?: 'Não informado') ?></strong>
                    </div>
                </div>

                <div class="agenda-card-actions">
                    <?php if ($consulta['tipo'] === 'Telemedicina' && !empty($consulta['link_teleconsulta']) && $consulta['status'] !== 'Cancelada'): ?>
                        <a 
                            href="<?= e($consulta['link_teleconsulta']) ?>" 
                            target="_blank" 
                            class="btn btn-secondary"
                        >
                            Acessar Teleconsulta
                        </a>
                    <?php endif; ?>

                    <?php if ($consulta['status'] === 'Agendada' || $consulta['status'] === 'Confirmada'): ?>
                        <form 
                            method="post" 
                            action="<?= BASE_URL ?>paciente/consulta_cancelar.php" 
                            class="inline-form"
                            data-confirm-title="Cancelar consulta"
                            data-confirm="Deseja realmente cancelar sua consulta com <?= e($consulta['profissional_nome']) ?>? Esta ação alterará o status do agendamento para cancelado."
                            data-confirm-button="Sim, cancelar"
                            data-confirm-cancel="Voltar"
                            data-confirm-type="danger"
                        >
                            <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                            <input type="hidden" name="id" value="<?= (int)$consulta['id'] ?>">

                            <button type="submit" class="btn btn-danger">
                                Cancelar Consulta
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>