<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = $_GET['msg'] ?? '';

if ($id <= 0) {
    header('Location: pacientes.php');
    exit;
}

function somenteNumerosAcessoPaciente($valor)
{
    return preg_replace('/\D/', '', (string)$valor);
}

function gerarLoginPaciente($cpf)
{
    $cpfNumeros = somenteNumerosAcessoPaciente($cpf);

    if (strlen($cpfNumeros) !== 11) {
        return null;
    }

    return $cpfNumeros . '@vidaplus.com';
}

function gerarSenhaInicialPaciente($dataNascimento)
{
    if (empty($dataNascimento)) {
        return null;
    }

    $data = DateTime::createFromFormat('Y-m-d', $dataNascimento);

    if (!$data) {
        return null;
    }

    return $data->format('dmY');
}

function gerarTelefoneWhatsappPaciente($telefone)
{
    $numero = somenteNumerosAcessoPaciente($telefone);

    if ($numero === '') {
        return null;
    }

    if (strlen($numero) === 10 || strlen($numero) === 11) {
        return '55' . $numero;
    }

    if (strlen($numero) >= 12) {
        return $numero;
    }

    return null;
}

function gerarMensagemWhatsappAcesso($nomePaciente, $login, $senhaInicial)
{
    $urlSistema = 'https://sghssuninter.free.nf/';

    return "Olá, {$nomePaciente}!\n\n" .
           "Seu acesso ao SGHSS VidaPlus foi criado com sucesso.\n\n" .
           "Login: {$login}\n" .
           "Senha inicial: {$senhaInicial}\n\n" .
           "Acesse:\n{$urlSistema}\n\n" .
           "No primeiro acesso, será obrigatório trocar a senha por segurança.\n\n" .
           "Equipe VidaPlus.";
}

$stmtPaciente = $pdo->prepare("
    SELECT *
    FROM pacientes
    WHERE id = :id
    AND ativo = 1
    LIMIT 1
");

$stmtPaciente->execute([
    ':id' => $id
]);

$paciente = $stmtPaciente->fetch();

if (!$paciente) {
    header('Location: pacientes.php');
    exit;
}

$loginAutomatico = gerarLoginPaciente($paciente['cpf'] ?? '');
$senhaInicial = gerarSenhaInicialPaciente($paciente['data_nascimento'] ?? '');
$telefoneWhatsapp = gerarTelefoneWhatsappPaciente($paciente['telefone'] ?? '');
$lgpdAceito = (int)($paciente['consentimento_lgpd'] ?? 0) === 1;

$usuario = null;

if (!empty($paciente['usuario_id'])) {
    $stmtUsuario = $pdo->prepare("
        SELECT id, nome, email, perfil, ativo, trocar_senha_primeiro_acesso, ultimo_login, criado_em
        FROM usuarios
        WHERE id = :id
        LIMIT 1
    ");

    $stmtUsuario->execute([
        ':id' => $paciente['usuario_id']
    ]);

    $usuario = $stmtUsuario->fetch();
}

if (!$usuario && $loginAutomatico) {
    $stmtUsuario = $pdo->prepare("
        SELECT id, nome, email, perfil, ativo, trocar_senha_primeiro_acesso, ultimo_login, criado_em
        FROM usuarios
        WHERE email = :email
        LIMIT 1
    ");

    $stmtUsuario->execute([
        ':email' => $loginAutomatico
    ]);

    $usuario = $stmtUsuario->fetch();

    if ($usuario) {
        $stmtVincular = $pdo->prepare("
            UPDATE pacientes
            SET usuario_id = :usuario_id,
                atualizado_em = NOW()
            WHERE id = :paciente_id
        ");

        $stmtVincular->execute([
            ':usuario_id' => $usuario['id'],
            ':paciente_id' => $paciente['id']
        ]);
    }
}

$linkWhatsappAcesso = null;
$mensagemWhatsappAcesso = null;

if (
    $usuario &&
    $telefoneWhatsapp &&
    $loginAutomatico &&
    $senhaInicial &&
    $lgpdAceito &&
    (int)$usuario['ativo'] === 1 &&
    (int)$usuario['trocar_senha_primeiro_acesso'] === 1
) {
    $mensagemWhatsappAcesso = gerarMensagemWhatsappAcesso(
        $paciente['nome'],
        $loginAutomatico,
        $senhaInicial
    );

    $linkWhatsappAcesso = 'https://wa.me/' . $telefoneWhatsapp . '?text=' . urlencode($mensagemWhatsappAcesso);
}

$pageTitle = 'Acesso do Paciente';
$pageSubtitle = 'Consulta e redefinição de acesso do paciente';
$menuAtivo = 'pacientes';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2>Acesso do Paciente</h2>
        <p>Consulte o login, status e opções de segurança do paciente.</p>
    </div>

    <a href="<?= BASE_URL ?>admin/pacientes.php" class="btn btn-light">
        Voltar
    </a>
</section>

<?php if ($msg === 'criado'): ?>
    <div class="alert-success">
        Acesso criado com sucesso. A senha inicial é a data de nascimento do paciente no formato DDMMAAAA.
    </div>
<?php elseif ($msg === 'resetado'): ?>
    <div class="alert-success">
        Senha redefinida com sucesso. O paciente deverá trocar a senha no próximo acesso.
    </div>
<?php elseif ($msg === 'erro_dados'): ?>
    <div class="alert-error">
        Não foi possível criar ou redefinir o acesso. Verifique se o paciente possui CPF e data de nascimento cadastrados.
    </div>
<?php endif; ?>

<section class="patient-access-hero">
    <div class="patient-access-info">
        <span class="about-kicker">Área Administrativa</span>

        <h2><?= e($paciente['nome']) ?></h2>

        <p>
            Acesso vinculado ao cadastro do paciente. O login é gerado automaticamente
            com base no CPF cadastrado.
        </p>

        <div class="patient-access-tags">
            <span>CPF: <?= e($paciente['cpf'] ?: '-') ?></span>

            <span>
                Nascimento:
                <?php if (!empty($paciente['data_nascimento'])): ?>
                    <?= date('d/m/Y', strtotime($paciente['data_nascimento'])) ?>
                <?php else: ?>
                    -
                <?php endif; ?>
            </span>

            <span>LGPD: <?= (int)$paciente['consentimento_lgpd'] === 1 ? 'Aceito' : 'Pendente' ?></span>
        </div>
    </div>

    <div class="patient-access-icon-card">
        <div class="patient-access-icon">🔑</div>
        <strong>Acesso VidaPlus</strong>
        <span>Paciente</span>
    </div>
</section>

<section class="patient-access-grid">
    <article class="patient-access-card">
        <div class="patient-access-card-header">
            <div>
                <span class="patient-access-card-icon">👤</span>
                <h3>Dados de Login</h3>
            </div>
        </div>

        <div class="patient-access-detail">
            <span>Login automático</span>

            <?php if ($loginAutomatico): ?>
                <strong><?= e($loginAutomatico) ?></strong>
            <?php else: ?>
                <strong class="text-danger">CPF inválido ou não informado</strong>
            <?php endif; ?>
        </div>

        <div class="patient-access-detail">
            <span>Senha inicial</span>

            <?php if ($senhaInicial): ?>
                <strong>Data de nascimento no formato DDMMAAAA</strong>
                <small>Exemplo: se nasceu em 15/03/1998, a senha inicial é 15031998.</small>
            <?php else: ?>
                <strong class="text-danger">Data de nascimento não informada</strong>
            <?php endif; ?>
        </div>
    </article>

    <article class="patient-access-card">
        <div class="patient-access-card-header">
            <div>
                <span class="patient-access-card-icon">🛡️</span>
                <h3>Status do Usuário</h3>
            </div>
        </div>

        <?php if ($usuario): ?>
            <div class="patient-access-detail">
                <span>Usuário vinculado</span>
                <strong><?= e($usuario['email']) ?></strong>
            </div>

            <div class="patient-access-status-list">
                <?php if ((int)$usuario['ativo'] === 1): ?>
                    <span class="badge badge-success">Usuário ativo</span>
                <?php else: ?>
                    <span class="badge badge-danger">Usuário inativo</span>
                <?php endif; ?>

                <?php if ((int)$usuario['trocar_senha_primeiro_acesso'] === 1): ?>
                    <span class="badge badge-warning">Troca de senha pendente</span>
                <?php else: ?>
                    <span class="badge badge-success">Senha pessoal definida</span>
                <?php endif; ?>

                <span class="badge badge-info">Perfil paciente</span>
            </div>

            <div class="patient-access-detail">
                <span>Último login</span>

                <?php if (!empty($usuario['ultimo_login'])): ?>
                    <strong><?= date('d/m/Y H:i', strtotime($usuario['ultimo_login'])) ?></strong>
                <?php else: ?>
                    <strong>Ainda não acessou</strong>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="patient-access-empty">
                Nenhum usuário vinculado a este paciente.
            </div>
        <?php endif; ?>
    </article>
</section>

<section class="panel">
    <div class="patient-access-actions-header">
        <div>
            <h2>Opções de Acesso</h2>
            <p>Use estas ações quando o paciente precisar de suporte para acessar o sistema.</p>
        </div>
    </div>

    <div class="patient-access-actions-grid">
        
                <?php if ($linkWhatsappAcesso): ?>
    <a 
        href="<?= e($linkWhatsappAcesso) ?>" 
        target="_blank"
        class="patient-access-action-box patient-access-whatsapp-box"
    >
        <div>
            <strong>Enviar acesso pelo WhatsApp</strong>
            <p>
                Abre o WhatsApp com uma mensagem pronta contendo login,
                senha inicial e link de acesso ao sistema.
            </p>
        </div>

        <span class="btn btn-primary-small">
            Enviar WhatsApp
        </span>
    </a>

    <div class="patient-access-action-box patient-access-copy-box">
        <div>
            <strong>Copiar mensagem de acesso</strong>
            <p>
                Copia a mesma mensagem pronta para enviar manualmente por WhatsApp,
                e-mail ou outro canal autorizado.
            </p>
        </div>

        <button 
            type="button" 
            class="btn btn-light js-copy-access-message"
            data-access-message="<?= e($mensagemWhatsappAcesso) ?>"
        >
            Copiar Mensagem
        </button>
    </div>
<?php elseif ($usuario && !$lgpdAceito && (int)$usuario['trocar_senha_primeiro_acesso'] === 1): ?>
            <div class="patient-access-action-box">
                <div>
                    <strong>Envio pelo WhatsApp bloqueado</strong>
                    <p>
                        O paciente ainda não possui consentimento LGPD registrado.
                        Atualize o cadastro do paciente antes de enviar dados de acesso pelo WhatsApp.
                    </p>
                </div>

                <span class="badge badge-danger">
                    LGPD pendente
                </span>
            </div>
        <?php elseif ($usuario && (int)$usuario['trocar_senha_primeiro_acesso'] === 1 && empty($telefoneWhatsapp)): ?>
            <div class="patient-access-action-box">
                <div>
                    <strong>WhatsApp indisponível</strong>
                    <p>
                        O paciente não possui telefone válido cadastrado. Atualize o cadastro
                        para liberar o envio do acesso pelo WhatsApp.
                    </p>
                </div>

                <span class="badge badge-warning">
                    Sem telefone
                </span>
            </div>
        <?php elseif ($usuario && (int)$usuario['trocar_senha_primeiro_acesso'] !== 1): ?>
            <div class="patient-access-action-box">
                <div>
                    <strong>Envio de senha inicial indisponível</strong>
                    <p>
                        Este paciente já definiu uma senha pessoal. Para reenviar o acesso inicial,
                        primeiro redefina a senha.
                    </p>
                </div>

                <span class="badge badge-success">
                    Senha pessoal definida
                </span>
            </div>
        <?php endif; ?>
        
        <?php if (!$usuario): ?>
            <form 
                method="post" 
                action="<?= BASE_URL ?>admin/paciente_acesso_salvar.php" 
                class="patient-access-action-box"
                data-confirm-title="Criar acesso do paciente"
                data-confirm="Deseja criar o acesso do paciente <?= e($paciente['nome']) ?>? O login será baseado no CPF e a senha inicial será baseada na data de nascimento."
                data-confirm-button="Criar acesso"
                data-confirm-cancel="Voltar"
                data-confirm-type="success"
            >
                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                <input type="hidden" name="paciente_id" value="<?= (int)$paciente['id'] ?>">
                <input type="hidden" name="acao" value="criar">

                <div>
                    <strong>Criar acesso do paciente</strong>
                    <p>
                        Cria automaticamente um usuário com login baseado no CPF e senha inicial
                        baseada na data de nascimento.
                    </p>
                </div>

                <button type="submit" class="btn btn-primary-small">
                    Criar Acesso
                </button>
            </form>
        <?php else: ?>
            <form 
                method="post" 
                action="<?= BASE_URL ?>admin/paciente_acesso_salvar.php" 
                class="patient-access-action-box"
                data-confirm-title="Redefinir senha inicial"
                data-confirm="Deseja realmente redefinir a senha inicial do paciente <?= e($paciente['nome']) ?>? A senha voltará a ser baseada na data de nascimento e a troca será obrigatória no próximo acesso."
                data-confirm-button="Redefinir senha"
                data-confirm-cancel="Voltar"
                data-confirm-type="warning"
            >
                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                <input type="hidden" name="paciente_id" value="<?= (int)$paciente['id'] ?>">
                <input type="hidden" name="acao" value="resetar">

                <div>
                    <strong>Redefinir senha inicial</strong>
                    <p>
                        A senha voltará a ser a data de nascimento do paciente e a troca será
                        obrigatória no próximo acesso.
                    </p>
                </div>

                <button type="submit" class="btn btn-secondary">
                    Redefinir Senha
                </button>
            </form>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const botoesCopiar = document.querySelectorAll('.js-copy-access-message');

    botoesCopiar.forEach(function (botao) {
        botao.addEventListener('click', async function () {
            const mensagem = botao.getAttribute('data-access-message') || '';

            if (!mensagem) {
                alert('Mensagem indisponível para cópia.');
                return;
            }

            const textoOriginal = botao.textContent;

            function mostrarCopiado() {
                botao.textContent = 'Copiado!';
                botao.disabled = true;

                setTimeout(function () {
                    botao.textContent = textoOriginal;
                    botao.disabled = false;
                }, 2000);
            }

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(mensagem);
                    mostrarCopiado();
                    return;
                }

                const campoTemporario = document.createElement('textarea');
                campoTemporario.value = mensagem;
                campoTemporario.style.position = 'fixed';
                campoTemporario.style.left = '-9999px';
                campoTemporario.style.top = '-9999px';

                document.body.appendChild(campoTemporario);
                campoTemporario.focus();
                campoTemporario.select();

                document.execCommand('copy');
                document.body.removeChild(campoTemporario);

                mostrarCopiado();
            } catch (erro) {
                alert('Não foi possível copiar automaticamente. Selecione a mensagem manualmente.');
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>