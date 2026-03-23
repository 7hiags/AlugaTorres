<?php

require_once __DIR__ . '/init.php';
?>

<header>
    <div class="header-container">

        <!-- Logo + Title Branding -->
        <div class="logo-branding">
            <a href="<?= BASE_URL ?>root/index.php" class="logo-link">
                <img src="<?= BASE_URL ?>assets/style/img/Logo_AlugaTorres_branco.png" alt="AlugaTorres Logo" class="logo" width="60">
            </a>
            <a href="<?= BASE_URL ?>root/index.php" class="site-title">
                <h1>AlugaTorres</h1>
            </a>
        </div>

        <!-- Menu Hambúrguer para dispositivos móveis -->
        <button class="hamburger" aria-label="Menu">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>

        <!-- Navegação -->
        <nav class="main-nav" id="main-nav">
            <a href="<?= BASE_URL ?>root/index.php">Início</a>
            <a href="<?= BASE_URL ?>root/pesquisa.php">Pesquisa</a>
            <a href="<?= BASE_URL ?>root/dashboard.php">Dashboard</a>
            <a href="<?= BASE_URL ?>root/sobretorres.php">Sobre Torres</a>
            <a href="<?= BASE_URL ?>root/contactos.php">Contactos</a>
        </nav>



        <!-- Área de Autenticação -->
        <div class="auth-section">
            <?php if (isset($_SESSION['user'])): ?>
                <div class="user-info">
                    <button class="profile-button" id="profile-toggle">
                        <i class="fas fa-user-circle"></i>
                        <span class="profile-name"><?= htmlspecialchars($_SESSION['user']) ?></span>
                    </button>
                </div>
            <?php else: ?>
                <a class="auth-button login" href="<?= BASE_URL ?>backend/autenticacao/login.php">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            <?php endif; ?>
        </div>
</header>

<?= renderPendingNotifications() ?>