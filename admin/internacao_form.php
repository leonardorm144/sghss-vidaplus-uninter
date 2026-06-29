<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$erro = $_GET['erro'] ?? '';

$internacao = [
    'id' => '',
    'paciente_id' => '',
    'leito_id' => '',
    'data_entrada' => date('Y-m-d H:i:s'),
    'motivo' => '',
    'observacoes' => ''
];

if ($id > 0) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM internacoes
        WHERE id = :id
        AND ativo = 1
        AND status = 'Ativa'
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $internacaoEncontrada = $stmt->fetch();

    if (!$internacaoEncontrada) {
        header('Location: internacoes.php');
        exit;
    }

    $internacao = $internacaoEncontrada;
}

$stmtPacientes = $pdo->query("
    SELECT id, nome, cpf
    FROM pacientes
    WHERE ativo = 1
    ORDER BY nome ASC
");

$pacientes = $stmtPacientes->fetchAll();

$leitoAtualId = $internacao['leito_id'] ?: 0;

$stmtLeitos = $pdo->prepare("
    SELECT
        l.id,
        l.numero,
        l.setor,
        l.status,
        u.nome AS unidade_nome
    FROM leitos l
    LEFT JOIN unidades u ON u.id = l.unidade_id
    WHERE l.ativo = 1
    AND (
        l.status = 'Disponivel'
        OR l.id = :leito_atual_id
    )
    ORDER BY u.nome ASC, l.setor ASC, l.numero ASC
");

$stmtLeitos->execute([
    ':leito_atual_id' => $leitoAtualId
]);

$leitos = $stmtLeitos->fetchAll();

$pageTitle = $id > 0 ? 'Editar Internação' : 'Nova Internação';
$pageSubtitle = 'Registro de paciente internado e ocupação de leito';
$menuAtivo = 'internacoes';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2><?= $id > 0 ? 'Editar Internação' : 'Registrar Internação' ?></h2>
        <p>Selecione o paciente e o leito que será ocupado.</p>
    </div>

    <a href="<?= BASE_URL ?>admin/internacoes.php" class="btn btn-light">
        Voltar
    </a>
</section>

<?php if ($erro === 'paciente'): ?>
    <div class="alert-error">Selecione um paciente válido.</div>
<?php elseif ($erro === 'leito'): ?>
    <div class="alert-error">Selecione um leito disponível.</div>
<?php elseif ($erro === 'leito_ocupado'): ?>
    <div class="alert-error">Este leito já está ocupado por outra internação ativa.</div>
<?php elseif ($erro === 'data'): ?>
    <div class="alert-error">Informe a data de entrada.</div>
<?php elseif ($erro === 'csrf'): ?>
    <div class="alert-error">Sessão expirada. Atualize a página e tente novamente.</div>
<?php endif; ?>

<section class="panel">
    <form method="post" action="<?= BASE_URL ?>admin/internacao_salvar.php" class="form-card">
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <input type="hidden" name="id" value="<?= e($internacao['id']) ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="paciente_id">Paciente</label>
                <select id="paciente_id" name="paciente_id" required>
                    <option value="">Selecione</option>

                    <?php foreach ($pacientes as $paciente): ?>
                        <option 
                            value="<?= (int)$paciente['id'] ?>"
                            <?= (int)$internacao['paciente_id'] === (int)$paciente['id'] ? 'selected' : '' ?>
                        >
                            <?= e($paciente['nome']) ?>
                            <?= !empty($paciente['cpf']) ? ' - CPF: ' . e($paciente['cpf']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="leito_id">Leito disponível</label>
                <select id="leito_id" name="leito_id" required>
                    <option value="">Selecione</option>

                    <?php foreach ($leitos as $leito): ?>
                        <option 
                            value="<?= (int)$leito['id'] ?>"
                            <?= (int)$internacao['leito_id'] === (int)$leito['id'] ? 'selected' : '' ?>
                        >
                            <?= !empty($leito['unidade_nome']) ? e($leito['unidade_nome']) . ' - ' : '' ?>
                            Leito <?= e($leito['numero']) ?>
                            <?= !empty($leito['setor']) ? ' / ' . e($leito['setor']) : '' ?>
                            <?= $leito['status'] !== 'Disponivel' ? ' / Atual' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="data_entrada">Data/Hora de entrada</label>
                <input 
                    type="datetime-local" 
                    id="data_entrada" 
                    name="data_entrada" 
                    value="<?= !empty($internacao['data_entrada']) ? date('Y-m-d\TH:i', strtotime($internacao['data_entrada'])) : '' ?>"
                    required
                >
            </div>

            <div class="form-group form-group-full">
                <label for="motivo">Motivo da internação</label>
                <textarea 
                    id="motivo" 
                    name="motivo" 
                    rows="4"
                    placeholder="Informe o motivo da internação"
                ><?= e($internacao['motivo']) ?></textarea>
            </div>

            <div class="form-group form-group-full">
                <label for="observacoes">Observações</label>
                <textarea 
                    id="observacoes" 
                    name="observacoes" 
                    rows="4"
                    placeholder="Informe observações administrativas ou clínicas"
                ><?= e($internacao['observacoes']) ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>admin/internacoes.php" class="btn btn-light">
                Cancelar
            </a>

            <button type="submit" class="btn btn-primary-small">
                Salvar Internação
            </button>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>