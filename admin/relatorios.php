<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$pageTitle = 'Relatórios';
$pageSubtitle = 'Indicadores administrativos e assistenciais do SGHSS VidaPlus';
$menuAtivo = 'relatorios';

$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
$dataFim = $_GET['data_fim'] ?? date('Y-m-d');

if ($dataInicio === '') {
    $dataInicio = date('Y-m-01');
}

if ($dataFim === '') {
    $dataFim = date('Y-m-d');
}

$inicioSql = $dataInicio . ' 00:00:00';
$fimSql = $dataFim . ' 23:59:59';

function contarPeriodo($pdo, $sql, $inicioSql, $fimSql)
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':inicio' => $inicioSql,
        ':fim' => $fimSql
    ]);

    return (int)$stmt->fetchColumn();
}

$totalConsultas = contarPeriodo($pdo, "
    SELECT COUNT(*)
    FROM consultas
    WHERE data_consulta BETWEEN :inicio AND :fim
", $inicioSql, $fimSql);

$totalPresenciais = contarPeriodo($pdo, "
    SELECT COUNT(*)
    FROM consultas
    WHERE data_consulta BETWEEN :inicio AND :fim
    AND tipo = 'Presencial'
", $inicioSql, $fimSql);

$totalTeleconsultas = contarPeriodo($pdo, "
    SELECT COUNT(*)
    FROM consultas
    WHERE data_consulta BETWEEN :inicio AND :fim
    AND tipo = 'Telemedicina'
", $inicioSql, $fimSql);

$totalConcluidas = contarPeriodo($pdo, "
    SELECT COUNT(*)
    FROM consultas
    WHERE data_consulta BETWEEN :inicio AND :fim
    AND status = 'Concluida'
", $inicioSql, $fimSql);

$totalPacientesNovos = contarPeriodo($pdo, "
    SELECT COUNT(*)
    FROM pacientes
    WHERE criado_em BETWEEN :inicio AND :fim
    AND ativo = 1
", $inicioSql, $fimSql);

$totalProntuarios = contarPeriodo($pdo, "
    SELECT COUNT(*)
    FROM prontuarios
    WHERE criado_em BETWEEN :inicio AND :fim
    AND ativo = 1
", $inicioSql, $fimSql);

$totalPrescricoes = contarPeriodo($pdo, "
    SELECT COUNT(*)
    FROM prescricoes
    WHERE data_emissao BETWEEN :inicio AND :fim
    AND ativo = 1
", $inicioSql, $fimSql);

$totalExames = contarPeriodo($pdo, "
    SELECT COUNT(*)
    FROM exames
    WHERE ativo = 1
    AND COALESCE(data_exame, criado_em) BETWEEN :inicio AND :fim
", $inicioSql, $fimSql);

$totalExamesSolicitados = contarPeriodo($pdo, "
    SELECT COUNT(*)
    FROM exames
    WHERE ativo = 1
    AND status = 'Solicitado'
    AND COALESCE(data_exame, criado_em) BETWEEN :inicio AND :fim
", $inicioSql, $fimSql);

$totalExamesAgendados = contarPeriodo($pdo, "
    SELECT COUNT(*)
    FROM exames
    WHERE ativo = 1
    AND status = 'Agendado'
    AND COALESCE(data_exame, criado_em) BETWEEN :inicio AND :fim
", $inicioSql, $fimSql);

$totalExamesRealizados = contarPeriodo($pdo, "
    SELECT COUNT(*)
    FROM exames
    WHERE ativo = 1
    AND status = 'Realizado'
    AND COALESCE(data_exame, criado_em) BETWEEN :inicio AND :fim
", $inicioSql, $fimSql);

$totalExamesCancelados = contarPeriodo($pdo, "
    SELECT COUNT(*)
    FROM exames
    WHERE ativo = 1
    AND status = 'Cancelado'
    AND COALESCE(data_exame, criado_em) BETWEEN :inicio AND :fim
", $inicioSql, $fimSql);

$totalInternacoesPeriodo = contarPeriodo($pdo, "
    SELECT COUNT(*)
    FROM internacoes
    WHERE ativo = 1
    AND data_entrada BETWEEN :inicio AND :fim
", $inicioSql, $fimSql);

$totalInternacoesAtivasPeriodo = contarPeriodo($pdo, "
    SELECT COUNT(*)
    FROM internacoes
    WHERE ativo = 1
    AND status = 'Ativa'
    AND data_entrada BETWEEN :inicio AND :fim
", $inicioSql, $fimSql);

$totalAltasPeriodo = contarPeriodo($pdo, "
    SELECT COUNT(*)
    FROM internacoes
    WHERE ativo = 1
    AND status = 'Alta'
    AND data_alta BETWEEN :inicio AND :fim
", $inicioSql, $fimSql);

$totalInternacoesCanceladas = contarPeriodo($pdo, "
    SELECT COUNT(*)
    FROM internacoes
    WHERE ativo = 1
    AND status = 'Cancelada'
    AND COALESCE(data_alta, atualizado_em, data_entrada) BETWEEN :inicio AND :fim
", $inicioSql, $fimSql);

$totalLeitos = (int)$pdo->query("
    SELECT COUNT(*)
    FROM leitos
    WHERE ativo = 1
")->fetchColumn();

$totalLeitosDisponiveis = (int)$pdo->query("
    SELECT COUNT(*)
    FROM leitos
    WHERE ativo = 1
    AND status = 'Disponivel'
")->fetchColumn();

$totalLeitosOcupados = (int)$pdo->query("
    SELECT COUNT(*)
    FROM leitos
    WHERE ativo = 1
    AND status = 'Ocupado'
")->fetchColumn();

$totalLeitosManutencao = (int)$pdo->query("
    SELECT COUNT(*)
    FROM leitos
    WHERE ativo = 1
    AND status = 'Manutencao'
")->fetchColumn();

$stmtStatusConsultas = $pdo->prepare("
    SELECT status, COUNT(*) AS total
    FROM consultas
    WHERE data_consulta BETWEEN :inicio AND :fim
    GROUP BY status
");

$stmtStatusConsultas->execute([
    ':inicio' => $inicioSql,
    ':fim' => $fimSql
]);

$statusConsultas = [
    'Agendada' => 0,
    'Confirmada' => 0,
    'Cancelada' => 0,
    'Concluida' => 0
];

foreach ($stmtStatusConsultas->fetchAll() as $linha) {
    $statusConsultas[$linha['status']] = (int)$linha['total'];
}

$stmtTiposConsultas = $pdo->prepare("
    SELECT tipo, COUNT(*) AS total
    FROM consultas
    WHERE data_consulta BETWEEN :inicio AND :fim
    GROUP BY tipo
");

$stmtTiposConsultas->execute([
    ':inicio' => $inicioSql,
    ':fim' => $fimSql
]);

$tiposConsultas = [
    'Presencial' => 0,
    'Telemedicina' => 0
];

foreach ($stmtTiposConsultas->fetchAll() as $linha) {
    $tiposConsultas[$linha['tipo']] = (int)$linha['total'];
}

$stmtStatusExames = $pdo->prepare("
    SELECT status, COUNT(*) AS total
    FROM exames
    WHERE ativo = 1
    AND COALESCE(data_exame, criado_em) BETWEEN :inicio AND :fim
    GROUP BY status
");

$stmtStatusExames->execute([
    ':inicio' => $inicioSql,
    ':fim' => $fimSql
]);

$statusExames = [
    'Solicitado' => 0,
    'Agendado' => 0,
    'Realizado' => 0,
    'Cancelado' => 0
];

foreach ($stmtStatusExames->fetchAll() as $linha) {
    $statusExames[$linha['status']] = (int)$linha['total'];
}

$stmtStatusInternacoes = $pdo->prepare("
    SELECT status, COUNT(*) AS total
    FROM internacoes
    WHERE ativo = 1
    AND (
        data_entrada BETWEEN :inicio_entrada AND :fim_entrada
        OR data_alta BETWEEN :inicio_alta AND :fim_alta
    )
    GROUP BY status
");

$stmtStatusInternacoes->execute([
    ':inicio_entrada' => $inicioSql,
    ':fim_entrada' => $fimSql,
    ':inicio_alta' => $inicioSql,
    ':fim_alta' => $fimSql
]);

$statusInternacoes = [
    'Ativa' => 0,
    'Alta' => 0,
    'Cancelada' => 0
];

foreach ($stmtStatusInternacoes->fetchAll() as $linha) {
    $statusInternacoes[$linha['status']] = (int)$linha['total'];
}

$stmtConsultasUnidade = $pdo->prepare("
    SELECT 
        COALESCE(u.nome, 'Sem unidade') AS unidade_nome,
        COUNT(*) AS total
    FROM consultas c
    LEFT JOIN unidades u ON u.id = c.unidade_id
    WHERE c.data_consulta BETWEEN :inicio AND :fim
    GROUP BY u.nome
    ORDER BY total DESC
    LIMIT 8
");

$stmtConsultasUnidade->execute([
    ':inicio' => $inicioSql,
    ':fim' => $fimSql
]);

$consultasPorUnidade = $stmtConsultasUnidade->fetchAll();

$stmtUltimasConsultas = $pdo->prepare("
    SELECT
        c.data_consulta,
        c.tipo,
        c.status,
        p.nome AS paciente_nome,
        pr.nome AS profissional_nome,
        u.nome AS unidade_nome
    FROM consultas c
    INNER JOIN pacientes p ON p.id = c.paciente_id
    INNER JOIN profissionais pr ON pr.id = c.profissional_id
    LEFT JOIN unidades u ON u.id = c.unidade_id
    WHERE c.data_consulta BETWEEN :inicio AND :fim
    ORDER BY c.data_consulta DESC
    LIMIT 10
");

$stmtUltimasConsultas->execute([
    ':inicio' => $inicioSql,
    ':fim' => $fimSql
]);

$ultimasConsultas = $stmtUltimasConsultas->fetchAll();

function labelStatusRelatorio($status)
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

function classeStatusRelatorio($status)
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

$labelsUnidades = [];
$valoresUnidades = [];

foreach ($consultasPorUnidade as $linha) {
    $labelsUnidades[] = $linha['unidade_nome'];
    $valoresUnidades[] = (int)$linha['total'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2>Relatórios Administrativos</h2>
        <p>Indicadores do período de <?= date('d/m/Y', strtotime($dataInicio)) ?> até <?= date('d/m/Y', strtotime($dataFim)) ?>.</p>
    </div>
</section>

<section class="panel">
    <form method="get" class="report-filter-form">
        <div class="report-filter-grid">
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

        <div class="form-actions report-actions">
            <a href="<?= BASE_URL ?>admin/relatorios.php" class="btn btn-light">
                Mês atual
            </a>

            <button type="submit" class="btn btn-secondary">
                Filtrar
            </button>

            <button type="button" class="btn btn-primary-small" onclick="window.print()">
                Imprimir
            </button>
        </div>
    </form>
</section>

<section class="cards-grid">
    <div class="card">
        <span class="card-label">Consultas</span>
        <strong><?= $totalConsultas ?></strong>
        <p>Total de consultas no período</p>
    </div>

    <div class="card">
        <span class="card-label">Presenciais</span>
        <strong><?= $totalPresenciais ?></strong>
        <p>Consultas presenciais</p>
    </div>

    <div class="card">
        <span class="card-label">Teleconsultas</span>
        <strong><?= $totalTeleconsultas ?></strong>
        <p>Atendimentos online</p>
    </div>

    <div class="card">
        <span class="card-label">Concluídas</span>
        <strong><?= $totalConcluidas ?></strong>
        <p>Consultas finalizadas</p>
    </div>
</section>

<section class="cards-grid">
    <div class="card">
        <span class="card-label">Novos Pacientes</span>
        <strong><?= $totalPacientesNovos ?></strong>
        <p>Pacientes cadastrados no período</p>
    </div>

    <div class="card">
        <span class="card-label">Prontuários</span>
        <strong><?= $totalProntuarios ?></strong>
        <p>Registros clínicos criados</p>
    </div>

    <div class="card">
        <span class="card-label">Prescrições</span>
        <strong><?= $totalPrescricoes ?></strong>
        <p>Receitas digitais emitidas</p>
    </div>

    <div class="card">
        <span class="card-label">Leitos Ativos</span>
        <strong><?= $totalLeitos ?></strong>
        <p>Total de leitos cadastrados</p>
    </div>
</section>

<section class="cards-grid">
    <div class="card">
        <span class="card-label">Exames</span>
        <strong><?= $totalExames ?></strong>
        <p>Total de exames no período</p>
    </div>

    <div class="card">
        <span class="card-label">Solicitados</span>
        <strong><?= $totalExamesSolicitados ?></strong>
        <p>Exames aguardando agendamento</p>
    </div>

    <div class="card">
        <span class="card-label">Agendados</span>
        <strong><?= $totalExamesAgendados ?></strong>
        <p>Exames com data definida</p>
    </div>

    <div class="card">
        <span class="card-label">Realizados</span>
        <strong><?= $totalExamesRealizados ?></strong>
        <p>Exames finalizados</p>
    </div>
</section>

<section class="cards-grid">
    <div class="card">
        <span class="card-label">Internações</span>
        <strong><?= $totalInternacoesPeriodo ?></strong>
        <p>Internações abertas no período</p>
    </div>

    <div class="card">
        <span class="card-label">Ativas</span>
        <strong><?= $totalInternacoesAtivasPeriodo ?></strong>
        <p>Internações ainda em andamento</p>
    </div>

    <div class="card">
        <span class="card-label">Altas</span>
        <strong><?= $totalAltasPeriodo ?></strong>
        <p>Altas registradas no período</p>
    </div>

    <div class="card">
        <span class="card-label">Canceladas</span>
        <strong><?= $totalInternacoesCanceladas ?></strong>
        <p>Internações canceladas no período</p>
    </div>
</section>

        </section>

<section class="report-grid">
    <div class="panel">
        <h2>Consultas por Status</h2>
        <p class="panel-subtitle">Distribuição das consultas conforme situação no período.</p>

        <div class="chart-box">
            <canvas id="graficoStatus"></canvas>
        </div>
    </div>

    <div class="panel">
        <h2>Consultas por Tipo</h2>
        <p class="panel-subtitle">Comparativo entre presencial e telemedicina.</p>

        <div class="chart-box">
            <canvas id="graficoTipo"></canvas>
        </div>
    </div>
</section>

<section class="report-grid">
    <div class="panel">
        <h2>Exames por Status</h2>
        <p class="panel-subtitle">Distribuição dos exames conforme situação no período.</p>

        <div class="chart-box">
            <canvas id="graficoExames"></canvas>
        </div>
    </div>

    <div class="panel">
        <h2>Resumo de Exames</h2>
        <p class="panel-subtitle">Indicadores operacionais dos exames cadastrados.</p>

        <div class="status-list">
            <div class="status-item">
                <div>
                    <strong>Solicitados</strong>
                    <span>Exames aguardando definição</span>
                </div>

                <span class="status-number status-blue">
                    <?= $totalExamesSolicitados ?>
                </span>
            </div>

            <div class="status-item">
                <div>
                    <strong>Agendados</strong>
                    <span>Exames com data marcada</span>
                </div>

                <span class="status-number status-yellow">
                    <?= $totalExamesAgendados ?>
                </span>
            </div>

            <div class="status-item">
                <div>
                    <strong>Realizados</strong>
                    <span>Exames concluídos</span>
                </div>

                <span class="status-number status-green">
                    <?= $totalExamesRealizados ?>
                </span>
            </div>

            <div class="status-item">
                <div>
                    <strong>Cancelados</strong>
                    <span>Exames cancelados no período</span>
                </div>

                <span class="status-number status-red">
                    <?= $totalExamesCancelados ?>
                </span>
            </div>
        </div>
    </div>
</section>

<section class="report-grid">
    <div class="panel">
        <h2>Internações por Status</h2>
        <p class="panel-subtitle">Distribuição das internações conforme situação no período.</p>

        <div class="chart-box">
            <canvas id="graficoInternacoes"></canvas>
        </div>
    </div>

    <div class="panel">
        <h2>Resumo Hospitalar</h2>
        <p class="panel-subtitle">Indicadores de internação e ocupação de leitos.</p>

        <div class="status-list">
            <div class="status-item">
                <div>
                    <strong>Internações Ativas</strong>
                    <span>Pacientes internados atualmente</span>
                </div>

                <span class="status-number status-yellow">
                    <?= $totalInternacoesAtivasPeriodo ?>
                </span>
            </div>

            <div class="status-item">
                <div>
                    <strong>Altas</strong>
                    <span>Altas registradas no período</span>
                </div>

                <span class="status-number status-green">
                    <?= $totalAltasPeriodo ?>
                </span>
            </div>

            <div class="status-item">
                <div>
                    <strong>Canceladas</strong>
                    <span>Internações canceladas no período</span>
                </div>

                <span class="status-number status-red">
                    <?= $totalInternacoesCanceladas ?>
                </span>
            </div>

            <div class="status-item">
                <div>
                    <strong>Leitos Ocupados</strong>
                    <span>Ocupação atual dos leitos</span>
                </div>

                <span class="status-number status-blue">
                    <?= $totalLeitosOcupados ?>
                </span>
            </div>
        </div>
    </div>
</section>


<section class="report-grid">
    <div class="panel">
        <h2>Consultas por Unidade</h2>
        <p class="panel-subtitle">Unidades com maior quantidade de consultas no período.</p>

        <div class="chart-box">
            <canvas id="graficoUnidades"></canvas>
        </div>
    </div>

    <div class="panel">
        <h2>Status Atual dos Leitos</h2>
        <p class="panel-subtitle">Resumo da disponibilidade hospitalar no momento.</p>

        <div class="status-list">
            <div class="status-item">
                <div>
                    <strong>Disponíveis</strong>
                    <span>Leitos livres para uso</span>
                </div>

                <span class="status-number status-green">
                    <?= $totalLeitosDisponiveis ?>
                </span>
            </div>

            <div class="status-item">
                <div>
                    <strong>Ocupados</strong>
                    <span>Leitos em utilização</span>
                </div>

                <span class="status-number status-yellow">
                    <?= $totalLeitosOcupados ?>
                </span>
            </div>

            <div class="status-item">
                <div>
                    <strong>Manutenção</strong>
                    <span>Leitos indisponíveis</span>
                </div>

                <span class="status-number status-red">
                    <?= $totalLeitosManutencao ?>
                </span>
            </div>
        </div>
    </div>
</section>

<section class="panel">
    <h2>Últimas Consultas do Período</h2>
    <p class="panel-subtitle">Lista das consultas mais recentes dentro do intervalo selecionado.</p>

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
                </tr>
            </thead>

            <tbody>
                <?php if (empty($ultimasConsultas)): ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            Nenhuma consulta encontrada neste período.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($ultimasConsultas as $consulta): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($consulta['data_consulta'])) ?></td>
                        <td><?= e($consulta['paciente_nome']) ?></td>
                        <td><?= e($consulta['profissional_nome']) ?></td>
                        <td><?= e($consulta['unidade_nome'] ?: '-') ?></td>
                        <td><?= e($consulta['tipo']) ?></td>
                        <td>
                            <span class="badge <?= classeStatusRelatorio($consulta['status']) ?>">
                                <?= e(labelStatusRelatorio($consulta['status'])) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const statusLabels = ['Agendada', 'Confirmada', 'Cancelada', 'Concluída'];
const statusValores = [
    <?= (int)$statusConsultas['Agendada'] ?>,
    <?= (int)$statusConsultas['Confirmada'] ?>,
    <?= (int)$statusConsultas['Cancelada'] ?>,
    <?= (int)$statusConsultas['Concluida'] ?>
];

const tipoLabels = ['Presencial', 'Telemedicina'];
const tipoValores = [
    <?= (int)$tiposConsultas['Presencial'] ?>,
    <?= (int)$tiposConsultas['Telemedicina'] ?>
];

const unidadeLabels = <?= json_encode($labelsUnidades, JSON_UNESCAPED_UNICODE) ?>;
const unidadeValores = <?= json_encode($valoresUnidades, JSON_UNESCAPED_UNICODE) ?>;
const exameLabels = ['Solicitado', 'Agendado', 'Realizado', 'Cancelado'];
const exameValores = [
    <?= (int)$statusExames['Solicitado'] ?>,
    <?= (int)$statusExames['Agendado'] ?>,
    <?= (int)$statusExames['Realizado'] ?>,
    <?= (int)$statusExames['Cancelado'] ?>
];
const internacaoLabels = ['Ativa', 'Alta', 'Cancelada'];
const internacaoValores = [
    <?= (int)$statusInternacoes['Ativa'] ?>,
    <?= (int)$statusInternacoes['Alta'] ?>,
    <?= (int)$statusInternacoes['Cancelada'] ?>
];

new Chart(document.getElementById('graficoStatus'), {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusValores,
            backgroundColor: ['#dbeafe', '#dcfce7', '#fee2e2', '#e2e8f0'],
            borderColor: '#ffffff',
            borderWidth: 3
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

new Chart(document.getElementById('graficoTipo'), {
    type: 'doughnut',
    data: {
        labels: tipoLabels,
        datasets: [{
            data: tipoValores,
            backgroundColor: ['#ccfbf1', '#fef3c7'],
            borderColor: '#ffffff',
            borderWidth: 3
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
    
new Chart(document.getElementById('graficoExames'), {
    type: 'doughnut',
    data: {
        labels: exameLabels,
        datasets: [{
            data: exameValores,
            backgroundColor: ['#dbeafe', '#fef3c7', '#dcfce7', '#fee2e2'],
            borderColor: '#ffffff',
            borderWidth: 3
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
    
new Chart(document.getElementById('graficoInternacoes'), {
    type: 'doughnut',
    data: {
        labels: internacaoLabels,
        datasets: [{
            data: internacaoValores,
            backgroundColor: ['#fef3c7', '#dcfce7', '#fee2e2'],
            borderColor: '#ffffff',
            borderWidth: 3
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

new Chart(document.getElementById('graficoUnidades'), {
    type: 'bar',
    data: {
        labels: unidadeLabels,
        datasets: [{
            label: 'Consultas',
            data: unidadeValores,
            backgroundColor: '#0f766e',
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>