<?php
// Base do projeto (XAMPP)
$BASE_URL = '/AlugaTorres/';


// inicia sessão se ainda não existir
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir helper de notificações
require_once __DIR__ . '/backend/notifications_helper.php';

if (!isset($_GET['refreshed'])) {
    // Adiciona o parâmetro refreshed à URL
    $separator = strpos($_SERVER['REQUEST_URI'], '?') !== false ? '&' : '?';
    header("Location: " . $_SERVER['REQUEST_URI'] . $separator . 'refreshed=1');
    exit;  // interrompe o resto do header
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AlugaTorres - Sua agência de viagens para destinos incríveis">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $BASE_URL ?>style/style.css">
    <!-- Sistema de Notificações Toast -->
    <script src="<?= $BASE_URL ?>js/notifications.js"></script>

    <script>
        // Verificar se o sistema de notificações carregou corretamente
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof AlugaTorresNotifications === 'undefined') {
                console.error('[AlugaTorres] ERRO: Sistema de notificações não carregou!');
            } else {
                console.log('[AlugaTorres] Sistema de notificações pronto');
            }
        });
    </script>
</head>



<body>

    <header>
        <div class="header-container">

            <!-- Logo -->
            <img
                src="<?= $BASE_URL ?>style/img/Logo_AlugaTorres_branco.png"
                alt="AlugaTorres Logo"
                class="logo"
                width="60">

            <h1>AlugaTorres</h1>

            <!-- Menu mobile -->
            <button class="hamburger" aria-label="Menu">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>

            <!-- Navegação -->
            <nav class="main-nav" id="main-nav">
                <a href="<?= $BASE_URL ?>index.php">Inicio</a>
                <a href="<?= $BASE_URL ?>pesquisa.php">Pesquisa</a>
                <a href="<?= $BASE_URL ?>dashboard.php">Dashboard</a>
                <a href="<?= $BASE_URL ?>sobretorres.php">Sobre Torres</a>
                <a href="<?= $BASE_URL ?>contactos.php">Contactos</a>
            </nav>

            <!-- Área de autenticação -->
            <div class="auth-section">
                <?php if (isset($_SESSION['user'])): ?>
                    <div class="user-info">
                        <button class="profile-button" id="profile-toggle">
                            <i class="fas fa-user-circle"></i>
                            <span class="profile-name">
                                <?= htmlspecialchars($_SESSION['user']) ?>
                            </span>
                        </button>
                    </div>
                <?php else: ?>
                    <a class="auth-button login" href="<?= $BASE_URL ?>backend/login.php">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Renderizar notificações pendentes da sessão -->
    <?= renderPendingNotifications() ?>