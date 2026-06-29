<?php

require_once __DIR__ . '/auth/funcoes_auth.php';

iniciarSessaoSegura();

if (usuarioLogado()) {
    header('Location: dashboard.php');
    exit;
}

header('Location: login.php');
exit;