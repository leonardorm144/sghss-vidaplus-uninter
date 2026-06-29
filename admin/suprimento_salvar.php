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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: suprimentos.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';

if (!validarTokenCsrf($csrfToken)) {
    header('Location: suprimento_form.php?erro=csrf');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$unidadeId = isset($_POST['unidade_id']) ? (int)$_POST['unidade_id'] : 0;
$nome = trim($_POST['nome'] ?? '');
$categoria = trim($_POST['categoria'] ?? '');
$unidadeMedida = trim($_POST['unidade_medida'] ?? 'Unidade');
$estoqueAtual = normalizarDecimalSuprimento($_POST['estoque_atual'] ?? 0);
$estoqueMinimo = normalizarDecimalSuprimento($_POST['estoque_minimo'] ?? 0);
$observacoes = trim($_POST['observacoes'] ?? '');

if (
    $unidadeId <= 0 ||
    $nome === '' ||
    $unidadeMedida === '' ||
    $estoqueAtual < 0 ||
    $estoqueMinimo < 0
) {
    $redirectId = $id > 0 ? '&id=' . $id : '';
    header('Location: suprimento_form.php?erro=dados' . $redirectId);
    exit;
}

$stmtUnidade = $pdo->prepare("
    SELECT id
    FROM unidades
    WHERE id = :id
    AND ativo = 1
    LIMIT 1
");

$stmtUnidade->execute([
    ':id' => $unidadeId
]);

if (!$stmtUnidade->fetch()) {
    $redirectId = $id > 0 ? '&id=' . $id : '';
    header('Location: suprimento_form.php?erro=unidade' . $redirectId);
    exit;
}

$usuarioId = $_SESSION['usuario_id'] ?? null;

try {
    $pdo->beginTransaction();

    if ($id > 0) {
        $stmtAtual = $pdo->prepare("
            SELECT *
            FROM suprimentos
            WHERE id = :id
            AND ativo = 1
            LIMIT 1
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

        $stmtUpdate = $pdo->prepare("
            UPDATE suprimentos
            SET unidade_id = :unidade_id,
                nome = :nome,
                categoria = :categoria,
                unidade_medida = :unidade_medida,
                estoque_atual = :estoque_atual,
                estoque_minimo = :estoque_minimo,
                observacoes = :observacoes,
                atualizado_em = NOW()
            WHERE id = :id
        ");

        $stmtUpdate->execute([
            ':unidade_id' => $unidadeId,
            ':nome' => $nome,
            ':categoria' => $categoria !== '' ? $categoria : null,
            ':unidade_medida' => $unidadeMedida,
            ':estoque_atual' => $estoqueAtual,
            ':estoque_minimo' => $estoqueMinimo,
            ':observacoes' => $observacoes !== '' ? $observacoes : null,
            ':id' => $id
        ]);

        if (abs($estoqueAtual - $estoqueAnterior) > 0.0001) {
            $quantidadeAjuste = abs($estoqueAtual - $estoqueAnterior);

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
                    'Ajuste',
                    :quantidade,
                    :estoque_anterior,
                    :estoque_posterior,
                    :observacao
                )
            ");

            $stmtMov->execute([
                ':suprimento_id' => $id,
                ':usuario_id' => $usuarioId,
                ':quantidade' => $quantidadeAjuste,
                ':estoque_anterior' => $estoqueAnterior,
                ':estoque_posterior' => $estoqueAtual,
                ':observacao' => 'Ajuste realizado na edição do cadastro.'
            ]);
        }

        if (function_exists('registrarAuditoria')) {
            registrarAuditoria(
                $pdo,
                $usuarioId,
                'SUPRIMENTO_ATUALIZADO',
                'suprimentos',
                $id,
                'Suprimento atualizado: ' . $nome
            );
        }

        $pdo->commit();

        header('Location: suprimentos.php?msg=atualizado');
        exit;
    }

    $stmtInsert = $pdo->prepare("
        INSERT INTO suprimentos
        (
            unidade_id,
            nome,
            categoria,
            unidade_medida,
            estoque_atual,
            estoque_minimo,
            observacoes,
            ativo
        )
        VALUES
        (
            :unidade_id,
            :nome,
            :categoria,
            :unidade_medida,
            :estoque_atual,
            :estoque_minimo,
            :observacoes,
            1
        )
    ");

    $stmtInsert->execute([
        ':unidade_id' => $unidadeId,
        ':nome' => $nome,
        ':categoria' => $categoria !== '' ? $categoria : null,
        ':unidade_medida' => $unidadeMedida,
        ':estoque_atual' => $estoqueAtual,
        ':estoque_minimo' => $estoqueMinimo,
        ':observacoes' => $observacoes !== '' ? $observacoes : null
    ]);

    $novoId = (int)$pdo->lastInsertId();

    if ($estoqueAtual > 0) {
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
                'Entrada',
                :quantidade,
                0,
                :estoque_posterior,
                :observacao
            )
        ");

        $stmtMov->execute([
            ':suprimento_id' => $novoId,
            ':usuario_id' => $usuarioId,
            ':quantidade' => $estoqueAtual,
            ':estoque_posterior' => $estoqueAtual,
            ':observacao' => 'Estoque inicial informado no cadastro.'
        ]);
    }

    if (function_exists('registrarAuditoria')) {
        registrarAuditoria(
            $pdo,
            $usuarioId,
            'SUPRIMENTO_CRIADO',
            'suprimentos',
            $novoId,
            'Suprimento criado: ' . $nome
        );
    }

    $pdo->commit();

    header('Location: suprimentos.php?msg=criado');
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Erro ao salvar suprimento: ' . $e->getMessage());

    $redirectId = $id > 0 ? '&id=' . $id : '';
    header('Location: suprimento_form.php?erro=dados' . $redirectId);
    exit;
}