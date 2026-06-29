<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$pageTitle = 'Internações';
$pageSubtitle = 'Controle de internações e ocupação de leitos';
$menuAtivo = 'internacoes';

$busca = trim($_GET['busca'] ?? '');
$statusFiltro = trim($_GET['status'] ?? '');
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
    "i.ativo = 1"
];

$params = [];

if ($busca !== '') {
    $termoBusca = '%' . $busca . '%';

    $where[] = "(
        p.nome LIKE :busca_paciente
        OR p.cpf LIKE :busca_cpf
        OR l.numero LIKE :busca_leito
        OR l.setor LIKE :busca_setor
        OR u.nome LIKE :busca_unidade
        OR i.motivo LIKE :busca_motivo
    )";

    $params[':busca_paciente'] = $termoBusca;
    $params[':busca_cpf'] = $termoBusca;
    $params[':busca_leito'] = $termoBusca;
    $params[':busca_setor'] = $termoBusca;
    $params[':busca_unidade'] = $termoBusca;
    $params[':busca_motivo'] = $termoBusca;
}

if ($statusFiltro !== '') {
    $where[] = "i.status = :status";
    $params[':status'] = $statusFiltro;
}

$whereSql = implode(' AND ', $where);

$stmtTotal = $pdo->prepare("
    SELECT COUNT(*)
    FROM internacoes i
    INNER JOIN pacientes p ON p.id = i.paciente_id
    INNER JOIN leitos l ON l.id = i.leito_id
    LEFT JOIN unidades u ON u.id = l.unidade_id
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
        i.*,
        p.nome AS paciente_nome,
        p.cpf AS paciente_cpf,
        l.numero AS leito_numero,
        l.setor AS leito_setor,
        u.nome AS unidade_nome
    FROM internacoes i
    INNER JOIN pacientes p ON p.id = i.paciente_id
    INNER JOIN leitos l ON l.id = i.leito_id
    LEFT JOIN unidades u ON u.id = l.unidade_id
    WHERE {$whereSql}
    ORDER BY 
        CASE i.status
            WHEN 'Ativa' THEN 1
            WHEN 'Alta' THEN 2
            WHEN 'Cancelada' THEN 3
            ELSE 4
        END,
        i.data_entrada DESC
    LIMIT :limite OFFSET :offset
");

foreach ($params as $chave => $valor) {
    $stmt->bindValue($chave, $valor);
}

$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$internacoes = $stmt->fetchAll();

$registroInicial = $totalRegistros > 0 ? $offset + 1 : 0;
$registroFinal = min($offset + $porPagina, $totalRegistros);

function labelStatusInternacao($status)
{
    switch ($status) {
        case 'Ativa':
            return 'Em internação';
        case 'Alta':
            return 'Alta médica';
        case 'Cancelada':
            return 'Cancelada';
        default:
            return $status;
    }
}

function classeStatusInternacao($status)
{
    switch ($status) {
        case 'Ativa':
            return 'badge-warning';
        case 'Alta':
            return 'badge-success';
        case 'Cancelada':
            return 'badge-danger';
        default:
            return 'badge-neutral';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2>Internações</h2>
        <p>Gerencie pacientes internados e a ocupação dos leitos.</p>
    </div>

    <a href="<?= BASE_URL ?>admin/internacao_form.php" class="btn btn-primary-small">
        Nova Internação
    </a>
</section>

<?php if ($msg === 'criada'): ?>
    <div class="alert-success">Internação registrada com sucesso.</div>
<?php elseif ($msg === 'atualizada'): ?>
    <div class="alert-success">Internação atualizada com sucesso.</div>
<?php elseif ($msg === 'alta'): ?>
    <div class="alert-success">Alta registrada e leito liberado com sucesso.</div>
<?php elseif ($msg === 'cancelada'): ?>
    <div class="alert-success">Internação cancelada e leito liberado com sucesso.</div>
<?php endif; ?>

<section class="panel">
    <form method="get" class="internacao-filter-form">
        <input type="hidden" name="por_pagina" value="<?= (int)$porPagina ?>">
        
        <div class="internacao-filter-grid">
            <div class="form-group">
                <label for="busca">Busca</label>
                <input 
                    type="text" 
                    id="busca"
                    name="busca" 
                    placeholder="Paciente, CPF, leito, setor, unidade ou motivo"
                    value="<?= e($busca) ?>"
                >
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Todos</option>
                    <option value="Ativa" <?= $statusFiltro === 'Ativa' ? 'selected' : '' ?>>Em internação</option>
                    <option value="Alta" <?= $statusFiltro === 'Alta' ? 'selected' : '' ?>>Alta médica</option>
                    <option value="Cancelada" <?= $statusFiltro === 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>admin/internacoes.php?por_pagina=<?= (int)$porPagina ?>" class="btn btn-light">
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
        <h2>Internações Encontradas</h2>
        <p>
            Exibindo 
            <strong><?= $registroInicial ?></strong>
            até
            <strong><?= $registroFinal ?></strong>
            de
            <strong><?= $totalRegistros ?></strong>
            internações conforme os filtros selecionados.
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
                    <th>Paciente</th>
                    <th>Leito</th>
                    <th>Entrada</th>
                    <th>Alta</th>
                    <th>Status</th>
                    <th>Motivo</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($internacoes)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            Nenhuma internação encontrada.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($internacoes as $internacao): ?>
                    <tr>
                        <td>
                            <?= e($internacao['paciente_nome']) ?>

                            <?php if (!empty($internacao['paciente_cpf'])): ?>
                                <br>
                                <small>CPF: <?= e($internacao['paciente_cpf']) ?></small>
                            <?php endif; ?>
                        </td>

                        <td>
                            Leito <?= e($internacao['leito_numero']) ?>
                            <br>
                            <small>
                                <?= e($internacao['leito_setor'] ?: '-') ?>
                                <?= !empty($internacao['unidade_nome']) ? ' - ' . e($internacao['unidade_nome']) : '' ?>
                            </small>
                        </td>

                        <td><?= date('d/m/Y H:i', strtotime($internacao['data_entrada'])) ?></td>

                        <td>
                            <?php if (!empty($internacao['data_alta'])): ?>
                                <?= date('d/m/Y H:i', strtotime($internacao['data_alta'])) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>

                        <td>
                            <span class="badge <?= classeStatusInternacao($internacao['status']) ?>">
                                <?= e(labelStatusInternacao($internacao['status'])) ?>
                            </span>
                        </td>

                        <td><?= e($internacao['motivo'] ?: '-') ?></td>

                        <td class="text-right">
                            <?php if ($internacao['status'] === 'Ativa'): ?>
                                <a 
                                    href="<?= BASE_URL ?>admin/internacao_form.php?id=<?= (int)$internacao['id'] ?>" 
                                    class="btn btn-light"
                                >
                                    Editar
                                </a>

                                <form 
                                    method="post" 
                                    action="<?= BASE_URL ?>admin/internacao_status.php" 
                                    class="inline-form"
                                    data-confirm-title="Registrar alta"
                                    data-confirm="Deseja registrar alta para o paciente <?= e($internacao['paciente_nome']) ?>? O leito <?= e($internacao['leito_numero']) ?> será liberado automaticamente."
                                    data-confirm-button="Registrar alta"
                                    data-confirm-cancel="Voltar"
                                    data-confirm-type="success"
                                >
                                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                    <input type="hidden" name="id" value="<?= (int)$internacao['id'] ?>">
                                    <input type="hidden" name="acao" value="alta">

                                    <button type="submit" class="btn btn-primary-small">
                                        Alta
                                    </button>
                                </form>

                                <form 
                                    method="post" 
                                    action="<?= BASE_URL ?>admin/internacao_status.php" 
                                    class="inline-form"
                                    data-confirm-title="Cancelar internação"
                                    data-confirm="Deseja realmente cancelar a internação do paciente <?= e($internacao['paciente_nome']) ?>? O leito <?= e($internacao['leito_numero']) ?> será liberado automaticamente."
                                    data-confirm-button="Sim, cancelar"
                                    data-confirm-cancel="Voltar"
                                    data-confirm-type="danger"
                                >
                                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                    <input type="hidden" name="id" value="<?= (int)$internacao['id'] ?>">
                                    <input type="hidden" name="acao" value="cancelar">

                                    <button type="submit" class="btn btn-danger">
                                        Cancelar
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="badge badge-neutral">Finalizada</span>
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
                        'por_pagina' => $porPagina
                    ];

                    $paginaAnterior = max(1, $paginaAtual - 1);
                    $proximaPagina = min($totalPaginas, $paginaAtual + 1);

                    $inicio = max(1, $paginaAtual - 2);
                    $fim = min($totalPaginas, $paginaAtual + 2);
                ?>

                <a 
                    class="pagination-link <?= $paginaAtual === 1 ? 'disabled' : '' ?>"
                    href="<?= BASE_URL ?>admin/internacoes.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $paginaAnterior])) ?>"
                >
                    Anterior
                </a>

                <?php if ($inicio > 1): ?>
                    <a 
                        class="pagination-link"
                        href="<?= BASE_URL ?>admin/internacoes.php?<?= http_build_query(array_merge($queryBase, ['pagina' => 1])) ?>"
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
                        href="<?= BASE_URL ?>admin/internacoes.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $pagina])) ?>"
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
                        href="<?= BASE_URL ?>admin/internacoes.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $totalPaginas])) ?>"
                    >
                        <?= $totalPaginas ?>
                    </a>
                <?php endif; ?>

                <a 
                    class="pagination-link <?= $paginaAtual === $totalPaginas ? 'disabled' : '' ?>"
                    href="<?= BASE_URL ?>admin/internacoes.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $proximaPagina])) ?>"
                >
                    Próxima
                </a>
            </div>
        <?php endif; ?>
    </div>
    
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>