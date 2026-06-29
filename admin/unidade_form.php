<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$erro = $_GET['erro'] ?? '';

$unidade = [
    'id' => '',
    'nome' => '',
    'tipo' => 'Hospital',
    'cidade' => '',
    'estado' => ''
];

if ($id > 0) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM unidades
        WHERE id = :id
        AND ativo = 1
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $unidadeEncontrada = $stmt->fetch();

    if (!$unidadeEncontrada) {
        header('Location: unidades.php');
        exit;
    }

    $unidade = $unidadeEncontrada;
}

$pageTitle = $id > 0 ? 'Editar Unidade' : 'Nova Unidade';
$pageSubtitle = 'Cadastre hospitais, clínicas, laboratórios e home care';
$menuAtivo = 'unidades';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2><?= $id > 0 ? 'Editar Unidade' : 'Cadastrar Unidade' ?></h2>
        <p>Essas unidades serão usadas em consultas, profissionais, leitos e exames.</p>
    </div>

    <a href="<?= BASE_URL ?>admin/unidades.php" class="btn btn-light">
        Voltar
    </a>
</section>

<?php if ($erro === 'nome'): ?>
    <div class="alert-error">Informe o nome da unidade.</div>
<?php elseif ($erro === 'tipo'): ?>
    <div class="alert-error">Tipo de unidade inválido.</div>
<?php elseif ($erro === 'csrf'): ?>
    <div class="alert-error">Sessão expirada. Atualize a página e tente novamente.</div>
<?php endif; ?>

<section class="panel">
    <form method="post" action="<?= BASE_URL ?>admin/unidade_salvar.php" class="form-card">
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <input type="hidden" name="id" value="<?= e($unidade['id']) ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="nome">Nome da unidade</label>
                <input 
                    type="text" 
                    id="nome" 
                    name="nome" 
                    value="<?= e($unidade['nome']) ?>"
                    placeholder="Ex: Hospital VidaPlus Central"
                    required
                >
            </div>

            <div class="form-group">
                <label for="tipo">Tipo</label>
                <select id="tipo" name="tipo" required>
                    <option value="Hospital" <?= $unidade['tipo'] === 'Hospital' ? 'selected' : '' ?>>
                        Hospital
                    </option>

                    <option value="Clinica" <?= $unidade['tipo'] === 'Clinica' ? 'selected' : '' ?>>
                        Clínica
                    </option>

                    <option value="Laboratorio" <?= $unidade['tipo'] === 'Laboratorio' ? 'selected' : '' ?>>
                        Laboratório
                    </option>

                    <option value="Home Care" <?= $unidade['tipo'] === 'Home Care' ? 'selected' : '' ?>>
                        Home Care
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="cidade">Cidade</label>
                <input 
                    type="text" 
                    id="cidade" 
                    name="cidade" 
                    value="<?= e($unidade['cidade']) ?>"
                    placeholder="Ex: São Paulo"
                >
            </div>

            <div class="form-group">
                <label for="estado">Estado</label>
                <input 
                    type="text" 
                    id="estado" 
                    name="estado" 
                    value="<?= e($unidade['estado']) ?>"
                    placeholder="Ex: SP"
                    maxlength="50"
                >
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>admin/unidades.php" class="btn btn-light">
                Cancelar
            </a>

            <button type="submit" class="btn btn-primary-small">
                Salvar Unidade
            </button>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>