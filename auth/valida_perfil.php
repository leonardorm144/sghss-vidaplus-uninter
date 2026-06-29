<?php

require_once __DIR__ . '/valida_sessao.php';

function exigirPerfil($perfisPermitidos)
{
    if (!is_array($perfisPermitidos)) {
        $perfisPermitidos = [$perfisPermitidos];
    }

    $perfilUsuario = $_SESSION['usuario_perfil'] ?? '';

    if (!in_array($perfilUsuario, $perfisPermitidos)) {
        header('Location: dashboard.php');
        exit;
    }
}