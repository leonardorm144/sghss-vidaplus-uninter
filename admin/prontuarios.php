<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$pageTitle = 'Prontuários';
$pageSubtitle = 'Registro clínico dos atendimentos realizados';
$menuAtivo = 'prontuarios';

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

$where = "
    WHERE pt.ativo = 1
    AND p.ativo = 1
    AND pr.ativo = 1
";

$params = [];

if ($busca !== '') {
    $where .= "
        AND (
            p.nome LIKE :busca_paciente
            OR pr.nome LIKE :busca_profissional
            OR pr.especialidade LIKE :busca_especialidade
            OR pt.descricao LIKE :busca_descricao
            OR pt.diagnostico LIKE :busca_diagnostico
            OR pt.conduta LIKE :busca_conduta
        )
    ";

    $termoBusca = '%' . $busca . '%';

    $params[':busca_paciente'] = $termoBusca;
    $params[':busca_profissional'] = $termoBusca;
    $params[':busca_especialidade'] = $termoBusca;
    $params[':busca_descricao'] = $termoBusca;
    $params[':busca_diagnostico'] = $termoBusca;
    $params[':busca_conduta'] = $termoBusca;
}

$stmtTotal = $pdo->prepare("
    SELECT COUNT(*)
    FROM prontuarios pt
    INNER JOIN pacientes p ON p.id = pt.paciente_id
    INNER JOIN profissionais pr ON pr.id = pt.profissional_id
    LEFT JOIN consultas c ON c.id = pt.consulta_id
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
        pt.*,
        p.nome AS paciente_nome,
        pr.nome AS profissional_nome,
        pr.especialidade AS profissional_especialidade,
        c.data_consulta,
        c.tipo AS consulta_tipo,
        c.status AS consulta_status
    FROM prontuarios pt
    INNER JOIN pacientes p ON p.id = pt.paciente_id
    INNER JOIN profissionais pr ON pr.id = pt.profissional_id
    LEFT JOIN consultas c ON c.id = pt.consulta_id
    {$where}
    ORDER BY pt.criado_em DESC
    LIMIT :limite OFFSET :offset
");

foreach ($params as $chave => $valor) {
    $stmt->bindValue($chave, $valor);
}

$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$prontuarios = $stmt->fetchAll();

$registroInicial = $totalRegistros > 0 ? $offset + 1 : 0;
$registroFinal = min($offset + $porPagina, $totalRegistros);

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2>Lista de Prontuários</h2>
        <p>Consulte e gerencie os registros clínicos dos pacientes.</p>
    </div>

    <a href="<?= BASE_URL ?>admin/prontuario_form.php" class="btn btn-primary-small">
        Novo Prontuário
    </a>
</section>

<?php if ($msg === 'criado'): ?>
    <div class="alert-success">Prontuário cadastrado com sucesso.</div>
<?php elseif ($msg === 'atualizado'): ?>
    <div class="alert-success">Prontuário atualizado com sucesso.</div>
<?php elseif ($msg === 'excluido'): ?>
    <div class="alert-success">Prontuário inativado com sucesso.</div>
<?php endif; ?>

<section class="panel">
    <form method="get" class="search-form">
    <input 
        type="text" 
        name="busca" 
        placeholder="Buscar por paciente, profissional, especialidade, diagnóstico ou descrição"
        value="<?= e($busca) ?>"
    >

    <input type="hidden" name="por_pagina" value="<?= (int)$porPagina ?>">

    <button type="submit" class="btn btn-secondary">
        Buscar
    </button>

    <?php if ($busca !== ''): ?>
        <a href="<?= BASE_URL ?>admin/prontuarios.php?por_pagina=<?= (int)$porPagina ?>" class="btn btn-light">
            Limpar
        </a>
    <?php endif; ?>
</form>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Paciente</th>
                    <th>Profissional</th>
                    <th>Consulta</th>
                    <th>Diagnóstico</th>
                    <th>Conduta</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($prontuarios)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            Nenhum prontuário encontrado.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($prontuarios as $prontuario): ?>
                    <tr>
                        <td>
                            <?php if (!empty($prontuario['criado_em'])): ?>
                                <?= date('d/m/Y H:i', strtotime($prontuario['criado_em'])) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>

                        <td><?= e($prontuario['paciente_nome']) ?></td>

                        <td>
                            <?= e($prontuario['profissional_nome']) ?>

                            <?php if (!empty($prontuario['profissional_especialidade'])): ?>
                                <br>
                                <small><?= e($prontuario['profissional_especialidade']) ?></small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if (!empty($prontuario['data_consulta'])): ?>
                                <?= date('d/m/Y H:i', strtotime($prontuario['data_consulta'])) ?>
                                <br>
                                <small><?= e($prontuario['consulta_tipo']) ?> - <?= e($prontuario['consulta_status']) ?></small>
                            <?php else: ?>
                                <span class="badge badge-neutral">Sem consulta</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= e($prontuario['diagnostico'] ?: '-') ?>
                        </td>

                        <td>
                            <?= e($prontuario['conduta'] ?: '-') ?>
                        </td>

                        <td class="text-right">
                            <a 
                                href="<?= BASE_URL ?>admin/prontuario_form.php?id=<?= (int)$prontuario['id'] ?>" 
                                class="btn btn-light"
                            >
                                Editar
                            </a>

                           <form 
                                method="post" 
                                action="<?= BASE_URL ?>admin/prontuario_excluir.php" 
                                class="inline-form"
                                data-confirm-title="Inativar prontuário"
                                data-confirm="Deseja realmente inativar o prontuário do paciente <?= e($prontuario['paciente_nome']) ?>? Ele não será mais exibido nas listagens ativas."
                                data-confirm-button="Sim, inativar"
                                data-confirm-cancel="Voltar"
                                data-confirm-type="danger"
                            >
                                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                <input type="hidden" name="id" value="<?= (int)$prontuario['id'] ?>">

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
                    href="<?= BASE_URL ?>admin/prontuarios.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $paginaAnterior])) ?>"
                >
                    Anterior
                </a>

                <?php if ($inicio > 1): ?>
                    <a 
                        class="pagination-link"
                        href="<?= BASE_URL ?>admin/prontuarios.php?<?= http_build_query(array_merge($queryBase, ['pagina' => 1])) ?>"
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
                        href="<?= BASE_URL ?>admin/prontuarios.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $pagina])) ?>"
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
                        href="<?= BASE_URL ?>admin/prontuarios.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $totalPaginas])) ?>"
                    >
                        <?= $totalPaginas ?>
                    </a>
                <?php endif; ?>

                <a 
                    class="pagination-link <?= $paginaAtual === $totalPaginas ? 'disabled' : '' ?>"
                    href="<?= BASE_URL ?>admin/prontuarios.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $proximaPagina])) ?>"
                >
                    Próxima
                </a>
            </div>
        <?php endif; ?>
    </div>
    
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>