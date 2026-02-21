<?php
// Base do projeto (XAMPP)
$BASE_URL = '/AlugaTorres/';

// inicia sessão se ainda não existir
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!-- Sidebar -->
<aside>
    <style>
        #sidebar {
            position: fixed;
            top: 0;
            right: -350px;
            width: 350px;
            height: 100%;
            background: white;
            box-shadow: -2px 0 5px rgba(0, 0, 0, 0.1);
            transition: right 0.3s ease;
            z-index: 1001;
            overflow-y: auto;
        }

        #sidebar.active {
            right: 0;
        }

        #sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: none;
        }

        #sidebar-overlay.active {
            display: block;
        }
    </style>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3>Menu do Utilizador</h3>
            <button class="close-sidebar" id="close-sidebar">&times;</button>
        </div>


        <div class="sidebar-content">

            <?php if (isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] !== 'admin'): ?>
                <a href="<?= $BASE_URL ?>perfil.php" class="sidebar-item">
                    <i class="fas fa-user-edit"></i> Meu Perfil
                </a>

                <a href="<?= $BASE_URL ?>definicoes.php" class="sidebar-item">
                    <i class="fas fa-cog"></i> Definições
                </a>
            <?php endif; ?>

            <?php if (isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] === 'proprietario'): ?>
                <a href="<?= $BASE_URL ?>proprietario/minhas_casas.php" class="sidebar-item">
                    <i class="fas fa-shopping-bag"></i> Minhas Casas
                </a>
            <?php elseif (isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] === 'arrendatario'): ?>
                <a href="<?= $BASE_URL ?>arrendatario/reservas.php" class="sidebar-item">
                    <i class="fas fa-book"></i> Minhas Reservas
                </a>
            <?php endif; ?>

            <?php if (isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] === 'admin'): ?>
                <h4 class="sidebar-section-title"><i class="fas fa-shield-alt"></i> Administração</h4>

                <a href="<?= $BASE_URL ?>admin/index.php" class="sidebar-item admin-item">
                    <i class="fas fa-tachometer-alt"></i> Dashboard Admin
                </a>
                <a href="<?= $BASE_URL ?>admin/utilizadores.php" class="sidebar-item admin-item">
                    <i class="fas fa-users-cog"></i> Gerir Utilizadores
                </a>
                <a href="<?= $BASE_URL ?>admin/casas.php" class="sidebar-item admin-item">
                    <i class="fas fa-home"></i> Gerir Casas
                </a>
                <a href="<?= $BASE_URL ?>admin/configuracoes.php" class="sidebar-item admin-item">
                    <i class="fas fa-cogs"></i> Definições
                </a>
                <a href="<?= $BASE_URL ?>admin/logs.php" class="sidebar-item admin-item">
                    <i class="fas fa-history"></i> Logs de Atividade
                </a>
                <div class="sidebar-divider"></div>
            <?php endif; ?>

            <a href="<?= $BASE_URL ?>backend/logout.php" class="sidebar-item logout">

                <i class="fas fa-sign-out-alt"></i> Sair
            </a>

        </div>
    </div>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>
</aside>