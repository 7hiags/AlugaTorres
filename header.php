<?php

/**
 * ========================================
 * Header - Cabeçalho do Site
 * ========================================
 * Este arquivo contém o cabeçalho padrão do site AlugaTorres,
 * incluindo navegação, logo, menu mobile e área de autenticação.
 * 
 * @author AlugaTorres
 * @version 1.0
 */

// ============================================
// Configurações e Inicialização
// ============================================

// Base do projeto (XAMPP) - URL base do site
$BASE_URL = '/AlugaTorres/';

// ============================================
// Gerenciamento de Sessão
// ============================================

// Inicia sessão se ainda não existir
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// Inclusão de Helpers e Componentes
// ============================================

// Incluir helper de notificações para exibir mensagens toast
require_once __DIR__ . '/backend/notifications_helper.php';

// ============================================
// Controle de Refresh de Página
// ============================================

/**
 * Verificação para evitar loops de refresh
 * Adiciona um parâmetro 'refreshed' à URL para evitar recarregamentos infinitos
 * Este é um truque para garantir que a página seja carregada corretamente
 */
if (!isset($_GET['refreshed'])) {
    // Adiciona o parâmetro refreshed à URL
    $separator = strpos($_SERVER['REQUEST_URI'], '?') !== false ? '&' : '?';
    header("Location: " . $_SERVER['REQUEST_URI'] . $separator . 'refreshed=1');
    exit;  // Interrompe o resto do script após o redirecionamento
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <!-- ========================================
         Meta Tags e Configurações do Documento
         ======================================== -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AlugaTorres - Sua agência de viagens para destinos incríveis">

    <!-- ========================================
         Folhas de Estilo (CSS)
         ======================================== -->
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Estilos personalizados do site -->
    <link rel="stylesheet" href="<?= $BASE_URL ?>style/style.css">

    <!-- ========================================
         Scripts JavaScript
         ======================================== -->
    <!-- Sistema de Notificações Toast -->
    <script src="<?= $BASE_URL ?>js/notifications.js"></script>

</head>

<!-- ========================================
     Corpo da Página
     ======================================== -->

<body>

    <!-- ========================================
         Cabeçalho Principal
         ======================================== -->
    <header>
        <div class="header-container">

            <!-- ========================================
                 Logo do Site
                 ======================================== -->
            <img
                src="<?= $BASE_URL ?>style/img/Logo_AlugaTorres_branco.png"
                alt="AlugaTorres Logo"
                class="logo"
                width="60">

            <!-- Título do Site -->
            <h1>AlugaTorres</h1>

            <!-- ========================================
                 Menu Mobile (Hamburger)
                 ======================================== -->
            <button class="hamburger" aria-label="Menu">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>

            <!-- ========================================
                 Navegação Principal
                 ======================================== -->
            <nav class="main-nav" id="main-nav">
                <a href="<?= $BASE_URL ?>index.php">Inicio</a>
                <a href="<?= $BASE_URL ?>pesquisa.php">Pesquisa</a>
                <a href="<?= $BASE_URL ?>dashboard.php">Dashboard</a>
                <a href="<?= $BASE_URL ?>sobretorres.php">Sobre Torres</a>
                <a href="<?= $BASE_URL ?>contactos.php">Contactos</a>
            </nav>

            <!-- ========================================
                 Área de Autenticação
                 ======================================== -->
            <div class="auth-section">
                <?php if (isset($_SESSION['user'])): ?>
                    <!-- Utilizador autenticado: mostra botão de perfil -->
                    <div class="user-info">
                        <button class="profile-button" id="profile-toggle">
                            <i class="fas fa-user-circle"></i>
                            <span class="profile-name">
                                <?= htmlspecialchars($_SESSION['user']) ?>
                            </span>
                        </button>
                    </div>
                <?php else: ?>
                    <!-- Visitante: mostra botão de login -->
                    <a class="auth-button login" href="<?= $BASE_URL ?>backend/login.php">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- ========================================
         Sistema de Notificações
         ======================================== -->
    <!-- Renderiza notificações pendentes da sessão (mensagens toast) -->
    <?= renderPendingNotifications() ?>