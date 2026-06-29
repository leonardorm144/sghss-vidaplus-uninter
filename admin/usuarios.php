<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$pageTitle = 'Usuários';
$pageSubtitle = 'Gestão de acessos, perfis e permissões do sistema';
$menuAtivo = 'usuarios';

$busca = trim($_GET['busca'] ?? '');
$msg = $_GET['msg'] ?? '';
$erro = $_GET['erro'] ?? '';

$opcoesPorPagina = [25, 50, 75, 100];

$porPagina = isset($_GET['por_pagina']) ? (int)$_GET['por_pagina'] : 25;

if (!in_array($porPagina, $opcoesPorPagina)) {
    $porPagina = 25;
}

$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

if ($paginaAtual < 1) {
    $paginaAtual = 1;
}

$where = "WHERE 1 = 1";
$params = [];

if ($busca !== '') {
    $where .= "
        AND (
            nome LIKE :busca_nome
            OR email LIKE :busca_email
            OR perfil LIKE :busca_perfil
        )
    ";

    $termoBusca = '%' . $busca . '%';

    $params[':busca_nome'] = $termoBusca;
    $params[':busca_email'] = $termoBusca;
    $params[':busca_perfil'] = $termoBusca;
}

$stmtTotal = $pdo->prepare("
    SELECT COUNT(*)
    FROM usuarios
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
    FROM usuarios
    {$where}
    ORDER BY ativo DESC, nome ASC
    LIMIT :limite OFFSET :offset
");

foreach ($params as $chave => $valor) {
    $stmt->bindValue($chave, $valor);
}

$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$usuarios = $stmt->fetchAll();

$registroInicial = $totalRegistros > 0 ? $offset + 1 : 0;
$registroFinal = min($offset + $porPagina, $totalRegistros);

require_once __DIR__ . '/../includes/header.php';

function labelPerfilUsuario($perfil)
{
    switch ($perfil) {
        case 'admin':
            return 'Administrador';
        case 'profissional':
            return 'Profissional';
        case 'paciente':
            return 'Paciente';
        case 'recepcao':
            return 'Recepção';
        default:
            return $perfil;
    }
}

function classePerfilUsuario($perfil)
{
    switch ($perfil) {
        case 'admin':
            return 'badge-danger';
        case 'profissional':
            return 'badge-success';
        case 'paciente':
            return 'badge-info';
        case 'recepcao':
            return 'badge-warning';
        default:
            return 'badge-neutral';
    }
}
?>

<section class="page-actions">
    <div>
        <h2>Lista de Usuários</h2>
        <p>Gerencie os acessos ao SGHSS VidaPlus.</p>
    </div>

    <a href="<?= BASE_URL ?>admin/usuario_form.php" class="btn btn-primary-small">
        Novo Usuário
    </a>
</section>

<?php if ($msg === 'criado'): ?>
    <div class="alert-success">Usuário cadastrado com sucesso.</div>
<?php elseif ($msg === 'atualizado'): ?>
    <div class="alert-success">Usuário atualizado com sucesso.</div>
<?php elseif ($msg === 'inativado'): ?>
    <div class="alert-success">Usuário inativado com sucesso.</div>
<?php elseif ($msg === 'reativado'): ?>
    <div class="alert-success">Usuário reativado com sucesso.</div>
<?php endif; ?>

<?php if ($erro === 'proprio_usuario'): ?>
    <div class="alert-error">Você não pode inativar o próprio usuário logado.</div>
<?php endif; ?>

<section class="panel">
    <form method="get" class="search-form">
    <input 
        type="text" 
        name="busca" 
        placeholder="Buscar por nome, e-mail ou perfil"
        value="<?= e($busca) ?>"
    >

    <input type="hidden" name="por_pagina" value="<?= (int)$porPagina ?>">

    <button type="submit" class="btn btn-secondary">
        Buscar
    </button>

    <?php if ($busca !== ''): ?>
        <a href="<?= BASE_URL ?>admin/usuarios.php?por_pagina=<?= (int)$porPagina ?>" class="btn btn-light">
            Limpar
        </a>
    <?php endif; ?>
</form>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>Status</th>
                    <th>Último login</th>
                    <th>Criado em</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            Nenhum usuário encontrado.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?= e($usuario['nome']) ?></td>
                        <td><?= e($usuario['email']) ?></td>
                        <td>
                            <span class="badge <?= classePerfilUsuario($usuario['perfil']) ?>">
                                <?= e(labelPerfilUsuario($usuario['perfil'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ((int)$usuario['ativo'] === 1): ?>
                                <span class="badge badge-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($usuario['ultimo_login'])): ?>
                                <?= date('d/m/Y H:i', strtotime($usuario['ultimo_login'])) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($usuario['criado_em'])): ?>
                                <?= date('d/m/Y H:i', strtotime($usuario['criado_em'])) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <a 
                                href="<?= BASE_URL ?>admin/usuario_form.php?id=<?= (int)$usuario['id'] ?>" 
                                class="btn btn-light"
                            >
                                Editar
                            </a>

                            <?php if ((int)$usuario['ativo'] === 1): ?>
                                <form 
                                    method="post" 
                                    action="<?= BASE_URL ?>admin/usuario_status.php" 
                                    class="inline-form"
                                    data-confirm-title="Inativar usuário"
                                    data-confirm="Deseja realmente inativar o usuário <?= e($usuario['nome']) ?>? Ele não conseguirá mais acessar o sistema."
                                    data-confirm-button="Sim, inativar"
                                    data-confirm-cancel="Voltar"
                                    data-confirm-type="danger"
                                >
                                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                    <input type="hidden" name="id" value="<?= (int)$usuario['id'] ?>">
                                    <input type="hidden" name="acao" value="inativar">

                                    <button type="submit" class="btn btn-danger">
                                        Inativar
                                    </button>
                                </form>
                            <?php else: ?>
                                <form 
                                    method="post" 
                                    action="<?= BASE_URL ?>admin/usuario_status.php" 
                                    class="inline-form"
                                    data-confirm-title="Reativar usuário"
                                    data-confirm="Deseja reativar o usuário <?= e($usuario['nome']) ?>? Ele voltará a ter acesso ao sistema."
                                    data-confirm-button="Reativar usuário"
                                    data-confirm-cancel="Voltar"
                                    data-confirm-type="success"
                                >
                                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                    <input type="hidden" name="id" value="<?= (int)$usuario['id'] ?>">
                                    <input type="hidden" name="acao" value="reativar">

                                    <button type="submit" class="btn btn-primary-small">
                                        Reativar
                                    </button>
                                </form>
                            <?php endif; ?>
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
                    href="<?= BASE_URL ?>admin/usuarios.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $paginaAnterior])) ?>"
                >
                    Anterior
                </a>

                <?php if ($inicio > 1): ?>
                    <a 
                        class="pagination-link"
                        href="<?= BASE_URL ?>admin/usuarios.php?<?= http_build_query(array_merge($queryBase, ['pagina' => 1])) ?>"
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
                        href="<?= BASE_URL ?>admin/usuarios.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $pagina])) ?>"
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
                        href="<?= BASE_URL ?>admin/usuarios.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $totalPaginas])) ?>"
                    >
                        <?= $totalPaginas ?>
                    </a>
                <?php endif; ?>

                <a 
                    class="pagination-link <?= $paginaAtual === $totalPaginas ? 'disabled' : '' ?>"
                    href="<?= BASE_URL ?>admin/usuarios.php?<?= http_build_query(array_merge($queryBase, ['pagina' => $proximaPagina])) ?>"
                >
                    Próxima
                </a>
            </div>
        <?php endif; ?>
    </div>
    
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>