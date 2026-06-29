<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$pageTitle = 'Pacientes';
$pageSubtitle = 'Cadastro e gerenciamento de pacientes';
$menuAtivo = 'pacientes';

$busca = trim($_GET['busca'] ?? '');
$msg = $_GET['msg'] ?? '';

$opcoesPorPagina = [25, 50, 75, 100];

$porPagina = isset($_GET['por_pagina']) ? (int)$_GET['por_pagina'] : 25;

if (!in_array($porPagina, $opcoesPorPagina)) {
    $porPagina = 25;
}

$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

if ($paginaAtual < 1) {
    $paginaAtual = 1;
}

$where = "WHERE ativo = 1";
$params = [];

if ($busca !== '') {
    $where .= "
        AND (
            nome LIKE :busca_nome
            OR cpf LIKE :busca_cpf
            OR telefone LIKE :busca_telefone
            OR email LIKE :busca_email
        )
    ";

    $termoBusca = '%' . $busca . '%';

    $params[':busca_nome'] = $termoBusca;
    $params[':busca_cpf'] = $termoBusca;
    $params[':busca_telefone'] = $termoBusca;
    $params[':busca_email'] = $termoBusca;
}

$stmtTotal = $pdo->prepare("
    SELECT COUNT(*)
    FROM pacientes
    {$where}
");

$stmtTotal->execute($params);

$totalRegistros = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, (int)ceil($totalRegistros / $porPagina));

if ($paginaAtual > $totalPaginas) {
    $paginaAtual = $totalPaginas;
}

$offset = ($paginaAtual - 1) * $porPagina;

$stmt = $pdo->prepare("
    SELECT *
    FROM pacientes
    {$where}
    ORDER BY nome ASC
    LIMIT :limite OFFSET :offset
");

foreach ($params as $chave => $valor) {
    $stmt->bindValue($chave, $valor);
}

$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$pacientes = $stmt->fetchAll();

$registroInicial = $totalRegistros > 0 ? $offset + 1 : 0;
$registroFinal = min($offset + $porPagina, $totalRegistros);

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-actions">
    <div>
        <h2>Lista de Pacientes</h2>
        <p>Consulte, cadastre e atualize os dados dos pacientes.</p>
    </div>

    <a href="<?= BASE_URL ?>admin/paciente_form.php" class="btn btn-primary-small">
        Novo Paciente
    </a>
</section>

<?php if ($msg === 'criado_usuario'): ?>
    <div class="alert-success">
        <strong>Paciente cadastrado com sucesso.</strong>
        <br><br>

        O acesso do paciente foi criado automaticamente:
        <br>

        <strong>Login:</strong> <?= e($_GET['login'] ?? '') ?>
        <br>

        <strong>Senha inicial:</strong> <?= e($_GET['senha'] ?? '') ?>
        <br><br>

        <small>
            Oriente o paciente a utilizar esses dados no primeiro acesso.
        </small>
    </div>
<?php elseif ($msg === 'criado'): ?>
    <div class="alert-success">Paciente cadastrado com sucesso.</div>
<?php elseif ($msg === 'atualizado'): ?>
    <div class="alert-success">Paciente atualizado com sucesso.</div>
<?php elseif ($msg === 'excluido'): ?>
    <div class="alert-success">Paciente inativado com sucesso.</div>
<?php endif; ?>

<section class="panel">
    <form method="get" class="search-form">
    <input 
        type="text" 
        name="busca" 
        placeholder="Buscar por nome, CPF, telefone ou e-mail"
        value="<?= e($busca) ?>"
    >

    <input type="hidden" name="por_pagina" value="<?= (int)$porPagina ?>">

    <button type="submit" class="btn btn-secondary">
        Buscar
    </button>

    <?php if ($busca !== ''): ?>
        <a href="<?= BASE_URL ?>admin/pacientes.php?por_pagina=<?= (int)$porPagina ?>" class="btn btn-light">
            Limpar
        </a>
    <?php endif; ?>
</form>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>Telefone</th>
                    <th>E-mail</th>
                    <th>Nascimento</th>
                    <th>LGPD</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($pacientes)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            Nenhum paciente encontrado.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($pacientes as $paciente): ?>
                    <tr>
                        <td><?= e($paciente['nome']) ?></td>
                        <td><?= e($paciente['cpf'] ?: '-') ?></td>
                        <td><?= e($paciente['telefone'] ?: '-') ?></td>
                        <td><?= e($paciente['email'] ?: '-') ?></td>
                        <td>
                            <?php if (!empty($paciente['data_nascimento'])): ?>
                                <?= date('d/m/Y', strtotime($paciente['data_nascimento'])) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int)$paciente['consentimento_lgpd'] === 1): ?>
                                <span class="badge badge-success">Aceito</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Pendente</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <a 
                                href="<?= BASE_URL ?>admin/paciente_form.php?id=<?= (int)$paciente['id'] ?>" 
                                class="btn btn-light"
                            >
                                Editar
                            </a>
                            
                            <a 
                                href="<?= BASE_URL ?>admin/paciente_acesso.php?id=<?= (int)$paciente['id'] ?>" 
                                class="btn btn-secondary"
                            >
                                Acesso
                            </a>

                            <form 
                                method="post" 
                                action="<?= BASE_URL ?>admin/paciente_excluir.php" 
                                class="inline-form"
                                data-confirm-title="Inativar paciente"
                                data-confirm="Deseja realmente inativar o paciente <?= e($paciente['nome']) ?>? Ele não será mais exibido nas listagens ativas."
                                data-confirm-button="Sim, inativar"
                                data-confirm-cancel="Voltar"
                                data-confirm-type="danger"
                            >
                                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                <input type="hidden" name="id" value="<?= (int)$paciente['id'] ?>">

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
    <div class="pagination-wrapper">
    <div class="pagination-info">
        Exibindo 
        <strong><?= $registroInicial ?></strong>
        até
        <strong><?= $registroFinal ?></strong>
        de
        <strong><?= $totalRegistros ?></strong>
        registros
    </div>

    <form method="get" class="pagination-size-form">
        <?php if ($busca !== ''): ?>
            <input type="hidden" name="busca" value="<?= e($busca) ?>">
        <?php endif; ?>

        <input type="hidden" name="pagina" value="1">

        <label for="por_pagina">Itens por página</label>

        <select id="por_pagina" name="por_pagina" onchange="this.form.submit()">
            <?php foreach ($opcoesPorPagina as $opcao): ?>
                <option value="<?= $opcao ?>" <?= $porPagina === $opcao ? 'selected' : '' ?>>
                    <?= $opcao ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($totalPaginas > 1): ?>
        <div class="pagination-pages">
            <?php
                $queryBase = [
                    'busca' => $busca,
                    'por_pagina' => $porPagina
                ];

                $paginaAnterior = max(1, $paginaAtual - 1);
                $proximaPagina = min($totalPaginas, $paginaAtual + 1);

                $inicio = max(1, $paginaAtual - 2);
                $fim = min($totalPaginas, $paginaAtual + 2);
            ?>

            <a 
                class="pagination-link <?= $paginaAtual === 1 ? 'disabled' : '' ?>"
                href="<?= BASE_URL ?>admin/pacientes.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $paginaAnterior])) ?>"
            >
                Anterior
            </a>

            <?php if ($inicio > 1): ?>
                <a 
                    class="pagination-link"
                    href="<?= BASE_URL ?>admin/pacientes.php?<?= http_build_query(array_merge($queryBase, ['pagina' => 1])) ?>"
                >
                    1
                </a>

                <?php if ($inicio > 2): ?>
                    <span class="pagination-dots">...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($pagina = $inicio; $pagina <= $fim; $pagina++): ?>
                <a 
                    class="pagination-link <?= $paginaAtual === $pagina ? 'active' : '' ?>"
                    href="<?= BASE_URL ?>admin/pacientes.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $pagina])) ?>"
                >
                    <?= $pagina ?>
                </a>
            <?php endfor; ?>

            <?php if ($fim < $totalPaginas): ?>
                <?php if ($fim < $totalPaginas - 1): ?>
                    <span class="pagination-dots">...</span>
                <?php endif; ?>

                <a 
                    class="pagination-link"
                    href="<?= BASE_URL ?>admin/pacientes.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $totalPaginas])) ?>"
                >
                    <?= $totalPaginas ?>
                </a>
            <?php endif; ?>

            <a 
                class="pagination-link <?= $paginaAtual === $totalPaginas ? 'disabled' : '' ?>"
                href="<?= BASE_URL ?>admin/pacientes.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $proximaPagina])) ?>"
            >
                Próxima
            </a>
        </div>
    <?php endif; ?>
</div>
</section>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>