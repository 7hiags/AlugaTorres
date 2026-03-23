<?php
// inicialização global comum
require_once __DIR__ . '/init.php';
?>

<footer>
    <div class="footer-content">
        <div class="footer-section">
            <h4>AlugaTorres</h4>
            <p>Sua agência de arrendamentos de confiança desde 2025</p>
            <p>Venha conhecer nossa cidade</p>
        </div>
        <div class="footer-section">
            <h4>Links Rápidos</h4>
            <ul>
                <li><a href="<?= BASE_URL ?>root/index.php">Home</a></li>
                <li><a href="<?= BASE_URL ?>root/pesquisa.php">Pesquisa</a></li>
                <li><a href="<?= BASE_URL ?>root/dashboard.php">Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>root/sobretorres.php">Sobre Torres</a></li>
                <li><a href="<?= BASE_URL ?>root/contactos.php">Contactos</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h4>Contactos</h4>
            <p><i class="fas fa-map-marker-alt"></i> Santarém, Portugal</p>
            <p><i class="fas fa-phone"></i> +351 929 326 577</p>
            <p><i class="fas fa-envelope"></i> alugatorrespt@gmail.com</p>
        </div>
        <div class="footer-section">
            <h4>Redes Sociais</h4>
            <div class="social-links">
                <a href="#" aria-label="Facebook" class="facebook"><i class="fab fa-facebook"></i></a>
                <a href="#" aria-label="Instagram" class="instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" aria-label="Twitter" class="twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" aria-label="LinkedIn" class="linkedin"><i class="fab fa-linkedin"></i></a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <span id="ano"></span> AlugaTorres. Todos os direitos reservados.</p>
    </div>
</footer>

<script src="<?= BASE_URL ?>assets/js/script.js"></script>
<script src="<?= BASE_URL ?>assets/js/notifications.js"></script>