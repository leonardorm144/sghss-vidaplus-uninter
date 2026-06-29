<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$pageTitle = 'Prescrições';
$pageSubtitle = 'Emissão e gerenciamento de prescrições digitais';
$menuAtivo = 'prescricoes';

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
    WHERE ps.ativo = 1
    AND p.ativo = 1
    AND pr.ativo = 1
";

$params = [];

if ($busca !== '') {
    $where .= "
        AND (
            p.nome LIKE :busca_paciente
            OR p.cpf LIKE :busca_cpf
            OR pr.nome LIKE :busca_profissional
            OR pr.especialidade LIKE :busca_especialidade
            OR ps.medicamento LIKE :busca_medicamento
            OR ps.orientacoes LIKE :busca_orientacoes
        )
    ";

    $termoBusca = '%' . $busca . '%';

    $params[':busca_paciente'] = $termoBusca;
    $params[':busca_cpf'] = $termoBusca;
    $params[':busca_profissional'] = $termoBusca;
    $params[':busca_especialidade'] = $termoBusca;
    $params[':busca_medicamento'] = $termoBusca;
    $params[':busca_orientacoes'] = $termoBusca;
}

$stmtTotal = $pdo->prepare("
    SELECT COUNT(*)
    FROM prescricoes ps
    INNER JOIN pacientes p ON p.id = ps.paciente_id
    INNER JOIN profissionais pr ON pr.id = ps.profissional_id
    LEFT JOIN consultas c ON c.id = ps.consulta_id
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
        ps.*,
        p.nome AS paciente_nome,
        p.cpf AS paciente_cpf,
        pr.nome AS profissional_nome,
        pr.especialidade AS profissional_especialidade,
        pr.registro_profissional,
        c.data_consulta,
        c.tipo AS consulta_tipo,
        c.status AS consulta_status
    FROM prescricoes ps
    INNER JOIN pacientes p ON p.id = ps.paciente_id
    INNER JOIN profissionais pr ON pr.id = ps.profissional_id
    LEFT JOIN consultas c ON c.id = ps.consulta_id
    {$where}
    ORDER BY ps.data_emissao DESC
    LIMIT :limite OFFSET :offset
");

foreach ($params as $chave => $valor) {
    $stmt->bindValue($chave, $valor);
}

$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$prescricoes = $stmt->fetchAll();

$registroInicial = $totalRegistros > 0 ? $offset + 1 : 0;
$registroFinal = min($offset + $porPagina, $totalRegistros);

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2>Lista de Prescrições</h2>
        <p>Consulte, emita e gerencie prescrições digitais dos pacientes.</p>
    </div>

    <a href="<?= BASE_URL ?>admin/prescricao_form.php" class="btn btn-primary-small">
        Nova Prescrição
    </a>
</section>

<?php if ($msg === 'criado'): ?>
    <div class="alert-success">Prescrição cadastrada com sucesso.</div>
<?php elseif ($msg === 'atualizado'): ?>
    <div class="alert-success">Prescrição atualizada com sucesso.</div>
<?php elseif ($msg === 'excluido'): ?>
    <div class="alert-success">Prescrição inativada com sucesso.</div>
<?php endif; ?>

<section class="panel">
    <form method="get" class="search-form">
    <input 
        type="text" 
        name="busca" 
        placeholder="Buscar por paciente, CPF, profissional, medicamento ou orientação"
        value="<?= e($busca) ?>"
    >

    <input type="hidden" name="por_pagina" value="<?= (int)$porPagina ?>">

    <button type="submit" class="btn btn-secondary">
        Buscar
    </button>

    <?php if ($busca !== ''): ?>
        <a href="<?= BASE_URL ?>admin/prescricoes.php?por_pagina=<?= (int)$porPagina ?>" class="btn btn-light">
            Limpar
        </a>
    <?php endif; ?>
</form>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Emissão</th>
                    <th>Paciente</th>
                    <th>Profissional</th>
                    <th>Medicamento</th>
                    <th>Dosagem</th>
                    <th>Consulta</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($prescricoes)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            Nenhuma prescrição encontrada.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($prescricoes as $prescricao): ?>
                    <tr>
                        <td>
                            <?php if (!empty($prescricao['data_emissao'])): ?>
                                <?= date('d/m/Y H:i', strtotime($prescricao['data_emissao'])) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= e($prescricao['paciente_nome']) ?>

                            <?php if (!empty($prescricao['paciente_cpf'])): ?>
                                <br>
                                <small>CPF: <?= e($prescricao['paciente_cpf']) ?></small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= e($prescricao['profissional_nome']) ?>

                            <?php if (!empty($prescricao['profissional_especialidade'])): ?>
                                <br>
                                <small><?= e($prescricao['profissional_especialidade']) ?></small>
                            <?php endif; ?>

                            <?php if (!empty($prescricao['registro_profissional'])): ?>
                                <br>
                                <small>Registro: <?= e($prescricao['registro_profissional']) ?></small>
                            <?php endif; ?>
                        </td>

                        <td><?= e($prescricao['medicamento']) ?></td>

                        <td><?= e($prescricao['dosagem'] ?: '-') ?></td>

                        <td>
                            <?php if (!empty($prescricao['data_consulta'])): ?>
                                <?= date('d/m/Y H:i', strtotime($prescricao['data_consulta'])) ?>
                                <br>
                                <small><?= e($prescricao['consulta_tipo']) ?> - <?= e($prescricao['consulta_status']) ?></small>
                            <?php else: ?>
                                <span class="badge badge-neutral">Sem consulta</span>
                            <?php endif; ?>
                        </td>

                        <td class="text-right">
                            <a 
                                href="<?= BASE_URL ?>admin/prescricao_visualizar.php?id=<?= (int)$prescricao['id'] ?>" 
                                class="btn btn-secondary"
                            >
                                Receita
                            </a>

                            <a 
                                href="<?= BASE_URL ?>admin/prescricao_form.php?id=<?= (int)$prescricao['id'] ?>" 
                                class="btn btn-light"
                            >
                                Editar
                            </a>

                            <form 
                                method="post" 
                                action="<?= BASE_URL ?>admin/prescricao_excluir.php" 
                                class="inline-form"
                                data-confirm-title="Inativar prescrição"
                                data-confirm="Deseja realmente inativar a prescrição de <?= e($prescricao['medicamento']) ?> do paciente <?= e($prescricao['paciente_nome']) ?>? Ela não será mais exibida nas listagens ativas."
                                data-confirm-button="Sim, inativar"
                                data-confirm-cancel="Voltar"
                                data-confirm-type="danger"
                            >
                                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                <input type="hidden" name="id" value="<?= (int)$prescricao['id'] ?>">

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
                    href="<?= BASE_URL ?>admin/prescricoes.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $paginaAnterior])) ?>"
                >
                    Anterior
                </a>

                <?php if ($inicio > 1): ?>
                    <a 
                        class="pagination-link"
                        href="<?= BASE_URL ?>admin/prescricoes.php?<?= http_build_query(array_merge($queryBase, ['pagina' => 1])) ?>"
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
                        href="<?= BASE_URL ?>admin/prescricoes.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $pagina])) ?>"
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
                        href="<?= BASE_URL ?>admin/prescricoes.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $totalPaginas])) ?>"
                    >
                        <?= $totalPaginas ?>
                    </a>
                <?php endif; ?>

                <a 
                    class="pagination-link <?= $paginaAtual === $totalPaginas ? 'disabled' : '' ?>"
                    href="<?= BASE_URL ?>admin/prescricoes.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $proximaPagina])) ?>"
                >
                    Próxima
                </a>
            </div>
        <?php endif; ?>
    </div>
    
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>