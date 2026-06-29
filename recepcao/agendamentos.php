<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('recepcao');

$pageTitle = 'Agendamentos';
$pageSubtitle = 'Controle de consultas presenciais e telemedicina';
$menuAtivo = 'agendamentos';

$busca = trim($_GET['busca'] ?? '');
$statusFiltro = trim($_GET['status'] ?? '');
$tipoFiltro = trim($_GET['tipo'] ?? '');
$dataInicio = trim($_GET['data_inicio'] ?? date('Y-m-01'));
$dataFim = trim($_GET['data_fim'] ?? date('Y-m-d', strtotime('+30 days')));
$msg = $_GET['msg'] ?? '';

$opcoesPorPagina = [25, 50, 75, 100];

$porPagina = isset($_GET['por_pagina']) ? (int)$_GET['por_pagina'] : 25;

if (!in_array($porPagina, $opcoesPorPagina)) {
    $porPagina = 25;
}

$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

if ($paginaAtual < 1) {
    $paginaAtual = 1;
}

$where = [
    "DATE(c.data_consulta) BETWEEN :data_inicio AND :data_fim"
];

$params = [
    ':data_inicio' => $dataInicio,
    ':data_fim' => $dataFim
];

if ($busca !== '') {
    $termoBusca = '%' . $busca . '%';

    $where[] = "(
        p.nome LIKE :busca_paciente
        OR p.cpf LIKE :busca_cpf
        OR pr.nome LIKE :busca_profissional
        OR pr.especialidade LIKE :busca_especialidade
        OR u.nome LIKE :busca_unidade
        OR c.motivo LIKE :busca_motivo
    )";

    $params[':busca_paciente'] = $termoBusca;
    $params[':busca_cpf'] = $termoBusca;
    $params[':busca_profissional'] = $termoBusca;
    $params[':busca_especialidade'] = $termoBusca;
    $params[':busca_unidade'] = $termoBusca;
    $params[':busca_motivo'] = $termoBusca;
}

if ($statusFiltro !== '') {
    $where[] = "c.status = :status";
    $params[':status'] = $statusFiltro;
}

if ($tipoFiltro !== '') {
    $where[] = "c.tipo = :tipo";
    $params[':tipo'] = $tipoFiltro;
}

$whereSql = implode(' AND ', $where);

$stmtTotal = $pdo->prepare("
    SELECT COUNT(*)
    FROM consultas c
    INNER JOIN pacientes p ON p.id = c.paciente_id
    INNER JOIN profissionais pr ON pr.id = c.profissional_id
    LEFT JOIN unidades u ON u.id = c.unidade_id
    WHERE {$whereSql}
");

$stmtTotal->execute($params);

$totalRegistros = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, (int)ceil($totalRegistros / $porPagina));

if ($paginaAtual > $totalPaginas) {
    $paginaAtual = $totalPaginas;
}

$offset = ($paginaAtual - 1) * $porPagina;

$stmt = $pdo->prepare("
    SELECT 
        c.*,
        p.nome AS paciente_nome,
        p.cpf AS paciente_cpf,
        pr.nome AS profissional_nome,
        pr.especialidade AS profissional_especialidade,
        u.nome AS unidade_nome
    FROM consultas c
    INNER JOIN pacientes p ON p.id = c.paciente_id
    INNER JOIN profissionais pr ON pr.id = c.profissional_id
    LEFT JOIN unidades u ON u.id = c.unidade_id
    WHERE {$whereSql}
    ORDER BY c.data_consulta ASC
    LIMIT :limite OFFSET :offset
");

foreach ($params as $chave => $valor) {
    $stmt->bindValue($chave, $valor);
}

$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$consultas = $stmt->fetchAll();

$registroInicial = $totalRegistros > 0 ? $offset + 1 : 0;
$registroFinal = min($offset + $porPagina, $totalRegistros);

function labelStatusRecepcao($status)
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

function classeStatusRecepcao($status)
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

<section class="page-actions">
    <div>
        <h2>Agendamentos</h2>
        <p>Gerencie consultas presenciais e teleconsultas dos pacientes.</p>
    </div>

    <a href="<?= BASE_URL ?>recepcao/agendamento_form.php" class="btn btn-primary-small">
        Novo Agendamento
    </a>
</section>

<?php if ($msg === 'criado'): ?>
    <div class="alert-success">Agendamento cadastrado com sucesso.</div>
<?php elseif ($msg === 'atualizado'): ?>
    <div class="alert-success">Agendamento atualizado com sucesso.</div>
<?php elseif ($msg === 'confirmado'): ?>
    <div class="alert-success">Consulta confirmada com sucesso.</div>
<?php elseif ($msg === 'cancelado'): ?>
    <div class="alert-success">Consulta cancelada com sucesso.</div>
<?php endif; ?>

<section class="panel">
    <form method="get" class="recepcao-filter-form">
        <input type="hidden" name="por_pagina" value="<?= (int)$porPagina ?>">
        
        <div class="recepcao-filter-grid">
            <div class="form-group">
                <label for="busca">Busca</label>
                <input 
                    type="text" 
                    id="busca"
                    name="busca" 
                    placeholder="Paciente, CPF, profissional, unidade ou motivo"
                    value="<?= e($busca) ?>"
                >
            </div>

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

            <div class="form-group">
                <label for="tipo">Tipo</label>
                <select id="tipo" name="tipo">
                    <option value="">Todos</option>
                    <option value="Presencial" <?= $tipoFiltro === 'Presencial' ? 'selected' : '' ?>>Presencial</option>
                    <option value="Telemedicina" <?= $tipoFiltro === 'Telemedicina' ? 'selected' : '' ?>>Telemedicina</option>
                </select>
            </div>
        </div>

        <div class="form-actions recepcao-actions">
            <a href="<?= BASE_URL ?>recepcao/agendamentos.php" class="btn btn-light">
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
        <p>
            Exibindo 
            <strong><?= $registroInicial ?></strong>
            até
            <strong><?= $registroFinal ?></strong>
            de
            <strong><?= $totalRegistros ?></strong>
            agendamentos conforme os filtros selecionados.
        </p>
    </div>

    <span class="audit-count">
        <?= $totalRegistros ?>
    </span>
</div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Paciente</th>
                    <th>Profissional</th>
                    <th>Unidade</th>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($consultas)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            Nenhum agendamento encontrado.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($consultas as $consulta): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($consulta['data_consulta'])) ?></td>

                        <td>
                            <?= e($consulta['paciente_nome']) ?>

                            <?php if (!empty($consulta['paciente_cpf'])): ?>
                                <br>
                                <small>CPF: <?= e($consulta['paciente_cpf']) ?></small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= e($consulta['profissional_nome']) ?>

                            <?php if (!empty($consulta['profissional_especialidade'])): ?>
                                <br>
                                <small><?= e($consulta['profissional_especialidade']) ?></small>
                            <?php endif; ?>
                        </td>

                        <td><?= e($consulta['unidade_nome'] ?: '-') ?></td>

                        <td><?= e($consulta['tipo']) ?></td>

                        <td>
                            <span class="badge <?= classeStatusRecepcao($consulta['status']) ?>">
                                <?= e(labelStatusRecepcao($consulta['status'])) ?>
                            </span>
                        </td>

                        <td class="text-right">
                            <?php if ($consulta['tipo'] === 'Telemedicina' && !empty($consulta['link_teleconsulta'])): ?>
                                <a 
                                    href="<?= e($consulta['link_teleconsulta']) ?>" 
                                    target="_blank" 
                                    class="btn btn-secondary"
                                >
                                    Sala
                                </a>
                            <?php endif; ?>

                            <a 
                                href="<?= BASE_URL ?>recepcao/agendamento_form.php?id=<?= (int)$consulta['id'] ?>" 
                                class="btn btn-light"
                            >
                                Editar
                            </a>

                            <?php if ($consulta['status'] === 'Agendada'): ?>
                                <form 
                                    method="post" 
                                    action="<?= BASE_URL ?>recepcao/agendamento_status.php" 
                                    class="inline-form"
                                    data-confirm-title="Confirmar consulta"
                                    data-confirm="Deseja confirmar a consulta do paciente <?= e($consulta['paciente_nome']) ?> com o profissional <?= e($consulta['profissional_nome']) ?>?"
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
                                    action="<?= BASE_URL ?>recepcao/agendamento_status.php" 
                                    class="inline-form"
                                    data-confirm-title="Cancelar consulta"
                                    data-confirm="Deseja realmente cancelar a consulta do paciente <?= e($consulta['paciente_nome']) ?>? Esta ação alterará o status do agendamento para cancelado."
                                    data-confirm-button="Sim, cancelar"
                                    data-confirm-cancel="Voltar"
                                    data-confirm-type="danger"
                                >
                                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                    <input type="hidden" name="id" value="<?= (int)$consulta['id'] ?>">
                                    <input type="hidden" name="acao" value="cancelar">

                                    <button type="submit" class="btn btn-danger">
                                        Cancelar
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
        <div class="pagination-wrapper">
        <div class="pagination-info">
            Exibindo 
            <strong><?= $registroInicial ?></strong>
            até
            <strong><?= $registroFinal ?></strong>
            de
            <strong><?= $totalRegistros ?></strong>
            registros
        </div>

        <form method="get" class="pagination-size-form">
            <?php if ($busca !== ''): ?>
                <input type="hidden" name="busca" value="<?= e($busca) ?>">
            <?php endif; ?>

            <?php if ($statusFiltro !== ''): ?>
                <input type="hidden" name="status" value="<?= e($statusFiltro) ?>">
            <?php endif; ?>

            <?php if ($tipoFiltro !== ''): ?>
                <input type="hidden" name="tipo" value="<?= e($tipoFiltro) ?>">
            <?php endif; ?>

            <?php if ($dataInicio !== ''): ?>
                <input type="hidden" name="data_inicio" value="<?= e($dataInicio) ?>">
            <?php endif; ?>

            <?php if ($dataFim !== ''): ?>
                <input type="hidden" name="data_fim" value="<?= e($dataFim) ?>">
            <?php endif; ?>

            <input type="hidden" name="pagina" value="1">

            <label for="por_pagina">Itens por página</label>

            <select id="por_pagina" name="por_pagina" onchange="this.form.submit()">
                <?php foreach ($opcoesPorPagina as $opcao): ?>
                    <option value="<?= $opcao ?>" <?= $porPagina === $opcao ? 'selected' : '' ?>>
                        <?= $opcao ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if ($totalPaginas > 1): ?>
            <div class="pagination-pages">
                <?php
                    $queryBase = [
                        'busca' => $busca,
                        'status' => $statusFiltro,
                        'tipo' => $tipoFiltro,
                        'data_inicio' => $dataInicio,
                        'data_fim' => $dataFim,
                        'por_pagina' => $porPagina
                    ];

                    $paginaAnterior = max(1, $paginaAtual - 1);
                    $proximaPagina = min($totalPaginas, $paginaAtual + 1);

                    $inicio = max(1, $paginaAtual - 2);
                    $fim = min($totalPaginas, $paginaAtual + 2);
                ?>

                <a 
                    class="pagination-link <?= $paginaAtual === 1 ? 'disabled' : '' ?>"
                    href="<?= BASE_URL ?>recepcao/agendamentos.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $paginaAnterior])) ?>"
                >
                    Anterior
                </a>

                <?php if ($inicio > 1): ?>
                    <a 
                        class="pagination-link"
                        href="<?= BASE_URL ?>recepcao/agendamentos.php?<?= http_build_query(array_merge($queryBase, ['pagina' => 1])) ?>"
                    >
                        1
                    </a>

                    <?php if ($inicio > 2): ?>
                        <span class="pagination-dots">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($pagina = $inicio; $pagina <= $fim; $pagina++): ?>
                    <a 
                        class="pagination-link <?= $paginaAtual === $pagina ? 'active' : '' ?>"
                        href="<?= BASE_URL ?>recepcao/agendamentos.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $pagina])) ?>"
                    >
                        <?= $pagina ?>
                    </a>
                <?php endfor; ?>

                <?php if ($fim < $totalPaginas): ?>
                    <?php if ($fim < $totalPaginas - 1): ?>
                        <span class="pagination-dots">...</span>
                    <?php endif; ?>

                    <a 
                        class="pagination-link"
                        href="<?= BASE_URL ?>recepcao/agendamentos.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $totalPaginas])) ?>"
                    >
                        <?= $totalPaginas ?>
                    </a>
                <?php endif; ?>

                <a 
                    class="pagination-link <?= $paginaAtual === $totalPaginas ? 'disabled' : '' ?>"
                    href="<?= BASE_URL ?>recepcao/agendamentos.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $proximaPagina])) ?>"
                >
                    Próxima
                </a>
            </div>
        <?php endif; ?>
    </div>
    
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>