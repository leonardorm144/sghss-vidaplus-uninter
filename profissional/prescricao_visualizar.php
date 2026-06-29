<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('profissional');

$usuarioId = $_SESSION['usuario_id'] ?? 0;

$stmtProfissionalLogado = $pdo->prepare("
    SELECT id
    FROM profissionais
    WHERE usuario_id = :usuario_id
    AND ativo = 1
    LIMIT 1
");

$stmtProfissionalLogado->execute([
    ':usuario_id' => $usuarioId
]);

$profissionalLogado = $stmtProfissionalLogado->fetch();

if (!$profissionalLogado) {
    header('Location: prescricoes.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: prescricoes.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        ps.*,
        p.nome AS paciente_nome,
        p.cpf AS paciente_cpf,
        p.data_nascimento,
        pr.nome AS profissional_nome,
        pr.especialidade AS profissional_especialidade,
        pr.registro_profissional,
        c.data_consulta,
        c.tipo AS consulta_tipo
    FROM prescricoes ps
    INNER JOIN pacientes p ON p.id = ps.paciente_id
    INNER JOIN profissionais pr ON pr.id = ps.profissional_id
    LEFT JOIN consultas c ON c.id = ps.consulta_id
    WHERE ps.id = :id
    AND ps.profissional_id = :profissional_id
    AND ps.ativo = 1
    LIMIT 1
");

$stmt->execute([
    ':id' => $id,
    ':profissional_id' => $profissionalLogado['id']
]);

$prescricao = $stmt->fetch();

if (!$prescricao) {
    header('Location: prescricoes.php');
    exit;
}

$pageTitle = 'Receita Digital';
$pageSubtitle = 'Visualização e impressão da prescrição';
$menuAtivo = 'prescricoes';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions no-print">
    <div>
        <h2>Receita Digital</h2>
        <p>Visualize a prescrição e utilize a opção de impressão.</p>
    </div>

    <div class="actions-inline">
        <button type="button" class="btn btn-primary-small" onclick="window.print()">
            Imprimir
        </button>

        <a href="<?= BASE_URL ?>profissional/prescricoes.php" class="btn btn-light">
            Voltar
        </a>
    </div>
</section>

<section class="receita-page">
    <div class="receita-documento">

        <div class="receita-topo">
            <div class="receita-marca">
                <div class="receita-logo-icon">+</div>

                <div>
                    <h2>VidaPlus</h2>
                    <span>SGHSS - Receita Digital</span>
                </div>
            </div>

            <div class="receita-emissao">
                <span>Emitida em</span>
                <strong><?= date('d/m/Y H:i', strtotime($prescricao['data_emissao'])) ?></strong>
            </div>
        </div>

        <div class="receita-titulo">
            <h1>Receita Digital</h1>
            <p>Prescrição emitida eletronicamente pelo Sistema de Gestão Hospitalar e Serviços de Saúde.</p>
        </div>

        <div class="receita-grid">
            <div class="receita-bloco">
                <h3>Dados do Paciente</h3>

                <div class="receita-linha">
                    <span>Nome</span>
                    <strong><?= e($prescricao['paciente_nome']) ?></strong>
                </div>

                <?php if (!empty($prescricao['paciente_cpf'])): ?>
                    <div class="receita-linha">
                        <span>CPF</span>
                        <strong><?= e($prescricao['paciente_cpf']) ?></strong>
                    </div>
                <?php endif; ?>

                <?php if (!empty($prescricao['data_nascimento'])): ?>
                    <div class="receita-linha">
                        <span>Data de nascimento</span>
                        <strong><?= date('d/m/Y', strtotime($prescricao['data_nascimento'])) ?></strong>
                    </div>
                <?php endif; ?>
            </div>

            <div class="receita-bloco">
                <h3>Profissional Responsável</h3>

                <div class="receita-linha">
                    <span>Nome</span>
                    <strong><?= e($prescricao['profissional_nome']) ?></strong>
                </div>

                <?php if (!empty($prescricao['profissional_especialidade'])): ?>
                    <div class="receita-linha">
                        <span>Especialidade</span>
                        <strong><?= e($prescricao['profissional_especialidade']) ?></strong>
                    </div>
                <?php endif; ?>

                <?php if (!empty($prescricao['registro_profissional'])): ?>
                    <div class="receita-linha">
                        <span>Registro</span>
                        <strong><?= e($prescricao['registro_profissional']) ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="receita-prescricao">
            <h3>Prescrição</h3>

            <div class="receita-medicamento-card">
                <span>Medicamento</span>
                <strong><?= e($prescricao['medicamento']) ?></strong>
            </div>

            <div class="receita-medicamento-card">
                <span>Dosagem</span>
                <strong><?= e($prescricao['dosagem'] ?: 'Não informada') ?></strong>
            </div>

            <div class="receita-orientacoes">
                <span>Orientações</span>
                <p><?= nl2br(e($prescricao['orientacoes'] ?: 'Sem orientações adicionais.')) ?></p>
            </div>
        </div>

        <?php if (!empty($prescricao['data_consulta'])): ?>
            <div class="receita-consulta">
                <h3>Consulta Vinculada</h3>

                <div class="receita-consulta-grid">
                    <div>
                        <span>Data da consulta</span>
                        <strong><?= date('d/m/Y H:i', strtotime($prescricao['data_consulta'])) ?></strong>
                    </div>

                    <div>
                        <span>Tipo</span>
                        <strong><?= e($prescricao['consulta_tipo']) ?></strong>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="receita-assinatura">
            <div class="receita-assinatura-linha"></div>
            <strong><?= e($prescricao['profissional_nome']) ?></strong>

            <?php if (!empty($prescricao['registro_profissional'])): ?>
                <span><?= e($prescricao['registro_profissional']) ?></span>
            <?php endif; ?>

            <small>Assinatura digital simulada para fins acadêmicos</small>
        </div>

        <div class="receita-rodape">
            <p>
                Documento emitido pelo SGHSS VidaPlus. Esta receita digital faz parte de um projeto acadêmico.
            </p>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>