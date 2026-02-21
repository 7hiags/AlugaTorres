<?php

/**
 * ========================================
 * Sidebar - Menu Lateral do Utilizador
 * ========================================
 * Este arquivo contém o menu lateral (sidebar) que é exibido aos utilizadores
 * autenticados. O menu varia conforme o tipo de utilizador (admin, proprietário ou arrendatário).
 * 
 * @author AlugaTorres
 * @version 1.0
 */

// ============================================
// Configurações e Inicialização
// ============================================

// Base do projeto (XAMPP) - URL base do site
$BASE_URL = '/AlugaTorres/';

// Inicia sessão se ainda não existir
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!-- ============================================
     Estrutura HTML da Sidebar
     ============================================ -->
<aside>
    <!-- ========================================
         Estilos CSS inline para a sidebar
         (Posicionamento, animação e aparência)
         ======================================== -->
    <style>
        /* ========================================
           Estilo principal da sidebar
           - Posicionamento fixo à direita
           - Largura de 350px
           - Animação de transição suave
           ======================================== */
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

        /* Estado ativo - sidebar visível */
        #sidebar.active {
            right: 0;
        }

        /* ========================================
           Overlay (fundo escuro)
           - Cob toda a tela quando sidebar ativa
           - 提供 suporte visual para fechar
           ======================================== */
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

        /* Estado ativo do overlay */
        #sidebar-overlay.active {
            display: block;
        }
    </style>

    <!-- ========================================
         Container principal da sidebar
         ======================================== -->
    <div class="sidebar" id="sidebar">
        <!-- Cabeçalho da sidebar -->
        <div class="sidebar-header">
            <h3>Menu do Utilizador</h3>
            <!-- Botão para fechar a sidebar -->
            <button class="close-sidebar" id="close-sidebar">&times;</button>
        </div>

        <!-- ========================================
             Conteúdo da sidebar - Menu de opções
             ======================================== -->
        <div class="sidebar-content">

            <!-- ========================================
                 Opções para utilizadores não-admin
                 (Proprietários e Arrendatários)
                 ======================================== -->
            <?php if (isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] !== 'admin'): ?>
                <!-- Link para página de perfil -->
                <a href="<?= $BASE_URL ?>perfil.php" class="sidebar-item">
                    <i class="fas fa-user-edit"></i> Meu Perfil
                </a>

                <!-- Link para definições da conta -->
                <a href="<?= $BASE_URL ?>definicoes.php" class="sidebar-item">
                    <i class="fas fa-cog"></i> Definições
                </a>
            <?php endif; ?>

            <!-- ========================================
                 Opções específicas por tipo de utilizador
                 ======================================== -->

            <!-- Menu para Proprietários -->
            <?php if (isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] === 'proprietario'): ?>
                <!-- Link para gestão das casas do proprietário -->
                <a href="<?= $BASE_URL ?>proprietario/minhas_casas.php" class="sidebar-item">
                    <i class="fas fa-shopping-bag"></i> Minhas Casas
                </a>

                <!-- Menu para Arrendatários -->
            <?php elseif (isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] === 'arrendatario'): ?>
                <!-- Link para reservas do arrendatário -->
                <a href="<?= $BASE_URL ?>arrendatario/reservas.php" class="sidebar-item">
                    <i class="fas fa-book"></i> Minhas Reservas
                </a>
            <?php endif; ?>

            <!-- ========================================
                 Menu de Administração (apenas admins)
                 ======================================== -->
            <?php if (isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] === 'admin'): ?>
                <!-- Título da secção admin -->
                <h4 class="sidebar-section-title"><i class="fas fa-shield-alt"></i> Administração</h4>

                <!-- Dashboard administrativo -->
                <a href="<?= $BASE_URL ?>admin/index.php" class="sidebar-item admin-item">
                    <i class="fas fa-tachometer-alt"></i> Dashboard Admin
                </a>

                <!-- Gestão de utilizadores -->
                <a href="<?= $BASE_URL ?>admin/utilizadores.php" class="sidebar-item admin-item">
                    <i class="fas fa-users-cog"></i> Gerir Utilizadores
                </a>

                <!-- Gestão de casas -->
                <a href="<?= $BASE_URL ?>admin/casas.php" class="sidebar-item admin-item">
                    <i class="fas fa-home"></i> Gerir Casas
                </a>

                <!-- Configurações do sistema -->
                <a href="<?= $BASE_URL ?>admin/configuracoes.php" class="sidebar-item admin-item">
                    <i class="fas fa-cogs"></i> Definições
                </a>

                <!-- Logs de atividade -->
                <a href="<?= $BASE_URL ?>admin/logs.php" class="sidebar-item admin-item">
                    <i class="fas fa-history"></i> Logs de Atividade
                </a>

                <!-- Divisor visual -->
                <div class="sidebar-divider"></div>
            <?php endif; ?>

            <!-- ========================================
                 Opção de logout (todos os utilizadores)
                 ======================================== -->
            <a href="<?= $BASE_URL ?>backend/logout.php" class="sidebar-item logout">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>

        </div>
    </div>

    <!-- Overlay para clique fora da sidebar -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
</aside>