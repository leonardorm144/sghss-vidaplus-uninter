<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('profissional');

$pageTitle = 'Prescrições';
$pageSubtitle = 'Receitas digitais emitidas pelo profissional logado';
$menuAtivo = 'prescricoes';

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

$prescricoes = [];

if ($profissional) {
    if ($busca !== '') {
        $termoBusca = '%' . $busca . '%';

        $stmt = $pdo->prepare("
            SELECT 
                ps.*,
                p.nome AS paciente_nome,
                p.cpf AS paciente_cpf,
                c.data_consulta,
                c.tipo AS consulta_tipo,
                c.status AS consulta_status
            FROM prescricoes ps
            INNER JOIN pacientes p ON p.id = ps.paciente_id
            LEFT JOIN consultas c ON c.id = ps.consulta_id
            WHERE ps.ativo = 1
            AND ps.profissional_id = :profissional_id
            AND (
                p.nome LIKE :busca_paciente
                OR p.cpf LIKE :busca_cpf
                OR ps.medicamento LIKE :busca_medicamento
                OR ps.dosagem LIKE :busca_dosagem
                OR ps.orientacoes LIKE :busca_orientacoes
            )
            ORDER BY ps.data_emissao DESC
        ");

        $stmt->execute([
            ':profissional_id' => $profissional['id'],
            ':busca_paciente' => $termoBusca,
            ':busca_cpf' => $termoBusca,
            ':busca_medicamento' => $termoBusca,
            ':busca_dosagem' => $termoBusca,
            ':busca_orientacoes' => $termoBusca
        ]);
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                ps.*,
                p.nome AS paciente_nome,
                p.cpf AS paciente_cpf,
                c.data_consulta,
                c.tipo AS consulta_tipo,
                c.status AS consulta_status
            FROM prescricoes ps
            INNER JOIN pacientes p ON p.id = ps.paciente_id
            LEFT JOIN consultas c ON c.id = ps.consulta_id
            WHERE ps.ativo = 1
            AND ps.profissional_id = :profissional_id
            ORDER BY ps.data_emissao DESC
        ");

        $stmt->execute([
            ':profissional_id' => $profissional['id']
        ]);
    }

    $prescricoes = $stmt->fetchAll();
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
        <h2>Minhas Prescrições</h2>
        <p>Receitas digitais emitidas por <?= e($profissional['nome']) ?>.</p>
    </div>

    <a href="<?= BASE_URL ?>profissional/prescricao_form.php" class="btn btn-primary-small">
        Nova Prescrição
    </a>
</section>

<?php if ($msg === 'criado'): ?>
    <div class="alert-success">Prescrição cadastrada com sucesso.</div>
<?php elseif ($msg === 'atualizado'): ?>
    <div class="alert-success">Prescrição atualizada com sucesso.</div>
<?php elseif ($msg === 'excluido'): ?>
    <div class="alert-success">Prescrição inativada com sucesso.</div>
<?php endif; ?>

<section class="panel">
    <form method="get" class="search-form">
        <input 
            type="text" 
            name="busca" 
            placeholder="Buscar por paciente, CPF, medicamento, dosagem ou orientação"
            value="<?= e($busca) ?>"
        >

        <button type="submit" class="btn btn-secondary">
            Buscar
        </button>

        <?php if ($busca !== ''): ?>
            <a href="<?= BASE_URL ?>profissional/prescricoes.php" class="btn btn-light">
                Limpar
            </a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Emissão</th>
                    <th>Paciente</th>
                    <th>Medicamento</th>
                    <th>Dosagem</th>
                    <th>Consulta</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($prescricoes)): ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            Nenhuma prescrição encontrada.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($prescricoes as $prescricao): ?>
                    <tr>
                        <td>
                            <?= !empty($prescricao['data_emissao']) ? date('d/m/Y H:i', strtotime($prescricao['data_emissao'])) : '-' ?>
                        </td>

                        <td>
                            <?= e($prescricao['paciente_nome']) ?>

                            <?php if (!empty($prescricao['paciente_cpf'])): ?>
                                <br>
                                <small>CPF: <?= e($prescricao['paciente_cpf']) ?></small>
                            <?php endif; ?>
                        </td>

                        <td><?= e($prescricao['medicamento']) ?></td>

                        <td><?= e($prescricao['dosagem'] ?: '-') ?></td>

                        <td>
                            <?php if (!empty($prescricao['data_consulta'])): ?>
                                <?= date('d/m/Y H:i', strtotime($prescricao['data_consulta'])) ?>
                                <br>
                                <small><?= e($prescricao['consulta_tipo']) ?> - <?= e($prescricao['consulta_status']) ?></small>
                            <?php else: ?>
                                <span class="badge badge-neutral">Sem consulta</span>
                            <?php endif; ?>
                        </td>

                        <td class="text-right">
                            <a 
                                href="<?= BASE_URL ?>profissional/prescricao_visualizar.php?id=<?= (int)$prescricao['id'] ?>" 
                                class="btn btn-secondary"
                            >
                                Receita
                            </a>

                            <a 
                                href="<?= BASE_URL ?>profissional/prescricao_form.php?id=<?= (int)$prescricao['id'] ?>" 
                                class="btn btn-light"
                            >
                                Editar
                            </a>

                            <form 
                                method="post" 
                                action="<?= BASE_URL ?>profissional/prescricao_excluir.php" 
                                class="inline-form"
                                data-confirm-title="Inativar prescrição"
                                data-confirm="Deseja realmente inativar esta prescrição do paciente <?= e($prescricao['paciente_nome']) ?>? Ela não será mais exibida nas listagens ativas."
                                data-confirm-button="Sim, inativar"
                                data-confirm-cancel="Voltar"
                                data-confirm-type="danger"
                            >
                                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                <input type="hidden" name="id" value="<?= (int)$prescricao['id'] ?>">

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