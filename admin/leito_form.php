<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$erro = $_GET['erro'] ?? '';

$leito = [
    'id' => '',
    'unidade_id' => '',
    'numero' => '',
    'setor' => '',
    'status' => 'Disponivel'
];

if ($id > 0) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM leitos
        WHERE id = :id
        AND ativo = 1
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $leitoEncontrado = $stmt->fetch();

    if (!$leitoEncontrado) {
        header('Location: leitos.php');
        exit;
    }

    $leito = $leitoEncontrado;
}

$stmtUnidades = $pdo->query("
    SELECT id, nome, tipo
    FROM unidades
    WHERE ativo = 1
    ORDER BY nome ASC
");

$unidades = $stmtUnidades->fetchAll();

$pageTitle = $id > 0 ? 'Editar Leito' : 'Novo Leito';
$pageSubtitle = 'Cadastre e controle leitos por unidade hospitalar';
$menuAtivo = 'leitos';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2><?= $id > 0 ? 'Editar Leito' : 'Cadastrar Leito' ?></h2>
        <p>Defina a unidade, número, setor e status atual do leito.</p>
    </div>

    <a href="<?= BASE_URL ?>admin/leitos.php" class="btn btn-light">
        Voltar
    </a>
</section>

<?php if ($erro === 'numero'): ?>
    <div class="alert-error">Informe o número do leito.</div>
<?php elseif ($erro === 'unidade'): ?>
    <div class="alert-error">Selecione a unidade do leito.</div>
<?php elseif ($erro === 'status'): ?>
    <div class="alert-error">Status do leito inválido.</div>
<?php elseif ($erro === 'duplicado'): ?>
    <div class="alert-error">Já existe um leito com este número nesta unidade.</div>
<?php elseif ($erro === 'csrf'): ?>
    <div class="alert-error">Sessão expirada. Atualize a página e tente novamente.</div>
<?php endif; ?>

<?php if (empty($unidades)): ?>
    <div class="alert-error">
        Nenhuma unidade cadastrada. Cadastre uma unidade antes de criar leitos.
    </div>
<?php endif; ?>

<section class="panel">
    <form method="post" action="<?= BASE_URL ?>admin/leito_salvar.php" class="form-card">
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <input type="hidden" name="id" value="<?= e($leito['id']) ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="unidade_id">Unidade</label>
                <select id="unidade_id" name="unidade_id" required>
                    <option value="">Selecione uma unidade</option>

                    <?php foreach ($unidades as $unidade): ?>
                        <option 
                            value="<?= (int)$unidade['id'] ?>"
                            <?= (int)$leito['unidade_id'] === (int)$unidade['id'] ? 'selected' : '' ?>
                        >
                            <?= e($unidade['nome']) ?> - <?= e($unidade['tipo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="numero">Número do leito</label>
                <input 
                    type="text" 
                    id="numero" 
                    name="numero" 
                    value="<?= e($leito['numero']) ?>"
                    placeholder="Ex: 101A, UTI-01, 204"
                    required
                >
            </div>

            <div class="form-group">
                <label for="setor">Setor</label>
                <input 
                    type="text" 
                    id="setor" 
                    name="setor" 
                    value="<?= e($leito['setor']) ?>"
                    placeholder="Ex: UTI, Pediatria, Enfermaria"
                >
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="Disponivel" <?= $leito['status'] === 'Disponivel' ? 'selected' : '' ?>>
                        Disponível
                    </option>

                    <option value="Ocupado" <?= $leito['status'] === 'Ocupado' ? 'selected' : '' ?>>
                        Ocupado
                    </option>

                    <option value="Manutencao" <?= $leito['status'] === 'Manutencao' ? 'selected' : '' ?>>
                        Manutenção
                    </option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>admin/leitos.php" class="btn btn-light">
                Cancelar
            </a>

            <button 
                type="submit" 
                class="btn btn-primary-small"
                <?= empty($unidades) ? 'disabled' : '' ?>
            >
                Salvar Leito
            </button>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>