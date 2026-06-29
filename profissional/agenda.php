<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('profissional');

$pageTitle = 'Minha Agenda';
$pageSubtitle = 'Consultas vinculadas ao profissional de saúde logado';
$menuAtivo = 'agenda';

$usuarioId = $_SESSION['usuario_id'] ?? 0;

$stmtProfissional = $pdo->prepare("
    SELECT *
    FROM profissionais
    WHERE usuario_id = :usuario_id
    AND ativo = 1
    LIMIT 1
");

$stmtProfissional->execute([
    ':usuario_id' => $usuarioId
]);

$profissional = $stmtProfissional->fetch();

$statusFiltro = trim($_GET['status'] ?? '');
$dataInicio = trim($_GET['data_inicio'] ?? date('Y-m-d'));
$dataFim = trim($_GET['data_fim'] ?? date('Y-m-d'));
$msg = $_GET['msg'] ?? '';

$consultas = [];

if ($profissional) {
    $where = [
        "c.profissional_id = :profissional_id",
        "DATE(c.data_consulta) BETWEEN :data_inicio AND :data_fim"
    ];

    $params = [
        ':profissional_id' => $profissional['id'],
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
            p.nome AS paciente_nome,
            p.cpf AS paciente_cpf,
            p.telefone AS paciente_telefone,
            u.nome AS unidade_nome,
            u.tipo AS unidade_tipo
        FROM consultas c
        INNER JOIN pacientes p ON p.id = c.paciente_id
        LEFT JOIN unidades u ON u.id = c.unidade_id
        WHERE {$whereSql}
        ORDER BY c.data_consulta ASC
    ");

    $stmtConsultas->execute($params);
    $consultas = $stmtConsultas->fetchAll();
}

function labelStatusAgenda($status)
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

function classeStatusAgenda($status)
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

<?php if (!$profissional): ?>
    <section class="panel">
        <h2>Profissional não vinculado</h2>
        <p>
            Seu usuário ainda não está vinculado a um cadastro de profissional de saúde.
            Peça para o administrador acessar <strong>Usuários</strong> e vincular este login ao profissional correto.
        </p>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    <?php exit; ?>
<?php endif; ?>

<section class="page-actions">
    <div>
        <h2>Agenda de <?= e($profissional['nome']) ?></h2>
        <p>
            <?= e($profissional['tipo']) ?>
            <?= !empty($profissional['especialidade']) ? ' - ' . e($profissional['especialidade']) : '' ?>
        </p>
    </div>
</section>

<?php if ($msg === 'confirmada'): ?>
    <div class="alert-success">Consulta confirmada com sucesso.</div>
<?php elseif ($msg === 'concluida'): ?>
    <div class="alert-success">Consulta concluída com sucesso.</div>
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
            <a href="<?= BASE_URL ?>profissional/agenda.php" class="btn btn-light">
                Hoje
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
            <p>Exibindo consultas vinculadas ao profissional logado.</p>
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

                        <h3><?= e($consulta['paciente_nome']) ?></h3>

                        <?php if (!empty($consulta['paciente_cpf'])): ?>
                            <p>CPF: <?= e($consulta['paciente_cpf']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($consulta['paciente_telefone'])): ?>
                            <p>Telefone: <?= e($consulta['paciente_telefone']) ?></p>
                        <?php endif; ?>
                    </div>

                    <span class="badge <?= classeStatusAgenda($consulta['status']) ?>">
                        <?= e(labelStatusAgenda($consulta['status'])) ?>
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
                    <a 
                        href="<?= BASE_URL ?>profissional/prontuario_form.php?consulta_id=<?= (int)$consulta['id'] ?>" 
                        class="btn btn-light"
                    >
                        Registrar Prontuário
                    </a>
                    
                    <a 
                        href="<?= BASE_URL ?>profissional/prescricao_form.php?consulta_id=<?= (int)$consulta['id'] ?>" 
                        class="btn btn-light"
                    >
                        Emitir Prescrição
                    </a>
                    
                    <?php if ($consulta['tipo'] === 'Telemedicina' && !empty($consulta['link_teleconsulta'])): ?>
                        <a 
                            href="<?= e($consulta['link_teleconsulta']) ?>" 
                            target="_blank" 
                            class="btn btn-secondary"
                        >
                            Abrir Teleconsulta
                        </a>
                    <?php endif; ?>

                    <?php if ($consulta['status'] === 'Agendada'): ?>
                        <form 
                            method="post" 
                            action="<?= BASE_URL ?>profissional/consulta_status.php" 
                            class="inline-form"
                            data-confirm-title="Confirmar consulta"
                            data-confirm="Deseja confirmar esta consulta para o paciente <?= e($consulta['paciente_nome']) ?>?"
                            data-confirm-button="Confirmar consulta"
                            data-confirm-cancel="Voltar"
                            data-confirm-type="success"
                        >
                            <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                            <input type="hidden" name="id" value="<?= (int)$consulta['id'] ?>">
                            <input type="hidden" name="acao" value="confirmar">

                            <button type="submit" class="btn btn-primary-small">
                                Confirmar
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($consulta['status'] === 'Agendada' || $consulta['status'] === 'Confirmada'): ?>
                        <form 
                            method="post" 
                            action="<?= BASE_URL ?>profissional/consulta_status.php" 
                            class="inline-form"
                            data-confirm-title="Concluir consulta"
                            data-confirm="Deseja realmente concluir esta consulta? Após concluir, ela ficará registrada como atendimento finalizado."
                            data-confirm-button="Concluir consulta"
                            data-confirm-cancel="Voltar"
                            data-confirm-type="success"
                        >
                            <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                            <input type="hidden" name="id" value="<?= (int)$consulta['id'] ?>">
                            <input type="hidden" name="acao" value="concluir">

                            <button type="submit" class="btn btn-light">
                                Concluir
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>