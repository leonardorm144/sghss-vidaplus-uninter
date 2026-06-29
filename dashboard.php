<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/auth/valida_sessao.php';

$pageTitle = 'Dashboard';
$pageSubtitle = 'Painel inicial do SGHSS VidaPlus';
$menuAtivo = 'dashboard';

$perfilUsuario = $_SESSION['usuario_perfil'] ?? '';
$usuarioId = $_SESSION['usuario_id'] ?? 0;

function contarDashboard($pdo, $sql, $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (int)$stmt->fetchColumn();
}

function buscarDashboard($pdo, $sql, $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function labelStatusDashboard($status)
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
        case 'Solicitado':
            return 'Solicitado';
        case 'Agendado':
            return 'Agendado';
        case 'Realizado':
            return 'Realizado';
        case 'Ativa':
            return 'Em internação';
        case 'Alta':
            return 'Alta médica';
        default:
            return $status ?: 'Não informado';
    }
}

function classeStatusDashboard($status)
{
    switch ($status) {
        case 'Agendada':
        case 'Solicitado':
            return 'badge-info';
        case 'Confirmada':
        case 'Realizado':
        case 'Alta':
            return 'badge-success';
        case 'Cancelada':
        case 'Cancelado':
            return 'badge-danger';
        case 'Agendado':
        case 'Ativa':
            return 'badge-warning';
        default:
            return 'badge-neutral';
    }
}

/* =========================
   ADMIN
========================= */

$admin = [];

if ($perfilUsuario === 'admin') {
    $admin['totalPacientes'] = contarDashboard($pdo, "
        SELECT COUNT(*) 
        FROM pacientes 
        WHERE ativo = 1
    ");

    $admin['totalProfissionais'] = contarDashboard($pdo, "
        SELECT COUNT(*) 
        FROM profissionais 
        WHERE ativo = 1
    ");

    $admin['totalUnidades'] = contarDashboard($pdo, "
        SELECT COUNT(*) 
        FROM unidades 
        WHERE ativo = 1
    ");

    $admin['consultasHoje'] = contarDashboard($pdo, "
        SELECT COUNT(*)
        FROM consultas
        WHERE DATE(data_consulta) = CURDATE()
    ");

    $admin['teleconsultasHoje'] = contarDashboard($pdo, "
        SELECT COUNT(*)
        FROM consultas
        WHERE DATE(data_consulta) = CURDATE()
        AND tipo = 'Telemedicina'
    ");

    $admin['examesPeriodo'] = contarDashboard($pdo, "
        SELECT COUNT(*)
        FROM exames
        WHERE ativo = 1
        AND MONTH(COALESCE(data_exame, criado_em)) = MONTH(CURDATE())
        AND YEAR(COALESCE(data_exame, criado_em)) = YEAR(CURDATE())
    ");

    $admin['internacoesAtivas'] = contarDashboard($pdo, "
        SELECT COUNT(*)
        FROM internacoes
        WHERE ativo = 1
        AND status = 'Ativa'
    ");

    $admin['altasHoje'] = contarDashboard($pdo, "
        SELECT COUNT(*)
        FROM internacoes
        WHERE ativo = 1
        AND status = 'Alta'
        AND DATE(data_alta) = CURDATE()
    ");

    $admin['leitosDisponiveis'] = contarDashboard($pdo, "
        SELECT COUNT(*)
        FROM leitos
        WHERE ativo = 1
        AND status = 'Disponivel'
    ");

    $admin['leitosOcupados'] = contarDashboard($pdo, "
        SELECT COUNT(*)
        FROM leitos
        WHERE ativo = 1
        AND status = 'Ocupado'
    ");

    $admin['leitosManutencao'] = contarDashboard($pdo, "
        SELECT COUNT(*)
        FROM leitos
        WHERE ativo = 1
        AND status = 'Manutencao'
    ");

    $admin['ultimasConsultas'] = buscarDashboard($pdo, "
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
        ORDER BY c.data_consulta DESC
        LIMIT 8
    ");
}

/* =========================
   RECEPÇÃO
========================= */

$recepcao = [];

if ($perfilUsuario === 'recepcao') {
    $recepcao['consultasHoje'] = contarDashboard($pdo, "
        SELECT COUNT(*)
        FROM consultas
        WHERE DATE(data_consulta) = CURDATE()
    ");

    $recepcao['consultasAgendadas'] = contarDashboard($pdo, "
        SELECT COUNT(*)
        FROM consultas
        WHERE status = 'Agendada'
        AND data_consulta >= NOW()
    ");

    $recepcao['teleconsultasHoje'] = contarDashboard($pdo, "
        SELECT COUNT(*)
        FROM consultas
        WHERE DATE(data_consulta) = CURDATE()
        AND tipo = 'Telemedicina'
    ");

    $recepcao['pacientesHoje'] = contarDashboard($pdo, "
        SELECT COUNT(*)
        FROM pacientes
        WHERE ativo = 1
        AND DATE(criado_em) = CURDATE()
    ");

    $recepcao['examesSolicitados'] = contarDashboard($pdo, "
        SELECT COUNT(*)
        FROM exames
        WHERE ativo = 1
        AND status = 'Solicitado'
    ");

    $recepcao['examesAgendados'] = contarDashboard($pdo, "
        SELECT COUNT(*)
        FROM exames
        WHERE ativo = 1
        AND status = 'Agendado'
    ");

    $recepcao['proximasConsultas'] = buscarDashboard($pdo, "
        SELECT
            c.id,
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
        WHERE c.data_consulta >= NOW()
        AND c.status IN ('Agendada', 'Confirmada')
        ORDER BY c.data_consulta ASC
        LIMIT 8
    ");

    $recepcao['ultimosExames'] = buscarDashboard($pdo, "
        SELECT
            e.id,
            e.nome_exame,
            e.status,
            e.data_exame,
            p.nome AS paciente_nome,
            u.nome AS unidade_nome
        FROM exames e
        INNER JOIN pacientes p ON p.id = e.paciente_id
        LEFT JOIN unidades u ON u.id = e.unidade_id
        WHERE e.ativo = 1
        ORDER BY COALESCE(e.data_exame, e.criado_em) DESC
        LIMIT 8
    ");
}

/* =========================
   PROFISSIONAL
========================= */

$profissional = null;
$dashProfissional = [];

if ($perfilUsuario === 'profissional') {
    $stmtProfissional = $pdo->prepare("
        SELECT *
        FROM profissionais
        WHERE usuario_id = :usuario_id
        AND ativo = 1
        LIMIT 1
    ");

    $stmtProfissional->execute([
        ':usuario_id' => $usuarioId
    ]);

    $profissional = $stmtProfissional->fetch();

    if ($profissional) {
        $dashProfissional['consultasHoje'] = contarDashboard($pdo, "
            SELECT COUNT(*)
            FROM consultas
            WHERE profissional_id = :profissional_id
            AND DATE(data_consulta) = CURDATE()
        ", [
            ':profissional_id' => $profissional['id']
        ]);

        $dashProfissional['proximasConsultas'] = contarDashboard($pdo, "
            SELECT COUNT(*)
            FROM consultas
            WHERE profissional_id = :profissional_id
            AND data_consulta >= NOW()
            AND status IN ('Agendada', 'Confirmada')
        ", [
            ':profissional_id' => $profissional['id']
        ]);

        $dashProfissional['teleconsultas'] = contarDashboard($pdo, "
            SELECT COUNT(*)
            FROM consultas
            WHERE profissional_id = :profissional_id
            AND data_consulta >= NOW()
            AND tipo = 'Telemedicina'
            AND status IN ('Agendada', 'Confirmada')
        ", [
            ':profissional_id' => $profissional['id']
        ]);

        $dashProfissional['prontuariosMes'] = contarDashboard($pdo, "
            SELECT COUNT(*)
            FROM prontuarios
            WHERE profissional_id = :profissional_id
            AND ativo = 1
            AND MONTH(criado_em) = MONTH(CURDATE())
            AND YEAR(criado_em) = YEAR(CURDATE())
        ", [
            ':profissional_id' => $profissional['id']
        ]);

        $dashProfissional['prescricoesMes'] = contarDashboard($pdo, "
            SELECT COUNT(*)
            FROM prescricoes
            WHERE profissional_id = :profissional_id
            AND ativo = 1
            AND MONTH(data_emissao) = MONTH(CURDATE())
            AND YEAR(data_emissao) = YEAR(CURDATE())
        ", [
            ':profissional_id' => $profissional['id']
        ]);

        $dashProfissional['agenda'] = buscarDashboard($pdo, "
            SELECT
                c.id,
                c.data_consulta,
                c.tipo,
                c.status,
                p.nome AS paciente_nome,
                u.nome AS unidade_nome
            FROM consultas c
            INNER JOIN pacientes p ON p.id = c.paciente_id
            LEFT JOIN unidades u ON u.id = c.unidade_id
            WHERE c.profissional_id = :profissional_id
            AND c.data_consulta >= NOW()
            AND c.status IN ('Agendada', 'Confirmada')
            ORDER BY c.data_consulta ASC
            LIMIT 8
        ", [
            ':profissional_id' => $profissional['id']
        ]);
    }
}

/* =========================
   PACIENTE
========================= */

$paciente = null;
$dashPaciente = [];

if ($perfilUsuario === 'paciente') {
    $stmtPaciente = $pdo->prepare("
        SELECT *
        FROM pacientes
        WHERE usuario_id = :usuario_id
        AND ativo = 1
        LIMIT 1
    ");

    $stmtPaciente->execute([
        ':usuario_id' => $usuarioId
    ]);

    $paciente = $stmtPaciente->fetch();

    if ($paciente) {
        $dashPaciente['proximasConsultasTotal'] = contarDashboard($pdo, "
            SELECT COUNT(*)
            FROM consultas
            WHERE paciente_id = :paciente_id
            AND data_consulta >= NOW()
            AND status IN ('Agendada', 'Confirmada')
        ", [
            ':paciente_id' => $paciente['id']
        ]);

        $dashPaciente['teleconsultasTotal'] = contarDashboard($pdo, "
            SELECT COUNT(*)
            FROM consultas
            WHERE paciente_id = :paciente_id
            AND data_consulta >= NOW()
            AND tipo = 'Telemedicina'
            AND status IN ('Agendada', 'Confirmada')
        ", [
            ':paciente_id' => $paciente['id']
        ]);

        $dashPaciente['examesTotal'] = contarDashboard($pdo, "
            SELECT COUNT(*)
            FROM exames
            WHERE paciente_id = :paciente_id
            AND ativo = 1
            AND status IN ('Solicitado', 'Agendado')
        ", [
            ':paciente_id' => $paciente['id']
        ]);

        $dashPaciente['prescricoesTotal'] = contarDashboard($pdo, "
            SELECT COUNT(*)
            FROM prescricoes
            WHERE paciente_id = :paciente_id
            AND ativo = 1
        ", [
            ':paciente_id' => $paciente['id']
        ]);

        $dashPaciente['internacoesAtivas'] = contarDashboard($pdo, "
            SELECT COUNT(*)
            FROM internacoes
            WHERE paciente_id = :paciente_id
            AND ativo = 1
            AND status = 'Ativa'
        ", [
            ':paciente_id' => $paciente['id']
        ]);

        $dashPaciente['proximasConsultas'] = buscarDashboard($pdo, "
            SELECT
                c.id,
                c.data_consulta,
                c.tipo,
                c.status,
                pr.nome AS profissional_nome,
                pr.especialidade AS profissional_especialidade,
                u.nome AS unidade_nome
            FROM consultas c
            INNER JOIN profissionais pr ON pr.id = c.profissional_id
            LEFT JOIN unidades u ON u.id = c.unidade_id
            WHERE c.paciente_id = :paciente_id
            AND c.data_consulta >= NOW()
            AND c.status IN ('Agendada', 'Confirmada')
            ORDER BY c.data_consulta ASC
            LIMIT 6
        ", [
            ':paciente_id' => $paciente['id']
        ]);

        $dashPaciente['proximosExames'] = buscarDashboard($pdo, "
            SELECT
                e.nome_exame,
                e.status,
                e.data_exame,
                u.nome AS unidade_nome
            FROM exames e
            LEFT JOIN unidades u ON u.id = e.unidade_id
            WHERE e.paciente_id = :paciente_id
            AND e.ativo = 1
            AND e.status IN ('Solicitado', 'Agendado')
            ORDER BY 
                CASE 
                    WHEN e.data_exame IS NULL THEN 1
                    ELSE 0
                END,
                e.data_exame ASC,
                e.criado_em DESC
            LIMIT 6
        ", [
            ':paciente_id' => $paciente['id']
        ]);
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($perfilUsuario === 'admin'): ?>

    <section class="page-actions">
        <div>
            <h2>Dashboard Administrativo</h2>
            <p>Visão geral dos atendimentos, pacientes, leitos, exames e internações.</p>
        </div>

        <a href="<?= BASE_URL ?>admin/relatorios.php" class="btn btn-primary-small">
            Ver Relatórios
        </a>
    </section>
	
	<section class="panel">
    <h2>Ações Rápidas</h2>
    <p class="panel-subtitle">Atalhos administrativos para as principais rotinas do sistema.</p>

    <div class="quick-actions-grid">
    <a href="<?= BASE_URL ?>admin/paciente_form.php" class="quick-action-card">
        <div class="quick-action-icon">+</div>
        <div class="quick-action-content">
            <strong>Novo Paciente</strong>
            <span>Cadastrar um novo paciente no sistema.</span>
        </div>
    </a>

    <a href="<?= BASE_URL ?>admin/consulta_form.php" class="quick-action-card">
        <div class="quick-action-icon">📅</div>
        <div class="quick-action-content">
            <strong>Nova Consulta</strong>
            <span>Agendar atendimento presencial ou telemedicina.</span>
        </div>
    </a>

    <a href="<?= BASE_URL ?>admin/internacao_form.php" class="quick-action-card">
        <div class="quick-action-icon">🏥</div>
        <div class="quick-action-content">
            <strong>Nova Internação</strong>
            <span>Registrar paciente em um leito disponível.</span>
        </div>
    </a>

    <a href="<?= BASE_URL ?>admin/relatorios.php" class="quick-action-card">
        <div class="quick-action-icon">📊</div>
        <div class="quick-action-content">
            <strong>Relatórios</strong>
            <span>Acompanhar indicadores administrativos.</span>
        </div>
    </a>
</div>
</section>



    <section class="cards-grid">
    <div class="card" data-icon="👥">
        <span class="card-label">Pacientes</span>
        <strong><?= $admin['totalPacientes'] ?></strong>
        <p>Pacientes ativos</p>
    </div>

    <div class="card" data-icon="🩺">
        <span class="card-label">Profissionais</span>
        <strong><?= $admin['totalProfissionais'] ?></strong>
        <p>Equipe cadastrada</p>
    </div>

    <div class="card" data-icon="🏥">
        <span class="card-label">Unidades</span>
        <strong><?= $admin['totalUnidades'] ?></strong>
        <p>Unidades ativas</p>
    </div>

    <div class="card" data-icon="📅">
        <span class="card-label">Consultas Hoje</span>
        <strong><?= $admin['consultasHoje'] ?></strong>
        <p>Atendimentos do dia</p>
    </div>
</section>

    <section class="cards-grid">
    <div class="card" data-icon="💻">
        <span class="card-label">Teleconsultas Hoje</span>
        <strong><?= $admin['teleconsultasHoje'] ?></strong>
        <p>Atendimentos online</p>
    </div>

    <div class="card" data-icon="🧪">
        <span class="card-label">Exames no Mês</span>
        <strong><?= $admin['examesPeriodo'] ?></strong>
        <p>Exames cadastrados</p>
    </div>

    <div class="card" data-icon="🚑">
        <span class="card-label">Internações Ativas</span>
        <strong><?= $admin['internacoesAtivas'] ?></strong>
        <p>Pacientes internados</p>
    </div>

    <div class="card" data-icon="✅">
        <span class="card-label">Altas Hoje</span>
        <strong><?= $admin['altasHoje'] ?></strong>
        <p>Altas registradas</p>
    </div>
</section>

    <section class="dashboard-grid">
        <div class="panel">
            <h2>Últimas Consultas</h2>
            <p class="panel-subtitle">Consultas mais recentes cadastradas no sistema.</p>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Paciente</th>
                            <th>Profissional</th>
                            <th>Tipo</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($admin['ultimasConsultas'])): ?>
                            <tr>
                                <td colspan="5" class="empty-state">Nenhuma consulta encontrada.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($admin['ultimasConsultas'] as $consulta): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($consulta['data_consulta'])) ?></td>
                                <td><?= e($consulta['paciente_nome']) ?></td>
                                <td><?= e($consulta['profissional_nome']) ?></td>
                                <td><?= e($consulta['tipo']) ?></td>
                                <td>
                                    <span class="badge <?= classeStatusDashboard($consulta['status']) ?>">
                                        <?= e(labelStatusDashboard($consulta['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <h2>Status dos Leitos</h2>
            <p class="panel-subtitle">Resumo da disponibilidade hospitalar.</p>

            <div class="status-list">
                <div class="status-item">
                    <div>
                        <strong>Disponíveis</strong>
                        <span>Leitos livres</span>
                    </div>
                    <span class="status-number status-green"><?= $admin['leitosDisponiveis'] ?></span>
                </div>

                <div class="status-item">
                    <div>
                        <strong>Ocupados</strong>
                        <span>Em utilização</span>
                    </div>
                    <span class="status-number status-yellow"><?= $admin['leitosOcupados'] ?></span>
                </div>

                <div class="status-item">
                    <div>
                        <strong>Manutenção</strong>
                        <span>Indisponíveis</span>
                    </div>
                    <span class="status-number status-red"><?= $admin['leitosManutencao'] ?></span>
                </div>
            </div>
        </div>
    </section>

<?php elseif ($perfilUsuario === 'recepcao'): ?>

    <section class="page-actions">
        <div>
            <h2>Dashboard da Recepção</h2>
            <p>Resumo dos agendamentos, pacientes e exames.</p>
        </div>

        <a href="<?= BASE_URL ?>recepcao/agendamento_form.php" class="btn btn-primary-small">
            Novo Agendamento
        </a>
    </section>

	<section class="panel">
    <h2>Ações Rápidas</h2>
    <p class="panel-subtitle">Atalhos para atendimento, cadastro e controle da recepção.</p>

    <div class="quick-actions-grid">
    <a href="<?= BASE_URL ?>recepcao/agendamento_form.php" class="quick-action-card">
        <div class="quick-action-icon">📅</div>
        <div class="quick-action-content">
            <strong>Novo Agendamento</strong>
            <span>Agendar consulta presencial ou telemedicina.</span>
        </div>
    </a>

    <a href="<?= BASE_URL ?>recepcao/paciente_form.php" class="quick-action-card">
        <div class="quick-action-icon">+</div>
        <div class="quick-action-content">
            <strong>Novo Paciente</strong>
            <span>Cadastrar paciente pela recepção.</span>
        </div>
    </a>

    <a href="<?= BASE_URL ?>recepcao/exame_form.php" class="quick-action-card">
        <div class="quick-action-icon">🧪</div>
        <div class="quick-action-content">
            <strong>Novo Exame</strong>
            <span>Solicitar ou agendar exame para paciente.</span>
        </div>
    </a>

    <a href="<?= BASE_URL ?>recepcao/agendamentos.php" class="quick-action-card">
        <div class="quick-action-icon">📋</div>
        <div class="quick-action-content">
            <strong>Ver Agendamentos</strong>
            <span>Consultar e gerenciar a agenda.</span>
        </div>
    </a>
</div>
</section>

    <section class="cards-grid">
    <div class="card" data-icon="📅">
        <span class="card-label">Consultas Hoje</span>
        <strong><?= $recepcao['consultasHoje'] ?></strong>
        <p>Atendimentos do dia</p>
    </div>

    <div class="card" data-icon="📋">
        <span class="card-label">Consultas Agendadas</span>
        <strong><?= $recepcao['consultasAgendadas'] ?></strong>
        <p>Próximas consultas</p>
    </div>

    <div class="card" data-icon="💻">
        <span class="card-label">Teleconsultas Hoje</span>
        <strong><?= $recepcao['teleconsultasHoje'] ?></strong>
        <p>Atendimentos online</p>
    </div>

    <div class="card" data-icon="👥">
        <span class="card-label">Pacientes Hoje</span>
        <strong><?= $recepcao['pacientesHoje'] ?></strong>
        <p>Novos cadastros</p>
    </div>
</section>

    <section class="cards-grid">
    <div class="card" data-icon="🧪">
        <span class="card-label">Exames Solicitados</span>
        <strong><?= $recepcao['examesSolicitados'] ?></strong>
        <p>Aguardando agendamento</p>
    </div>

    <div class="card" data-icon="🗓️">
        <span class="card-label">Exames Agendados</span>
        <strong><?= $recepcao['examesAgendados'] ?></strong>
        <p>Com data definida</p>
    </div>

    <div class="card" data-icon="📌">
        <span class="card-label">Agendamentos</span>
        <strong><?= count($recepcao['proximasConsultas']) ?></strong>
        <p>Próximos na lista</p>
    </div>

    <div class="card" data-icon="🧾">
        <span class="card-label">Últimos Exames</span>
        <strong><?= count($recepcao['ultimosExames']) ?></strong>
        <p>Registros recentes</p>
    </div>
</section>

    <section class="dashboard-grid">
        <div class="panel">
            <h2>Próximos Agendamentos</h2>
            <p class="panel-subtitle">Consultas agendadas ou confirmadas.</p>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Paciente</th>
                            <th>Profissional</th>
                            <th>Tipo</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($recepcao['proximasConsultas'])): ?>
                            <tr>
                                <td colspan="5" class="empty-state">Nenhum agendamento encontrado.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($recepcao['proximasConsultas'] as $consulta): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($consulta['data_consulta'])) ?></td>
                                <td><?= e($consulta['paciente_nome']) ?></td>
                                <td><?= e($consulta['profissional_nome']) ?></td>
                                <td><?= e($consulta['tipo']) ?></td>
                                <td>
                                    <span class="badge <?= classeStatusDashboard($consulta['status']) ?>">
                                        <?= e(labelStatusDashboard($consulta['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <h2>Exames Recentes</h2>
            <p class="panel-subtitle">Últimos exames cadastrados pela recepção.</p>

            <div class="historico-list">
                <?php if (empty($recepcao['ultimosExames'])): ?>
                    <div class="empty-state agenda-empty">Nenhum exame encontrado.</div>
                <?php endif; ?>

                <?php foreach ($recepcao['ultimosExames'] as $exame): ?>
                    <article class="historico-card">
                        <div class="historico-card-header historico-card-header-flex">
                            <div>
                                <span class="agenda-date">
                                    <?= !empty($exame['data_exame']) ? date('d/m/Y H:i', strtotime($exame['data_exame'])) : 'Sem data definida' ?>
                                </span>

                                <h3><?= e($exame['nome_exame']) ?></h3>
                                <p><?= e($exame['paciente_nome']) ?></p>
                            </div>

                            <span class="badge <?= classeStatusDashboard($exame['status']) ?>">
                                <?= e(labelStatusDashboard($exame['status'])) ?>
                            </span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

<?php elseif ($perfilUsuario === 'profissional'): ?>

    <?php if (!$profissional): ?>
        <section class="panel">
            <h2>Profissional não vinculado</h2>
            <p>Seu usuário ainda não está vinculado a um cadastro de profissional.</p>
        </section>
    <?php else: ?>

        <section class="page-actions">
            <div>
                <h2>Olá, <?= e($profissional['nome']) ?></h2>
                <p>Resumo da sua agenda, atendimentos e registros clínicos.</p>
            </div>

            <a href="<?= BASE_URL ?>profissional/agenda.php" class="btn btn-primary-small">
                Minha Agenda
            </a>
        </section>

		<section class="panel">
    <h2>Ações Rápidas</h2>
    <p class="panel-subtitle">Atalhos para agenda, registros clínicos e prescrições.</p>

    <div class="quick-actions-grid">
    <a href="<?= BASE_URL ?>profissional/agenda.php" class="quick-action-card">
        <div class="quick-action-icon">📅</div>
        <div class="quick-action-content">
            <strong>Minha Agenda</strong>
            <span>Ver consultas e teleconsultas agendadas.</span>
        </div>
    </a>

    <a href="<?= BASE_URL ?>profissional/prontuarios.php" class="quick-action-card">
        <div class="quick-action-icon">📝</div>
        <div class="quick-action-content">
            <strong>Prontuários</strong>
            <span>Consultar e registrar histórico clínico.</span>
        </div>
    </a>

    <a href="<?= BASE_URL ?>profissional/prescricoes.php" class="quick-action-card">
        <div class="quick-action-icon">💊</div>
        <div class="quick-action-content">
            <strong>Prescrições</strong>
            <span>Consultar receitas digitais emitidas.</span>
        </div>
    </a>

    <a href="<?= BASE_URL ?>profissional/prescricao_form.php" class="quick-action-card">
        <div class="quick-action-icon">+</div>
        <div class="quick-action-content">
            <strong>Nova Prescrição</strong>
            <span>Emitir nova receita digital.</span>
        </div>
    </a>
</div>
</section>

        <section class="cards-grid">
    <div class="card" data-icon="📅">
        <span class="card-label">Consultas Hoje</span>
        <strong><?= $dashProfissional['consultasHoje'] ?></strong>
        <p>Atendimentos do dia</p>
    </div>

    <div class="card" data-icon="📋">
        <span class="card-label">Próximas Consultas</span>
        <strong><?= $dashProfissional['proximasConsultas'] ?></strong>
        <p>Agenda futura</p>
    </div>

    <div class="card" data-icon="💻">
        <span class="card-label">Teleconsultas</span>
        <strong><?= $dashProfissional['teleconsultas'] ?></strong>
        <p>Atendimentos online</p>
    </div>

    <div class="card" data-icon="📝">
        <span class="card-label">Prontuários no Mês</span>
        <strong><?= $dashProfissional['prontuariosMes'] ?></strong>
        <p>Registros criados</p>
    </div>
</section>

        <section class="panel">
            <h2>Próximos Atendimentos</h2>
            <p class="panel-subtitle">Pacientes agendados para os próximos atendimentos.</p>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Paciente</th>
                            <th>Unidade</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($dashProfissional['agenda'])): ?>
                            <tr>
                                <td colspan="6" class="empty-state">Nenhum atendimento futuro encontrado.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($dashProfissional['agenda'] as $consulta): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($consulta['data_consulta'])) ?></td>
                                <td><?= e($consulta['paciente_nome']) ?></td>
                                <td><?= e($consulta['unidade_nome'] ?: '-') ?></td>
                                <td><?= e($consulta['tipo']) ?></td>
                                <td>
                                    <span class="badge <?= classeStatusDashboard($consulta['status']) ?>">
                                        <?= e(labelStatusDashboard($consulta['status'])) ?>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <a 
                                        href="<?= BASE_URL ?>profissional/prontuario_form.php?consulta_id=<?= (int)$consulta['id'] ?>" 
                                        class="btn btn-light"
                                    >
                                        Prontuário
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

    <?php endif; ?>

<?php elseif ($perfilUsuario === 'paciente'): ?>

    <?php if (!$paciente): ?>
        <section class="panel">
            <h2>Paciente não vinculado</h2>
            <p>Seu usuário ainda não está vinculado a um cadastro de paciente.</p>
        </section>
    <?php else: ?>

        <section class="page-actions">
            <div>
                <h2>Olá, <?= e($paciente['nome']) ?></h2>
                <p>Resumo das suas consultas, exames, receitas e internações.</p>
            </div>

            <a href="<?= BASE_URL ?>paciente/historico.php" class="btn btn-primary-small">
                Meu Histórico
            </a>
        </section>

		<section class="panel">
    <h2>Ações Rápidas</h2>
    <p class="panel-subtitle">Atalhos para acompanhar consultas, histórico e telemedicina.</p>

    <div class="quick-actions-grid">
    <a href="<?= BASE_URL ?>paciente/consultas.php" class="quick-action-card">
        <div class="quick-action-icon">📅</div>
        <div class="quick-action-content">
            <strong>Minhas Consultas</strong>
            <span>Ver consultas presenciais e online.</span>
        </div>
    </a>

    <a href="<?= BASE_URL ?>paciente/historico.php" class="quick-action-card">
        <div class="quick-action-icon">📁</div>
        <div class="quick-action-content">
            <strong>Meu Histórico</strong>
            <span>Consultar prontuários, exames e internações.</span>
        </div>
    </a>

    <a href="<?= BASE_URL ?>paciente/teleconsulta.php" class="quick-action-card">
        <div class="quick-action-icon">💻</div>
        <div class="quick-action-content">
            <strong>Teleconsulta</strong>
            <span>Acessar atendimentos online agendados.</span>
        </div>
    </a>

    <a href="<?= BASE_URL ?>paciente/historico.php" class="quick-action-card">
        <div class="quick-action-icon">💊</div>
        <div class="quick-action-content">
            <strong>Prescrições</strong>
            <span>Visualizar receitas digitais emitidas.</span>
        </div>
    </a>
</div>
</section>

        <section class="cards-grid">
    <div class="card" data-icon="📅">
        <span class="card-label">Próximas Consultas</span>
        <strong><?= $dashPaciente['proximasConsultasTotal'] ?></strong>
        <p>Agendadas ou confirmadas</p>
    </div>

    <div class="card" data-icon="💻">
        <span class="card-label">Teleconsultas</span>
        <strong><?= $dashPaciente['teleconsultasTotal'] ?></strong>
        <p>Atendimentos online</p>
    </div>

    <div class="card" data-icon="🧪">
        <span class="card-label">Exames Pendentes</span>
        <strong><?= $dashPaciente['examesTotal'] ?></strong>
        <p>Solicitados ou agendados</p>
    </div>

    <div class="card" data-icon="💊">
        <span class="card-label">Prescrições</span>
        <strong><?= $dashPaciente['prescricoesTotal'] ?></strong>
        <p>Receitas digitais</p>
    </div>
</section>

        <section class="dashboard-grid">
            <div class="panel">
                <h2>Minhas Próximas Consultas</h2>
                <p class="panel-subtitle">Consultas e teleconsultas futuras.</p>

                <div class="historico-list">
                    <?php if (empty($dashPaciente['proximasConsultas'])): ?>
                        <div class="empty-state agenda-empty">Nenhuma consulta futura encontrada.</div>
                    <?php endif; ?>

                    <?php foreach ($dashPaciente['proximasConsultas'] as $consulta): ?>
                        <article class="historico-card">
                            <div class="historico-card-header historico-card-header-flex">
                                <div>
                                    <span class="agenda-date">
                                        <?= date('d/m/Y H:i', strtotime($consulta['data_consulta'])) ?>
                                    </span>

                                    <h3><?= e($consulta['profissional_nome']) ?></h3>

                                    <p>
                                        <?= e($consulta['profissional_especialidade'] ?: 'Especialidade não informada') ?>
                                        <?= !empty($consulta['unidade_nome']) ? ' - ' . e($consulta['unidade_nome']) : '' ?>
                                    </p>
                                </div>

                                <span class="badge <?= classeStatusDashboard($consulta['status']) ?>">
                                    <?= e(labelStatusDashboard($consulta['status'])) ?>
                                </span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="panel">
                <h2>Meus Exames</h2>
                <p class="panel-subtitle">Exames solicitados ou agendados.</p>

                <div class="historico-list">
                    <?php if (empty($dashPaciente['proximosExames'])): ?>
                        <div class="empty-state agenda-empty">Nenhum exame pendente encontrado.</div>
                    <?php endif; ?>

                    <?php foreach ($dashPaciente['proximosExames'] as $exame): ?>
                        <article class="historico-card">
                            <div class="historico-card-header historico-card-header-flex">
                                <div>
                                    <span class="agenda-date">
                                        <?= !empty($exame['data_exame']) ? date('d/m/Y H:i', strtotime($exame['data_exame'])) : 'Sem data definida' ?>
                                    </span>

                                    <h3><?= e($exame['nome_exame']) ?></h3>
                                    <p><?= e($exame['unidade_nome'] ?: 'Unidade não informada') ?></p>
                                </div>

                                <span class="badge <?= classeStatusDashboard($exame['status']) ?>">
                                    <?= e(labelStatusDashboard($exame['status'])) ?>
                                </span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <?php if ($dashPaciente['internacoesAtivas'] > 0): ?>
            <section class="panel">
                <h2>Internação Ativa</h2>
                <p class="panel-subtitle">
                    Existe uma internação ativa vinculada ao seu cadastro. Acesse o histórico para visualizar os detalhes.
                </p>

                <a href="<?= BASE_URL ?>paciente/historico.php" class="btn btn-secondary">
                    Ver Histórico
                </a>
            </section>
        <?php endif; ?>

    <?php endif; ?>

<?php else: ?>

    <section class="panel">
        <h2>Perfil não identificado</h2>
        <p>Não foi possível carregar o dashboard para este perfil.</p>
    </section>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>