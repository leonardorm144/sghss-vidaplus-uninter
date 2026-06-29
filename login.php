<?php

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/auth/funcoes_auth.php';

iniciarSessaoSegura();

if (
    $usuario['perfil'] === 'paciente' &&
    (int)$usuario['trocar_senha_primeiro_acesso'] === 1
) {
    header('Location: trocar_senha.php');
    exit;
}

if (usuarioLogado()) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';
$emailDigitado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $emailDigitado = $email;

    if ($email === '' || $senha === '') {
        $erro = 'Informe seu e-mail e senha.';
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                id, 
                nome, 
                email, 
                senha, 
                perfil, 
                ativo,
                trocar_senha_primeiro_acesso
            FROM usuarios
            WHERE email = :email
            LIMIT 1
        ");

        $stmt->execute([
            ':email' => $email
        ]);

        $usuario = $stmt->fetch();

        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            $erro = 'E-mail ou senha inválidos.';

            registrarAuditoria(
                $pdo,
                null,
                'LOGIN_FALHOU',
                'usuarios',
                null,
                'Tentativa de login com o e-mail: ' . $email
            );
        } elseif ((int)$usuario['ativo'] !== 1) {
            $erro = 'Usuário inativo. Entre em contato com o administrador.';
        } else {
            session_regenerate_id(true);

            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_perfil'] = $usuario['perfil'];
            $_SESSION['trocar_senha_primeiro_acesso'] = (int)$usuario['trocar_senha_primeiro_acesso'];

            $stmt = $pdo->prepare("
                UPDATE usuarios
                SET ultimo_login = NOW()
                WHERE id = :id
            ");

            $stmt->execute([
                ':id' => $usuario['id']
            ]);

            registrarAuditoria(
                $pdo,
                $usuario['id'],
                'LOGIN_SUCESSO',
                'usuarios',
                $usuario['id'],
                'Usuário acessou o sistema.'
            );

            header('Location: dashboard.php');
            exit;
        }
    }
}
?>

<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Login - <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>

<body class="login-body">

    <main class="login-page">
        <section class="login-shell">
            <div class="login-hero">
                <div class="login-hero-content">
                    <div class="login-brand">
                        <div class="login-brand-icon">+</div>

                        <div>
                            <h1>VidaPlus</h1>
                            <span>SGHSS</span>
                        </div>
                    </div>

                    <div class="login-hero-text">
                        <span class="login-kicker">Sistema Hospitalar Integrado</span>

                        <h2>Gestão moderna para saúde, atendimento e telemedicina.</h2>

                        <p>
                            Plataforma acadêmica desenvolvida para centralizar pacientes,
                            profissionais, consultas, exames, internações, leitos e auditoria.
                        </p>
                    </div>

                    <div class="login-features">
                        <div class="login-feature">
                            <span>🩺</span>
                            <div>
                                <strong>Atendimento Clínico</strong>
                                <small>Consultas, prontuários e prescrições digitais.</small>
                            </div>
                        </div>

                        <div class="login-feature">
                            <span>🏥</span>
                            <div>
                                <strong>Gestão Hospitalar</strong>
                                <small>Leitos, internações, unidades e relatórios.</small>
                            </div>
                        </div>

                        <div class="login-feature">
                            <span>🔐</span>
                            <div>
                                <strong>Segurança e Auditoria</strong>
                                <small>Controle de acesso por perfil e registros LGPD.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <section class="login-card">
                <div class="login-card-header">
                    <div class="login-mobile-logo">
                        <div class="login-brand-icon">+</div>
                        <div>
                            <h1>VidaPlus</h1>
                            <span>SGHSS</span>
                        </div>
                    </div>

                    <span class="login-status">
                        <span></span>
                        Sistema online
                    </span>

                    <h2>Acesso ao Sistema</h2>
                    <p>Entre com suas credenciais para acessar o painel.</p>
                </div>

                <?php if ($erro): ?>
                    <div class="alert-error login-alert">
                        <?= e($erro) ?>
                    </div>
                <?php endif; ?>

                <form method="post" autocomplete="off" class="login-form">
                    <div class="form-group login-field">
                        <label for="email">E-mail</label>

                        <div class="login-input-wrap">
                            <span class="login-input-icon">✉️</span>

                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                placeholder="Digite seu e-mail"
                                value="<?= e($emailDigitado) ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group login-field">
                        <label for="senha">Senha</label>

                        <div class="login-input-wrap">
                            <span class="login-input-icon">🔒</span>

                            <input 
                                type="password" 
                                id="senha" 
                                name="senha" 
                                placeholder="Digite sua senha"
                                required
                            >

                            <button type="button" class="password-toggle" data-toggle-password>
                                Mostrar
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="login-submit">
                        <span>Entrar no sistema</span>
                        <strong>→</strong>
                    </button>
                </form>

                <div class="login-support-box">
                    <div>
                        <strong>Projeto acadêmico UNINTER</strong>
                        <span>Sistema SGHSS VidaPlus</span>
                    </div>

                    <span class="login-support-icon">🎓</span>
                </div>
            </section>
        </section>
    </main>

    <script>
        const togglePassword = document.querySelector('[data-toggle-password]');
        const passwordInput = document.getElementById('senha');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function () {
                const isPassword = passwordInput.type === 'password';

                passwordInput.type = isPassword ? 'text' : 'password';
                togglePassword.textContent = isPassword ? 'Ocultar' : 'Mostrar';
            });
        }
    </script>

</body>
</html>