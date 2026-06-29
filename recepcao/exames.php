<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('recepcao');

$pageTitle = 'Exames';
$pageSubtitle = 'Controle de exames solicitados, agendados e realizados';
$menuAtivo = 'exames';

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
    "e.ativo = 1"
];

$params = [];

if ($busca !== '') {
    $termoBusca = '%' . $busca . '%';

    $where[] = "(
        p.nome LIKE :busca_paciente
        OR p.cpf LIKE :busca_cpf
        OR e.nome_exame LIKE :busca_exame
        OR e.resultado LIKE :busca_resultado
        OR e.observacoes LIKE :busca_observacoes
        OR u.nome LIKE :busca_unidade
    )";

    $params[':busca_paciente'] = $termoBusca;
    $params[':busca_cpf'] = $termoBusca;
    $params[':busca_exame'] = $termoBusca;
    $params[':busca_resultado'] = $termoBusca;
    $params[':busca_observacoes'] = $termoBusca;
    $params[':busca_unidade'] = $termoBusca;
}

if ($statusFiltro !== '') {
    $where[] = "e.status = :status";
    $params[':status'] = $statusFiltro;
}

$whereSql = implode(' AND ', $where);

$stmtTotal = $pdo->prepare("
    SELECT COUNT(*)
    FROM exames e
    INNER JOIN pacientes p ON p.id = e.paciente_id
    LEFT JOIN unidades u ON u.id = e.unidade_id
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
        e.*,
        p.nome AS paciente_nome,
        p.cpf AS paciente_cpf,
        u.nome AS unidade_nome,
        u.tipo AS unidade_tipo
    FROM exames e
    INNER JOIN pacientes p ON p.id = e.paciente_id
    LEFT JOIN unidades u ON u.id = e.unidade_id
    WHERE {$whereSql}
    ORDER BY 
        CASE 
            WHEN e.data_exame IS NULL THEN 1
            ELSE 0
        END,
        e.data_exame ASC,
        e.criado_em DESC
    LIMIT :limite OFFSET :offset
");

foreach ($params as $chave => $valor) {
    $stmt->bindValue($chave, $valor);
}

$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$exames = $stmt->fetchAll();

$registroInicial = $totalRegistros > 0 ? $offset + 1 : 0;
$registroFinal = min($offset + $porPagina, $totalRegistros);

function classeStatusExame($status)
{
    switch ($status) {
        case 'Solicitado':
            return 'badge-info';
        case 'Agendado':
            return 'badge-warning';
        case 'Realizado':
            return 'badge-success';
        case 'Cancelado':
            return 'badge-danger';
        default:
            return 'badge-neutral';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2>Exames</h2>
        <p>Gerencie solicitações, agendamentos e resultados de exames.</p>
    </div>

    <a href="<?= BASE_URL ?>recepcao/exame_form.php" class="btn btn-primary-small">
        Novo Exame
    </a>
</section>

<?php if ($msg === 'criado'): ?>
    <div class="alert-success">Exame cadastrado com sucesso.</div>
<?php elseif ($msg === 'atualizado'): ?>
    <div class="alert-success">Exame atualizado com sucesso.</div>
<?php elseif ($msg === 'status'): ?>
    <div class="alert-success">Status do exame atualizado com sucesso.</div>
<?php elseif ($msg === 'excluido'): ?>
    <div class="alert-success">Exame inativado com sucesso.</div>
<?php endif; ?>

<section class="panel">
    <form method="get" class="recepcao-filter-form">
        <input type="hidden" name="por_pagina" value="<?= (int)$porPagina ?>">
        <div class="exames-filter-grid">
            <div class="form-group">
                <label for="busca">Busca</label>
                <input 
                    type="text" 
                    id="busca"
                    name="busca" 
                    placeholder="Paciente, CPF, exame, unidade, resultado ou observação"
                    value="<?= e($busca) ?>"
                >
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Todos</option>
                    <option value="Solicitado" <?= $statusFiltro === 'Solicitado' ? 'selected' : '' ?>>Solicitado</option>
                    <option value="Agendado" <?= $statusFiltro === 'Agendado' ? 'selected' : '' ?>>Agendado</option>
                    <option value="Realizado" <?= $statusFiltro === 'Realizado' ? 'selected' : '' ?>>Realizado</option>
                    <option value="Cancelado" <?= $statusFiltro === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                </select>
            </div>
        </div>

        <div class="form-actions recepcao-actions">
            <a href="<?= BASE_URL ?>recepcao/exames.php" class="btn btn-light">
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
        <h2>Exames Encontrados</h2>
        <p>
            Exibindo 
            <strong><?= $registroInicial ?></strong>
            até
            <strong><?= $registroFinal ?></strong>
            de
            <strong><?= $totalRegistros ?></strong>
            exames conforme os filtros selecionados.
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
                    <th>Exame</th>
                    <th>Data/Hora</th>
                    <th>Unidade</th>
                    <th>Status</th>
                    <th>Resultado</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($exames)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            Nenhum exame encontrado.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($exames as $exame): ?>
                    <tr>
                        <td>
                            <?= e($exame['paciente_nome']) ?>

                            <?php if (!empty($exame['paciente_cpf'])): ?>
                                <br>
                                <small>CPF: <?= e($exame['paciente_cpf']) ?></small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <strong><?= e($exame['nome_exame']) ?></strong>

                            <?php if (!empty($exame['observacoes'])): ?>
                                <br>
                                <small><?= e($exame['observacoes']) ?></small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if (!empty($exame['data_exame'])): ?>
                                <?= date('d/m/Y H:i', strtotime($exame['data_exame'])) ?>
                            <?php else: ?>
                                <span class="badge badge-neutral">Sem data</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if (!empty($exame['unidade_nome'])): ?>
                                <?= e($exame['unidade_nome']) ?>
                                <br>
                                <small><?= e($exame['unidade_tipo']) ?></small>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>

                        <td>
                            <span class="badge <?= classeStatusExame($exame['status']) ?>">
                                <?= e($exame['status']) ?>
                            </span>
                        </td>

                        <td>
                            <?= e($exame['resultado'] ?: '-') ?>
                        </td>

                        <td class="text-right">
                            <a 
                                href="<?= BASE_URL ?>recepcao/exame_form.php?id=<?= (int)$exame['id'] ?>" 
                                class="btn btn-light"
                            >
                                Editar
                            </a>

                            <?php if ($exame['status'] !== 'Realizado' && $exame['status'] !== 'Cancelado'): ?>
                                <form 
                                    method="post" 
                                    action="<?= BASE_URL ?>recepcao/exame_status.php" 
                                    class="inline-form"
                                >
                                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                    <input type="hidden" name="id" value="<?= (int)$exame['id'] ?>">
                                    <input type="hidden" name="acao" value="realizar">

                                    <button type="submit" class="btn btn-primary-small">
                                        Realizar
                                    </button>
                                </form>

                                <form 
                                    method="post" 
                                    action="<?= BASE_URL ?>recepcao/exame_status.php" 
                                    class="inline-form"
                                    data-confirm-title="Cancelar exame"
                                    data-confirm="Deseja realmente cancelar o exame <?= e($exame['nome_exame']) ?> do paciente <?= e($exame['paciente_nome']) ?>?"
                                    data-confirm-button="Sim, cancelar"
                                    data-confirm-cancel="Voltar"
                                    data-confirm-type="danger"
                                >
                                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                    <input type="hidden" name="id" value="<?= (int)$exame['id'] ?>">
                                    <input type="hidden" name="acao" value="cancelar">

                                    <button type="submit" class="btn btn-danger">
                                        Cancelar
                                    </button>
                                </form>
                            <?php endif; ?>

                            <form 
                                method="post" 
                                action="<?= BASE_URL ?>recepcao/exame_excluir.php" 
                                class="inline-form"
                                data-confirm-title="Inativar exame"
                                data-confirm="Deseja realmente inativar o exame <?= e($exame['nome_exame']) ?> do paciente <?= e($exame['paciente_nome']) ?>? Ele não será mais exibido nas listagens ativas."
                                data-confirm-button="Sim, inativar"
                                data-confirm-cancel="Voltar"
                                data-confirm-type="danger"
                            >
                                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                <input type="hidden" name="id" value="<?= (int)$exame['id'] ?>">

                                <button type="submit" class="btn btn-danger">
                                    Inativar
                                </button>
                            </form>
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
                    href="<?= BASE_URL ?>recepcao/exames.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $paginaAnterior])) ?>"
                >
                    Anterior
                </a>

                <?php if ($inicio > 1): ?>
                    <a 
                        class="pagination-link"
                        href="<?= BASE_URL ?>recepcao/exames.php?<?= http_build_query(array_merge($queryBase, ['pagina' => 1])) ?>"
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
                        href="<?= BASE_URL ?>recepcao/exames.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $pagina])) ?>"
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
                        href="<?= BASE_URL ?>recepcao/exames.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $totalPaginas])) ?>"
                    >
                        <?= $totalPaginas ?>
                    </a>
                <?php endif; ?>

                <a 
                    class="pagination-link <?= $paginaAtual === $totalPaginas ? 'disabled' : '' ?>"
                    href="<?= BASE_URL ?>recepcao/exames.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $proximaPagina])) ?>"
                >
                    Próxima
                </a>
            </div>
        <?php endif; ?>
    </div>
    
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>