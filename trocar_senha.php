<?php

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/auth/valida_sessao.php';

if (
    ($_SESSION['usuario_perfil'] ?? '') !== 'paciente' ||
    (int)($_SESSION['trocar_senha_primeiro_acesso'] ?? 0) !== 1
) {
    header('Location: dashboard.php');
    exit;
}

$erro = $_GET['erro'] ?? '';
$nomeUsuario = $_SESSION['usuario_nome'] ?? 'Paciente';
$primeiroNome = explode(' ', trim($nomeUsuario))[0] ?: 'Paciente';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Trocar Senha - <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body class="password-page-body">

    <main class="password-page">
        <section class="password-shell">
            <div class="password-side">
                <div class="password-brand">
                    <div class="password-brand-icon">+</div>

                    <div>
                        <h1>VidaPlus</h1>
                        <span>SGHSS</span>
                    </div>
                </div>

                <span class="password-kicker">Primeiro acesso do paciente</span>

                <h2>Olá, <?= e($primeiroNome) ?>. Vamos proteger sua conta.</h2>

                <p>
                    Seu acesso foi criado automaticamente pela recepção.  
                    Antes de continuar, defina uma nova senha pessoal para garantir
                    mais segurança aos seus dados.
                </p>

                <div class="password-side-list">
                    <div class="password-side-item">
                        <span>🔐</span>
                        <div>
                            <strong>Mais segurança</strong>
                            <small>A senha inicial não deve continuar sendo utilizada.</small>
                        </div>
                    </div>

                    <div class="password-side-item">
                        <span>🛡️</span>
                        <div>
                            <strong>Proteção de dados</strong>
                            <small>Essa etapa ajuda a manter o acesso alinhado à LGPD.</small>
                        </div>
                    </div>

                    <div class="password-side-item">
                        <span>🙂</span>
                        <div>
                            <strong>Acesso pessoal</strong>
                            <small>Após salvar, você poderá acessar normalmente seu painel.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="password-form-card">
                <div class="password-form-header">
                    <div class="password-form-icon">🔑</div>

                    <h2>Troca obrigatória de senha</h2>
                    <p>Crie uma nova senha para liberar seu acesso ao sistema.</p>
                </div>

                <?php if ($erro === 'csrf'): ?>
                    <div class="alert-error">Sessão expirada. Tente novamente.</div>
                <?php elseif ($erro === 'vazia'): ?>
                    <div class="alert-error">Informe a nova senha e a confirmação.</div>
                <?php elseif ($erro === 'diferente'): ?>
                    <div class="alert-error">A confirmação da senha não confere.</div>
                <?php elseif ($erro === 'fraca'): ?>
                    <div class="alert-error">A senha deve ter pelo menos 8 caracteres, contendo letras e números.</div>
                <?php elseif ($erro === 'nascimento'): ?>
                    <div class="alert-error">A nova senha não pode ser igual à senha inicial.</div>
                <?php endif; ?>

                <form method="post" action="<?= BASE_URL ?>trocar_senha_salvar.php" class="password-form">
                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">

                    <div class="form-grid">
    <div class="form-group">
        <label for="nova_senha">Nova senha</label>

        <div class="password-input-wrap">
            <input
                type="password"
                id="nova_senha"
                name="nova_senha"
                placeholder="Digite a nova senha"
                required
            >

            <button 
                type="button" 
                class="password-toggle-btn" 
                data-toggle-password 
                data-target="nova_senha"
            >
                Mostrar
            </button>
        </div>
    </div>

    <div class="form-group">
        <label for="confirmar_senha">Confirmar nova senha</label>

        <div class="password-input-wrap">
            <input
                type="password"
                id="confirmar_senha"
                name="confirmar_senha"
                placeholder="Repita a nova senha"
                required
            >

            <button 
                type="button" 
                class="password-toggle-btn" 
                data-toggle-password 
                data-target="confirmar_senha"
            >
                Mostrar
            </button>
        </div>
    </div>
</div>

                    <div class="password-rules-box password-live-rules">
    <strong>Requisitos da senha</strong>

    <div class="password-strength-box">
        <div class="password-strength-track">
            <div class="password-strength-bar" id="passwordStrengthBar"></div>
        </div>

        <span id="passwordStrengthText">Digite uma senha para verificar a força.</span>
    </div>

    <div class="password-checklist">
        <span class="password-rule" id="ruleLength">
            <b>○</b> Mínimo de 8 caracteres
        </span>

        <span class="password-rule" id="ruleLetter">
            <b>○</b> Contém pelo menos uma letra
        </span>

        <span class="password-rule" id="ruleNumber">
            <b>○</b> Contém pelo menos um número
        </span>

        <span class="password-rule" id="ruleConfirm">
            <b>○</b> Confirmação igual à nova senha
        </span>

        <span class="password-rule">
            <b>ℹ</b> Não pode ser igual à data de nascimento
        </span>
    </div>
</div>

                    <div class="form-actions password-form-actions">
                        <a href="<?= BASE_URL ?>logout.php" class="btn btn-light">
                            Sair
                        </a>

                        <button type="submit" class="btn btn-primary-small">
                            Salvar nova senha
                        </button>
                    </div>
                </form>

                <div class="password-footer-note">
                    SGHSS VidaPlus © <?= date('Y') ?> - Projeto acadêmico UNINTER
                </div>
            </div>
        </section>
    </main>
    
    <script>
document.addEventListener('DOMContentLoaded', function () {
    const novaSenha = document.getElementById('nova_senha');
    const confirmarSenha = document.getElementById('confirmar_senha');

    const ruleLength = document.getElementById('ruleLength');
    const ruleLetter = document.getElementById('ruleLetter');
    const ruleNumber = document.getElementById('ruleNumber');
    const ruleConfirm = document.getElementById('ruleConfirm');

    const strengthBar = document.getElementById('passwordStrengthBar');
    const strengthText = document.getElementById('passwordStrengthText');

    const toggles = document.querySelectorAll('[data-toggle-password]');

    function atualizarRegra(elemento, valido) {
        if (!elemento) {
            return;
        }

        const marcador = elemento.querySelector('b');

        elemento.classList.toggle('is-valid', valido);
        elemento.classList.toggle('is-invalid', !valido);

        if (marcador) {
            marcador.textContent = valido ? '✓' : '○';
        }
    }

    function calcularForca(senha) {
        let pontos = 0;

        if (senha.length >= 8) pontos++;
        if (/[a-zA-Z]/.test(senha)) pontos++;
        if (/[0-9]/.test(senha)) pontos++;
        if (/[^a-zA-Z0-9]/.test(senha)) pontos++;
        if (senha.length >= 12) pontos++;

        return pontos;
    }

    function atualizarForcaSenha() {
        const senha = novaSenha.value;
        const confirmacao = confirmarSenha.value;

        const temTamanho = senha.length >= 8;
        const temLetra = /[a-zA-Z]/.test(senha);
        const temNumero = /[0-9]/.test(senha);
        const confirmacaoOk = senha !== '' && senha === confirmacao;

        atualizarRegra(ruleLength, temTamanho);
        atualizarRegra(ruleLetter, temLetra);
        atualizarRegra(ruleNumber, temNumero);
        atualizarRegra(ruleConfirm, confirmacaoOk);

        const forca = calcularForca(senha);

        strengthBar.className = 'password-strength-bar';

        if (senha.length === 0) {
            strengthBar.style.width = '0%';
            strengthText.textContent = 'Digite uma senha para verificar a força.';
            return;
        }

        if (forca <= 2) {
            strengthBar.style.width = '35%';
            strengthBar.classList.add('weak');
            strengthText.textContent = 'Senha fraca';
            return;
        }

        if (forca === 3 || forca === 4) {
            strengthBar.style.width = '70%';
            strengthBar.classList.add('medium');
            strengthText.textContent = 'Senha média';
            return;
        }

        strengthBar.style.width = '100%';
        strengthBar.classList.add('strong');
        strengthText.textContent = 'Senha forte';
    }

    toggles.forEach(function (botao) {
        botao.addEventListener('click', function () {
            const targetId = botao.getAttribute('data-target');
            const input = document.getElementById(targetId);

            if (!input) {
                return;
            }

            const estaOculto = input.type === 'password';

            input.type = estaOculto ? 'text' : 'password';
            botao.textContent = estaOculto ? 'Ocultar' : 'Mostrar';
        });
    });

    if (novaSenha && confirmarSenha) {
        novaSenha.addEventListener('input', atualizarForcaSenha);
        confirmarSenha.addEventListener('input', atualizarForcaSenha);

        atualizarForcaSenha();
    }
});
</script>

</body>
</html>