<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$erro = $_GET['erro'] ?? '';

$profissional = [
    'id' => '',
    'usuario_id' => '',
    'unidade_id' => '',
    'nome' => '',
    'tipo' => 'Medico',
    'especialidade' => '',
    'registro_profissional' => '',
    'telefone' => '',
    'email' => ''
];

if ($id > 0) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM profissionais
        WHERE id = :id
        AND ativo = 1
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $profissionalEncontrado = $stmt->fetch();

    if (!$profissionalEncontrado) {
        header('Location: profissionais.php');
        exit;
    }

    $profissional = $profissionalEncontrado;
}

$stmtUnidades = $pdo->query("
    SELECT id, nome, tipo
    FROM unidades
    WHERE ativo = 1
    ORDER BY nome ASC
");

$unidades = $stmtUnidades->fetchAll();

$pageTitle = $id > 0 ? 'Editar Profissional' : 'Novo Profissional';
$pageSubtitle = 'Preencha os dados profissionais e vínculo com unidade';
$menuAtivo = 'profissionais';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2><?= $id > 0 ? 'Editar Profissional' : 'Cadastrar Profissional' ?></h2>
        <p>Esses dados serão usados em agendas, consultas, prontuários e prescrições.</p>
    </div>

    <a href="<?= BASE_URL ?>admin/profissionais.php" class="btn btn-light">
        Voltar
    </a>
</section>

<?php if ($erro === 'nome'): ?>
    <div class="alert-error">Informe o nome do profissional.</div>
<?php elseif ($erro === 'tipo'): ?>
    <div class="alert-error">Tipo de profissional inválido.</div>
<?php elseif ($erro === 'csrf'): ?>
    <div class="alert-error">Sessão expirada. Atualize a página e tente novamente.</div>
<?php endif; ?>

<section class="panel">
    <form method="post" action="<?= BASE_URL ?>admin/profissional_salvar.php" class="form-card">
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <input type="hidden" name="id" value="<?= e($profissional['id']) ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="nome">Nome completo</label>
                <input 
                    type="text" 
                    id="nome" 
                    name="nome" 
                    value="<?= e($profissional['nome']) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="tipo">Tipo</label>
                <select id="tipo" name="tipo" required>
                    <option value="Medico" <?= $profissional['tipo'] === 'Medico' ? 'selected' : '' ?>>
                        Médico
                    </option>
                    <option value="Enfermeiro" <?= $profissional['tipo'] === 'Enfermeiro' ? 'selected' : '' ?>>
                        Enfermeiro
                    </option>
                    <option value="Tecnico" <?= $profissional['tipo'] === 'Tecnico' ? 'selected' : '' ?>>
                        Técnico
                    </option>
                    <option value="Outro" <?= $profissional['tipo'] === 'Outro' ? 'selected' : '' ?>>
                        Outro
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="especialidade">Especialidade</label>
                <input 
                    type="text" 
                    id="especialidade" 
                    name="especialidade" 
                    value="<?= e($profissional['especialidade']) ?>"
                    placeholder="Ex: Cardiologia, Pediatria, Enfermagem"
                >
            </div>

            <div class="form-group">
                <label for="registro_profissional">Registro profissional</label>
                <input 
                    type="text" 
                    id="registro_profissional" 
                    name="registro_profissional" 
                    value="<?= e($profissional['registro_profissional']) ?>"
                    placeholder="Ex: CRM, COREN, registro interno"
                >
            </div>

            <div class="form-group">
                <label for="telefone">Telefone</label>
                <input 
                    type="text" 
                    id="telefone" 
                    name="telefone" 
                    value="<?= e($profissional['telefone']) ?>"
                >
            </div>

            <div class="form-group">
                <label for="email">E-mail</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="<?= e($profissional['email']) ?>"
                >
            </div>

            <div class="form-group form-group-full">
                <label for="unidade_id">Unidade de atendimento</label>
                <select id="unidade_id" name="unidade_id">
                    <option value="">Nenhuma unidade vinculada</option>

                    <?php foreach ($unidades as $unidade): ?>
                        <option 
                            value="<?= (int)$unidade['id'] ?>"
                            <?= (int)$profissional['unidade_id'] === (int)$unidade['id'] ? 'selected' : '' ?>
                        >
                            <?= e($unidade['nome']) ?> - <?= e($unidade['tipo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if (empty($unidades)): ?>
                    <small class="form-help">
                        Nenhuma unidade cadastrada ainda. Depois criaremos o cadastro de unidades.
                    </small>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>admin/profissionais.php" class="btn btn-light">
                Cancelar
            </a>

            <button type="submit" class="btn btn-primary-small">
                Salvar Profissional
            </button>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>