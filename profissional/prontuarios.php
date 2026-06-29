<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('profissional');

$pageTitle = 'Prontuários';
$pageSubtitle = 'Registros clínicos realizados pelo profissional logado';
$menuAtivo = 'prontuarios';

$usuarioId = $_SESSION['usuario_id'] ?? 0;

$stmtProfissional = $pdo->prepare("
    SELECT *
    FROM profissionais
    WHERE usuario_id = :usuario_id
    AND ativo = 1
    LIMIT 1
");

$stmtProfissional->execute([
    ':usuario_id' => $usuarioId
]);

$profissional = $stmtProfissional->fetch();

$busca = trim($_GET['busca'] ?? '');
$msg = $_GET['msg'] ?? '';

$prontuarios = [];

if ($profissional) {
    if ($busca !== '') {
        $termoBusca = '%' . $busca . '%';

        $stmt = $pdo->prepare("
            SELECT 
                pt.*,
                p.nome AS paciente_nome,
                p.cpf AS paciente_cpf,
                c.data_consulta,
                c.tipo AS consulta_tipo,
                c.status AS consulta_status
            FROM prontuarios pt
            INNER JOIN pacientes p ON p.id = pt.paciente_id
            LEFT JOIN consultas c ON c.id = pt.consulta_id
            WHERE pt.ativo = 1
            AND pt.profissional_id = :profissional_id
            AND (
                p.nome LIKE :busca_paciente
                OR p.cpf LIKE :busca_cpf
                OR pt.descricao LIKE :busca_descricao
                OR pt.diagnostico LIKE :busca_diagnostico
                OR pt.conduta LIKE :busca_conduta
            )
            ORDER BY pt.criado_em DESC
        ");

        $stmt->execute([
            ':profissional_id' => $profissional['id'],
            ':busca_paciente' => $termoBusca,
            ':busca_cpf' => $termoBusca,
            ':busca_descricao' => $termoBusca,
            ':busca_diagnostico' => $termoBusca,
            ':busca_conduta' => $termoBusca
        ]);
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                pt.*,
                p.nome AS paciente_nome,
                p.cpf AS paciente_cpf,
                c.data_consulta,
                c.tipo AS consulta_tipo,
                c.status AS consulta_status
            FROM prontuarios pt
            INNER JOIN pacientes p ON p.id = pt.paciente_id
            LEFT JOIN consultas c ON c.id = pt.consulta_id
            WHERE pt.ativo = 1
            AND pt.profissional_id = :profissional_id
            ORDER BY pt.criado_em DESC
        ");

        $stmt->execute([
            ':profissional_id' => $profissional['id']
        ]);
    }

    $prontuarios = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!$profissional): ?>
    <section class="panel">
        <h2>Profissional não vinculado</h2>
        <p>
            Seu usuário ainda não está vinculado a um cadastro de profissional de saúde.
            Peça para o administrador vincular este login ao profissional correto.
        </p>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    <?php exit; ?>
<?php endif; ?>

<section class="page-actions">
    <div>
        <h2>Meus Prontuários</h2>
        <p>Registros clínicos cadastrados por <?= e($profissional['nome']) ?>.</p>
    </div>

    <a href="<?= BASE_URL ?>profissional/prontuario_form.php" class="btn btn-primary-small">
        Novo Prontuário
    </a>
</section>

<?php if ($msg === 'criado'): ?>
    <div class="alert-success">Prontuário cadastrado com sucesso.</div>
<?php elseif ($msg === 'atualizado'): ?>
    <div class="alert-success">Prontuário atualizado com sucesso.</div>
<?php elseif ($msg === 'excluido'): ?>
    <div class="alert-success">Prontuário inativado com sucesso.</div>
<?php endif; ?>

<section class="panel">
    <form method="get" class="search-form">
        <input 
            type="text" 
            name="busca" 
            placeholder="Buscar por paciente, CPF, diagnóstico, descrição ou conduta"
            value="<?= e($busca) ?>"
        >

        <button type="submit" class="btn btn-secondary">
            Buscar
        </button>

        <?php if ($busca !== ''): ?>
            <a href="<?= BASE_URL ?>profissional/prontuarios.php" class="btn btn-light">
                Limpar
            </a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Paciente</th>
                    <th>Consulta</th>
                    <th>Diagnóstico</th>
                    <th>Conduta</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($prontuarios)): ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            Nenhum prontuário encontrado.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($prontuarios as $prontuario): ?>
                    <tr>
                        <td>
                            <?= !empty($prontuario['criado_em']) ? date('d/m/Y H:i', strtotime($prontuario['criado_em'])) : '-' ?>
                        </td>

                        <td>
                            <?= e($prontuario['paciente_nome']) ?>

                            <?php if (!empty($prontuario['paciente_cpf'])): ?>
                                <br>
                                <small>CPF: <?= e($prontuario['paciente_cpf']) ?></small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if (!empty($prontuario['data_consulta'])): ?>
                                <?= date('d/m/Y H:i', strtotime($prontuario['data_consulta'])) ?>
                                <br>
                                <small><?= e($prontuario['consulta_tipo']) ?> - <?= e($prontuario['consulta_status']) ?></small>
                            <?php else: ?>
                                <span class="badge badge-neutral">Sem consulta</span>
                            <?php endif; ?>
                        </td>

                        <td><?= e($prontuario['diagnostico'] ?: '-') ?></td>

                        <td><?= e($prontuario['conduta'] ?: '-') ?></td>

                        <td class="text-right">
                            <a 
                                href="<?= BASE_URL ?>profissional/prontuario_form.php?id=<?= (int)$prontuario['id'] ?>" 
                                class="btn btn-light"
                            >
                                Editar
                            </a>

                            <form 
                                method="post" 
                                action="<?= BASE_URL ?>profissional/prontuario_excluir.php" 
                                class="inline-form"
                                data-confirm-title="Inativar prontuário"
                                data-confirm="Deseja realmente inativar este prontuário do paciente <?= e($prontuario['paciente_nome']) ?>? Ele não será mais exibido nas listagens ativas."
                                data-confirm-button="Sim, inativar"
                                data-confirm-cancel="Voltar"
                                data-confirm-type="danger"
                            >
                                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                <input type="hidden" name="id" value="<?= (int)$prontuario['id'] ?>">

                                <button type="submit" class="btn btn-danger">
                                    Inativar
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>