<?php
// Base do projeto (XAMPP)
$BASE_URL = '/alugatorres/';

// inicia sessão se ainda não existir
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!-- Sidebar -->
<aside>
    <div class="sidebar" id="sidebar">

        <div class="sidebar-header">
            <h3>Menu do Utilizador</h3>
            <button class="close-sidebar" id="close-sidebar">&times;</button>
        </div>

        <div class="sidebar-content">

            <a href="<?= $BASE_URL ?>perfil.php" class="sidebar-item">
                <i class="fas fa-user-edit"></i> Meu Perfil
            </a>

            <a href="<?= $BASE_URL ?>definicoes.php" class="sidebar-item">
                <i class="fas fa-cog"></i> Definições
            </a>

            <?php if (isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] === 'proprietario'): ?>
                <a href="<?= $BASE_URL ?>proprietario/minhas_casas.php" class="sidebar-item">
                    <i class="fas fa-shopping-bag"></i> Minhas Casas
                </a>
            <?php else: ?>
                <a href="<?= $BASE_URL ?>arrendatario/reservas.php" class="sidebar-item">
                    <i class="fas fa-book"></i> Minhas Reservas
                </a>
            <?php endif; ?>

            <a href="<?= $BASE_URL ?>backend/logout.php" class="sidebar-item logout">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>

        </div>
    </div>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>
</aside>