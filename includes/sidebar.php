<?php

if (!function_exists('classeMenuAtivo')) {
    function classeMenuAtivo($item, $menuAtivo)
    {
        return $item === $menuAtivo ? 'active' : '';
    }
}

if (!function_exists('labelPerfilSidebar')) {
    function labelPerfilSidebar($perfil)
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
                return 'Usuário';
        }
    }
}
?>

<aside class="sidebar">
    <div class="sidebar-top">
        <div class="sidebar-logo">
            <div class="logo-icon">+</div>

            <div class="logo-text">
                <h2>VidaPlus</h2>
                <span>SGHSS</span>
            </div>
        </div>

        <div class="sidebar-user-card">
            <div class="user-avatar">
                <?= strtoupper(substr($nomeUsuario ?? 'U', 0, 1)) ?>
            </div>

            <div>
                <strong><?= e($nomeUsuario ?? 'Usuário') ?></strong>
                <span><?= e(labelPerfilSidebar($perfil)) ?></span>
            </div>
        </div>
    </div>

    <nav class="sidebar-menu">
        <div class="menu-section">
            <span class="menu-section-title">Principal</span>

            <a href="<?= BASE_URL ?>dashboard.php" class="<?= classeMenuAtivo('dashboard', $menuAtivo) ?>">
                <span class="menu-icon">🏠</span>
                <span class="menu-text">Início</span>
            </a>
        </div>

        <?php if ($perfil === 'admin'): ?>
            <div class="menu-section">
                <span class="menu-section-title">Gestão Clínica</span>

                <a href="<?= BASE_URL ?>admin/pacientes.php" class="<?= classeMenuAtivo('pacientes', $menuAtivo) ?>">
                    <span class="menu-icon">👥</span>
                    <span class="menu-text">Pacientes</span>
                </a>

                <a href="<?= BASE_URL ?>admin/profissionais.php" class="<?= classeMenuAtivo('profissionais', $menuAtivo) ?>">
                    <span class="menu-icon">🩺</span>
                    <span class="menu-text">Profissionais</span>
                </a>

                <a href="<?= BASE_URL ?>admin/consultas.php" class="<?= classeMenuAtivo('consultas', $menuAtivo) ?>">
                    <span class="menu-icon">📅</span>
                    <span class="menu-text">Consultas</span>
                </a>

                <a href="<?= BASE_URL ?>admin/prontuarios.php" class="<?= classeMenuAtivo('prontuarios', $menuAtivo) ?>">
                    <span class="menu-icon">📝</span>
                    <span class="menu-text">Prontuários</span>
                </a>

                <a href="<?= BASE_URL ?>admin/prescricoes.php" class="<?= classeMenuAtivo('prescricoes', $menuAtivo) ?>">
                    <span class="menu-icon">💊</span>
                    <span class="menu-text">Prescrições</span>
                </a>
            </div>

            <div class="menu-section">
                <span class="menu-section-title">Hospitalar</span>

                <a href="<?= BASE_URL ?>admin/unidades.php" class="<?= classeMenuAtivo('unidades', $menuAtivo) ?>">
                    <span class="menu-icon">🏥</span>
                    <span class="menu-text">Unidades</span>
                </a>

                <a href="<?= BASE_URL ?>admin/leitos.php" class="<?= classeMenuAtivo('leitos', $menuAtivo) ?>">
                    <span class="menu-icon">🛏️</span>
                    <span class="menu-text">Leitos</span>
                </a>

                <a href="<?= BASE_URL ?>admin/internacoes.php" class="<?= classeMenuAtivo('internacoes', $menuAtivo) ?>">
                    <span class="menu-icon">🚑</span>
                    <span class="menu-text">Internações</span>
                </a>
                
                <a href="<?= BASE_URL ?>admin/suprimentos.php" class="<?= classeMenuAtivo('suprimentos', $menuAtivo) ?>">
                    <span class="menu-icon">📦</span>
                    <span class="menu-text">Suprimentos</span>
                </a>
                
            </div>

            <div class="menu-section">
                <span class="menu-section-title">Administração</span>

                <a href="<?= BASE_URL ?>admin/relatorios.php" class="<?= classeMenuAtivo('relatorios', $menuAtivo) ?>">
                    <span class="menu-icon">📊</span>
                    <span class="menu-text">Relatórios</span>
                </a>
                
                <a href="<?= BASE_URL ?>admin/sobre.php" class="<?= classeMenuAtivo('sobre', $menuAtivo) ?>">
                    <span class="menu-icon">✨</span>
                    <span class="menu-text">Sobre o Sistema</span>
                </a>

                <a href="<?= BASE_URL ?>admin/usuarios.php" class="<?= classeMenuAtivo('usuarios', $menuAtivo) ?>">
                    <span class="menu-icon">👤</span>
                    <span class="menu-text">Usuários</span>
                </a>

                <a href="<?= BASE_URL ?>admin/auditoria.php" class="<?= classeMenuAtivo('auditoria', $menuAtivo) ?>">
                    <span class="menu-icon">🔐</span>
                    <span class="menu-text">Auditoria</span>
                </a>
            </div>
        <?php endif; ?>

        <?php if ($perfil === 'profissional'): ?>
            <div class="menu-section">
                <span class="menu-section-title">Área Profissional</span>

                <a href="<?= BASE_URL ?>profissional/agenda.php" class="<?= classeMenuAtivo('agenda', $menuAtivo) ?>">
                    <span class="menu-icon">📅</span>
                    <span class="menu-text">Minha Agenda</span>
                </a>

                <a href="<?= BASE_URL ?>profissional/prontuarios.php" class="<?= classeMenuAtivo('prontuarios', $menuAtivo) ?>">
                    <span class="menu-icon">📝</span>
                    <span class="menu-text">Prontuários</span>
                </a>

                <a href="<?= BASE_URL ?>profissional/prescricoes.php" class="<?= classeMenuAtivo('prescricoes', $menuAtivo) ?>">
                    <span class="menu-icon">💊</span>
                    <span class="menu-text">Prescrições</span>
                </a>
            </div>
        <?php endif; ?>

        <?php if ($perfil === 'paciente'): ?>
            <div class="menu-section">
                <span class="menu-section-title">Área do Paciente</span>

                <a href="<?= BASE_URL ?>paciente/consultas.php" class="<?= classeMenuAtivo('minhas_consultas', $menuAtivo) ?>">
                    <span class="menu-icon">📅</span>
                    <span class="menu-text">Consultas</span>
                </a>

                <a href="<?= BASE_URL ?>paciente/historico.php" class="<?= classeMenuAtivo('meu_historico', $menuAtivo) ?>">
                    <span class="menu-icon">📁</span>
                    <span class="menu-text">Histórico</span>
                </a>

                <a href="<?= BASE_URL ?>paciente/teleconsulta.php" class="<?= classeMenuAtivo('teleconsulta', $menuAtivo) ?>">
                    <span class="menu-icon">💻</span>
                    <span class="menu-text">Teleconsulta</span>
                </a>
            </div>
        <?php endif; ?>

        <?php if ($perfil === 'recepcao'): ?>
            <div class="menu-section">
                <span class="menu-section-title">Recepção</span>

                <a href="<?= BASE_URL ?>recepcao/agendamentos.php" class="<?= classeMenuAtivo('agendamentos', $menuAtivo) ?>">
                    <span class="menu-icon">📋</span>
                    <span class="menu-text">Agendamentos</span>
                </a>

                <a href="<?= BASE_URL ?>recepcao/pacientes.php" class="<?= classeMenuAtivo('pacientes', $menuAtivo) ?>">
                    <span class="menu-icon">👥</span>
                    <span class="menu-text">Pacientes</span>
                </a>

                <a href="<?= BASE_URL ?>recepcao/exames.php" class="<?= classeMenuAtivo('exames', $menuAtivo) ?>">
                    <span class="menu-icon">🧪</span>
                    <span class="menu-text">Exames</span>
                </a>
            </div>
        <?php endif; ?>
        
                <div class="menu-section mobile-menu-only">
            <a href="<?= BASE_URL ?>logout.php" class="logout-link">
                <span class="menu-icon">🚪</span>
                <span class="menu-text">Sair</span>
            </a>
        </div>
        
    </nav>

    <div class="sidebar-footer-menu">
        <a href="<?= BASE_URL ?>logout.php" class="logout-link">
            <span class="menu-icon">🚪</span>
            <span class="menu-text">Sair</span>
        </a>
    </div>
</aside>