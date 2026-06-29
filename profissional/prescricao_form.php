<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('profissional');

$usuarioId = $_SESSION['usuario_id'] ?? 0;

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

if (!$profissional) {
    header('Location: prescricoes.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$consultaIdGet = isset($_GET['consulta_id']) ? (int)$_GET['consulta_id'] : 0;
$erro = $_GET['erro'] ?? '';

$prescricao = [
    'id' => '',
    'paciente_id' => '',
    'consulta_id' => '',
    'medicamento' => '',
    'dosagem' => '',
    'orientacoes' => ''
];

if ($id > 0) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM prescricoes
        WHERE id = :id
        AND profissional_id = :profissional_id
        AND ativo = 1
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $id,
        ':profissional_id' => $profissional['id']
    ]);

    $prescricaoEncontrada = $stmt->fetch();

    if (!$prescricaoEncontrada) {
        header('Location: prescricoes.php');
        exit;
    }

    $prescricao = $prescricaoEncontrada;
}

if ($id <= 0 && $consultaIdGet > 0) {
    $stmtConsultaInicial = $pdo->prepare("
        SELECT paciente_id
        FROM consultas
        WHERE id = :id
        AND profissional_id = :profissional_id
        AND status <> 'Cancelada'
        LIMIT 1
    ");

    $stmtConsultaInicial->execute([
        ':id' => $consultaIdGet,
        ':profissional_id' => $profissional['id']
    ]);

    $consultaInicial = $stmtConsultaInicial->fetch();

    if ($consultaInicial) {
        $prescricao['consulta_id'] = $consultaIdGet;
        $prescricao['paciente_id'] = $consultaInicial['paciente_id'];
    }
}

$stmtPacientes = $pdo->prepare("
    SELECT DISTINCT
        p.id,
        p.nome,
        p.cpf
    FROM pacientes p
    INNER JOIN consultas c ON c.paciente_id = p.id
    WHERE c.profissional_id = :profissional_id
    AND p.ativo = 1
    ORDER BY p.nome ASC
");

$stmtPacientes->execute([
    ':profissional_id' => $profissional['id']
]);

$pacientes = $stmtPacientes->fetchAll();

$stmtConsultas = $pdo->prepare("
    SELECT 
        c.id,
        c.paciente_id,
        c.data_consulta,
        c.tipo,
        c.status,
        p.nome AS paciente_nome
    FROM consultas c
    INNER JOIN pacientes p ON p.id = c.paciente_id
    WHERE c.profissional_id = :profissional_id
    AND c.status <> 'Cancelada'
    AND p.ativo = 1
    ORDER BY c.data_consulta DESC
");

$stmtConsultas->execute([
    ':profissional_id' => $profissional['id']
]);

$consultas = $stmtConsultas->fetchAll();

$pageTitle = $id > 0 ? 'Editar Prescrição' : 'Nova Prescrição';
$pageSubtitle = 'Emissão de receita digital pelo profissional';
$menuAtivo = 'prescricoes';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2><?= $id > 0 ? 'Editar Prescrição' : 'Emitir Prescrição' ?></h2>
        <p>Informe medicamento, dosagem e orientações ao paciente.</p>
    </div>

    <a href="<?= BASE_URL ?>profissional/prescricoes.php" class="btn btn-light">
        Voltar
    </a>
</section>

<?php if ($erro === 'paciente'): ?>
    <div class="alert-error">Selecione o paciente.</div>
<?php elseif ($erro === 'medicamento'): ?>
    <div class="alert-error">Informe o medicamento.</div>
<?php elseif ($erro === 'consulta'): ?>
    <div class="alert-error">Consulta inválida para este profissional.</div>
<?php elseif ($erro === 'csrf'): ?>
    <div class="alert-error">Sessão expirada. Atualize a página e tente novamente.</div>
<?php endif; ?>

<?php if (empty($pacientes)): ?>
    <div class="alert-error">
        Nenhum paciente encontrado para este profissional. Primeiro é necessário ter uma consulta vinculada a você.
    </div>
<?php endif; ?>

<section class="panel">
    <form method="post" action="<?= BASE_URL ?>profissional/prescricao_salvar.php" class="form-card">
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <input type="hidden" name="id" value="<?= e($prescricao['id']) ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="paciente_id">Paciente</label>
                <select id="paciente_id" name="paciente_id" required>
                    <option value="">Selecione o paciente</option>

                    <?php foreach ($pacientes as $paciente): ?>
                        <option 
                            value="<?= (int)$paciente['id'] ?>"
                            <?= (int)$prescricao['paciente_id'] === (int)$paciente['id'] ? 'selected' : '' ?>
                        >
                            <?= e($paciente['nome']) ?>
                            <?= !empty($paciente['cpf']) ? ' - CPF: ' . e($paciente['cpf']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="consulta_id">Consulta vinculada</label>
                <select id="consulta_id" name="consulta_id">
                    <option value="">Sem consulta vinculada</option>

                    <?php foreach ($consultas as $consulta): ?>
                        <option 
                            value="<?= (int)$consulta['id'] ?>"
                            <?= (int)$prescricao['consulta_id'] === (int)$consulta['id'] ? 'selected' : '' ?>
                        >
                            <?= date('d/m/Y H:i', strtotime($consulta['data_consulta'])) ?>
                            - <?= e($consulta['paciente_nome']) ?>
                            / <?= e($consulta['tipo']) ?>
                            / <?= e($consulta['status']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="medicamento">Medicamento</label>
                <input 
                    type="text" 
                    id="medicamento" 
                    name="medicamento" 
                    value="<?= e($prescricao['medicamento']) ?>"
                    placeholder="Ex: Dipirona 500mg"
                    required
                >
            </div>

            <div class="form-group">
                <label for="dosagem">Dosagem</label>
                <input 
                    type="text" 
                    id="dosagem" 
                    name="dosagem" 
                    value="<?= e($prescricao['dosagem']) ?>"
                    placeholder="Ex: 1 comprimido de 8 em 8 horas"
                >
            </div>

            <div class="form-group form-group-full">
                <label for="orientacoes">Orientações</label>
                <textarea 
                    id="orientacoes" 
                    name="orientacoes" 
                    rows="5"
                    placeholder="Informe orientações de uso, duração do tratamento e cuidados adicionais"
                ><?= e($prescricao['orientacoes']) ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>profissional/prescricoes.php" class="btn btn-light">
                Cancelar
            </a>

            <button 
                type="submit" 
                class="btn btn-primary-small"
                <?= empty($pacientes) ? 'disabled' : '' ?>
            >
                Salvar Prescrição
            </button>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>