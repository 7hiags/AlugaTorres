<?php
session_start();
if (isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Registo de Tipo de Utilizador - AlugaTorres">
    <title>AlugaTorres | Registo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="website icon" type="png" href="../style/img/Logo_AlugaTorres_branco.png">
</head>

<body>
    <?php include '../header.php'; ?>
    <?php include '../sidebar.php'; ?>

    <div class="perfil-container">
        <h1 class="perfil-title">Junte-se ao AlugaTorres</h1>
        <p class="perfil-subtitle">Selecione o tipo de perfil que melhor se adapta a si</p>

        <div class="perfil-options">
            <div class="perfil-card" data-tipo="arrendatario">
                <div class="perfil-icon">
                    <i class="fas fa-user-tag"></i>
                </div>
                <h3>Arrendatário</h3>
                <p>Procura alojamento para férias ou estadia em Torres Novas</p>
                <ul>
                    <li><i class="fas fa-check"></i> Pesquisar e reservar casas</li>
                    <li><i class="fas fa-check"></i> Guardar favoritos</li>
                    <li><i class="fas fa-check"></i> Gerir as suas reservas</li>
                </ul>

                <p><strong>Ideal para:</strong> Turistas, viajantes, famílias</p>
            </div>

            <div class="perfil-card" data-tipo="proprietario">
                <div class="perfil-icon">
                    <i class="fas fa-home"></i>
                </div>
                <h3>Proprietário</h3>
                <p>Tem uma propriedade para arrendar em Torres Novas</p>
                <ul>
                    <li><i class="fas fa-check"></i> Anunciar a sua propriedade</li>
                    <li><i class="fas fa-check"></i> Gerir calendário de disponibilidade</li>
                    <li><i class="fas fa-check"></i> Receber e confirmar reservas</li>
                    <li><i class="fas fa-check"></i> Aceder a ferramentas de gestão</li>
                </ul>
                <p><strong>Ideal para:</strong> Proprietários, gestores imobiliários</p>
            </div>
        </div>

        <form id="perfilForm" action="registro.php" method="GET">
            <input type="hidden" name="tipo_utilizador" id="tipoUtilizador">
            <button type="submit" class="btn-continuar" id="btnContinuar" disabled>
                <i class="fas fa-arrow-right"></i> Continuar com o Registro
            </button>
        </form>

        <p class="login-link">
            Já tem uma conta? <a href="login.php">Faça login aqui</a>
        </p>
    </div>

    <?php include '../footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.perfil-card');
            const btnContinuar = document.getElementById('btnContinuar');
            const tipoUsuarioInput = document.getElementById('tipoUtilizador');

            cards.forEach(card => {
                card.addEventListener('click', function() {
                    // Remove active class from all cards
                    cards.forEach(c => c.classList.remove('active'));

                    // Add active class to clicked card
                    this.classList.add('active');

                    // Get the tipo from data attribute
                    const tipo = this.getAttribute('data-tipo');
                    tipoUsuarioInput.value = tipo;

                    // Enable continue button
                    btnContinuar.disabled = false;
                });
            });
        });

        document.addEventListener("DOMContentLoaded", function() {
            const profileToggle = document.getElementById("profile-toggle");
            const sidebar = document.getElementById("sidebar");
            const sidebarOverlay = document.getElementById("sidebar-overlay");
            const closeSidebar = document.getElementById("close-sidebar");

            if (profileToggle) {
                profileToggle.addEventListener("click", function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    sidebar.classList.toggle("active");
                    sidebarOverlay.classList.toggle("active");
                });
            }

            if (closeSidebar) {
                closeSidebar.addEventListener("click", function() {
                    sidebar.classList.remove("active");
                    sidebarOverlay.classList.remove("active");
                });
            }

            // Close sidebar when clicking outside
            document.addEventListener("click", function(event) {
                if (
                    !sidebar.contains(event.target) &&
                    !profileToggle.contains(event.target)
                ) {
                    sidebar.classList.remove("active");
                    sidebarOverlay.classList.remove("active");
                }
            });
        });
    </script>
</body>

</html>