<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

function normalizarDecimalSuprimento($valor)
{
    $valor = trim((string)$valor);

    if ($valor === '') {
        return 0.00;
    }

    if (strpos($valor, ',') !== false) {
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
    }

    return (float)$valor;
}

function formatarQuantidadeSuprimento($valor)
{
    $numero = (float)$valor;

    if (floor($numero) == $numero) {
        return number_format($numero, 0, ',', '.');
    }

    return number_format($numero, 2, ',', '.');
}

function labelStatusSuprimento($estoqueAtual, $estoqueMinimo)
{
    $estoqueAtual = (float)$estoqueAtual;
    $estoqueMinimo = (float)$estoqueMinimo;

    if ($estoqueAtual <= 0) {
        return 'Sem estoque';
    }

    if ($estoqueAtual <= $estoqueMinimo) {
        return 'Baixo estoque';
    }

    return 'Normal';
}

function classeStatusSuprimento($estoqueAtual, $estoqueMinimo)
{
    $estoqueAtual = (float)$estoqueAtual;
    $estoqueMinimo = (float)$estoqueMinimo;

    if ($estoqueAtual <= 0) {
        return 'badge-danger';
    }

    if ($estoqueAtual <= $estoqueMinimo) {
        return 'badge-warning';
    }

    return 'badge-success';
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['id'] ?? 0);
$tipo = trim($_GET['tipo'] ?? $_POST['tipo_movimentacao'] ?? 'Entrada');
$erro = $_GET['erro'] ?? '';

$tiposPermitidos = ['Entrada', 'Saida'];

if (!in_array($tipo, $tiposPermitidos)) {
    $tipo = 'Entrada';
}

if ($id <= 0) {
    header('Location: suprimentos.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!validarTokenCsrf($csrfToken)) {
        header('Location: suprimento_movimentar.php?id=' . $id . '&tipo=' . urlencode($tipo) . '&erro=csrf');
        exit;
    }

    $quantidade = normalizarDecimalSuprimento($_POST['quantidade'] ?? 0);
    $observacao = trim($_POST['observacao'] ?? '');
    $usuarioId = $_SESSION['usuario_id'] ?? null;

    if ($quantidade <= 0) {
        header('Location: suprimento_movimentar.php?id=' . $id . '&tipo=' . urlencode($tipo) . '&erro=quantidade');
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmtAtual = $pdo->prepare("
            SELECT *
            FROM suprimentos
            WHERE id = :id
            AND ativo = 1
            LIMIT 1
            FOR UPDATE
        ");

        $stmtAtual->execute([
            ':id' => $id
        ]);

        $suprimentoAtual = $stmtAtual->fetch();

        if (!$suprimentoAtual) {
            $pdo->rollBack();
            header('Location: suprimentos.php');
            exit;
        }

        $estoqueAnterior = (float)$suprimentoAtual['estoque_atual'];

        if ($tipo === 'Entrada') {
            $estoquePosterior = $estoqueAnterior + $quantidade;
            $acaoAuditoria = 'SUPRIMENTO_ENTRADA';
            $textoAuditoria = 'Entrada de estoque registrada: ' . $suprimentoAtual['nome'];
        } else {
            if ($quantidade > $estoqueAnterior) {
                $pdo->rollBack();
                header('Location: suprimento_movimentar.php?id=' . $id . '&tipo=Saida&erro=estoque');
                exit;
            }

            $estoquePosterior = $estoqueAnterior - $quantidade;
            $acaoAuditoria = 'SUPRIMENTO_SAIDA';
            $textoAuditoria = 'Saída de estoque registrada: ' . $suprimentoAtual['nome'];
        }

        $stmtUpdate = $pdo->prepare("
            UPDATE suprimentos
            SET estoque_atual = :estoque_atual,
                atualizado_em = NOW()
            WHERE id = :id
        ");

        $stmtUpdate->execute([
            ':estoque_atual' => $estoquePosterior,
            ':id' => $id
        ]);

        $stmtMov = $pdo->prepare("
            INSERT INTO suprimentos_movimentacoes
            (
                suprimento_id,
                usuario_id,
                tipo_movimentacao,
                quantidade,
                estoque_anterior,
                estoque_posterior,
                observacao
            )
            VALUES
            (
                :suprimento_id,
                :usuario_id,
                :tipo_movimentacao,
                :quantidade,
                :estoque_anterior,
                :estoque_posterior,
                :observacao
            )
        ");

        $stmtMov->execute([
            ':suprimento_id' => $id,
            ':usuario_id' => $usuarioId,
            ':tipo_movimentacao' => $tipo,
            ':quantidade' => $quantidade,
            ':estoque_anterior' => $estoqueAnterior,
            ':estoque_posterior' => $estoquePosterior,
            ':observacao' => $observacao !== '' ? $observacao : null
        ]);

        if (function_exists('registrarAuditoria')) {
            registrarAuditoria(
                $pdo,
                $usuarioId,
                $acaoAuditoria,
                'suprimentos',
                $id,
                $textoAuditoria . ' | Quantidade: ' . $quantidade
            );
        }

        $pdo->commit();

        header('Location: suprimentos.php?msg=movimentado');
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Erro ao movimentar suprimento: ' . $e->getMessage());

        header('Location: suprimento_movimentar.php?id=' . $id . '&tipo=' . urlencode($tipo) . '&erro=dados');
        exit;
    }
}

$stmtSuprimento = $pdo->prepare("
    SELECT 
        s.*,
        u.nome AS unidade_nome,
        u.tipo AS unidade_tipo
    FROM suprimentos s
    INNER JOIN unidades u ON u.id = s.unidade_id
    WHERE s.id = :id
    AND s.ativo = 1
    LIMIT 1
");

$stmtSuprimento->execute([
    ':id' => $id
]);

$suprimento = $stmtSuprimento->fetch();

if (!$suprimento) {
    header('Location: suprimentos.php');
    exit;
}

$stmtHistorico = $pdo->prepare("
    SELECT 
        m.*,
        us.nome AS usuario_nome
    FROM suprimentos_movimentacoes m
    LEFT JOIN usuarios us ON us.id = m.usuario_id
    WHERE m.suprimento_id = :suprimento_id
    ORDER BY m.criado_em DESC
    LIMIT 20
");

$stmtHistorico->execute([
    ':suprimento_id' => $id
]);

$historico = $stmtHistorico->fetchAll();

$pageTitle = $tipo === 'Entrada' ? 'Entrada de Suprimento' : 'Saída de Suprimento';
$pageSubtitle = 'Movimentação de estoque hospitalar';
$menuAtivo = 'suprimentos';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2><?= $tipo === 'Entrada' ? 'Registrar Entrada' : 'Registrar Saída' ?></h2>
        <p>
            Controle de movimentação do suprimento selecionado.
        </p>
    </div>

    <a href="<?= BASE_URL ?>admin/suprimentos.php" class="btn btn-light">
        Voltar
    </a>
</section>

<?php if ($erro === 'csrf'): ?>
    <div class="alert-error">Sessão expirada ou token inválido. Tente novamente.</div>
<?php elseif ($erro === 'quantidade'): ?>
    <div class="alert-error">Informe uma quantidade maior que zero.</div>
<?php elseif ($erro === 'estoque'): ?>
    <div class="alert-error">Não é possível registrar saída maior que o estoque atual.</div>
<?php elseif ($erro === 'dados'): ?>
    <div class="alert-error">Não foi possível registrar a movimentação. Verifique os dados informados.</div>
<?php endif; ?>

<section class="patient-access-grid">
    <article class="patient-access-card">
        <div class="patient-access-card-header">
            <div>
                <span class="patient-access-card-icon">📦</span>
                <h3><?= e($suprimento['nome']) ?></h3>
            </div>
        </div>

        <div class="patient-access-detail">
            <span>Categoria</span>
            <strong><?= e($suprimento['categoria'] ?: '-') ?></strong>
        </div>

        <div class="patient-access-detail">
            <span>Unidade</span>
            <strong><?= e($suprimento['unidade_nome']) ?></strong>
            <small><?= e($suprimento['unidade_tipo'] ?: '') ?></small>
        </div>

        <div class="patient-access-detail">
            <span>Status atual</span>
            <strong>
                <span class="badge <?= classeStatusSuprimento($suprimento['estoque_atual'], $suprimento['estoque_minimo']) ?>">
                    <?= e(labelStatusSuprimento($suprimento['estoque_atual'], $suprimento['estoque_minimo'])) ?>
                </span>
            </strong>
        </div>
    </article>

    <article class="patient-access-card">
        <div class="patient-access-card-header">
            <div>
                <span class="patient-access-card-icon">📊</span>
                <h3>Estoque</h3>
            </div>
        </div>

        <div class="patient-access-detail">
            <span>Estoque atual</span>
            <strong>
                <?= formatarQuantidadeSuprimento($suprimento['estoque_atual']) ?>
                <?= e($suprimento['unidade_medida']) ?>
            </strong>
        </div>

        <div class="patient-access-detail">
            <span>Estoque mínimo</span>
            <strong>
                <?= formatarQuantidadeSuprimento($suprimento['estoque_minimo']) ?>
                <?= e($suprimento['unidade_medida']) ?>
            </strong>
        </div>

        <div class="patient-access-detail">
            <span>Movimentação</span>
            <strong><?= e($tipo === 'Entrada' ? 'Entrada de estoque' : 'Saída de estoque') ?></strong>
        </div>
    </article>
</section>

<section class="panel">
    <form 
        method="post" 
        action="<?= BASE_URL ?>admin/suprimento_movimentar.php"
        data-confirm-title="<?= $tipo === 'Entrada' ? 'Registrar entrada' : 'Registrar saída' ?>"
        data-confirm="<?= $tipo === 'Entrada' ? 'Deseja registrar entrada para este suprimento?' : 'Deseja registrar saída para este suprimento?' ?>"
        data-confirm-button="<?= $tipo === 'Entrada' ? 'Registrar entrada' : 'Registrar saída' ?>"
        data-confirm-cancel="Voltar"
        data-confirm-type="<?= $tipo === 'Entrada' ? 'success' : 'warning' ?>"
    >
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <input type="hidden" name="id" value="<?= (int)$suprimento['id'] ?>">
        <input type="hidden" name="tipo_movimentacao" value="<?= e($tipo) ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="quantidade">Quantidade *</label>
                <input 
                    type="number" 
                    id="quantidade" 
                    name="quantidade" 
                    min="0.01"
                    step="0.01"
                    required
                    placeholder="Ex.: 10"
                >
            </div>

            <div class="form-group form-group-full">
                <label for="observacao">Observação</label>
                <textarea 
                    id="observacao" 
                    name="observacao" 
                    rows="4"
                    placeholder="Ex.: entrada por compra, saída para uso interno, ajuste de conferência..."
                ></textarea>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>admin/suprimentos.php" class="btn btn-light">
                Cancelar
            </a>

            <button type="submit" class="<?= $tipo === 'Entrada' ? 'btn btn-primary-small' : 'btn btn-secondary' ?>">
                <?= $tipo === 'Entrada' ? 'Registrar Entrada' : 'Registrar Saída' ?>
            </button>
        </div>
    </form>
</section>

<section class="panel">
    <div class="audit-summary">
        <div>
            <h2>Últimas Movimentações</h2>
            <p>Histórico recente de entradas, saídas e ajustes deste suprimento.</p>
        </div>

        <span class="audit-count">
            <?= count($historico) ?>
        </span>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Quantidade</th>
                    <th>Estoque Anterior</th>
                    <th>Estoque Posterior</th>
                    <th>Usuário</th>
                    <th>Observação</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($historico)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            Nenhuma movimentação registrada para este suprimento.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($historico as $mov): ?>
                    <tr>
                        <td>
                            <?= !empty($mov['criado_em']) ? date('d/m/Y H:i', strtotime($mov['criado_em'])) : '-' ?>
                        </td>

                        <td>
                            <?php if ($mov['tipo_movimentacao'] === 'Entrada'): ?>
                                <span class="badge badge-success">Entrada</span>
                            <?php elseif ($mov['tipo_movimentacao'] === 'Saida'): ?>
                                <span class="badge badge-warning">Saída</span>
                            <?php else: ?>
                                <span class="badge badge-info">Ajuste</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= formatarQuantidadeSuprimento($mov['quantidade']) ?>
                            <?= e($suprimento['unidade_medida']) ?>
                        </td>

                        <td>
                            <?= formatarQuantidadeSuprimento($mov['estoque_anterior']) ?>
                            <?= e($suprimento['unidade_medida']) ?>
                        </td>

                        <td>
                            <?= formatarQuantidadeSuprimento($mov['estoque_posterior']) ?>
                            <?= e($suprimento['unidade_medida']) ?>
                        </td>

                        <td><?= e($mov['usuario_nome'] ?: '-') ?></td>

                        <td><?= e($mov['observacao'] ?: '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>