<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$pageTitle = 'Consultas';
$pageSubtitle = 'Agendamento e acompanhamento de consultas presenciais e telemedicina';
$menuAtivo = 'consultas';

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

$where = "WHERE 1 = 1";
$params = [];

if ($busca !== '') {
    $where .= "
        AND (
            p.nome LIKE :busca_paciente
            OR pr.nome LIKE :busca_profissional
            OR pr.especialidade LIKE :busca_especialidade
            OR u.nome LIKE :busca_unidade
            OR c.status LIKE :busca_status
            OR c.tipo LIKE :busca_tipo
        )
    ";

    $termoBusca = '%' . $busca . '%';

    $params[':busca_paciente'] = $termoBusca;
    $params[':busca_profissional'] = $termoBusca;
    $params[':busca_especialidade'] = $termoBusca;
    $params[':busca_unidade'] = $termoBusca;
    $params[':busca_status'] = $termoBusca;
    $params[':busca_tipo'] = $termoBusca;
}

$stmtTotal = $pdo->prepare("
    SELECT COUNT(*)
    FROM consultas c
    INNER JOIN pacientes p ON p.id = c.paciente_id
    INNER JOIN profissionais pr ON pr.id = c.profissional_id
    LEFT JOIN unidades u ON u.id = c.unidade_id
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
        c.*,
        p.nome AS paciente_nome,
        pr.nome AS profissional_nome,
        pr.especialidade AS profissional_especialidade,
        u.nome AS unidade_nome
    FROM consultas c
    INNER JOIN pacientes p ON p.id = c.paciente_id
    INNER JOIN profissionais pr ON pr.id = c.profissional_id
    LEFT JOIN unidades u ON u.id = c.unidade_id
    {$where}
    ORDER BY c.data_consulta DESC
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

require_once __DIR__ . '/../includes/header.php';

function labelStatusConsulta($status)
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

function classeStatusConsulta($status)
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

function labelTipoConsulta($tipo)
{
    return $tipo === 'Telemedicina' ? 'Telemedicina' : 'Presencial';
}
?>

<section class="page-actions">
    <div>
        <h2>Lista de Consultas</h2>
        <p>Gerencie agendamentos presenciais e online.</p>
    </div>

    <a href="<?= BASE_URL ?>admin/consulta_form.php" class="btn btn-primary-small">
        Nova Consulta
    </a>
</section>

<?php if ($msg === 'criado'): ?>
    <div class="alert-success">Consulta agendada com sucesso.</div>
<?php elseif ($msg === 'atualizado'): ?>
    <div class="alert-success">Consulta atualizada com sucesso.</div>
<?php elseif ($msg === 'confirmada'): ?>
    <div class="alert-success">Consulta confirmada com sucesso.</div>
<?php elseif ($msg === 'cancelada'): ?>
    <div class="alert-success">Consulta cancelada com sucesso.</div>
<?php elseif ($msg === 'concluida'): ?>
    <div class="alert-success">Consulta concluída com sucesso.</div>
<?php endif; ?>

<section class="panel">
    <form method="get" class="search-form">
    <input 
        type="text" 
        name="busca" 
        placeholder="Buscar por paciente, profissional, unidade, status ou tipo"
        value="<?= e($busca) ?>"
    >

    <input type="hidden" name="por_pagina" value="<?= (int)$porPagina ?>">

    <button type="submit" class="btn btn-secondary">
        Buscar
    </button>

    <?php if ($busca !== ''): ?>
        <a href="<?= BASE_URL ?>admin/consultas.php?por_pagina=<?= (int)$porPagina ?>" class="btn btn-light">
            Limpar
        </a>
    <?php endif; ?>
</form>

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
                            Nenhuma consulta encontrada.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($consultas as $consulta): ?>
                    <tr>
                        <td>
                            <?= date('d/m/Y H:i', strtotime($consulta['data_consulta'])) ?>
                        </td>

                        <td><?= e($consulta['paciente_nome']) ?></td>

                        <td>
                            <?= e($consulta['profissional_nome']) ?>
                            <?php if (!empty($consulta['profissional_especialidade'])): ?>
                                <br>
                                <small><?= e($consulta['profissional_especialidade']) ?></small>
                            <?php endif; ?>
                        </td>

                        <td><?= e($consulta['unidade_nome'] ?: '-') ?></td>

                        <td>
                            <span class="badge badge-warning">
                                <?= e(labelTipoConsulta($consulta['tipo'])) ?>
                            </span>
                        </td>

                        <td>
                            <span class="badge <?= classeStatusConsulta($consulta['status']) ?>">
                                <?= e(labelStatusConsulta($consulta['status'])) ?>
                            </span>
                        </td>

                        <td class="text-right">
                            <?php if ($consulta['tipo'] === 'Telemedicina' && !empty($consulta['link_teleconsulta'])): ?>
                                <a 
                                    href="<?= e($consulta['link_teleconsulta']) ?>" 
                                    class="btn btn-secondary"
                                    target="_blank"
                                >
                                    Sala
                                </a>
                            <?php endif; ?>

                            <?php if ($consulta['status'] !== 'Cancelada' && $consulta['status'] !== 'Concluida'): ?>
                                <a 
                                    href="<?= BASE_URL ?>admin/consulta_form.php?id=<?= (int)$consulta['id'] ?>" 
                                    class="btn btn-light"
                                >
                                    Editar
                                </a>
                            <?php endif; ?>

                            <?php if ($consulta['status'] === 'Agendada'): ?>
                                <form 
                                    method="post" 
                                    action="<?= BASE_URL ?>admin/consulta_status.php" 
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
                                    action="<?= BASE_URL ?>admin/consulta_status.php" 
                                    class="inline-form"
                                    data-confirm-title="Concluir consulta"
                                    data-confirm="Deseja realmente concluir a consulta do paciente <?= e($consulta['paciente_nome']) ?>? Após concluir, ela ficará registrada como atendimento finalizado."
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

                                <form 
                                    method="post" 
                                    action="<?= BASE_URL ?>admin/consulta_status.php" 
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
                    href="<?= BASE_URL ?>admin/consultas.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $paginaAnterior])) ?>"
                >
                    Anterior
                </a>

                <?php if ($inicio > 1): ?>
                    <a 
                        class="pagination-link"
                        href="<?= BASE_URL ?>admin/consultas.php?<?= http_build_query(array_merge($queryBase, ['pagina' => 1])) ?>"
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
                        href="<?= BASE_URL ?>admin/consultas.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $pagina])) ?>"
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
                        href="<?= BASE_URL ?>admin/consultas.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $totalPaginas])) ?>"
                    >
                        <?= $totalPaginas ?>
                    </a>
                <?php endif; ?>

                <a 
                    class="pagination-link <?= $paginaAtual === $totalPaginas ? 'disabled' : '' ?>"
                    href="<?= BASE_URL ?>admin/consultas.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $proximaPagina])) ?>"
                >
                    Próxima
                </a>
            </div>
        <?php endif; ?>
    </div>
    
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>