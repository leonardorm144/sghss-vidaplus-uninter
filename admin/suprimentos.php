<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$pageTitle = 'Suprimentos';
$pageSubtitle = 'Controle de estoque e materiais hospitalares';
$menuAtivo = 'suprimentos';

$busca = trim($_GET['busca'] ?? '');
$categoriaFiltro = trim($_GET['categoria'] ?? '');
$unidadeFiltro = isset($_GET['unidade_id']) ? (int)$_GET['unidade_id'] : 0;
$statusEstoqueFiltro = trim($_GET['status_estoque'] ?? '');
$statusCadastroFiltro = trim($_GET['status_cadastro'] ?? 'ativos');
$msg = $_GET['msg'] ?? '';

if (!in_array($statusCadastroFiltro, ['ativos', 'inativos', 'todos'])) {
    $statusCadastroFiltro = 'ativos';
}

$opcoesPorPagina = [25, 50, 75, 100];

$porPagina = isset($_GET['por_pagina']) ? (int)$_GET['por_pagina'] : 25;

if (!in_array($porPagina, $opcoesPorPagina)) {
    $porPagina = 25;
}

$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

if ($paginaAtual < 1) {
    $paginaAtual = 1;
}

$stmtUnidades = $pdo->query("
    SELECT id, nome
    FROM unidades
    WHERE ativo = 1
    ORDER BY nome ASC
");

$unidades = $stmtUnidades->fetchAll();

$stmtCategorias = $pdo->query("
    SELECT DISTINCT categoria
    FROM suprimentos
    WHERE categoria IS NOT NULL
    AND categoria <> ''
    ORDER BY categoria ASC
");

$categorias = $stmtCategorias->fetchAll();

$where = [];

if ($statusCadastroFiltro === 'ativos') {
    $where[] = "s.ativo = 1";
} elseif ($statusCadastroFiltro === 'inativos') {
    $where[] = "s.ativo = 0";
}

$params = [];

if ($busca !== '') {
    $termoBusca = '%' . $busca . '%';

    $where[] = "(
        s.nome LIKE :busca_nome
        OR s.categoria LIKE :busca_categoria
        OR s.observacoes LIKE :busca_observacoes
        OR u.nome LIKE :busca_unidade
    )";

    $params[':busca_nome'] = $termoBusca;
    $params[':busca_categoria'] = $termoBusca;
    $params[':busca_observacoes'] = $termoBusca;
    $params[':busca_unidade'] = $termoBusca;
}

if ($categoriaFiltro !== '') {
    $where[] = "s.categoria = :categoria";
    $params[':categoria'] = $categoriaFiltro;
}

if ($unidadeFiltro > 0) {
    $where[] = "s.unidade_id = :unidade_id";
    $params[':unidade_id'] = $unidadeFiltro;
}

if ($statusEstoqueFiltro === 'sem_estoque') {
    $where[] = "s.estoque_atual <= 0";
} elseif ($statusEstoqueFiltro === 'baixo_estoque') {
    $where[] = "s.estoque_atual > 0 AND s.estoque_atual <= s.estoque_minimo";
} elseif ($statusEstoqueFiltro === 'normal') {
    $where[] = "s.estoque_atual > s.estoque_minimo";
}

$whereSql = !empty($where) ? implode(' AND ', $where) : '1=1';

$stmtTotal = $pdo->prepare("
    SELECT COUNT(*)
    FROM suprimentos s
    INNER JOIN unidades u ON u.id = s.unidade_id
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
        s.*,
        u.nome AS unidade_nome,
        u.tipo AS unidade_tipo
    FROM suprimentos s
    INNER JOIN unidades u ON u.id = s.unidade_id
    WHERE {$whereSql}
    ORDER BY 
        CASE 
            WHEN s.estoque_atual <= 0 THEN 1
            WHEN s.estoque_atual <= s.estoque_minimo THEN 2
            ELSE 3
        END,
        s.nome ASC
    LIMIT :limite OFFSET :offset
");

foreach ($params as $chave => $valor) {
    if ($chave === ':unidade_id') {
        $stmt->bindValue($chave, $valor, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($chave, $valor);
    }
}

$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$suprimentos = $stmt->fetchAll();

$registroInicial = $totalRegistros > 0 ? $offset + 1 : 0;
$registroFinal = min($offset + $porPagina, $totalRegistros);

$stmtResumo = $pdo->query("
    SELECT
        COUNT(*) AS total_itens,
        SUM(CASE WHEN estoque_atual <= 0 THEN 1 ELSE 0 END) AS sem_estoque,
        SUM(CASE WHEN estoque_atual > 0 AND estoque_atual <= estoque_minimo THEN 1 ELSE 0 END) AS baixo_estoque,
        SUM(CASE WHEN estoque_atual > estoque_minimo THEN 1 ELSE 0 END) AS estoque_normal
    FROM suprimentos
    WHERE ativo = 1
");

$resumo = $stmtResumo->fetch();

function formatarQuantidadeSuprimento($valor)
{
    $numero = (float)$valor;

    if (floor($numero) == $numero) {
        return number_format($numero, 0, ',', '.');
    }

    return number_format($numero, 2, ',', '.');
}

function labelStatusSuprimento($estoqueAtual, $estoqueMinimo)
{
    $estoqueAtual = (float)$estoqueAtual;
    $estoqueMinimo = (float)$estoqueMinimo;

    if ($estoqueAtual <= 0) {
        return 'Sem estoque';
    }

    if ($estoqueAtual <= $estoqueMinimo) {
        return 'Baixo estoque';
    }

    return 'Normal';
}

function classeStatusSuprimento($estoqueAtual, $estoqueMinimo)
{
    $estoqueAtual = (float)$estoqueAtual;
    $estoqueMinimo = (float)$estoqueMinimo;

    if ($estoqueAtual <= 0) {
        return 'badge-danger';
    }

    if ($estoqueAtual <= $estoqueMinimo) {
        return 'badge-warning';
    }

    return 'badge-success';
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2>Suprimentos</h2>
        <p>Gerencie materiais hospitalares, estoque atual e alertas de reposição.</p>
    </div>

    <a href="<?= BASE_URL ?>admin/suprimento_form.php" class="btn btn-primary-small">
        Novo Suprimento
    </a>
</section>

<?php if ($msg === 'criado'): ?>
    <div class="alert-success">Suprimento cadastrado com sucesso.</div>
<?php elseif ($msg === 'atualizado'): ?>
    <div class="alert-success">Suprimento atualizado com sucesso.</div>
<?php elseif ($msg === 'movimentado'): ?>
    <div class="alert-success">Movimentação registrada com sucesso.</div>
<?php elseif ($msg === 'excluido'): ?>
    <div class="alert-success">Suprimento inativado com sucesso.</div>
<?php elseif ($msg === 'ativado'): ?>
    <div class="alert-success">Suprimento reativado com sucesso.</div>
<?php endif; ?>

<section class="cards-grid">
    <div class="card">
        <span>Total de Itens</span>
        <strong><?= (int)($resumo['total_itens'] ?? 0) ?></strong>
        <small>Suprimentos ativos</small>
    </div>

    <div class="card">
        <span>Estoque Normal</span>
        <strong><?= (int)($resumo['estoque_normal'] ?? 0) ?></strong>
        <small>Acima do mínimo</small>
    </div>

    <div class="card">
        <span>Baixo Estoque</span>
        <strong><?= (int)($resumo['baixo_estoque'] ?? 0) ?></strong>
        <small>Requer atenção</small>
    </div>

    <div class="card">
        <span>Sem Estoque</span>
        <strong><?= (int)($resumo['sem_estoque'] ?? 0) ?></strong>
        <small>Reposição urgente</small>
    </div>
</section>

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
                    placeholder="Nome, categoria, unidade ou observação"
                    value="<?= e($busca) ?>"
                >
            </div>

            <div class="form-group">
                <label for="categoria">Categoria</label>
                <select id="categoria" name="categoria">
                    <option value="">Todas</option>

                    <?php foreach ($categorias as $categoria): ?>
                        <option 
                            value="<?= e($categoria['categoria']) ?>" 
                            <?= $categoriaFiltro === $categoria['categoria'] ? 'selected' : '' ?>
                        >
                            <?= e($categoria['categoria']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="unidade_id">Unidade</label>
                <select id="unidade_id" name="unidade_id">
                    <option value="0">Todas</option>

                    <?php foreach ($unidades as $unidade): ?>
                        <option 
                            value="<?= (int)$unidade['id'] ?>" 
                            <?= $unidadeFiltro === (int)$unidade['id'] ? 'selected' : '' ?>
                        >
                            <?= e($unidade['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="status_estoque">Status do estoque</label>
                <select id="status_estoque" name="status_estoque">
                    <option value="">Todos</option>
                    <option value="normal" <?= $statusEstoqueFiltro === 'normal' ? 'selected' : '' ?>>Normal</option>
                    <option value="baixo_estoque" <?= $statusEstoqueFiltro === 'baixo_estoque' ? 'selected' : '' ?>>Baixo estoque</option>
                    <option value="sem_estoque" <?= $statusEstoqueFiltro === 'sem_estoque' ? 'selected' : '' ?>>Sem estoque</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="status_cadastro">Cadastro</label>
                <select id="status_cadastro" name="status_cadastro">
                    <option value="ativos" <?= $statusCadastroFiltro === 'ativos' ? 'selected' : '' ?>>Ativos</option>
                    <option value="inativos" <?= $statusCadastroFiltro === 'inativos' ? 'selected' : '' ?>>Inativos</option>
                    <option value="todos" <?= $statusCadastroFiltro === 'todos' ? 'selected' : '' ?>>Todos</option>
                </select>
            </div>
            
        </div>

        <div class="form-actions recepcao-actions">
            <a href="<?= BASE_URL ?>admin/suprimentos.php" class="btn btn-light">
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
            <h2>Suprimentos Encontrados</h2>
            <p>
                Exibindo
                <strong><?= $registroInicial ?></strong>
                até
                <strong><?= $registroFinal ?></strong>
                de
                <strong><?= $totalRegistros ?></strong>
                itens conforme os filtros selecionados.
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
                    <th>Suprimento</th>
                    <th>Categoria</th>
                    <th>Unidade</th>
                    <th>Estoque Atual</th>
                    <th>Estoque Mínimo</th>
                    <th>Estoque</th>
                    <th>Cadastro</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($suprimentos)): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            Nenhum suprimento encontrado.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($suprimentos as $suprimento): ?>
                    <tr>
                        <td>
                            <strong><?= e($suprimento['nome']) ?></strong>

                            <?php if (!empty($suprimento['observacoes'])): ?>
                                <br>
                                <small><?= e($suprimento['observacoes']) ?></small>
                            <?php endif; ?>
                        </td>

                        <td><?= e($suprimento['categoria'] ?: '-') ?></td>

                        <td>
                            <?= e($suprimento['unidade_nome']) ?>

                            <?php if (!empty($suprimento['unidade_tipo'])): ?>
                                <br>
                                <small><?= e($suprimento['unidade_tipo']) ?></small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <strong>
                                <?= formatarQuantidadeSuprimento($suprimento['estoque_atual']) ?>
                                <?= e($suprimento['unidade_medida']) ?>
                            </strong>
                        </td>

                        <td>
                            <?= formatarQuantidadeSuprimento($suprimento['estoque_minimo']) ?>
                            <?= e($suprimento['unidade_medida']) ?>
                        </td>

                        <td>
                            <span class="badge <?= classeStatusSuprimento($suprimento['estoque_atual'], $suprimento['estoque_minimo']) ?>">
                                <?= e(labelStatusSuprimento($suprimento['estoque_atual'], $suprimento['estoque_minimo'])) ?>
                            </span>
                        </td>

                        <td>
                            <?php if ((int)$suprimento['ativo'] === 1): ?>
                                <span class="badge badge-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inativo</span>
                            <?php endif; ?>
                        </td>

                        <td class="text-right">
                            <?php if ((int)$suprimento['ativo'] === 1): ?>
                                <a 
                                    href="<?= BASE_URL ?>admin/suprimento_form.php?id=<?= (int)$suprimento['id'] ?>" 
                                    class="btn btn-light"
                                >
                                    Editar
                                </a>

                                <a 
                                    href="<?= BASE_URL ?>admin/suprimento_movimentar.php?id=<?= (int)$suprimento['id'] ?>&tipo=Entrada" 
                                    class="btn btn-primary-small"
                                >
                                    Entrada
                                </a>

                                <a 
                                    href="<?= BASE_URL ?>admin/suprimento_movimentar.php?id=<?= (int)$suprimento['id'] ?>&tipo=Saida" 
                                    class="btn btn-light"
                                >
                                    Saída
                                </a>

                                <form 
                                    method="post" 
                                    action="<?= BASE_URL ?>admin/suprimento_excluir.php" 
                                    class="inline-form"
                                    data-confirm-title="Inativar suprimento"
                                    data-confirm="Deseja realmente inativar o suprimento <?= e($suprimento['nome']) ?>? Ele continuará no histórico, mas não ficará disponível para movimentações."
                                    data-confirm-button="Sim, inativar"
                                    data-confirm-cancel="Voltar"
                                    data-confirm-type="danger"
                                >
                                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                    <input type="hidden" name="id" value="<?= (int)$suprimento['id'] ?>">

                                    <button type="submit" class="btn btn-danger">
                                        Inativar
                                    </button>
                                </form>
                            <?php else: ?>
                                <form 
                                    method="post" 
                                    action="<?= BASE_URL ?>admin/suprimento_ativar.php" 
                                    class="inline-form"
                                    data-confirm-title="Reativar suprimento"
                                    data-confirm="Deseja reativar o suprimento <?= e($suprimento['nome']) ?>? Ele voltará a ficar disponível para movimentações de estoque."
                                    data-confirm-button="Reativar suprimento"
                                    data-confirm-cancel="Voltar"
                                    data-confirm-type="success"
                                >
                                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                    <input type="hidden" name="id" value="<?= (int)$suprimento['id'] ?>">

                                    <button type="submit" class="btn btn-primary-small">
                                        Reativar
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

            <?php if ($categoriaFiltro !== ''): ?>
                <input type="hidden" name="categoria" value="<?= e($categoriaFiltro) ?>">
            <?php endif; ?>

            <?php if ($unidadeFiltro > 0): ?>
                <input type="hidden" name="unidade_id" value="<?= (int)$unidadeFiltro ?>">
            <?php endif; ?>

            <?php if ($statusEstoqueFiltro !== ''): ?>
                <input type="hidden" name="status_estoque" value="<?= e($statusEstoqueFiltro) ?>">
            <?php endif; ?>
            
            <input type="hidden" name="status_cadastro" value="<?= e($statusCadastroFiltro) ?>">

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
                        'categoria' => $categoriaFiltro,
                        'unidade_id' => $unidadeFiltro,
                        'status_estoque' => $statusEstoqueFiltro,
                        'status_cadastro' => $statusCadastroFiltro,
                        'por_pagina' => $porPagina
                    ];

                    $paginaAnterior = max(1, $paginaAtual - 1);
                    $proximaPagina = min($totalPaginas, $paginaAtual + 1);

                    $inicio = max(1, $paginaAtual - 2);
                    $fim = min($totalPaginas, $paginaAtual + 2);
                ?>

                <a 
                    class="pagination-link <?= $paginaAtual === 1 ? 'disabled' : '' ?>"
                    href="<?= BASE_URL ?>admin/suprimentos.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $paginaAnterior])) ?>"
                >
                    Anterior
                </a>

                <?php if ($inicio > 1): ?>
                    <a 
                        class="pagination-link"
                        href="<?= BASE_URL ?>admin/suprimentos.php?<?= http_build_query(array_merge($queryBase, ['pagina' => 1])) ?>"
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
                        href="<?= BASE_URL ?>admin/suprimentos.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $pagina])) ?>"
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
                        href="<?= BASE_URL ?>admin/suprimentos.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $totalPaginas])) ?>"
                    >
                        <?= $totalPaginas ?>
                    </a>
                <?php endif; ?>

                <a 
                    class="pagination-link <?= $paginaAtual === $totalPaginas ? 'disabled' : '' ?>"
                    href="<?= BASE_URL ?>admin/suprimentos.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $proximaPagina])) ?>"
                >
                    Próxima
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>