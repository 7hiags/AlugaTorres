-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 23, 2026 at 01:14 PM
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

CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `acao` varchar(255) NOT NULL,
  `detalhes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `acao`, `detalhes`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'Acesso ao Dashboard', 'Visualização do painel administrativo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-23 12:26:58'),
(2, 1, 'Acesso à Gestão de Casas', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-23 12:27:05'),
(3, 1, 'Acesso à Gestão de Casas', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-23 12:32:05'),
(4, 1, 'Aprovar Casa', 'ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-23 12:32:08'),
(5, 1, 'Acesso à Gestão de Casas', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-23 12:32:08'),
(6, 1, 'Acesso ao Dashboard', 'Visualização do painel administrativo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-23 13:05:49'),
(7, 1, 'Acesso à Gestão de Utilizadores', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-23 13:05:54'),
(8, 1, 'Eliminar Utilizador', 'ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-23 13:05:59'),
(9, 1, 'Acesso à Gestão de Utilizadores', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-23 13:05:59'),
(10, 1, 'Eliminar Utilizador', 'ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-23 13:06:01'),
(11, 1, 'Acesso à Gestão de Utilizadores', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-23 13:06:01');

-- --------------------------------------------------------

--
-- Table structure for table `avaliacoes`
--

CREATE TABLE IF NOT EXISTS `avaliacoes` (
  `id` int(11) NOT NULL,
  `casa_id` int(11) NOT NULL,
  `arrendatario_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comentario` text DEFAULT NULL,
  `resposta` text DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `resposta_data` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bloqueios`
--

CREATE TABLE IF NOT EXISTS `bloqueios` (
  `id` int(11) NOT NULL,
  `casa_id` int(11) NOT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `criado_por` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `casas`
--

CREATE TABLE IF NOT EXISTS `casas` (
  `id` int(11) NOT NULL,
  `proprietario_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `morada` varchar(255) NOT NULL,
  `codigo_postal` varchar(10) NOT NULL,
  `cidade` varchar(100) NOT NULL,
  `freguesia` varchar(100) DEFAULT NULL,
  `tipo_propriedade` varchar(50) NOT NULL,
  `custom_tipo` varchar(100) DEFAULT NULL,
  `quartos` int(11) NOT NULL,
  `camas` int(11) NOT NULL,
  `casas_de_banho` int(11) NOT NULL,
  `area` int(11) DEFAULT NULL,
  `capacidade` int(11) NOT NULL,
  `preco_noite` decimal(10,2) NOT NULL,
  `preco_limpeza` decimal(10,2) NOT NULL DEFAULT 0.00,
  `taxa_seguranca` decimal(10,2) NOT NULL DEFAULT 0.00,
  `minimo_noites` int(11) NOT NULL DEFAULT 1,
  `maximo_noites` int(11) DEFAULT 30,
  `hora_checkin` time NOT NULL DEFAULT '15:00:00',
  `hora_checkout` time NOT NULL DEFAULT '11:00:00',
  `comodidades` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`comodidades`)),
  `regras` text DEFAULT NULL,
  `fotos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`fotos`)),
  `disponivel` tinyint(1) NOT NULL DEFAULT 1,
  `destaque` tinyint(1) NOT NULL DEFAULT 0,
  `aprovado` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=pendente,1=aprovada,2=rejeitada',
  `motivo_rejeicao` text DEFAULT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `localizacao` varchar(255) DEFAULT NULL,
  `media_avaliacao` decimal(3,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `definicoes_utilizador`
--

CREATE TABLE IF NOT EXISTS `definicoes_utilizador` (
  `id` int(11) NOT NULL,
  `utilizador_id` int(11) NOT NULL,
  `lingua` varchar(20) NOT NULL DEFAULT 'portuguese',
  `email_notificacoes` tinyint(1) NOT NULL DEFAULT 1,
  `sms_notificacoes` tinyint(1) NOT NULL DEFAULT 0,
  `promocoes` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mensagens_contactos`
--

CREATE TABLE IF NOT EXISTS `mensagens_contactos` (
  `id` int(11) NOT NULL,
  `utilizador_id` int(11) DEFAULT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `assunto` varchar(255) NOT NULL,
  `mensagem` text NOT NULL,
  `data_envio` datetime NOT NULL DEFAULT current_timestamp(),
  `lida` tinyint(1) NOT NULL DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `data_subscricao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `code` varchar(6) DEFAULT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `precos_especiais`
--

CREATE TABLE IF NOT EXISTS `precos_especiais` (
  `id` int(11) NOT NULL,
  `casa_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `preco` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservas`
--

CREATE TABLE IF NOT EXISTS `reservas` (
  `id` int(11) NOT NULL,
  `casa_id` int(11) NOT NULL,
  `arrendatario_id` int(11) NOT NULL,
  `data_checkin` date NOT NULL,
  `data_checkout` date NOT NULL,
  `data_reserva` datetime NOT NULL DEFAULT current_timestamp(),
  `noites` int(11) NOT NULL,
  `total_hospedes` int(11) NOT NULL,
  `preco_noite` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `taxa_limpeza` decimal(10,2) NOT NULL DEFAULT 0.00,
  `taxa_seguranca` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `status` enum('pendente','confirmada','concluida','cancelada','rejeitada') NOT NULL DEFAULT 'pendente',
  `data_cancelamento` datetime DEFAULT NULL,
  `data_confirmacao` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `utilizadores`
--

CREATE TABLE IF NOT EXISTS `utilizadores` (
  `id` int(11) NOT NULL,
  `utilizador` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `palavrapasse_hash` varchar(255) NOT NULL,
  `tipo_utilizador` enum('proprietario','arrendatario','admin') NOT NULL DEFAULT 'arrendatario',
  `data_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `telefone` varchar(20) DEFAULT NULL,
  `nif` varchar(9) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `utilizadores`
--

INSERT INTO `utilizadores` (`id`, `utilizador`, `email`, `palavrapasse_hash`, `tipo_utilizador`, `data_registro`, `ativo`, `telefone`, `nif`) VALUES
(1, 'Administrador', 'admin@alugatorres.pt', '$2y$10$ltxNtUDumVoeGnUnidAfQuGKCM9iwvY75i.7MB61BuEtnEnQBp0SG', 'admin', '2026-03-23 11:07:43', 1, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `avaliacoes`
--
ALTER TABLE `avaliacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `casa_id` (`casa_id`),
  ADD KEY `arrendatario_id` (`arrendatario_id`);

--
-- Indexes for table `bloqueios`
--
ALTER TABLE `bloqueios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `casa_id` (`casa_id`),
  ADD KEY `criado_por` (`criado_por`);

--
-- Indexes for table `casas`
--
ALTER TABLE `casas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proprietario_id` (`proprietario_id`);

--
-- Indexes for table `definicoes_utilizador`
--
ALTER TABLE `definicoes_utilizador`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `utilizador_id` (`utilizador_id`);

--
-- Indexes for table `mensagens_contactos`
--
ALTER TABLE `mensagens_contactos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilizador_id` (`utilizador_id`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `email_idx` (`email`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `token_idx` (`token`);

--
-- Indexes for table `precos_especiais`
--
ALTER TABLE `precos_especiais`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `casa_data_unique` (`casa_id`,`data`),
  ADD KEY `casa_id` (`casa_id`);

--
-- Indexes for table `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `casa_id` (`casa_id`),
  ADD KEY `arrendatario_id` (`arrendatario_id`);

--
-- Indexes for table `utilizadores`
--
ALTER TABLE `utilizadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `email_idx` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `avaliacoes`
--
ALTER TABLE `avaliacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bloqueios`
--
ALTER TABLE `bloqueios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `casas`
--
ALTER TABLE `casas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `definicoes_utilizador`
--
ALTER TABLE `definicoes_utilizador`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `mensagens_contactos`
--
ALTER TABLE `mensagens_contactos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `precos_especiais`
--
ALTER TABLE `precos_especiais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `utilizadores`
--
ALTER TABLE `utilizadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_admin_id_fk` FOREIGN KEY (`admin_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `avaliacoes`
--
ALTER TABLE `avaliacoes`
  ADD CONSTRAINT `avaliacoes_arrendatario_id_fk` FOREIGN KEY (`arrendatario_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `avaliacoes_casa_id_fk` FOREIGN KEY (`casa_id`) REFERENCES `casas` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bloqueios`
--
ALTER TABLE `bloqueios`
  ADD CONSTRAINT `bloqueios_casa_id_fk` FOREIGN KEY (`casa_id`) REFERENCES `casas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bloqueios_criado_por_fk` FOREIGN KEY (`criado_por`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `casas`
--
ALTER TABLE `casas`
  ADD CONSTRAINT `casas_proprietario_id_fk` FOREIGN KEY (`proprietario_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `definicoes_utilizador`
--
ALTER TABLE `definicoes_utilizador`
  ADD CONSTRAINT `definicoes_utilizador_id_fk` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mensagens_contactos`
--
ALTER TABLE `mensagens_contactos`
  ADD CONSTRAINT `mensagens_contactos_utilizador_id_fk` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `precos_especiais`
--
ALTER TABLE `precos_especiais`
  ADD CONSTRAINT `precos_especiais_casa_id_fk` FOREIGN KEY (`casa_id`) REFERENCES `casas` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `reservas_arrendatario_id_fk` FOREIGN KEY (`arrendatario_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservas_casa_id_fk` FOREIGN KEY (`casa_id`) REFERENCES `casas` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
