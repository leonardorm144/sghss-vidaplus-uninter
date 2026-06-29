<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('paciente');

$pageTitle = 'Meu Histórico';
$pageSubtitle = 'Histórico clínico, prontuários e prescrições';
$menuAtivo = 'meu_historico';

$usuarioId = $_SESSION['usuario_id'] ?? 0;

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

$prontuarios = [];
$prescricoes = [];
$exames = [];
$internacoes = [];

if ($paciente) {
    $stmtProntuarios = $pdo->prepare("
        SELECT 
            pt.*,
            pr.nome AS profissional_nome,
            pr.especialidade AS profissional_especialidade,
            c.data_consulta,
            c.tipo AS consulta_tipo
        FROM prontuarios pt
        INNER JOIN profissionais pr ON pr.id = pt.profissional_id
        LEFT JOIN consultas c ON c.id = pt.consulta_id
        WHERE pt.paciente_id = :paciente_id
        AND pt.ativo = 1
        ORDER BY pt.criado_em DESC
    ");

    $stmtProntuarios->execute([
        ':paciente_id' => $paciente['id']
    ]);

    $prontuarios = $stmtProntuarios->fetchAll();

    $stmtPrescricoes = $pdo->prepare("
        SELECT 
            ps.*,
            pr.nome AS profissional_nome,
            pr.especialidade AS profissional_especialidade,
            pr.registro_profissional,
            c.data_consulta,
            c.tipo AS consulta_tipo
        FROM prescricoes ps
        INNER JOIN profissionais pr ON pr.id = ps.profissional_id
        LEFT JOIN consultas c ON c.id = ps.consulta_id
        WHERE ps.paciente_id = :paciente_id
        AND ps.ativo = 1
        ORDER BY ps.data_emissao DESC
    ");

    $stmtPrescricoes->execute([
        ':paciente_id' => $paciente['id']
    ]);

    $prescricoes = $stmtPrescricoes->fetchAll();
    
    $stmtExames = $pdo->prepare("
    SELECT 
        e.*,
        u.nome AS unidade_nome,
        u.tipo AS unidade_tipo,
        u.cidade AS unidade_cidade,
        u.estado AS unidade_estado
    FROM exames e
    LEFT JOIN unidades u ON u.id = e.unidade_id
    WHERE e.paciente_id = :paciente_id
    AND e.ativo = 1
    ORDER BY 
        CASE 
            WHEN e.data_exame IS NULL THEN 1
            ELSE 0
        END,
        e.data_exame DESC,
        e.criado_em DESC
");

$stmtExames->execute([
    ':paciente_id' => $paciente['id']
]);

$exames = $stmtExames->fetchAll();
    
$stmtInternacoes = $pdo->prepare("
    SELECT 
        i.*,
        l.numero AS leito_numero,
        l.setor AS leito_setor,
        u.nome AS unidade_nome,
        u.tipo AS unidade_tipo,
        u.cidade AS unidade_cidade,
        u.estado AS unidade_estado
    FROM internacoes i
    INNER JOIN leitos l ON l.id = i.leito_id
    LEFT JOIN unidades u ON u.id = l.unidade_id
    WHERE i.paciente_id = :paciente_id
    AND i.ativo = 1
    ORDER BY i.data_entrada DESC
");

$stmtInternacoes->execute([
    ':paciente_id' => $paciente['id']
]);

$internacoes = $stmtInternacoes->fetchAll();
    
}

function classeStatusPacienteExame($status)
{
    switch ($status) {
        case 'Solicitado':
            return 'badge-info';
        case 'Agendado':
            return 'badge-warning';
        case 'Realizado':
            return 'badge-success';
        case 'Cancelado':
            return 'badge-danger';
        default:
            return 'badge-neutral';
    }
}

function labelStatusPacienteInternacao($status)
{
    switch ($status) {
        case 'Ativa':
            return 'Em internação';
        case 'Alta':
            return 'Alta médica';
        case 'Cancelada':
            return 'Cancelada';
        default:
            return $status ?: 'Não informado';
    }
}

function classeStatusPacienteInternacao($status)
{
    switch ($status) {
        case 'Ativa':
            return 'badge-warning';
        case 'Alta':
            return 'badge-success';
        case 'Cancelada':
            return 'badge-danger';
        default:
            return 'badge-neutral';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!$paciente): ?>
    <section class="panel">
        <h2>Paciente não vinculado</h2>
        <p>
            Seu usuário ainda não está vinculado a um cadastro de paciente.
            Peça para o administrador vincular este login ao paciente correto.
        </p>
    </section>

	
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    <?php exit; ?>
<?php endif; ?>

<section class="page-actions">
    <div>
        <h2>Histórico de <?= e($paciente['nome']) ?></h2>
        <p>Visualização dos registros clínicos e receitas digitais.</p>
    </div>
</section>

<section class="dashboard-grid">
    <div class="panel">
        <h2>Prontuários</h2>
        <p class="panel-subtitle">Registros clínicos cadastrados pelos profissionais de saúde.</p>

        <div class="historico-list">
            <?php if (empty($prontuarios)): ?>
                <div class="empty-state agenda-empty">
                    Nenhum prontuário encontrado.
                </div>
            <?php endif; ?>

            <?php foreach ($prontuarios as $prontuario): ?>
                <article class="historico-card">
                    <div class="historico-card-header">
                        <div>
                            <span class="agenda-date">
                                <?= date('d/m/Y H:i', strtotime($prontuario['criado_em'])) ?>
                            </span>

                            <h3><?= e($prontuario['profissional_nome']) ?></h3>

                            <?php if (!empty($prontuario['profissional_especialidade'])): ?>
                                <p><?= e($prontuario['profissional_especialidade']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="historico-section">
                        <strong>Descrição do atendimento</strong>
                        <p><?= nl2br(e($prontuario['descricao'])) ?></p>
                    </div>

                    <div class="historico-section">
                        <strong>Diagnóstico</strong>
                        <p><?= nl2br(e($prontuario['diagnostico'] ?: 'Não informado.')) ?></p>
                    </div>

                    <div class="historico-section">
                        <strong>Conduta</strong>
                        <p><?= nl2br(e($prontuario['conduta'] ?: 'Não informada.')) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="panel">
        <h2>Prescrições</h2>
        <p class="panel-subtitle">Receitas digitais emitidas para o paciente.</p>

        <div class="historico-list">
            <?php if (empty($prescricoes)): ?>
                <div class="empty-state agenda-empty">
                    Nenhuma prescrição encontrada.
                </div>
            <?php endif; ?>

            <?php foreach ($prescricoes as $prescricao): ?>
                <article class="historico-card">
                    <div class="historico-card-header">
                        <div>
                            <span class="agenda-date">
                                <?= date('d/m/Y H:i', strtotime($prescricao['data_emissao'])) ?>
                            </span>

                            <h3><?= e($prescricao['medicamento']) ?></h3>
                            <p><?= e($prescricao['dosagem'] ?: 'Dosagem não informada') ?></p>
                        </div>
                    </div>

                    <div class="historico-section">
                        <strong>Profissional</strong>
                        <p>
                            <?= e($prescricao['profissional_nome']) ?>
                            <?= !empty($prescricao['profissional_especialidade']) ? ' - ' . e($prescricao['profissional_especialidade']) : '' ?>
                        </p>
                    </div>

                    <div class="historico-section">
                        <strong>Orientações</strong>
                        <p><?= nl2br(e($prescricao['orientacoes'] ?: 'Sem orientações adicionais.')) ?></p>
                    </div>

                    <div class="agenda-card-actions">
                        <a 
                            href="<?= BASE_URL ?>paciente/prescricao_visualizar.php?id=<?= (int)$prescricao['id'] ?>" 
                            class="btn btn-secondary"
                        >
                            Ver Receita
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="panel">
    <div class="historico-title-row">
        <div>
            <h2>Exames</h2>
            <p class="panel-subtitle">Exames solicitados, agendados e realizados para o paciente.</p>
        </div>

        <span class="audit-count">
            <?= count($exames) ?>
        </span>
    </div>

    <div class="historico-list">
        <?php if (empty($exames)): ?>
            <div class="empty-state agenda-empty">
                Nenhum exame encontrado.
            </div>
        <?php endif; ?>

        <?php foreach ($exames as $exame): ?>
            <article class="historico-card">
                <div class="historico-card-header historico-card-header-flex">
                    <div>
                        <?php if (!empty($exame['data_exame'])): ?>
                            <span class="agenda-date">
                                <?= date('d/m/Y H:i', strtotime($exame['data_exame'])) ?>
                            </span>
                        <?php else: ?>
                            <span class="agenda-date">
                                Sem data definida
                            </span>
                        <?php endif; ?>

                        <h3><?= e($exame['nome_exame']) ?></h3>

                        <?php if (!empty($exame['unidade_nome'])): ?>
                            <p>
                                <?= e($exame['unidade_nome']) ?>
                                <?= !empty($exame['unidade_tipo']) ? ' - ' . e($exame['unidade_tipo']) : '' ?>
                            </p>
                        <?php else: ?>
                            <p>Unidade não informada</p>
                        <?php endif; ?>
                    </div>

                    <span class="badge <?= classeStatusPacienteExame($exame['status']) ?>">
                        <?= e($exame['status']) ?>
                    </span>
                </div>

                <div class="historico-section">
                    <strong>Resultado</strong>

                    <?php if (!empty($exame['resultado'])): ?>
                        <p><?= nl2br(e($exame['resultado'])) ?></p>
                    <?php else: ?>
                        <p>Resultado ainda não disponível.</p>
                    <?php endif; ?>
                </div>

                <div class="historico-section">
                    <strong>Observações</strong>

                    <?php if (!empty($exame['observacoes'])): ?>
                        <p><?= nl2br(e($exame['observacoes'])) ?></p>
                    <?php else: ?>
                        <p>Sem observações adicionais.</p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($exame['unidade_cidade']) || !empty($exame['unidade_estado'])): ?>
                    <div class="historico-section">
                        <strong>Local</strong>
                        <p>
                            <?= e($exame['unidade_cidade'] ?: '-') ?>
                            <?= !empty($exame['unidade_estado']) ? ' - ' . e($exame['unidade_estado']) : '' ?>
                        </p>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel">
    <div class="historico-title-row">
        <div>
            <h2>Internações</h2>
            <p class="panel-subtitle">Histórico de internações hospitalares vinculadas ao paciente.</p>
        </div>

        <span class="audit-count">
            <?= count($internacoes) ?>
        </span>
    </div>

    <div class="historico-list">
        <?php if (empty($internacoes)): ?>
            <div class="empty-state agenda-empty">
                Nenhuma internação encontrada.
            </div>
        <?php endif; ?>

        <?php foreach ($internacoes as $internacao): ?>
            <article class="historico-card">
                <div class="historico-card-header historico-card-header-flex">
                    <div>
                        <span class="agenda-date">
                            Entrada: <?= date('d/m/Y H:i', strtotime($internacao['data_entrada'])) ?>
                        </span>

                        <h3>
                            <?= !empty($internacao['unidade_nome']) ? e($internacao['unidade_nome']) : 'Unidade não informada' ?>
                        </h3>

                        <p>
                            Leito <?= e($internacao['leito_numero']) ?>
                            <?= !empty($internacao['leito_setor']) ? ' - ' . e($internacao['leito_setor']) : '' ?>
                        </p>
                    </div>

                    <span class="badge <?= classeStatusPacienteInternacao($internacao['status']) ?>">
                        <?= e(labelStatusPacienteInternacao($internacao['status'])) ?>
                    </span>
                </div>

                <div class="historico-section">
                    <strong>Período da internação</strong>

                    <p>
                        <strong>Entrada:</strong>
                        <?= date('d/m/Y H:i', strtotime($internacao['data_entrada'])) ?>
                    </p>

                    <p>
                        <strong>Alta:</strong>
                        <?php if (!empty($internacao['data_alta'])): ?>
                            <?= date('d/m/Y H:i', strtotime($internacao['data_alta'])) ?>
                        <?php else: ?>
                            Ainda sem alta registrada.
                        <?php endif; ?>
                    </p>
                </div>

                <div class="historico-section">
                    <strong>Motivo da internação</strong>

                    <?php if (!empty($internacao['motivo'])): ?>
                        <p><?= nl2br(e($internacao['motivo'])) ?></p>
                    <?php else: ?>
                        <p>Motivo não informado.</p>
                    <?php endif; ?>
                </div>

                <div class="historico-section">
                    <strong>Observações</strong>

                    <?php if (!empty($internacao['observacoes'])): ?>
                        <p><?= nl2br(e($internacao['observacoes'])) ?></p>
                    <?php else: ?>
                        <p>Sem observações adicionais.</p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($internacao['unidade_cidade']) || !empty($internacao['unidade_estado'])): ?>
                    <div class="historico-section">
                        <strong>Local</strong>
                        <p>
                            <?= e($internacao['unidade_cidade'] ?: '-') ?>
                            <?= !empty($internacao['unidade_estado']) ? ' - ' . e($internacao['unidade_estado']) : '' ?>
                        </p>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>