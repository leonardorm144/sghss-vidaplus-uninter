<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/valida_perfil.php';

exigirPerfil('admin');

$pageTitle = 'Sobre o Sistema';
$pageSubtitle = 'Visão geral do projeto SGHSS VidaPlus';
$menuAtivo = 'sobre';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="about-hero">
    <div class="about-hero-content">
        <span class="about-kicker">Projeto Acadêmico UNINTER</span>

        <h2>SGHSS VidaPlus</h2>

       <p>
            Sistema de Gestão Hospitalar e de Serviços de Saúde desenvolvido para centralizar
            atendimentos, pacientes, profissionais, exames, prontuários, prescrições,
            internações, leitos, suprimentos, telemedicina e auditoria.
        </p>

        <div class="about-hero-actions">
            <a href="<?= BASE_URL ?>dashboard.php" class="btn btn-primary-small">
                Acessar Dashboard
            </a>

            <a href="<?= BASE_URL ?>admin/relatorios.php" class="btn btn-secondary">
                Ver Relatórios
            </a>
        </div>
    </div>

    <div class="about-hero-card">
        <div class="about-system-icon">+</div>

        <strong>VidaPlus</strong>
        <span>Sistema SGHSS</span>

        <div class="about-status">
            <span></span>
            Sistema online
        </div>
    </div>
</section>

<section class="about-grid">
    <article class="about-card">
        <div class="about-card-icon">🎯</div>
        <h3>Objetivo do Sistema</h3>
        <p>
            Centralizar a gestão de uma rede de saúde, permitindo que diferentes perfis
            acessem recursos específicos conforme sua função dentro da instituição.
        </p>
    </article>

    <article class="about-card">
        <div class="about-card-icon">🏥</div>
        <h3>Gestão Hospitalar</h3>
        <p>
            Controle de unidades, leitos, internações, altas hospitalares, suprimentos
            e disponibilidade de recursos para apoio administrativo.
        </p>
    </article>

    <article class="about-card">
        <div class="about-card-icon">🩺</div>
        <h3>Atendimento Clínico</h3>
        <p>
            Cadastro de consultas, prontuários, prescrições digitais, histórico do paciente
            e acompanhamento por profissionais de saúde.
        </p>
    </article>

    <article class="about-card">
        <div class="about-card-icon">💻</div>
        <h3>Telemedicina</h3>
        <p>
            Simulação de atendimentos online por meio de links de teleconsulta, permitindo
            integração entre paciente e profissional.
        </p>
    </article>
</section>

<section class="about-section">
    <div class="about-section-header">
        <div>
            <span class="about-kicker">Módulos</span>
            <h2>Funcionalidades implementadas</h2>
            <p>
                O projeto foi desenvolvido com foco em organização, usabilidade, controle de acesso
                e apresentação visual moderna.
            </p>
        </div>
    </div>

    <div class="about-modules-grid">
        <div class="about-module">
            <span>👥</span>
            <div>
                <strong>Pacientes</strong>
                <p>Cadastro, edição, histórico, consultas, exames e prescrições.</p>
            </div>
        </div>

        <div class="about-module">
            <span>🩺</span>
            <div>
                <strong>Profissionais</strong>
                <p>Agenda médica, prontuários, prescrições e atendimentos.</p>
            </div>
        </div>

        <div class="about-module">
            <span>📅</span>
            <div>
                <strong>Consultas</strong>
                <p>Agendamento presencial, telemedicina e controle de status.</p>
            </div>
        </div>

        <div class="about-module">
            <span>🧪</span>
            <div>
                <strong>Exames</strong>
                <p>Solicitação, agendamento, realização e resultado de exames.</p>
            </div>
        </div>

        <div class="about-module">
            <span>🛏️</span>
            <div>
                <strong>Leitos</strong>
                <p>Controle de disponibilidade, ocupação e manutenção.</p>
            </div>
        </div>

        <div class="about-module">
            <span>🚑</span>
            <div>
                <strong>Internações</strong>
                <p>Registro de internação, alta médica e liberação automática do leito.</p>
            </div>
        </div>
        
        <div class="about-module">
            <span>📦</span>
            <div>
                <strong>Suprimentos</strong>
                <p>
                    Controle de materiais hospitalares, estoque atual, estoque mínimo,
                    entradas, saídas, inativação e reativação de itens.
                </p>
            </div>
        </div>

        <div class="about-module">
            <span>📊</span>
            <div>
                <strong>Relatórios</strong>
                <p>Indicadores administrativos, clínicos, hospitalares e assistenciais.</p>
            </div>
        </div>

        <div class="about-module">
            <span>🔐</span>
            <div>
                <strong>Auditoria</strong>
                <p>Registro de acessos, alterações e ações importantes no sistema.</p>
            </div>
        </div>
        
        <div class="about-module">
            <span>🔑</span>
            <div>
                <strong>Primeiro acesso seguro</strong>
                <p>
                    Usuários de pacientes são criados automaticamente pela recepção e devem
                    trocar a senha inicial no primeiro acesso.
                </p>
            </div>
        </div>
        
        <div class="about-module">
            <span>💬</span>
            <div>
                <strong>Envio assistido por WhatsApp</strong>
                <p>
                    Acesso do paciente pode ser preparado para envio pelo WhatsApp,
                    respeitando o consentimento LGPD registrado no cadastro.
                </p>
            </div>
        </div>

        <div class="about-module">
            <span>📱</span>
            <div>
                <strong>Interface responsiva</strong>
                <p>
                    O sistema possui layout adaptado para desktop, notebook e celular,
                    com navegação mobile em formato de aplicativo.
                </p>
            </div>
        </div>

        <div class="about-module">
            <span>✅</span>
            <div>
                <strong>Confirmações personalizadas</strong>
                <p>
                    Ações sensíveis utilizam modais próprios do sistema, substituindo
                    confirmações nativas do navegador.
                </p>
            </div>
        </div>
    </div>
    
    
</section>

<section class="about-section">
    <div class="about-section-header">
        <div>
            <span class="about-kicker">Usabilidade</span>
            <h2>Experiência do usuário</h2>
            <p>
                Além das funcionalidades administrativas e clínicas, o sistema foi
                desenvolvido com foco em navegação simples, visual moderno e uso em
                diferentes dispositivos.
            </p>
        </div>
    </div>

    <div class="about-grid">
        <article class="about-card">
            <div class="about-card-icon">📱</div>
            <h3>Layout Mobile</h3>
            <p>
                O sistema possui navegação adaptada para celular, com menu inferior
                interativo e experiência semelhante a um aplicativo.
            </p>
        </article>

        <article class="about-card">
            <div class="about-card-icon">🪟</div>
            <h3>Modais Personalizados</h3>
            <p>
                Confirmações importantes foram padronizadas com janelas modernas,
                evitando alertas nativos do navegador e melhorando a experiência visual.
            </p>
        </article>

        <article class="about-card">
            <div class="about-card-icon">🔑</div>
            <h3>Acesso do Paciente</h3>
            <p>
                O sistema permite criar, consultar e redefinir o acesso do paciente,
                com senha inicial segura e troca obrigatória no primeiro login.
            </p>
        </article>

        <article class="about-card">
            <div class="about-card-icon">💬</div>
            <h3>Suporte via WhatsApp</h3>
            <p>
                A recepção e o administrador podem preparar uma mensagem de acesso
                para envio ao paciente, respeitando as regras de consentimento LGPD.
            </p>
        </article>
    </div>
</section>

<section class="about-section">
    <div class="about-section-header">
        <div>
            <span class="about-kicker">Perfis</span>
            <h2>Controle de acesso por perfil</h2>
            <p>
                Cada tipo de usuário possui uma área específica, com menu, dashboard e funcionalidades
                adaptadas à sua rotina.
            </p>
        </div>
    </div>

    <div class="about-profile-grid">
        <article class="about-profile-card">
            <div class="about-profile-icon">🛡️</div>
            <h3>Administrador</h3>
            <p>
                Gerencia usuários, pacientes, profissionais, unidades, leitos, internações,
                suprimentos, relatórios e auditoria.
            </p>
        </article>

        <article class="about-profile-card">
            <div class="about-profile-icon">📋</div>
            <h3>Recepção</h3>
            <p>
                Realiza agendamentos, cadastra pacientes, controla exames e acompanha a agenda
                de atendimento.
            </p>
        </article>

        <article class="about-profile-card">
            <div class="about-profile-icon">🩺</div>
            <h3>Profissional</h3>
            <p>
                Acompanha a própria agenda, registra prontuários e emite prescrições digitais.
            </p>
        </article>

        <article class="about-profile-card">
            <div class="about-profile-icon">🙂</div>
            <h3>Paciente</h3>
            <p>
                Visualiza consultas, teleconsultas, histórico clínico, exames, prescrições
                e internações.
            </p>
        </article>
    </div>
</section>

<section class="about-section">
    <div class="about-section-header">
        <div>
            <span class="about-kicker">Fluxo</span>
            <h2>Como o sistema funciona</h2>
            <p>
                O SGHSS VidaPlus organiza a jornada do paciente desde o cadastro até o histórico
                completo de atendimento.
            </p>
        </div>
    </div>

    <div class="about-timeline">
    <div class="about-timeline-item">
        <span>1</span>
        <div>
            <strong>Cadastro do paciente</strong>
            <p>
                O paciente é cadastrado pela recepção, e o sistema cria automaticamente
                um usuário vinculado ao seu registro.
            </p>
        </div>
    </div>

    <div class="about-timeline-item">
        <span>2</span>
        <div>
            <strong>Primeiro acesso seguro</strong>
            <p>
                No primeiro login, o paciente utiliza o acesso inicial e é direcionado
                obrigatoriamente para criar uma nova senha pessoal.
            </p>
        </div>
    </div>

    <div class="about-timeline-item">
        <span>3</span>
        <div>
            <strong>Agendamento</strong>
            <p>A recepção ou o administrador agenda consultas presenciais ou teleconsultas.</p>
        </div>
    </div>

    <div class="about-timeline-item">
        <span>4</span>
        <div>
            <strong>Atendimento</strong>
            <p>O profissional realiza o atendimento e registra prontuários e prescrições.</p>
        </div>
    </div>

    <div class="about-timeline-item">
    <span>5</span>
    <div>
        <strong>Exames e internações</strong>
        <p>O sistema permite controlar exames, resultados, leitos e internações.</p>
    </div>
        </div>

        <div class="about-timeline-item">
            <span>6</span>
            <div>
                <strong>Suprimentos hospitalares</strong>
                <p>
                    O administrador acompanha materiais, estoque mínimo, entradas, saídas
                    e alertas de reposição.
                </p>
            </div>
        </div>

        <div class="about-timeline-item">
            <span>7</span>
            <div>
                <strong>Histórico e relatórios</strong>
                <p>Paciente visualiza seu histórico e o administrador acompanha indicadores.</p>
            </div>
        </div>
</div>
</section>

<section class="about-two-columns">
    <article class="about-section about-security">
        <div class="about-section-header">
            <div>
                <span class="about-kicker">Segurança</span>
                <h2>LGPD e auditoria</h2>
                <p>
                    O sistema foi estruturado considerando boas práticas de segurança,
                    controle de sessão, perfis de acesso e registro de ações importantes.
                </p>
            </div>
        </div>

        <ul class="about-check-list">
            <li>Controle de acesso por perfil: administrador, recepção, profissional e paciente.</li>
            <li>Senhas armazenadas com hash seguro.</li>
            <li>Troca obrigatória de senha no primeiro acesso do paciente.</li>
            <li>Proteção CSRF em formulários e ações sensíveis.</li>
            <li>Registro de login, alterações e ações críticas em auditoria.</li>
            <li>Registro de movimentações de suprimentos com usuário, data, estoque anterior e estoque posterior.</li>
            <li>Separação de áreas por tipo de usuário.</li>
            <li>Histórico clínico acessível somente ao paciente vinculado.</li>
            <li>Consentimento LGPD registrado no cadastro do paciente.</li>
            <li>Bloqueio de envio de dados de acesso pelo WhatsApp quando a LGPD está pendente.</li>
            <li>Confirmações personalizadas antes de ações como cancelar, inativar, concluir ou redefinir senha.</li>
        </ul>
    </article>

    <article class="about-section about-tech">
        <div class="about-section-header">
            <div>
                <span class="about-kicker">Tecnologias</span>
                <h2>Base técnica</h2>
                <p>
                    O projeto foi desenvolvido utilizando tecnologias web tradicionais,
                    com foco em front-end responsivo e integração com banco de dados.
                </p>
            </div>
        </div>

    <div class="about-tech-list">
        <span>PHP</span>
        <span>MySQL</span>
        <span>PDO</span>
        <span>HTML5</span>
        <span>CSS3</span>
        <span>JavaScript</span>
        <span>Chart.js</span>
        <span>Responsivo</span>
        <span>CSRF</span>
        <span>Auditoria</span>
        <span>LGPD</span>
        <span>Controle de Estoque</span>
    </div>
    </article>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>