<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/auth/funcoes_auth.php';

iniciarSessaoSegura();

$usuarioId = $_SESSION['usuario_id'] ?? null;

if ($usuarioId) {
    registrarAuditoria(
        $pdo,
        $usuarioId,
        'LOGOUT',
        'usuarios',
        $usuarioId,
        'Usuário saiu do sistema.'
    );
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: login.php');
exit;