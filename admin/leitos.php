<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$pageTitle = 'Leitos';
$pageSubtitle = 'Controle de leitos por unidade hospitalar';
$menuAtivo = 'leitos';

$busca = trim($_GET['busca'] ?? '');
$msg = $_GET['msg'] ?? '';
$erro = $_GET['erro'] ?? '';

$opcoesPorPagina = [25, 50, 75, 100];

$porPagina = isset($_GET['por_pagina']) ? (int)$_GET['por_pagina'] : 25;

if (!in_array($porPagina, $opcoesPorPagina)) {
    $porPagina = 25;
}

$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

if ($paginaAtual < 1) {
    $paginaAtual = 1;
}

$where = "WHERE l.ativo = 1";
$params = [];

if ($busca !== '') {
    $where .= "
        AND (
            l.numero LIKE :busca_numero
            OR l.setor LIKE :busca_setor
            OR l.status LIKE :busca_status
            OR u.nome LIKE :busca_unidade
            OR u.tipo LIKE :busca_tipo_unidade
        )
    ";

    $termoBusca = '%' . $busca . '%';

    $params[':busca_numero'] = $termoBusca;
    $params[':busca_setor'] = $termoBusca;
    $params[':busca_status'] = $termoBusca;
    $params[':busca_unidade'] = $termoBusca;
    $params[':busca_tipo_unidade'] = $termoBusca;
}

$stmtTotal = $pdo->prepare("
    SELECT COUNT(*)
    FROM leitos l
    INNER JOIN unidades u ON u.id = l.unidade_id
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
    SELECT 
        l.*,
        u.nome AS unidade_nome,
        u.tipo AS unidade_tipo
    FROM leitos l
    INNER JOIN unidades u ON u.id = l.unidade_id
    {$where}
    ORDER BY u.nome ASC, l.setor ASC, l.numero ASC
    LIMIT :limite OFFSET :offset
");

foreach ($params as $chave => $valor) {
    $stmt->bindValue($chave, $valor);
}

$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$leitos = $stmt->fetchAll();

$registroInicial = $totalRegistros > 0 ? $offset + 1 : 0;
$registroFinal = min($offset + $porPagina, $totalRegistros);

require_once __DIR__ . '/../includes/header.php';

function labelStatusLeito($status)
{
    switch ($status) {
        case 'Disponivel':
            return 'Disponível';
        case 'Ocupado':
            return 'Ocupado';
        case 'Manutencao':
            return 'Manutenção';
        default:
            return $status;
    }
}

function classeStatusLeito($status)
{
    switch ($status) {
        case 'Disponivel':
            return 'badge-success';
        case 'Ocupado':
            return 'badge-warning';
        case 'Manutencao':
            return 'badge-danger';
        default:
            return 'badge-warning';
    }
}
?>

<section class="page-actions">
    <div>
        <h2>Lista de Leitos</h2>
        <p>Gerencie a disponibilidade de leitos por unidade, setor e status.</p>
    </div>

    <a href="<?= BASE_URL ?>admin/leito_form.php" class="btn btn-primary-small">
        Novo Leito
    </a>
</section>

<?php if ($msg === 'criado'): ?>
    <div class="alert-success">Leito cadastrado com sucesso.</div>
<?php elseif ($msg === 'atualizado'): ?>
    <div class="alert-success">Leito atualizado com sucesso.</div>
<?php elseif ($msg === 'excluido'): ?>
    <div class="alert-success">Leito inativado com sucesso.</div>
<?php endif; ?>

<?php if ($erro === 'ocupado'): ?>
    <div class="alert-error">
        Não é possível inativar um leito ocupado. Altere o status antes de inativar.
    </div>
<?php endif; ?>

<section class="panel">
    <form method="get" class="search-form">
    <input 
        type="text" 
        name="busca" 
        placeholder="Buscar por número, setor, status, unidade ou tipo"
        value="<?= e($busca) ?>"
    >

    <input type="hidden" name="por_pagina" value="<?= (int)$porPagina ?>">

    <button type="submit" class="btn btn-secondary">
        Buscar
    </button>

    <?php if ($busca !== ''): ?>
        <a href="<?= BASE_URL ?>admin/leitos.php?por_pagina=<?= (int)$porPagina ?>" class="btn btn-light">
            Limpar
        </a>
    <?php endif; ?>
</form>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Unidade</th>
                    <th>Tipo</th>
                    <th>Número</th>
                    <th>Setor</th>
                    <th>Status</th>
                    <th>Criado em</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($leitos)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            Nenhum leito encontrado.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($leitos as $leito): ?>
                    <tr>
                        <td><?= e($leito['unidade_nome']) ?></td>
                        <td><?= e($leito['unidade_tipo']) ?></td>
                        <td><?= e($leito['numero']) ?></td>
                        <td><?= e($leito['setor'] ?: '-') ?></td>
                        <td>
                            <span class="badge <?= classeStatusLeito($leito['status']) ?>">
                                <?= e(labelStatusLeito($leito['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($leito['criado_em'])): ?>
                                <?= date('d/m/Y H:i', strtotime($leito['criado_em'])) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <a 
                                href="<?= BASE_URL ?>admin/leito_form.php?id=<?= (int)$leito['id'] ?>" 
                                class="btn btn-light"
                            >
                                Editar
                            </a>

                            <form 
                                method="post" 
                                action="<?= BASE_URL ?>admin/leito_excluir.php" 
                                class="inline-form"
                                data-confirm-title="Inativar leito"
                                data-confirm="Deseja realmente inativar o leito <?= e($leito['numero']) ?> da unidade <?= e($leito['unidade_nome']) ?>? Ele não será mais exibido nas listagens ativas."
                                data-confirm-button="Sim, inativar"
                                data-confirm-cancel="Voltar"
                                data-confirm-type="danger"
                            >
                                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                <input type="hidden" name="id" value="<?= (int)$leito['id'] ?>">

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
                    href="<?= BASE_URL ?>admin/leitos.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $paginaAnterior])) ?>"
                >
                    Anterior
                </a>

                <?php if ($inicio > 1): ?>
                    <a 
                        class="pagination-link"
                        href="<?= BASE_URL ?>admin/leitos.php?<?= http_build_query(array_merge($queryBase, ['pagina' => 1])) ?>"
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
                        href="<?= BASE_URL ?>admin/leitos.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $pagina])) ?>"
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
                        href="<?= BASE_URL ?>admin/leitos.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $totalPaginas])) ?>"
                    >
                        <?= $totalPaginas ?>
                    </a>
                <?php endif; ?>

                <a 
                    class="pagination-link <?= $paginaAtual === $totalPaginas ? 'disabled' : '' ?>"
                    href="<?= BASE_URL ?>admin/leitos.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $proximaPagina])) ?>"
                >
                    Próxima
                </a>
            </div>
        <?php endif; ?>
    </div>
    
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>