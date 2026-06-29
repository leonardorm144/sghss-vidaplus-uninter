<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../auth/valida_sessao.php';

$pageTitle = $pageTitle ?? 'Dashboard';
$pageSubtitle = $pageSubtitle ?? 'Sistema de Gestão Hospitalar e Serviços de Saúde';
$menuAtivo = $menuAtivo ?? 'dashboard';

$nomeUsuario = $_SESSION['usuario_nome'] ?? 'Usuário';
$perfil = $_SESSION['usuario_perfil'] ?? 'sem perfil';

if (!function_exists('labelPerfilHeader')) {
    function labelPerfilHeader($perfil)
    {
        switch ($perfil) {
            case 'admin':
                return 'Administrador';
            case 'profissional':
                return 'Profissional de Saúde';
            case 'paciente':
                return 'Paciente';
            case 'recepcao':
                return 'Recepção';
            default:
                return 'Usuário';
        }
    }
}

if (!function_exists('iconePerfilHeader')) {
    function iconePerfilHeader($perfil)
    {
        switch ($perfil) {
            case 'admin':
                return '🛡️';
            case 'profissional':
                return '🩺';
            case 'paciente':
                return '🙂';
            case 'recepcao':
                return '📋';
            default:
                return '👤';
        }
    }
}

if (!function_exists('saudacaoHeader')) {
    function saudacaoHeader()
    {
        $hora = (int)date('H');

        if ($hora >= 5 && $hora < 12) {
            return 'Bom dia';
        }

        if ($hora >= 12 && $hora < 18) {
            return 'Boa tarde';
        }

        return 'Boa noite';
    }
}
?>

<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title><?= e($pageTitle) ?> - <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body class="app-body">

<?php require_once __DIR__ . '/sidebar.php'; ?>

<main class="main-content">

    <header class="topbar">
        <div class="topbar-title-area">
            <span class="topbar-kicker">
                <?= e(saudacaoHeader()) ?>, <?= e(explode(' ', trim($nomeUsuario))[0] ?: 'Usuário') ?>
            </span>

            <h1><?= e($pageTitle) ?></h1>
            <p><?= e($pageSubtitle) ?></p>
        </div>

        <div class="topbar-actions">
            <div class="system-status">
                <span class="status-dot"></span>
                <div>
                    <strong>Sistema online</strong>
                    <small>VidaPlus SGHSS</small>
                </div>
            </div>

            <div class="topbar-date">
                <span>Hoje</span>
                <strong><?= date('d/m/Y') ?></strong>
            </div>

            <div class="user-info">
                <div class="topbar-avatar">
                    <?= e(iconePerfilHeader($perfil)) ?>
                </div>

                <div class="topbar-user-text">
                    <strong><?= e($nomeUsuario) ?></strong>
                    <span><?= e(labelPerfilHeader($perfil)) ?></span>
                </div>
            </div>
        </div>
    </header>