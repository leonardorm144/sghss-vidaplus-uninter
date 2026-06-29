<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$pageTitle = 'Auditoria';
$pageSubtitle = 'Logs de segurança, acessos e alterações do sistema';
$menuAtivo = 'auditoria';

$busca = trim($_GET['busca'] ?? '');
$usuarioFiltro = isset($_GET['usuario_id']) && $_GET['usuario_id'] !== '' ? (int)$_GET['usuario_id'] : null;
$tabelaFiltro = trim($_GET['tabela'] ?? '');
$dataInicio = trim($_GET['data_inicio'] ?? '');
$dataFim = trim($_GET['data_fim'] ?? '');

$opcoesPorPagina = [25, 50, 75, 100];

$porPagina = isset($_GET['por_pagina']) ? (int)$_GET['por_pagina'] : 25;

if (!in_array($porPagina, $opcoesPorPagina)) {
    $porPagina = 25;
}

$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

if ($paginaAtual < 1) {
    $paginaAtual = 1;
}

$where = [];
$params = [];

if ($busca !== '') {
    $where[] = "(
        al.acao LIKE :busca_acao
        OR al.detalhes LIKE :busca_detalhes
        OR al.ip LIKE :busca_ip
        OR u.nome LIKE :busca_usuario
        OR u.email LIKE :busca_email
    )";

    $termoBusca = '%' . $busca . '%';

    $params[':busca_acao'] = $termoBusca;
    $params[':busca_detalhes'] = $termoBusca;
    $params[':busca_ip'] = $termoBusca;
    $params[':busca_usuario'] = $termoBusca;
    $params[':busca_email'] = $termoBusca;
}

if ($usuarioFiltro !== null) {
    $where[] = "al.usuario_id = :usuario_id";
    $params[':usuario_id'] = $usuarioFiltro;
}

if ($tabelaFiltro !== '') {
    $where[] = "al.tabela_afetada = :tabela_afetada";
    $params[':tabela_afetada'] = $tabelaFiltro;
}

if ($dataInicio !== '') {
    $where[] = "DATE(al.criado_em) >= :data_inicio";
    $params[':data_inicio'] = $dataInicio;
}

if ($dataFim !== '') {
    $where[] = "DATE(al.criado_em) <= :data_fim";
    $params[':data_fim'] = $dataFim;
}

$whereSql = '';

if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

$stmtTotal = $pdo->prepare("
    SELECT COUNT(*)
    FROM auditoria_logs al
    LEFT JOIN usuarios u ON u.id = al.usuario_id
    {$whereSql}
");

$stmtTotal->execute($params);

$totalRegistros = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, (int)ceil($totalRegistros / $porPagina));

if ($paginaAtual > $totalPaginas) {
    $paginaAtual = $totalPaginas;
}

$offset = ($paginaAtual - 1) * $porPagina;

$sql = "
    SELECT 
        al.*,
        u.nome AS usuario_nome,
        u.email AS usuario_email,
        u.perfil AS usuario_perfil
    FROM auditoria_logs al
    LEFT JOIN usuarios u ON u.id = al.usuario_id
    {$whereSql}
    ORDER BY al.criado_em DESC
    LIMIT :limite OFFSET :offset
";

$stmt = $pdo->prepare($sql);

foreach ($params as $chave => $valor) {
    $stmt->bindValue($chave, $valor);
}

$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$logs = $stmt->fetchAll();

$registroInicial = $totalRegistros > 0 ? $offset + 1 : 0;
$registroFinal = min($offset + $porPagina, $totalRegistros);

$stmtUsuarios = $pdo->query("
    SELECT id, nome, email
    FROM usuarios
    ORDER BY nome ASC
");

$usuarios = $stmtUsuarios->fetchAll();
$usuarioFiltroTexto = '';

if ($usuarioFiltro !== null) {
    foreach ($usuarios as $usuario) {
        if ((int)$usuario['id'] === $usuarioFiltro) {
            $usuarioFiltroTexto = $usuario['nome'] . ' - ' . $usuario['email'];
            break;
        }
    }
}

$stmtTabelas = $pdo->query("
    SELECT DISTINCT tabela_afetada
    FROM auditoria_logs
    WHERE tabela_afetada IS NOT NULL
    AND tabela_afetada <> ''
    ORDER BY tabela_afetada ASC
");

$tabelas = $stmtTabelas->fetchAll();

function labelAcaoAuditoria($acao)
{
    $mapa = [
        'LOGIN_SUCESSO' => 'Login realizado',
        'LOGIN_FALHOU' => 'Falha no login',
        'LOGOUT' => 'Logout',

        'PACIENTE_CRIADO' => 'Paciente criado',
        'PACIENTE_ATUALIZADO' => 'Paciente atualizado',
        'PACIENTE_INATIVADO' => 'Paciente inativado',

        'PROFISSIONAL_CRIADO' => 'Profissional criado',
        'PROFISSIONAL_ATUALIZADO' => 'Profissional atualizado',
        'PROFISSIONAL_INATIVADO' => 'Profissional inativado',

        'UNIDADE_CRIADA' => 'Unidade criada',
        'UNIDADE_ATUALIZADA' => 'Unidade atualizada',
        'UNIDADE_INATIVADA' => 'Unidade inativada',

        'LEITO_CRIADO' => 'Leito criado',
        'LEITO_ATUALIZADO' => 'Leito atualizado',
        'LEITO_INATIVADO' => 'Leito inativado',

        'CONSULTA_CRIADA' => 'Consulta criada',
        'CONSULTA_ATUALIZADA' => 'Consulta atualizada',
        'CONSULTA_CONFIRMADA' => 'Consulta confirmada',
        'CONSULTA_CANCELADA' => 'Consulta cancelada',
        'CONSULTA_CONCLUIDA' => 'Consulta concluída',

        'PRONTUARIO_CRIADO' => 'Prontuário criado',
        'PRONTUARIO_ATUALIZADO' => 'Prontuário atualizado',
        'PRONTUARIO_INATIVADO' => 'Prontuário inativado',

        'PRESCRICAO_CRIADA' => 'Prescrição criada',
        'PRESCRICAO_ATUALIZADA' => 'Prescrição atualizada',
        'PRESCRICAO_INATIVADA' => 'Prescrição inativada',

        'USUARIO_CRIADO' => 'Usuário criado',
        'USUARIO_ATUALIZADO' => 'Usuário atualizado',
        'USUARIO_INATIVADO' => 'Usuário inativado',
        'USUARIO_REATIVADO' => 'Usuário reativado',
        
        'CONSULTA_CONFIRMADA_PROFISSIONAL' => 'Consulta confirmada pelo profissional',
        'CONSULTA_CONCLUIDA_PROFISSIONAL' => 'Consulta concluída pelo profissional',

        'PRONTUARIO_CRIADO_PROFISSIONAL' => 'Prontuário criado pelo profissional',
        'PRONTUARIO_ATUALIZADO_PROFISSIONAL' => 'Prontuário atualizado pelo profissional',
        'PRONTUARIO_INATIVADO_PROFISSIONAL' => 'Prontuário inativado pelo profissional',

        'PRESCRICAO_CRIADA_PROFISSIONAL' => 'Prescrição criada pelo profissional',
        'PRESCRICAO_ATUALIZADA_PROFISSIONAL' => 'Prescrição atualizada pelo profissional',
        'PRESCRICAO_INATIVADA_PROFISSIONAL' => 'Prescrição inativada pelo profissional',
        
        'CONSULTA_CANCELADA_PACIENTE' => 'Consulta cancelada pelo paciente',
        
        'AGENDAMENTO_CRIADO_RECEPCAO' => 'Agendamento criado pela recepção',
        'AGENDAMENTO_ATUALIZADO_RECEPCAO' => 'Agendamento atualizado pela recepção',
        'AGENDAMENTO_CONFIRMADO_RECEPCAO' => 'Agendamento confirmado pela recepção',
        'AGENDAMENTO_CANCELADO_RECEPCAO' => 'Agendamento cancelado pela recepção',
        
        'PACIENTE_CRIADO_RECEPCAO' => 'Paciente criado pela recepção',
        'PACIENTE_ATUALIZADO_RECEPCAO' => 'Paciente atualizado pela recepção',
        'PACIENTE_INATIVADO_RECEPCAO' => 'Paciente inativado pela recepção',
        
        'EXAME_CRIADO_RECEPCAO' => 'Exame criado pela recepção',
        'EXAME_ATUALIZADO_RECEPCAO' => 'Exame atualizado pela recepção',
        'EXAME_REALIZADO_RECEPCAO' => 'Exame realizado pela recepção',
        'EXAME_CANCELADO_RECEPCAO' => 'Exame cancelado pela recepção',
        'EXAME_INATIVADO_RECEPCAO' => 'Exame inativado pela recepção',
        
        'INTERNACAO_CRIADA' => 'Internação criada',
        'INTERNACAO_ATUALIZADA' => 'Internação atualizada',
        'INTERNACAO_ALTA' => 'Alta de internação',
        'INTERNACAO_CANCELADA' => 'Internação cancelada',
        
    ];

    return $mapa[$acao] ?? $acao;
}

function classeAcaoAuditoria($acao)
{
    if (strpos($acao, 'LOGIN_FALHOU') !== false) {
        return 'badge-danger';
    }

    if (strpos($acao, 'CRIADO') !== false || strpos($acao, 'CRIADA') !== false || strpos($acao, 'LOGIN_SUCESSO') !== false || strpos($acao, 'REATIVADO') !== false) {
        return 'badge-success';
    }

    if (strpos($acao, 'ATUALIZADO') !== false || strpos($acao, 'ATUALIZADA') !== false || strpos($acao, 'CONFIRMADA') !== false || strpos($acao, 'CONCLUIDA') !== false) {
        return 'badge-info';
    }

    if (strpos($acao, 'INATIVADO') !== false || strpos($acao, 'INATIVADA') !== false || strpos($acao, 'CANCELADA') !== false || strpos($acao, 'LOGOUT') !== false) {
        return 'badge-warning';
    }

    return 'badge-neutral';
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2>Logs de Auditoria</h2>
        <p>Monitore acessos, alterações e ações sensíveis realizadas no sistema.</p>
    </div>
</section>

<section class="panel">
    <form method="get" class="audit-filter-form">
        <input type="hidden" name="por_pagina" value="<?= (int)$porPagina ?>">
        
        <div class="audit-filter-grid">
            <div class="form-group">
                <label for="busca">Busca geral</label>
                <input 
                    type="text" 
                    id="busca"
                    name="busca" 
                    placeholder="Ação, detalhes, IP, usuário ou e-mail"
                    value="<?= e($busca) ?>"
                >
            </div>

            <div class="form-group">
                <label for="usuario_busca">Usuário</label>

                <input
                    type="text"
                    id="usuario_busca"
                    name="usuario_busca"
                    list="usuarios_sugestoes"
                    placeholder="Digite nome ou e-mail do usuário"
                    value="<?= e($usuarioFiltroTexto) ?>"
                    autocomplete="off"
                >

                <input 
                    type="hidden" 
                    id="usuario_id" 
                    name="usuario_id" 
                    value="<?= $usuarioFiltro !== null ? (int)$usuarioFiltro : '' ?>"
                >

                <datalist id="usuarios_sugestoes">
                    <?php foreach ($usuarios as $usuario): ?>
                        <option value="<?= e($usuario['nome'] . ' - ' . $usuario['email']) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div class="form-group">
                <label for="tabela">Tabela</label>
                <select id="tabela" name="tabela">
                    <option value="">Todas</option>

                    <?php foreach ($tabelas as $tabela): ?>
                        <option 
                            value="<?= e($tabela['tabela_afetada']) ?>"
                            <?= $tabelaFiltro === $tabela['tabela_afetada'] ? 'selected' : '' ?>
                        >
                            <?= e($tabela['tabela_afetada']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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
        </div>

        <div class="form-actions audit-actions">
            <a href="<?= BASE_URL ?>admin/auditoria.php" class="btn btn-light">
                Limpar filtros
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
            <h2>Registros Encontrados</h2>
            <p>
                Exibindo 
                <strong><?= $registroInicial ?></strong>
                até
                <strong><?= $registroFinal ?></strong>
                de
                <strong><?= $totalRegistros ?></strong>
                registros conforme os filtros aplicados.
            </p>
        </div>

        <span class="audit-count">
            <?= $totalRegistros ?>
        </span>
    </div>

    <div class="table-responsive">
        <table class="data-table audit-table">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Usuário</th>
                    <th>Ação</th>
                    <th>Tabela</th>
                    <th>Registro</th>
                    <th>IP</th>
                    <th>Detalhes</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            Nenhum log encontrado.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td>
                            <?php if (!empty($log['criado_em'])): ?>
                                <?= date('d/m/Y H:i:s', strtotime($log['criado_em'])) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if (!empty($log['usuario_nome'])): ?>
                                <?= e($log['usuario_nome']) ?>
                                <br>
                                <small><?= e($log['usuario_email']) ?></small>
                            <?php else: ?>
                                <span class="badge badge-neutral">Não identificado</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <span class="badge <?= classeAcaoAuditoria($log['acao']) ?>">
                                <?= e(labelAcaoAuditoria($log['acao'])) ?>
                            </span>
                            <br>
                            <small><?= e($log['acao']) ?></small>
                        </td>

                        <td><?= e($log['tabela_afetada'] ?: '-') ?></td>

                        <td>
                            <?= !empty($log['registro_id']) ? (int)$log['registro_id'] : '-' ?>
                        </td>

                        <td><?= e($log['ip'] ?: '-') ?></td>

                        <td class="audit-details">
                            <?= e($log['detalhes'] ?: '-') ?>
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

            <?php if ($usuarioFiltro !== null): ?>
                <input type="hidden" name="usuario_id" value="<?= (int)$usuarioFiltro ?>">
            <?php endif; ?>

            <?php if ($tabelaFiltro !== ''): ?>
                <input type="hidden" name="tabela" value="<?= e($tabelaFiltro) ?>">
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
                        'usuario_id' => $usuarioFiltro ?? '',
                        'tabela' => $tabelaFiltro,
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
                    href="<?= BASE_URL ?>admin/auditoria.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $paginaAnterior])) ?>"
                >
                    Anterior
                </a>

                <?php if ($inicio > 1): ?>
                    <a 
                        class="pagination-link"
                        href="<?= BASE_URL ?>admin/auditoria.php?<?= http_build_query(array_merge($queryBase, ['pagina' => 1])) ?>"
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
                        href="<?= BASE_URL ?>admin/auditoria.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $pagina])) ?>"
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
                        href="<?= BASE_URL ?>admin/auditoria.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $totalPaginas])) ?>"
                    >
                        <?= $totalPaginas ?>
                    </a>
                <?php endif; ?>

                <a 
                    class="pagination-link <?= $paginaAtual === $totalPaginas ? 'disabled' : '' ?>"
                    href="<?= BASE_URL ?>admin/auditoria.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $proximaPagina])) ?>"
                >
                    Próxima
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const campoUsuario = document.getElementById('usuario_busca');
    const campoUsuarioId = document.getElementById('usuario_id');

    if (!campoUsuario || !campoUsuarioId) {
        return;
    }

    const usuarios = <?= json_encode(array_map(function ($usuario) {
        return [
            'id' => (int)$usuario['id'],
            'texto' => $usuario['nome'] . ' - ' . $usuario['email']
        ];
    }, $usuarios), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function atualizarUsuarioSelecionado() {
        const valorDigitado = campoUsuario.value.trim();

        const usuarioEncontrado = usuarios.find(function (usuario) {
            return usuario.texto === valorDigitado;
        });

        campoUsuarioId.value = usuarioEncontrado ? usuarioEncontrado.id : '';
    }

    campoUsuario.addEventListener('input', atualizarUsuarioSelecionado);
    campoUsuario.addEventListener('change', atualizarUsuarioSelecionado);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>