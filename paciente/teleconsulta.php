<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('paciente');

$pageTitle = 'Teleconsulta';
$pageSubtitle = 'Acesso aos atendimentos online';
$menuAtivo = 'teleconsulta';

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

$teleconsultas = [];

if ($paciente) {
    $stmtTeleconsultas = $pdo->prepare("
        SELECT 
            c.*,
            pr.nome AS profissional_nome,
            pr.especialidade AS profissional_especialidade
        FROM consultas c
        INNER JOIN profissionais pr ON pr.id = c.profissional_id
        WHERE c.paciente_id = :paciente_id
        AND c.tipo = 'Telemedicina'
        AND c.status IN ('Agendada', 'Confirmada')
        ORDER BY c.data_consulta ASC
    ");

    $stmtTeleconsultas->execute([
        ':paciente_id' => $paciente['id']
    ]);

    $teleconsultas = $stmtTeleconsultas->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!$paciente): ?>
    <section class="panel">
        <h2>Paciente não vinculado</h2>
        <p>Seu usuário ainda não está vinculado a um cadastro de paciente.</p>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    <?php exit; ?>
<?php endif; ?>

<section class="page-actions">
    <div>
        <h2>Minhas Teleconsultas</h2>
        <p>Acesse suas consultas online agendadas ou confirmadas.</p>
    </div>
</section>

<section class="panel">
    <div class="agenda-list">
        <?php if (empty($teleconsultas)): ?>
            <div class="empty-state agenda-empty">
                Nenhuma teleconsulta disponível no momento.
            </div>
        <?php endif; ?>

        <?php foreach ($teleconsultas as $consulta): ?>
            <article class="agenda-card">
                <div class="agenda-card-header">
                    <div>
                        <span class="agenda-date">
                            <?= date('d/m/Y H:i', strtotime($consulta['data_consulta'])) ?>
                        </span>

                        <h3><?= e($consulta['profissional_nome']) ?></h3>

                        <?php if (!empty($consulta['profissional_especialidade'])): ?>
                            <p>Especialidade: <?= e($consulta['profissional_especialidade']) ?></p>
                        <?php endif; ?>

                        <p>Motivo: <?= e($consulta['motivo'] ?: 'Não informado') ?></p>
                    </div>

                    <span class="badge badge-success">
                        <?= e($consulta['status']) ?>
                    </span>
                </div>

                <div class="agenda-card-actions">
                    <?php if (!empty($consulta['link_teleconsulta'])): ?>
                        <a 
                            href="<?= e($consulta['link_teleconsulta']) ?>" 
                            target="_blank"
                            class="btn btn-secondary"
                        >
                            Entrar na Sala
                        </a>
                    <?php else: ?>
                        <span class="badge badge-warning">Link não disponível</span>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>