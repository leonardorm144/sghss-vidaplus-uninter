<?php

$pageTitle = 'Teleconsulta';
$pageSubtitle = 'Ambiente online de atendimento VidaPlus';
$menuAtivo = 'consultas';

require_once __DIR__ . '/includes/header.php';

$sala = trim($_GET['sala'] ?? '');
?>

<section class="page-actions">
    <div>
        <h2>Sala de Teleconsulta</h2>
        <p>Ambiente seguro para atendimento online entre paciente e profissional.</p>
    </div>

    <a href="<?= BASE_URL ?>dashboard.php" class="btn btn-light">
        Voltar
    </a>
</section>

<section class="panel">
    <?php if ($sala === ''): ?>
        <div class="alert-error">
            Sala de teleconsulta não informada.
        </div>
    <?php else: ?>
        <div class="teleconsulta-box">
            <div class="teleconsulta-video">
                <div class="teleconsulta-avatar">+</div>
                <h3>Teleconsulta VidaPlus</h3>
                <p>Sala: <?= e($sala) ?></p>
            </div>

            <div class="teleconsulta-info">
                <h2>Atendimento Online</h2>

                <p>
                    Esta tela representa o ambiente de telemedicina do SGHSS VidaPlus.
                    No projeto acadêmico, ela simula a sala segura da consulta online.
                </p>

                <ul>
                    <li>Paciente e profissional acessam pelo link da consulta.</li>
                    <li>O profissional registra prontuário e prescrição após o atendimento.</li>
                    <li>O acesso é controlado por login e sessão ativa.</li>
                    <li>A consulta fica registrada no histórico do sistema.</li>
                </ul>

                <button type="button" class="btn btn-primary-small" onclick="alert('Simulação de chamada iniciada com sucesso.')">
                    Iniciar atendimento
                </button>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>