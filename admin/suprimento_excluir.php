<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: suprimentos.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';

if (!validarTokenCsrf($csrfToken)) {
    header('Location: suprimentos.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    header('Location: suprimentos.php');
    exit;
}

$usuarioId = $_SESSION['usuario_id'] ?? null;

try {
    $stmtSuprimento = $pdo->prepare("
        SELECT nome
        FROM suprimentos
        WHERE id = :id
        AND ativo = 1
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

    $stmt = $pdo->prepare("
        UPDATE suprimentos
        SET ativo = 0,
            atualizado_em = NOW()
        WHERE id = :id
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    if (function_exists('registrarAuditoria')) {
        registrarAuditoria(
            $pdo,
            $usuarioId,
            'SUPRIMENTO_INATIVADO',
            'suprimentos',
            $id,
            'Suprimento inativado: ' . $suprimento['nome']
        );
    }

    header('Location: suprimentos.php?status_cadastro=todos&msg=excluido');
exit;
} catch (Exception $e) {
    error_log('Erro ao inativar suprimento: ' . $e->getMessage());

    header('Location: suprimentos.php');
    exit;
}