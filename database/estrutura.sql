-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql101.infinityfree.com
-- Tempo de geração: 29/06/2026 às 14:12
-- Versão do servidor: 11.4.12-MariaDB
-- Versão do PHP: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `if0_40526926_sghss_uninter`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `auditoria_logs`
--

CREATE TABLE `auditoria_logs` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `acao` varchar(120) NOT NULL,
  `tabela_afetada` varchar(80) DEFAULT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `detalhes` text DEFAULT NULL,
  `ip` varchar(60) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `consultas`
--

CREATE TABLE `consultas` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `profissional_id` int(11) NOT NULL,
  `unidade_id` int(11) DEFAULT NULL,
  `tipo` enum('Presencial','Telemedicina') NOT NULL,
  `status` enum('Agendada','Confirmada','Cancelada','Concluida') DEFAULT 'Agendada',
  `data_consulta` datetime NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `link_teleconsulta` varchar(255) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `exames`
--

CREATE TABLE `exames` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `unidade_id` int(11) DEFAULT NULL,
  `tipo_exame` varchar(120) NOT NULL,
  `status` enum('Solicitado','Agendado','Realizado','Cancelado') DEFAULT 'Solicitado',
  `data_exame` datetime DEFAULT NULL,
  `resultado` text DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `nome_exame` varchar(150) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `atualizado_em` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `internacoes`
--

CREATE TABLE `internacoes` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `leito_id` int(11) NOT NULL,
  `data_entrada` datetime NOT NULL,
  `data_saida` datetime DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Ativa',
  `observacoes` text DEFAULT NULL,
  `data_alta` datetime DEFAULT NULL,
  `motivo` text DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `leitos`
--

CREATE TABLE `leitos` (
  `id` int(11) NOT NULL,
  `unidade_id` int(11) NOT NULL,
  `numero` varchar(30) NOT NULL,
  `setor` varchar(80) DEFAULT NULL,
  `status` enum('Disponivel','Ocupado','Manutencao') DEFAULT 'Disponivel',
  `criado_em` datetime DEFAULT current_timestamp(),
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `atualizado_em` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pacientes`
--

CREATE TABLE `pacientes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `nome` varchar(120) NOT NULL,
  `cpf` varchar(20) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `sexo` varchar(20) DEFAULT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `contato_emergencia` varchar(120) DEFAULT NULL,
  `telefone_emergencia` varchar(30) DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `consentimento_lgpd` tinyint(1) NOT NULL DEFAULT 0,
  `atualizado_em` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `prescricoes`
--

CREATE TABLE `prescricoes` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `profissional_id` int(11) NOT NULL,
  `consulta_id` int(11) DEFAULT NULL,
  `medicamento` varchar(150) NOT NULL,
  `dosagem` varchar(100) DEFAULT NULL,
  `orientacoes` text DEFAULT NULL,
  `data_emissao` datetime DEFAULT current_timestamp(),
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `atualizado_em` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `profissionais`
--

CREATE TABLE `profissionais` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `unidade_id` int(11) DEFAULT NULL,
  `nome` varchar(120) NOT NULL,
  `tipo` enum('Medico','Enfermeiro','Tecnico','Outro') NOT NULL,
  `especialidade` varchar(100) DEFAULT NULL,
  `registro_profissional` varchar(50) DEFAULT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `prontuarios`
--

CREATE TABLE `prontuarios` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `profissional_id` int(11) NOT NULL,
  `consulta_id` int(11) DEFAULT NULL,
  `descricao` text NOT NULL,
  `diagnostico` text DEFAULT NULL,
  `conduta` text DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `atualizado_em` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `suprimentos`
--

CREATE TABLE `suprimentos` (
  `id` int(11) NOT NULL,
  `unidade_id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `unidade_medida` varchar(30) NOT NULL DEFAULT 'Unidade',
  `estoque_atual` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estoque_minimo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `suprimentos_movimentacoes`
--

CREATE TABLE `suprimentos_movimentacoes` (
  `id` int(11) NOT NULL,
  `suprimento_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `tipo_movimentacao` varchar(20) NOT NULL,
  `quantidade` decimal(10,2) NOT NULL,
  `estoque_anterior` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estoque_posterior` decimal(10,2) NOT NULL DEFAULT 0.00,
  `observacao` text DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `unidades`
--

CREATE TABLE `unidades` (
  `id` int(11) NOT NULL,
  `nome` varchar(120) NOT NULL,
  `tipo` enum('Hospital','Clinica','Laboratorio','Home Care') NOT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `perfil` enum('admin','profissional','paciente','recepcao') NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `trocar_senha_primeiro_acesso` tinyint(1) NOT NULL DEFAULT 0,
  `ultimo_login` datetime DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Índices de tabelas apagadas
--

--
-- Índices de tabela `auditoria_logs`
--
ALTER TABLE `auditoria_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `consultas`
--
ALTER TABLE `consultas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `profissional_id` (`profissional_id`),
  ADD KEY `unidade_id` (`unidade_id`);

--
-- Índices de tabela `exames`
--
ALTER TABLE `exames`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `unidade_id` (`unidade_id`);

--
-- Índices de tabela `internacoes`
--
ALTER TABLE `internacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `leito_id` (`leito_id`);

--
-- Índices de tabela `leitos`
--
ALTER TABLE `leitos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unidade_id` (`unidade_id`);

--
-- Índices de tabela `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf` (`cpf`),
  ADD KEY `idx_pacientes_usuario_id` (`usuario_id`);

--
-- Índices de tabela `prescricoes`
--
ALTER TABLE `prescricoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `profissional_id` (`profissional_id`),
  ADD KEY `consulta_id` (`consulta_id`);

--
-- Índices de tabela `profissionais`
--
ALTER TABLE `profissionais`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `unidade_id` (`unidade_id`);

--
-- Índices de tabela `prontuarios`
--
ALTER TABLE `prontuarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `profissional_id` (`profissional_id`),
  ADD KEY `consulta_id` (`consulta_id`);

--
-- Índices de tabela `suprimentos`
--
ALTER TABLE `suprimentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_suprimentos_unidade_id` (`unidade_id`),
  ADD KEY `idx_suprimentos_nome` (`nome`),
  ADD KEY `idx_suprimentos_categoria` (`categoria`),
  ADD KEY `idx_suprimentos_ativo` (`ativo`);

--
-- Índices de tabela `suprimentos_movimentacoes`
--
ALTER TABLE `suprimentos_movimentacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mov_suprimento_id` (`suprimento_id`),
  ADD KEY `idx_mov_usuario_id` (`usuario_id`),
  ADD KEY `idx_mov_tipo` (`tipo_movimentacao`),
  ADD KEY `idx_mov_criado_em` (`criado_em`);

--
-- Índices de tabela `unidades`
--
ALTER TABLE `unidades`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de tabelas apagadas
--

--
-- AUTO_INCREMENT de tabela `auditoria_logs`
--
ALTER TABLE `auditoria_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `consultas`
--
ALTER TABLE `consultas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `exames`
--
ALTER TABLE `exames`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `internacoes`
--
ALTER TABLE `internacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `leitos`
--
ALTER TABLE `leitos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `prescricoes`
--
ALTER TABLE `prescricoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `profissionais`
--
ALTER TABLE `profissionais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `prontuarios`
--
ALTER TABLE `prontuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `suprimentos`
--
ALTER TABLE `suprimentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `suprimentos_movimentacoes`
--
ALTER TABLE `suprimentos_movimentacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `unidades`
--
ALTER TABLE `unidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
