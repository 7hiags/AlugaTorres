-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 17, 2026 at 03:49 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dbalugatorres`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `acao` varchar(100) NOT NULL,
  `detalhes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `avaliacoes`
--

CREATE TABLE `avaliacoes` (
  `id` int(11) NOT NULL,
  `casa_id` int(11) NOT NULL,
  `reserva_id` int(11) NOT NULL,
  `arrendatario_id` int(11) NOT NULL,
  `classificacao` tinyint(4) NOT NULL CHECK (`classificacao` >= 1 and `classificacao` <= 5),
  `comentario` text DEFAULT NULL,
  `limpeza` tinyint(4) DEFAULT NULL CHECK (`limpeza` >= 1 and `limpeza` <= 5),
  `localizacao` tinyint(4) DEFAULT NULL CHECK (`localizacao` >= 1 and `localizacao` <= 5),
  `comunicacao` tinyint(4) DEFAULT NULL CHECK (`comunicacao` >= 1 and `comunicacao` <= 5),
  `data_avaliacao` datetime DEFAULT current_timestamp(),
  `resposta_proprietario` text DEFAULT NULL,
  `data_resposta` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bloqueios`
--

CREATE TABLE `bloqueios` (
  `id` int(11) NOT NULL,
  `casa_id` int(11) NOT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `criado_por` int(11) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `calendario_disponibilidade`
--

CREATE TABLE `calendario_disponibilidade` (
  `id` int(11) NOT NULL,
  `casa_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `disponivel` tinyint(1) DEFAULT 1,
  `preco_especial` decimal(10,2) DEFAULT NULL,
  `reserva_id` int(11) DEFAULT NULL,
  `bloqueio_proprietario` tinyint(1) DEFAULT 0,
  `notas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `calendario_disponibilidade`
--

INSERT INTO `calendario_disponibilidade` (`id`, `casa_id`, `data`, `disponivel`, `preco_especial`, `reserva_id`, `bloqueio_proprietario`, `notas`) VALUES
(1, 3, '2026-02-04', 0, NULL, NULL, 1, NULL),
(2, 3, '2026-01-31', 0, NULL, NULL, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `casas`
--

CREATE TABLE `casas` (
  `id` int(11) NOT NULL,
  `proprietario_id` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descricao` text DEFAULT NULL,
  `morada` text NOT NULL,
  `codigo_postal` varchar(20) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT 'Torres Novas',
  `freguesia` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `tipo_propriedade` enum('casa','apartamento','vivenda','quinta','outro') DEFAULT 'casa',
  `quartos` tinyint(4) DEFAULT 1,
  `camas` tinyint(4) DEFAULT 1,
  `banheiros` tinyint(4) DEFAULT 1,
  `area` int(11) DEFAULT NULL,
  `capacidade` int(11) DEFAULT 2,
  `preco_noite` decimal(10,2) NOT NULL,
  `preco_limpeza` decimal(10,2) DEFAULT 0.00,
  `taxa_seguranca` decimal(10,2) DEFAULT 0.00,
  `minimo_noites` tinyint(4) DEFAULT 1,
  `maximo_noites` smallint(6) DEFAULT 30,
  `hora_checkin` time DEFAULT '15:00:00',
  `hora_checkout` time DEFAULT '11:00:00',
  `comodidades` text DEFAULT NULL,
  `regras` text DEFAULT NULL,
  `destaque` tinyint(1) DEFAULT 0,
  `disponivel` tinyint(1) DEFAULT 1,
  `data_criacao` datetime DEFAULT current_timestamp(),
  `data_atualizacao` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `fotos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array JSON com caminhos das fotos da propriedade (máx. 7)' CHECK (json_valid(`fotos`)),
  `aprovado` tinyint(1) DEFAULT 0 COMMENT '0=Pendente, 1=Aprovada, 2=Rejeitada',
  `motivo_rejeicao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `casas`
--

INSERT INTO `casas` (`id`, `proprietario_id`, `titulo`, `descricao`, `morada`, `codigo_postal`, `cidade`, `freguesia`, `latitude`, `longitude`, `tipo_propriedade`, `quartos`, `camas`, `banheiros`, `area`, `capacidade`, `preco_noite`, `preco_limpeza`, `taxa_seguranca`, `minimo_noites`, `maximo_noites`, `hora_checkin`, `hora_checkout`, `comodidades`, `regras`, `destaque`, `disponivel`, `data_criacao`, `data_atualizacao`, `fotos`, `aprovado`, `motivo_rejeicao`) VALUES
(3, 7, 'Casa centro de torres novas', 'Casa 2 lugares', 'St. Rua Principal 11, Casais Martânes - Torres Novas', '2350223', 'Torres Novas', 'pedrogao', NULL, NULL, 'casa', 1, 1, 1, NULL, 2, 12.00, 0.00, 0.00, 5, 10, '15:00:00', '15:00:00', '[]', '', 0, 1, '2026-01-28 20:23:38', '2026-01-28 20:26:33', NULL, 0, NULL),
(4, 7, 'Alecrim', 'Apartamento T1, Perto do rio Almonda', 'Rua Principal, 23', '2350223', 'Torres Novas', 'assentiz', NULL, NULL, 'apartamento', 2, 2, 2, NULL, 3, 20.00, 10.00, 5.00, 1, 3, '15:00:00', '11:00:00', '[\"wifi\",\"cozinha\",\"secador\"]', '', 0, 1, '2026-02-11 15:18:05', '2026-02-11 22:29:36', NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `configuracoes_usuario`
--

CREATE TABLE `configuracoes_usuario` (
  `id` int(11) NOT NULL,
  `utilizador_id` int(11) NOT NULL,
  `notificacoes_email` tinyint(1) DEFAULT 1,
  `notificacoes_sms` tinyint(1) DEFAULT 0,
  `idioma` varchar(5) DEFAULT 'pt',
  `moeda` varchar(3) DEFAULT 'EUR',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `configuracoes_usuario`
--

INSERT INTO `configuracoes_usuario` (`id`, `utilizador_id`, `notificacoes_email`, `notificacoes_sms`, `idioma`, `moeda`, `criado_em`, `atualizado_em`) VALUES
(1, 7, 1, 0, 'pt', '0', '2026-01-28 20:24:19', '2026-01-28 20:24:19'),
(2, 10, 1, 0, 'pt', '0', '2026-02-13 11:44:04', '2026-02-13 11:44:04');

-- --------------------------------------------------------

--
-- Table structure for table `mensagens`
--

CREATE TABLE `mensagens` (
  `id` int(11) NOT NULL,
  `reserva_id` int(11) NOT NULL,
  `remetente_id` int(11) NOT NULL,
  `mensagem` text NOT NULL,
  `data_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  `lida` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mensagens_contactos`
--

CREATE TABLE `mensagens_contactos` (
  `id` int(11) NOT NULL,
  `utilizador_id` int(11) DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `assunto` varchar(200) NOT NULL,
  `mensagem` text NOT NULL,
  `tipo_mensagem` enum('geral','suporte','reserva','propriedade') DEFAULT 'geral',
  `lida` tinyint(1) DEFAULT 0,
  `data_envio` datetime DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `resposta` text DEFAULT NULL,
  `data_resposta` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mensagens_contactos`
--

INSERT INTO `mensagens_contactos` (`id`, `utilizador_id`, `nome`, `email`, `assunto`, `mensagem`, `tipo_mensagem`, `lida`, `data_envio`, `ip_address`, `user_agent`, `resposta`, `data_resposta`) VALUES
(1, NULL, 'Thiago', 'thiagosilvauha@gmail.com', 'casd', 'aDQd', 'geral', 0, '2025-12-30 14:15:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, NULL),
(2, 7, 'Thirrasgo', 'thiagosilvauha@gmail.com', 'aacvf', 'cvsvwg', 'geral', 0, '2026-02-06 21:12:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reservas`
--

CREATE TABLE `reservas` (
  `id` int(11) NOT NULL,
  `casa_id` int(11) NOT NULL,
  `arrendatario_id` int(11) NOT NULL,
  `data_checkin` date NOT NULL,
  `data_checkout` date NOT NULL,
  `noites` int(11) NOT NULL,
  `total_hospedes` int(11) NOT NULL,
  `preco_noite` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `taxa_limpeza` decimal(10,2) DEFAULT 0.00,
  `taxa_seguranca` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `status` enum('pendente','confirmada','cancelada','concluida','rejeitada') DEFAULT 'pendente',
  `metodo_pagamento` varchar(50) DEFAULT NULL,
  `data_reserva` datetime DEFAULT current_timestamp(),
  `data_confirmacao` datetime DEFAULT NULL,
  `data_cancelamento` datetime DEFAULT NULL,
  `notas` text DEFAULT NULL
) ;

--
-- Dumping data for table `reservas`
--

INSERT INTO `reservas` (`id`, `casa_id`, `arrendatario_id`, `data_checkin`, `data_checkout`, `noites`, `total_hospedes`, `preco_noite`, `subtotal`, `taxa_limpeza`, `taxa_seguranca`, `total`, `status`, `metodo_pagamento`, `data_reserva`, `data_confirmacao`, `data_cancelamento`, `notas`) VALUES
(11, 4, 9, '2026-02-12', '2026-02-19', 7, 2, 20.00, 140.00, 10.00, 5.00, 155.00, 'cancelada', NULL, '2026-02-12 22:31:04', NULL, '2026-02-13 11:36:40', NULL),
(12, 4, 10, '2026-02-17', '2026-02-28', 11, 2, 20.00, 220.00, 10.00, 5.00, 235.00, 'confirmada', NULL, '2026-02-13 11:42:59', NULL, NULL, NULL),
(13, 4, 9, '2026-02-28', '2026-03-03', 3, 5, 20.00, 60.00, 10.00, 5.00, 75.00, 'confirmada', NULL, '2026-02-16 14:27:21', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `utilizadores`
--

CREATE TABLE `utilizadores` (
  `id` int(11) NOT NULL,
  `utilizador` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `palavrapasse_hash` varchar(255) NOT NULL,
  `tipo_utilizador` enum('proprietario','arrendatario','admin') DEFAULT 'arrendatario',
  `telefone` varchar(20) DEFAULT NULL,
  `morada` text DEFAULT NULL,
  `nif` varchar(20) DEFAULT NULL,
  `data_registro` datetime DEFAULT current_timestamp(),
  `ultimo_login` datetime DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `foto_perfil` varchar(255) DEFAULT NULL COMMENT 'Caminho da foto de perfil do utilizador'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `utilizadores`
--

INSERT INTO `utilizadores` (`id`, `utilizador`, `email`, `palavrapasse_hash`, `tipo_utilizador`, `telefone`, `morada`, `nif`, `data_registro`, `ultimo_login`, `ativo`, `foto_perfil`) VALUES
(1, 'Administrador', 'admin@alugatorres.pt', '$2b$12$2cH4nEXKLEADhx4/LF9qdeic4r491QM.m00N/Jn1s3CKqLyW7DZke', 'admin', NULL, NULL, NULL, '2025-12-12 13:52:02', NULL, 1, NULL),
(7, 'Thiago da Silva', 'thiagosilvauha@gmail.com', '$2y$10$w1AJ4MTXGudxo77uuPe0BuFs3RKsnO.bEi/vhgVWNsFjsNVq1pmE6', 'proprietario', '+351929326577', 'St. Rua Principal 11, Casais Martânes - Torres Novas', '', '2026-01-03 15:37:24', NULL, 1, NULL),
(9, 'Thiago da Silva', 'thiagosilvauh@gmail.com', '$2y$10$S1SdpoMX5kWWr9ZdBZADSeV8z8dFrh4rDyXqvSo2oq3xoKHEDLNPC', 'arrendatario', '+351929326577', NULL, '', '2026-01-11 13:22:10', NULL, 1, 'uploads/fotos_perfil/perfil_9_699476ecce916_1771337452.jpg'),
(10, 'Ricardo', 'ricardo@gmail.com', '$2y$10$BUykupTcbGYjlbHIcoVDwud9vCnnViS5OW/mrLVVVH6wnKas5NOa.', 'arrendatario', '912 912 129', NULL, '', '2026-02-13 11:42:14', NULL, 1, NULL),
(11, 'Thiago da Silva', 'thiago@gmail.com', '$2y$10$LGMsbSwP03.Ez1VYdxyPa.wcbW2l6fq019FMt87CQgHDGSePDN4Km', 'arrendatario', '+351929326577', NULL, '', '2026-02-15 18:03:10', NULL, 1, NULL),
(12, 'ts', 't@gmail.com', '$2y$10$uUftt5rT4MbpQbqq7f0lk.JmEiowwpFbSVf9a8fPl/Y7W1gs4bf36', 'proprietario', '+351929326577', NULL, 'fder3vsvwbg', '2026-02-15 20:48:41', NULL, 1, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_logs_admin_id` (`admin_id`),
  ADD KEY `idx_admin_logs_created_at` (`created_at`);

--
-- Indexes for table `avaliacoes`
--
ALTER TABLE `avaliacoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reserva_id` (`reserva_id`),
  ADD KEY `idx_casa` (`casa_id`),
  ADD KEY `idx_arrendatario` (`arrendatario_id`);

--
-- Indexes for table `bloqueios`
--
ALTER TABLE `bloqueios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `casa_id` (`casa_id`),
  ADD KEY `criado_por` (`criado_por`);

--
-- Indexes for table `calendario_disponibilidade`
--
ALTER TABLE `calendario_disponibilidade`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_casa_data` (`casa_id`,`data`),
  ADD KEY `reserva_id` (`reserva_id`),
  ADD KEY `idx_data` (`data`),
  ADD KEY `idx_disponivel` (`disponivel`);

--
-- Indexes for table `casas`
--
ALTER TABLE `casas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_proprietario` (`proprietario_id`),
  ADD KEY `idx_cidade` (`cidade`),
  ADD KEY `idx_preco` (`preco_noite`),
  ADD KEY `idx_disponivel` (`disponivel`),
  ADD KEY `idx_casas_aprovado` (`aprovado`);

--
-- Indexes for table `configuracoes_usuario`
--
ALTER TABLE `configuracoes_usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `utilizador_id` (`utilizador_id`);

--
-- Indexes for table `mensagens`
--
ALTER TABLE `mensagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reserva_id` (`reserva_id`),
  ADD KEY `remetente_id` (`remetente_id`);

--
-- Indexes for table `mensagens_contactos`
--
ALTER TABLE `mensagens_contactos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilizador_id` (`utilizador_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_lida` (`lida`),
  ADD KEY `idx_data_envio` (`data_envio`);

--
-- Indexes for table `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_casa` (`casa_id`),
  ADD KEY `idx_arrendatario` (`arrendatario_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_datas` (`data_checkin`,`data_checkout`),
  ADD KEY `idx_reservas_casa` (`casa_id`),
  ADD KEY `idx_reservas_arrendatario` (`arrendatario_id`);

--
-- Indexes for table `utilizadores`
--
ALTER TABLE `utilizadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_tipo` (`tipo_utilizador`),
  ADD KEY `idx_utilizadores_ativo` (`ativo`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `avaliacoes`
--
ALTER TABLE `avaliacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bloqueios`
--
ALTER TABLE `bloqueios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `calendario_disponibilidade`
--
ALTER TABLE `calendario_disponibilidade`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `casas`
--
ALTER TABLE `casas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `configuracoes_usuario`
--
ALTER TABLE `configuracoes_usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `mensagens`
--
ALTER TABLE `mensagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mensagens_contactos`
--
ALTER TABLE `mensagens_contactos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `utilizadores`
--
ALTER TABLE `utilizadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `avaliacoes`
--
ALTER TABLE `avaliacoes`
  ADD CONSTRAINT `avaliacoes_ibfk_1` FOREIGN KEY (`casa_id`) REFERENCES `casas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `avaliacoes_ibfk_2` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `avaliacoes_ibfk_3` FOREIGN KEY (`arrendatario_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bloqueios`
--
ALTER TABLE `bloqueios`
  ADD CONSTRAINT `bloqueios_ibfk_1` FOREIGN KEY (`casa_id`) REFERENCES `casas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bloqueios_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `calendario_disponibilidade`
--
ALTER TABLE `calendario_disponibilidade`
  ADD CONSTRAINT `calendario_disponibilidade_ibfk_1` FOREIGN KEY (`casa_id`) REFERENCES `casas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `calendario_disponibilidade_ibfk_2` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `casas`
--
ALTER TABLE `casas`
  ADD CONSTRAINT `casas_ibfk_1` FOREIGN KEY (`proprietario_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `configuracoes_usuario`
--
ALTER TABLE `configuracoes_usuario`
  ADD CONSTRAINT `configuracoes_usuario_ibfk_1` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mensagens`
--
ALTER TABLE `mensagens`
  ADD CONSTRAINT `mensagens_ibfk_1` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mensagens_ibfk_2` FOREIGN KEY (`remetente_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mensagens_contactos`
--
ALTER TABLE `mensagens_contactos`
  ADD CONSTRAINT `mensagens_contactos_ibfk_1` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `reservas_ibfk_1` FOREIGN KEY (`casa_id`) REFERENCES `casas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservas_ibfk_2` FOREIGN KEY (`arrendatario_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
