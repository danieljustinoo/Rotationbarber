-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 21-Mar-2025 às 23:40
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `rotation_barber`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `agendamentos`
--

CREATE TABLE `agendamentos` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `barbeiro_id` int(11) NOT NULL,
  `servico` varchar(100) NOT NULL,
  `data` date NOT NULL,
  `horario` time NOT NULL,
  `pagamento` varchar(50) NOT NULL DEFAULT 'local',
  `estado` enum('pendente','confirmado','cancelado','concluído') NOT NULL DEFAULT 'pendente',
  `preco` decimal(10,2) DEFAULT NULL,
  `payment_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `agendamentos`
--

INSERT INTO `agendamentos` (`id`, `cliente_id`, `barbeiro_id`, `servico`, `data`, `horario`, `pagamento`, `estado`, `preco`, `payment_id`) VALUES
(66, 33, 2, 'corte-navalha', '2025-03-21', '14:00:00', 'local', 'confirmado', 12.00, NULL),
(67, 33, 3, 'massagem-relaxante', '2025-03-25', '09:00:00', 'online-paypal', 'confirmado', 20.00, '8WU35395X64300333'),
(68, 33, 4, 'coloracao-lavagem', '2025-03-25', '14:00:00', 'online-paypal', 'confirmado', 20.00, '58C56275C64529534'),
(69, 33, 3, 'sobrancelha', '2025-03-24', '14:30:00', 'local', 'confirmado', 3.00, NULL),
(70, 34, 2, 'sobrancelha', '2025-03-25', '11:30:00', 'local', 'cancelado', 3.00, NULL),
(71, 35, 3, 'massagem-relaxante', '2025-03-26', '14:00:00', 'online-paypal', 'cancelado', 20.00, '3HN50323US089580P'),
(72, 33, 3, 'coloracao-lavagem', '2025-03-20', '14:00:00', 'online-paypal', 'pendente', NULL, NULL),
(73, 35, 3, 'massagem-relaxante', '2025-03-24', '14:00:00', 'online-paypal', 'confirmado', 20.00, '5AU61062YK9200715');

-- --------------------------------------------------------

--
-- Estrutura da tabela `barber_unavailability`
--

CREATE TABLE `barber_unavailability` (
  `id` int(11) NOT NULL,
  `barbeiro_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fim` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `barber_unavailability`
--

INSERT INTO `barber_unavailability` (`id`, `barbeiro_id`, `data`, `hora_inicio`, `hora_fim`) VALUES
(5, 2, '2025-03-19', '00:00:00', '23:59:59'),
(7, 3, '2025-03-26', '00:00:00', '23:59:59');

-- --------------------------------------------------------

--
-- Estrutura da tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `categorias`
--

INSERT INTO `categorias` (`id`, `nome`) VALUES
(1, 'Corte de Cabelo'),
(2, 'Barba'),
(3, 'Tratamento'),
(4, 'cortes'),
(5, 'tratamento-corporal'),
(6, 'lavagem-facial'),
(7, 'barba'),
(8, 'spa-de-beleza');

-- --------------------------------------------------------

--
-- Estrutura da tabela `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(1, 'danielmacavete@gmail.com', '15dbe9b0722814ff54a49d9d0ee5e5f0411cfb04794b163d5de96fb52b69a99601295e7cbb0b3c3ca804c06c08e2c10e8ec1', '2025-03-09 00:04:16', '2025-03-08 22:04:16'),
(2, 'danielmacavete@gmail.com', '05bf775e73fd3dbb2937721e5728267dc2cc6e380e890bb8e7133b2c05c8a0b183bd478a1f93ae0177bb39b4c902752cd634', '2025-03-09 00:04:16', '2025-03-08 22:04:16'),
(17, 'danielmacavete@gmail.com', '227be880cfee7be3528ad204c401181cf68844fb1040909aead048a4cdef98ef30649c83f53d4c6a12ced6df78fcacde3842', '2025-03-13 20:45:17', '2025-03-13 18:45:17'),
(18, 'danielmacavete@gmail.com', 'fb246f78fa32e9cc2a4ee8a9340be5f7683460d2af484405335a28e46eea4dc122d878f2feedc0f96fc1148e1da7538e0340', '2025-03-13 20:49:21', '2025-03-13 18:49:21');

-- --------------------------------------------------------

--
-- Estrutura da tabela `servicos`
--

CREATE TABLE `servicos` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `duracao` int(11) NOT NULL DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `servicos`
--

INSERT INTO `servicos` (`id`, `nome`, `preco`, `categoria`, `categoria_id`, `duracao`) VALUES
(15, 'corte-simples', 10.00, '', 1, 30),
(16, 'corte-criativo', 12.00, '', 1, 30),
(17, 'buzz-cut', 9.00, '', 1, 30),
(18, 'corte-navalha', 12.00, '', 1, 30),
(19, 'corte-freestyle', 13.00, '', 1, 30),
(20, 'sobrancelha', 3.00, '', 1, 30),
(21, 'massagem-relaxante', 20.00, '', 2, 30),
(22, 'coloracao-lavagem', 20.00, '', 3, 30),
(23, 'mascara-preta', 35.00, '', 3, 30),
(24, 'barbear-lamina', 7.00, '', 4, 30),
(25, 'barbear-maquina-tesoura', 8.00, '', 4, 30),
(26, 'barbear-criativo', 7.00, '', 4, 30),
(27, 'beleza-spa', 40.00, '', 5, 30);

-- --------------------------------------------------------

--
-- Estrutura da tabela `subscribers`
--

CREATE TABLE `subscribers` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `subscribers`
--

INSERT INTO `subscribers` (`id`, `email`, `subscribed_at`) VALUES
(8, 'danielmfut@gmail.com', '2025-03-21 12:00:57'),
(9, 'danielmacavete@gmail.com', '2025-03-21 12:14:20'),
(10, 'justinomacave@gmail.com', '2025-03-21 21:09:57');

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `role` enum('cliente','barbeiro','admin') DEFAULT 'cliente',
  `imagem` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `telefone`, `role`, `imagem`) VALUES
(2, 'João', 'joao@rotationbarber.com', '$2y$10$Xnj4qY6Z00W1dTLz7YwIluzweRNLdCPNwvzXyYYvqe7DgAt9Cv0xK', '987654321', 'barbeiro', 'Midia/Modelo.png'),
(3, 'Pedro', 'pedro@rotationbarber.com', '$2y$10$Xnj4qY6Z00W1dTLz7YwIluzweRNLdCPNwvzXyYYvqe7DgAt9Cv0xK', '456789123', 'barbeiro', 'Midia/Modelo2.png'),
(4, 'Carlos', 'carlos@rotationbarber.com', '$2y$10$Xnj4qY6Z00W1dTLz7YwIluzweRNLdCPNwvzXyYYvqe7DgAt9Cv0xK', '321654987', 'barbeiro', 'Midia/Modelo3.png'),
(5, 'Admin Rotation', 'admin@rotationbarber.com', '$2y$10$Xnj4qY6Z00W1dTLz7YwIluzweRNLdCPNwvzXyYYvqe7DgAt9Cv0xK', NULL, 'admin', NULL),
(33, 'Tomas silva', 'tomasilva@gmail.com', '$2y$10$Ry1gVg8cBJGIXKuCN/MmY.RoUIZKoRlcemGj/CVjHZkGG7MHMDH9y', '932156894', 'cliente', NULL),
(34, 'Hugo', '27123@aerdl.eu', '$2y$10$ZpWuYRWQJLXLUf/90zH4j.VoGoRMHk/iFnlU79/qXJ1W.rr4gD97e', '932567345', 'cliente', NULL),
(35, 'Kevin', 'danielmacavete@gmail.com', '$2y$10$3ZCzDXjqE3pN146gEvBnxOZzJEbVK07ult0.aQjBmdL25S5m3LnC2', '953764847', 'cliente', NULL);

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `agendamentos`
--
ALTER TABLE `agendamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `barbeiro_id` (`barbeiro_id`);

--
-- Índices para tabela `barber_unavailability`
--
ALTER TABLE `barber_unavailability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `barbeiro_id` (`barbeiro_id`);

--
-- Índices para tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_token` (`token`);

--
-- Índices para tabela `servicos`
--
ALTER TABLE `servicos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_categoria` (`categoria_id`);

--
-- Índices para tabela `subscribers`
--
ALTER TABLE `subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices para tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `agendamentos`
--
ALTER TABLE `agendamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT de tabela `barber_unavailability`
--
ALTER TABLE `barber_unavailability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `servicos`
--
ALTER TABLE `servicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de tabela `subscribers`
--
ALTER TABLE `subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `agendamentos`
--
ALTER TABLE `agendamentos`
  ADD CONSTRAINT `agendamentos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `agendamentos_ibfk_2` FOREIGN KEY (`barbeiro_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `agendamentos_ibfk_3` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `agendamentos_ibfk_4` FOREIGN KEY (`barbeiro_id`) REFERENCES `usuarios` (`id`);

--
-- Limitadores para a tabela `barber_unavailability`
--
ALTER TABLE `barber_unavailability`
  ADD CONSTRAINT `barber_unavailability_ibfk_1` FOREIGN KEY (`barbeiro_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `servicos`
--
ALTER TABLE `servicos`
  ADD CONSTRAINT `fk_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
