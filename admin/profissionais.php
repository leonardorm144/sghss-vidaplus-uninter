<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$pageTitle = 'Profissionais';
$pageSubtitle = 'Cadastro e gerenciamento de profissionais de saúde';
$menuAtivo = 'profissionais';

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

$where = "WHERE p.ativo = 1";
$params = [];

if ($busca !== '') {
    $where .= "
        AND (
            p.nome LIKE :busca_nome
            OR p.email LIKE :busca_email
            OR p.registro_profissional LIKE :busca_registro
            OR p.especialidade LIKE :busca_especialidade
            OR p.tipo LIKE :busca_tipo
            OR u.nome LIKE :busca_unidade
        )
    ";

    $termoBusca = '%' . $busca . '%';

    $params[':busca_nome'] = $termoBusca;
    $params[':busca_email'] = $termoBusca;
    $params[':busca_registro'] = $termoBusca;
    $params[':busca_especialidade'] = $termoBusca;
    $params[':busca_tipo'] = $termoBusca;
    $params[':busca_unidade'] = $termoBusca;
}

$stmtTotal = $pdo->prepare("
    SELECT COUNT(*)
    FROM profissionais p
    LEFT JOIN unidades u ON u.id = p.unidade_id
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
        p.*,
        u.nome AS unidade_nome
    FROM profissionais p
    LEFT JOIN unidades u ON u.id = p.unidade_id
    {$where}
    ORDER BY p.nome ASC
    LIMIT :limite OFFSET :offset
");

foreach ($params as $chave => $valor) {
    $stmt->bindValue($chave, $valor);
}

$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$profissionais = $stmt->fetchAll();

$registroInicial = $totalRegistros > 0 ? $offset + 1 : 0;
$registroFinal = min($offset + $porPagina, $totalRegistros);

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2>Lista de Profissionais</h2>
        <p>Gerencie médicos, enfermeiros, técnicos e demais profissionais.</p>
    </div>

    <a href="<?= BASE_URL ?>admin/profissional_form.php" class="btn btn-primary-small">
        Novo Profissional
    </a>
</section>

<?php if ($msg === 'criado'): ?>
    <div class="alert-success">Profissional cadastrado com sucesso.</div>
<?php elseif ($msg === 'atualizado'): ?>
    <div class="alert-success">Profissional atualizado com sucesso.</div>
<?php elseif ($msg === 'excluido'): ?>
    <div class="alert-success">Profissional inativado com sucesso.</div>
<?php endif; ?>

<section class="panel">
    <form method="get" class="search-form">
    <input 
        type="text" 
        name="busca" 
        placeholder="Buscar por nome, e-mail, registro, especialidade, tipo ou unidade"
        value="<?= e($busca) ?>"
    >

    <input type="hidden" name="por_pagina" value="<?= (int)$porPagina ?>">

    <button type="submit" class="btn btn-secondary">
        Buscar
    </button>

    <?php if ($busca !== ''): ?>
        <a href="<?= BASE_URL ?>admin/profissionais.php?por_pagina=<?= (int)$porPagina ?>" class="btn btn-light">
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
                    <th>Especialidade</th>
                    <th>Registro</th>
                    <th>Unidade</th>
                    <th>Telefone</th>
                    <th>E-mail</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($profissionais)): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            Nenhum profissional encontrado.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($profissionais as $profissional): ?>
                    <tr>
                        <td><?= e($profissional['nome']) ?></td>
                        <td>
                            <span class="badge badge-success">
                                <?= e($profissional['tipo']) ?>
                            </span>
                        </td>
                        <td><?= e($profissional['especialidade'] ?: '-') ?></td>
                        <td><?= e($profissional['registro_profissional'] ?: '-') ?></td>
                        <td><?= e($profissional['unidade_nome'] ?: '-') ?></td>
                        <td><?= e($profissional['telefone'] ?: '-') ?></td>
                        <td><?= e($profissional['email'] ?: '-') ?></td>
                        <td class="text-right">
                            <a 
                                href="<?= BASE_URL ?>admin/profissional_form.php?id=<?= (int)$profissional['id'] ?>" 
                                class="btn btn-light"
                            >
                                Editar
                            </a>

                            <form 
                                method="post" 
                                action="<?= BASE_URL ?>admin/profissional_excluir.php" 
                                class="inline-form"
                                data-confirm-title="Inativar profissional"
                                data-confirm="Deseja realmente inativar o profissional <?= e($profissional['nome']) ?>? Ele não será mais exibido nas listagens ativas."
                                data-confirm-button="Sim, inativar"
                                data-confirm-cancel="Voltar"
                                data-confirm-type="danger"
                            >
                                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                <input type="hidden" name="id" value="<?= (int)$profissional['id'] ?>">

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
                    href="<?= BASE_URL ?>admin/profissionais.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $paginaAnterior])) ?>"
                >
                    Anterior
                </a>

                <?php if ($inicio > 1): ?>
                    <a 
                        class="pagination-link"
                        href="<?= BASE_URL ?>admin/profissionais.php?<?= http_build_query(array_merge($queryBase, ['pagina' => 1])) ?>"
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
                        href="<?= BASE_URL ?>admin/profissionais.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $pagina])) ?>"
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
                        href="<?= BASE_URL ?>admin/profissionais.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $totalPaginas])) ?>"
                    >
                        <?= $totalPaginas ?>
                    </a>
                <?php endif; ?>

                <a 
                    class="pagination-link <?= $paginaAtual === $totalPaginas ? 'disabled' : '' ?>"
                    href="<?= BASE_URL ?>admin/profissionais.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $proximaPagina])) ?>"
                >
                    Próxima
                </a>
            </div>
        <?php endif; ?>
    </div>
    
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>