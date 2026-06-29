<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$erro = $_GET['erro'] ?? '';

$usuario = [
    'id' => '',
    'nome' => '',
    'email' => '',
    'perfil' => 'recepcao',
    'ativo' => 1
];

if ($id > 0) {
    $stmt = $pdo->prepare("
        SELECT id, nome, email, perfil, ativo
        FROM usuarios
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $usuarioEncontrado = $stmt->fetch();

    if (!$usuarioEncontrado) {
        header('Location: usuarios.php');
        exit;
    }

    $usuario = $usuarioEncontrado;
}

$stmtPacientes = $pdo->prepare("
    SELECT id, nome, cpf
    FROM pacientes
    WHERE ativo = 1
    AND (usuario_id IS NULL OR usuario_id = :usuario_id)
    ORDER BY nome ASC
");

$stmtPacientes->execute([
    ':usuario_id' => $id
]);

$pacientes = $stmtPacientes->fetchAll();

$stmtProfissionais = $pdo->prepare("
    SELECT id, nome, especialidade, registro_profissional
    FROM profissionais
    WHERE ativo = 1
    AND (usuario_id IS NULL OR usuario_id = :usuario_id)
    ORDER BY nome ASC
");

$stmtProfissionais->execute([
    ':usuario_id' => $id
]);

$profissionais = $stmtProfissionais->fetchAll();

$pacienteVinculadoId = null;
$profissionalVinculadoId = null;

if ($id > 0) {
    $stmtPacienteVinculado = $pdo->prepare("
        SELECT id
        FROM pacientes
        WHERE usuario_id = :usuario_id
        LIMIT 1
    ");

    $stmtPacienteVinculado->execute([
        ':usuario_id' => $id
    ]);

    $pacienteVinculadoId = $stmtPacienteVinculado->fetchColumn();

    $stmtProfissionalVinculado = $pdo->prepare("
        SELECT id
        FROM profissionais
        WHERE usuario_id = :usuario_id
        LIMIT 1
    ");

    $stmtProfissionalVinculado->execute([
        ':usuario_id' => $id
    ]);

    $profissionalVinculadoId = $stmtProfissionalVinculado->fetchColumn();
}

$pageTitle = $id > 0 ? 'Editar Usuário' : 'Novo Usuário';
$pageSubtitle = 'Cadastro de acesso e definição de perfil';
$menuAtivo = 'usuarios';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2><?= $id > 0 ? 'Editar Usuário' : 'Cadastrar Usuário' ?></h2>
        <p>Defina os dados de acesso, perfil e vínculo com paciente ou profissional.</p>
    </div>

    <a href="<?= BASE_URL ?>admin/usuarios.php" class="btn btn-light">
        Voltar
    </a>
</section>

<?php if ($erro === 'nome'): ?>
    <div class="alert-error">Informe o nome do usuário.</div>
<?php elseif ($erro === 'email'): ?>
    <div class="alert-error">Informe um e-mail válido.</div>
<?php elseif ($erro === 'email_duplicado'): ?>
    <div class="alert-error">Já existe um usuário cadastrado com este e-mail.</div>
<?php elseif ($erro === 'perfil'): ?>
    <div class="alert-error">Perfil inválido.</div>
<?php elseif ($erro === 'senha'): ?>
    <div class="alert-error">A senha deve ter pelo menos 6 caracteres.</div>
<?php elseif ($erro === 'confirmacao'): ?>
    <div class="alert-error">A confirmação de senha não confere.</div>
<?php elseif ($erro === 'csrf'): ?>
    <div class="alert-error">Sessão expirada. Atualize a página e tente novamente.</div>
<?php endif; ?>

<section class="panel">
    <form method="post" action="<?= BASE_URL ?>admin/usuario_salvar.php" class="form-card">
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <input type="hidden" name="id" value="<?= e($usuario['id']) ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="nome">Nome completo</label>
                <input 
                    type="text" 
                    id="nome" 
                    name="nome" 
                    value="<?= e($usuario['nome']) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">E-mail de acesso</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="<?= e($usuario['email']) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="perfil">Perfil</label>
                <select id="perfil" name="perfil" required>
                    <option value="admin" <?= $usuario['perfil'] === 'admin' ? 'selected' : '' ?>>
                        Administrador
                    </option>

                    <option value="profissional" <?= $usuario['perfil'] === 'profissional' ? 'selected' : '' ?>>
                        Profissional de Saúde
                    </option>

                    <option value="paciente" <?= $usuario['perfil'] === 'paciente' ? 'selected' : '' ?>>
                        Paciente
                    </option>

                    <option value="recepcao" <?= $usuario['perfil'] === 'recepcao' ? 'selected' : '' ?>>
                        Recepção
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label>Status</label>

                <?php if ((int)$usuario['ativo'] === 1): ?>
                    <span class="badge badge-success form-status-badge">Ativo</span>
                <?php else: ?>
                    <span class="badge badge-danger form-status-badge">Inativo</span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="senha">
                    <?= $id > 0 ? 'Nova senha' : 'Senha' ?>
                </label>
                <input 
                    type="password" 
                    id="senha" 
                    name="senha" 
                    placeholder="<?= $id > 0 ? 'Deixe em branco para manter a senha atual' : 'Mínimo 6 caracteres' ?>"
                    <?= $id > 0 ? '' : 'required' ?>
                >
            </div>

            <div class="form-group">
                <label for="confirmar_senha">
                    <?= $id > 0 ? 'Confirmar nova senha' : 'Confirmar senha' ?>
                </label>
                <input 
                    type="password" 
                    id="confirmar_senha" 
                    name="confirmar_senha" 
                    placeholder="Digite novamente a senha"
                    <?= $id > 0 ? '' : 'required' ?>
                >
            </div>

            <div class="form-group form-group-full">
                <label for="paciente_id">Vincular a paciente</label>
                <select id="paciente_id" name="paciente_id">
                    <option value="">Nenhum paciente vinculado</option>

                    <?php foreach ($pacientes as $paciente): ?>
                        <option 
                            value="<?= (int)$paciente['id'] ?>"
                            <?= (int)$pacienteVinculadoId === (int)$paciente['id'] ? 'selected' : '' ?>
                        >
                            <?= e($paciente['nome']) ?>
                            <?= !empty($paciente['cpf']) ? ' - CPF: ' . e($paciente['cpf']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <small class="form-help">
                    Use este campo quando o perfil for Paciente.
                </small>
            </div>

            <div class="form-group form-group-full">
                <label for="profissional_id">Vincular a profissional de saúde</label>
                <select id="profissional_id" name="profissional_id">
                    <option value="">Nenhum profissional vinculado</option>

                    <?php foreach ($profissionais as $profissional): ?>
                        <option 
                            value="<?= (int)$profissional['id'] ?>"
                            <?= (int)$profissionalVinculadoId === (int)$profissional['id'] ? 'selected' : '' ?>
                        >
                            <?= e($profissional['nome']) ?>
                            <?= !empty($profissional['especialidade']) ? ' - ' . e($profissional['especialidade']) : '' ?>
                            <?= !empty($profissional['registro_profissional']) ? ' / ' . e($profissional['registro_profissional']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <small class="form-help">
                    Use este campo quando o perfil for Profissional de Saúde.
                </small>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>admin/usuarios.php" class="btn btn-light">
                Cancelar
            </a>

            <button type="submit" class="btn btn-primary-small">
                Salvar Usuário
            </button>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>