<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$pageTitle = 'Unidades';
$pageSubtitle = 'Cadastro e gerenciamento das unidades VidaPlus';
$menuAtivo = 'unidades';

$busca = trim($_GET['busca'] ?? '');
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

$where = "WHERE ativo = 1";
$params = [];

if ($busca !== '') {
    $where .= "
        AND (
            nome LIKE :busca_nome
            OR tipo LIKE :busca_tipo
            OR cidade LIKE :busca_cidade
            OR estado LIKE :busca_estado
        )
    ";

    $termoBusca = '%' . $busca . '%';

    $params[':busca_nome'] = $termoBusca;
    $params[':busca_tipo'] = $termoBusca;
    $params[':busca_cidade'] = $termoBusca;
    $params[':busca_estado'] = $termoBusca;
}

$stmtTotal = $pdo->prepare("
    SELECT COUNT(*)
    FROM unidades
    {$where}
");

$stmtTotal->execute($params);

$totalRegistros = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, (int)ceil($totalRegistros / $porPagina));

if ($paginaAtual > $totalPaginas) {
    $paginaAtual = $totalPaginas;
}

$offset = ($paginaAtual - 1) * $porPagina;

$stmt = $pdo->prepare("
    SELECT *
    FROM unidades
    {$where}
    ORDER BY nome ASC
    LIMIT :limite OFFSET :offset
");

foreach ($params as $chave => $valor) {
    $stmt->bindValue($chave, $valor);
}

$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$unidades = $stmt->fetchAll();

$registroInicial = $totalRegistros > 0 ? $offset + 1 : 0;
$registroFinal = min($offset + $porPagina, $totalRegistros);

require_once __DIR__ . '/../includes/header.php';

function labelTipoUnidade($tipo)
{
    switch ($tipo) {
        case 'Hospital':
            return 'Hospital';
        case 'Clinica':
            return 'Clínica';
        case 'Laboratorio':
            return 'Laboratório';
        case 'Home Care':
            return 'Home Care';
        default:
            return $tipo;
    }
}
?>

<section class="page-actions">
    <div>
        <h2>Lista de Unidades</h2>
        <p>Gerencie hospitais, clínicas, laboratórios e equipes de home care.</p>
    </div>

    <a href="<?= BASE_URL ?>admin/unidade_form.php" class="btn btn-primary-small">
        Nova Unidade
    </a>
</section>

<?php if ($msg === 'criado'): ?>
    <div class="alert-success">Unidade cadastrada com sucesso.</div>
<?php elseif ($msg === 'atualizado'): ?>
    <div class="alert-success">Unidade atualizada com sucesso.</div>
<?php elseif ($msg === 'excluido'): ?>
    <div class="alert-success">Unidade inativada com sucesso.</div>
<?php endif; ?>

<section class="panel">
    <form method="get" class="search-form">
    <input 
        type="text" 
        name="busca" 
        placeholder="Buscar por nome, tipo, cidade ou estado"
        value="<?= e($busca) ?>"
    >

    <input type="hidden" name="por_pagina" value="<?= (int)$porPagina ?>">

    <button type="submit" class="btn btn-secondary">
        Buscar
    </button>

    <?php if ($busca !== ''): ?>
        <a href="<?= BASE_URL ?>admin/unidades.php?por_pagina=<?= (int)$porPagina ?>" class="btn btn-light">
            Limpar
        </a>
    <?php endif; ?>
</form>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Cidade</th>
                    <th>Estado</th>
                    <th>Criado em</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($unidades)): ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            Nenhuma unidade encontrada.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($unidades as $unidade): ?>
                    <tr>
                        <td><?= e($unidade['nome']) ?></td>
                        <td>
                            <span class="badge badge-success">
                                <?= e(labelTipoUnidade($unidade['tipo'])) ?>
                            </span>
                        </td>
                        <td><?= e($unidade['cidade'] ?: '-') ?></td>
                        <td><?= e($unidade['estado'] ?: '-') ?></td>
                        <td>
                            <?php if (!empty($unidade['criado_em'])): ?>
                                <?= date('d/m/Y H:i', strtotime($unidade['criado_em'])) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <a 
                                href="<?= BASE_URL ?>admin/unidade_form.php?id=<?= (int)$unidade['id'] ?>" 
                                class="btn btn-light"
                            >
                                Editar
                            </a>

                            <form 
                                method="post" 
                                action="<?= BASE_URL ?>admin/unidade_excluir.php" 
                                class="inline-form"
                                data-confirm-title="Inativar unidade"
                                data-confirm="Deseja realmente inativar a unidade <?= e($unidade['nome']) ?>? Ela não será mais exibida nas listagens ativas."
                                data-confirm-button="Sim, inativar"
                                data-confirm-cancel="Voltar"
                                data-confirm-type="danger"
                            >
                                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                <input type="hidden" name="id" value="<?= (int)$unidade['id'] ?>">

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
                        'por_pagina' => $porPagina
                    ];

                    $paginaAnterior = max(1, $paginaAtual - 1);
                    $proximaPagina = min($totalPaginas, $paginaAtual + 1);

                    $inicio = max(1, $paginaAtual - 2);
                    $fim = min($totalPaginas, $paginaAtual + 2);
                ?>

                <a 
                    class="pagination-link <?= $paginaAtual === 1 ? 'disabled' : '' ?>"
                    href="<?= BASE_URL ?>admin/unidades.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $paginaAnterior])) ?>"
                >
                    Anterior
                </a>

                <?php if ($inicio > 1): ?>
                    <a 
                        class="pagination-link"
                        href="<?= BASE_URL ?>admin/unidades.php?<?= http_build_query(array_merge($queryBase, ['pagina' => 1])) ?>"
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
                        href="<?= BASE_URL ?>admin/unidades.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $pagina])) ?>"
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
                        href="<?= BASE_URL ?>admin/unidades.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $totalPaginas])) ?>"
                    >
                        <?= $totalPaginas ?>
                    </a>
                <?php endif; ?>

                <a 
                    class="pagination-link <?= $paginaAtual === $totalPaginas ? 'disabled' : '' ?>"
                    href="<?= BASE_URL ?>admin/unidades.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $proximaPagina])) ?>"
                >
                    Próxima
                </a>
            </div>
        <?php endif; ?>
    </div>
    
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>