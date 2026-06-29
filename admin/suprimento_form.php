<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$erro = $_GET['erro'] ?? '';

$suprimento = [
    'id' => 0,
    'unidade_id' => '',
    'nome' => '',
    'categoria' => '',
    'unidade_medida' => 'Unidade',
    'estoque_atual' => '0.00',
    'estoque_minimo' => '0.00',
    'observacoes' => ''
];

if ($id > 0) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM suprimentos
        WHERE id = :id
        AND ativo = 1
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $registro = $stmt->fetch();

    if (!$registro) {
        header('Location: suprimentos.php');
        exit;
    }

    $suprimento = $registro;
}

$stmtUnidades = $pdo->query("
    SELECT id, nome, tipo
    FROM unidades
    WHERE ativo = 1
    ORDER BY nome ASC
");

$unidades = $stmtUnidades->fetchAll();

$stmtCategorias = $pdo->query("
    SELECT DISTINCT categoria
    FROM suprimentos
    WHERE ativo = 1
    AND categoria IS NOT NULL
    AND categoria <> ''
    ORDER BY categoria ASC
");

$categorias = $stmtCategorias->fetchAll();

function valorDecimalInput($valor)
{
    return number_format((float)$valor, 2, '.', '');
}

$pageTitle = $id > 0 ? 'Editar Suprimento' : 'Novo Suprimento';
$pageSubtitle = 'Cadastro de materiais e controle inicial de estoque';
$menuAtivo = 'suprimentos';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2><?= $id > 0 ? 'Editar Suprimento' : 'Novo Suprimento' ?></h2>
        <p>
            Informe os dados do item, unidade vinculada, estoque atual e estoque mínimo.
        </p>
    </div>

    <a href="<?= BASE_URL ?>admin/suprimentos.php" class="btn btn-light">
        Voltar
    </a>
</section>

<?php if ($erro === 'dados'): ?>
    <div class="alert-error">
        Preencha corretamente os campos obrigatórios.
    </div>
<?php elseif ($erro === 'csrf'): ?>
    <div class="alert-error">
        Sessão expirada ou token inválido. Tente novamente.
    </div>
<?php elseif ($erro === 'unidade'): ?>
    <div class="alert-error">
        Selecione uma unidade válida para o suprimento.
    </div>
<?php endif; ?>

<section class="panel">
    <form method="post" action="<?= BASE_URL ?>admin/suprimento_salvar.php">
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <input type="hidden" name="id" value="<?= (int)$suprimento['id'] ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="nome">Nome do suprimento *</label>
                <input 
                    type="text" 
                    id="nome" 
                    name="nome" 
                    maxlength="150"
                    required
                    placeholder="Ex.: Luva descartável"
                    value="<?= e($suprimento['nome']) ?>"
                >
            </div>

            <div class="form-group">
                <label for="categoria">Categoria</label>
                <input 
                    type="text" 
                    id="categoria" 
                    name="categoria" 
                    maxlength="100"
                    list="lista_categorias"
                    placeholder="Ex.: EPI, Curativo, Higienização"
                    value="<?= e($suprimento['categoria']) ?>"
                >

                <datalist id="lista_categorias">
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= e($categoria['categoria']) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div class="form-group">
                <label for="unidade_id">Unidade *</label>
                <select id="unidade_id" name="unidade_id" required>
                    <option value="">Selecione</option>

                    <?php foreach ($unidades as $unidade): ?>
                        <option 
                            value="<?= (int)$unidade['id'] ?>"
                            <?= (int)$suprimento['unidade_id'] === (int)$unidade['id'] ? 'selected' : '' ?>
                        >
                            <?= e($unidade['nome']) ?><?= !empty($unidade['tipo']) ? ' - ' . e($unidade['tipo']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="unidade_medida">Unidade de medida *</label>
                <select id="unidade_medida" name="unidade_medida" required>
                    <?php
                        $opcoesMedida = [
                            'Unidade',
                            'Caixa',
                            'Pacote',
                            'Frasco',
                            'Ampola',
                            'Par',
                            'Kit',
                            'Litro',
                            'Mililitro',
                            'Metro',
                            'Rolo'
                        ];

                        if (!in_array($suprimento['unidade_medida'], $opcoesMedida) && $suprimento['unidade_medida'] !== '') {
                            $opcoesMedida[] = $suprimento['unidade_medida'];
                        }
                    ?>

                    <?php foreach ($opcoesMedida as $medida): ?>
                        <option 
                            value="<?= e($medida) ?>"
                            <?= $suprimento['unidade_medida'] === $medida ? 'selected' : '' ?>
                        >
                            <?= e($medida) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="estoque_atual">Estoque atual *</label>
                <input 
                    type="number" 
                    id="estoque_atual" 
                    name="estoque_atual" 
                    min="0"
                    step="0.01"
                    required
                    value="<?= e(valorDecimalInput($suprimento['estoque_atual'])) ?>"
                >
                <small>
                    No cadastro inicial, este valor será usado como estoque inicial.
                    Ao editar, alterações nesse campo serão registradas como ajuste.
                </small>
            </div>

            <div class="form-group">
                <label for="estoque_minimo">Estoque mínimo *</label>
                <input 
                    type="number" 
                    id="estoque_minimo" 
                    name="estoque_minimo" 
                    min="0"
                    step="0.01"
                    required
                    value="<?= e(valorDecimalInput($suprimento['estoque_minimo'])) ?>"
                >
            </div>

            <div class="form-group form-group-full">
                <label for="observacoes">Observações</label>
                <textarea 
                    id="observacoes" 
                    name="observacoes" 
                    rows="4"
                    placeholder="Informações adicionais sobre o suprimento"
                ><?= e($suprimento['observacoes']) ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>admin/suprimentos.php" class="btn btn-light">
                Cancelar
            </a>

            <button type="submit" class="btn btn-primary-small">
                Salvar Suprimento
            </button>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>