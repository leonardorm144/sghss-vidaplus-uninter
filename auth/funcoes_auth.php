<?php

function iniciarSessaoSegura()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function usuarioLogado()
{
    iniciarSessaoSegura();
    return isset($_SESSION['usuario_id']);
}

function redirecionarPorPerfil($perfil)
{
    switch ($perfil) {
        case 'admin':
            header('Location: dashboard.php');
            exit;

        case 'profissional':
            header('Location: dashboard.php');
            exit;

        case 'paciente':
            header('Location: dashboard.php');
            exit;

        case 'recepcao':
            header('Location: dashboard.php');
            exit;

        default:
            header('Location: login.php');
            exit;
    }
}

function registrarAuditoria($pdo, $usuarioId, $acao, $tabela = null, $registroId = null, $detalhes = null)
{
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'IP não identificado';

        $stmt = $pdo->prepare("
            INSERT INTO auditoria_logs 
            (usuario_id, acao, tabela_afetada, registro_id, detalhes, ip)
            VALUES 
            (:usuario_id, :acao, :tabela_afetada, :registro_id, :detalhes, :ip)
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':acao' => $acao,
            ':tabela_afetada' => $tabela,
            ':registro_id' => $registroId,
            ':detalhes' => $detalhes,
            ':ip' => $ip
        ]);
    } catch (Exception $e) {
        error_log("Erro ao registrar auditoria: " . $e->getMessage());
    }
}

function gerarTokenCsrf()
{
    iniciarSessaoSegura();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validarTokenCsrf($token)
{
    iniciarSessaoSegura();

    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}