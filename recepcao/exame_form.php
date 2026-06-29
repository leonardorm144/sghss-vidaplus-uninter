<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('recepcao');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$erro = $_GET['erro'] ?? '';

$exame = [
    'id' => '',
    'paciente_id' => '',
    'unidade_id' => '',
    'nome_exame' => '',
    'data_exame' => '',
    'status' => 'Solicitado',
    'resultado' => '',
    'observacoes' => ''
];

if ($id > 0) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM exames
        WHERE id = :id
        AND ativo = 1
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $exameEncontrado = $stmt->fetch();

    if (!$exameEncontrado) {
        header('Location: exames.php');
        exit;
    }

    $exame = $exameEncontrado;
}

$stmtPacientes = $pdo->query("
    SELECT id, nome, cpf
    FROM pacientes
    WHERE ativo = 1
    ORDER BY nome ASC
");

$pacientes = $stmtPacientes->fetchAll();

$stmtUnidades = $pdo->query("
    SELECT id, nome, tipo, cidade, estado
    FROM unidades
    WHERE ativo = 1
    ORDER BY nome ASC
");

$unidades = $stmtUnidades->fetchAll();

$pageTitle = $id > 0 ? 'Editar Exame' : 'Novo Exame';
$pageSubtitle = 'Cadastro de solicitação, agendamento e resultado de exame';
$menuAtivo = 'exames';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2><?= $id > 0 ? 'Editar Exame' : 'Cadastrar Exame' ?></h2>
        <p>Informe paciente, tipo de exame, data, status e resultado.</p>
    </div>

    <a href="<?= BASE_URL ?>recepcao/exames.php" class="btn btn-light">
        Voltar
    </a>
</section>

<?php if ($erro === 'paciente'): ?>
    <div class="alert-error">Selecione um paciente válido.</div>
<?php elseif ($erro === 'nome_exame'): ?>
    <div class="alert-error">Informe o nome do exame.</div>
<?php elseif ($erro === 'status'): ?>
    <div class="alert-error">Status inválido.</div>
<?php elseif ($erro === 'unidade'): ?>
    <div class="alert-error">Unidade inválida.</div>
<?php elseif ($erro === 'csrf'): ?>
    <div class="alert-error">Sessão expirada. Atualize a página e tente novamente.</div>
<?php endif; ?>

<section class="panel">
    <form method="post" action="<?= BASE_URL ?>recepcao/exame_salvar.php" class="form-card">
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <input type="hidden" name="id" value="<?= e($exame['id']) ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="paciente_id">Paciente</label>
                <select id="paciente_id" name="paciente_id" required>
                    <option value="">Selecione</option>

                    <?php foreach ($pacientes as $paciente): ?>
                        <option 
                            value="<?= (int)$paciente['id'] ?>"
                            <?= (int)$exame['paciente_id'] === (int)$paciente['id'] ? 'selected' : '' ?>
                        >
                            <?= e($paciente['nome']) ?>
                            <?= !empty($paciente['cpf']) ? ' - CPF: ' . e($paciente['cpf']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="unidade_id">Unidade/Laboratório</label>
                <select id="unidade_id" name="unidade_id">
                    <option value="">Sem unidade definida</option>

                    <?php foreach ($unidades as $unidade): ?>
                        <option 
                            value="<?= (int)$unidade['id'] ?>"
                            <?= (int)$exame['unidade_id'] === (int)$unidade['id'] ? 'selected' : '' ?>
                        >
                            <?= e($unidade['nome']) ?>
                            - <?= e($unidade['tipo']) ?>
                            <?= !empty($unidade['cidade']) ? ' / ' . e($unidade['cidade']) : '' ?>
                            <?= !empty($unidade['estado']) ? ' - ' . e($unidade['estado']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="nome_exame">Nome do exame</label>
                <input 
                    type="text" 
                    id="nome_exame" 
                    name="nome_exame" 
                    value="<?= e($exame['nome_exame']) ?>"
                    placeholder="Ex: Hemograma completo"
                    required
                >
            </div>

            <div class="form-group">
                <label for="data_exame">Data/Hora do exame</label>
                <input 
                    type="datetime-local" 
                    id="data_exame" 
                    name="data_exame" 
                    value="<?= !empty($exame['data_exame']) ? date('Y-m-d\TH:i', strtotime($exame['data_exame'])) : '' ?>"
                >
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="Solicitado" <?= $exame['status'] === 'Solicitado' ? 'selected' : '' ?>>
                        Solicitado
                    </option>

                    <option value="Agendado" <?= $exame['status'] === 'Agendado' ? 'selected' : '' ?>>
                        Agendado
                    </option>

                    <option value="Realizado" <?= $exame['status'] === 'Realizado' ? 'selected' : '' ?>>
                        Realizado
                    </option>

                    <option value="Cancelado" <?= $exame['status'] === 'Cancelado' ? 'selected' : '' ?>>
                        Cancelado
                    </option>
                </select>
            </div>

            <div class="form-group form-group-full">
                <label for="resultado">Resultado/Resumo</label>
                <textarea 
                    id="resultado" 
                    name="resultado" 
                    rows="5"
                    placeholder="Informe o resultado ou resumo do exame, se disponível"
                ><?= e($exame['resultado']) ?></textarea>
            </div>

            <div class="form-group form-group-full">
                <label for="observacoes">Observações</label>
                <textarea 
                    id="observacoes" 
                    name="observacoes" 
                    rows="4"
                    placeholder="Informe orientações, preparo necessário ou observações internas"
                ><?= e($exame['observacoes']) ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>recepcao/exames.php" class="btn btn-light">
                Cancelar
            </a>

            <button type="submit" class="btn btn-primary-small">
                Salvar Exame
            </button>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>