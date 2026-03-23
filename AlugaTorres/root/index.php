<?php

// inicialização global
require_once __DIR__ . '/init.php';

// Handle notifications from eliminar_conta.php
if (isset($_SESSION['notification']) && !isset($_SESSION['user'])) {
  $notification = $_SESSION['notification'];
  unset($_SESSION['notification']);
}

// Obtém o tipo de utilizador da sessão (padrão: 'arrendatario' se não estiver definido)
$tipo_utilizador = $_SESSION['tipo_utilizador'] ?? 'arrendatario';

$pageTitle = 'AlugaTorres | Inicio';
$metaDescription = 'AlugaTorres - Sua agência de viagens para destinos incríveis';
require_once __DIR__ . '/head.php';
include 'header.php';
include 'sidebar.php';
?>
<?php if (isset($notification)): ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof AlugaTorresNotifications !== 'undefined') {
        AlugaTorresNotifications.success(<?php echo json_encode($notification['message']); ?>);
      }
    });
  </script>
<?php endif; ?>

<main>

  <!-- Slider de Imagens - Apresentação -->
  <section class="slider-container">
    <div class="slider">

      <!-- Slide 1 - Cultura e História -->
      <div class="slide">
        <img src="../assets/style/img/TorresNovas3.jpg" alt="Praça de Torres Novas">
        <div class="slide-content">
          <h2>Explore a nossa cultura e história.</h2>
          <p>Aprecie os pontos turísticos e a cultura de nossa cidade.</p>
        </div>
      </div>

      <!-- Slide 2 - Paisagens e Arquitetura -->
      <div class="slide">
        <img src="../assets/style/img/TorresNovas1.jpg" alt="Castelo de Torres Novas">
        <div class="slide-content">
          <h2>Descubra novos lugares.</h2>
          <p>Explore nossas lindas paisagens e arquitetura única</p>
        </div>
      </div>

      <!-- Slide 3 - Culinária Local -->
      <div class="slide">
        <img src="../assets/style/img/baca.jpg" alt="Culinária de Torres Novas">
        <div class="slide-content">
          <h2>Conheça nossa culinária.</h2>
          <p>Conheça pratos fascinantes e deliciosos</p>
        </div>
      </div>
    </div>

    <!-- Controles de Navegação do Slider -->
    <div class="slider-controls">
      <div class="slider-dots"></div>
    </div>
  </section>

  <!-- Seção de Funcionalidades/Destaques -->
  <section class="features">
    <!-- Card 1 - Destinos Exclusivos -->
    <div class="feature-card">
      <i class="fa-solid fa-location-arrow"></i>
      <h3>Pontos Turísticos Exclusivos</h3>
      <p>Descubra lugares únicos da nossa cidade</p>
    </div>

    <!-- Card 2 - Viagem Segura -->
    <div class="feature-card">
      <i class="fas fa-shield-alt"></i>
      <h3>Segurança</h3>
      <p>Sua segurança é prioridade em Torres Novas</p>
    </div>

    <!-- Card 3 - Atendimento Especial -->
    <div class="feature-card">
      <i class="fas fa-heart"></i>
      <h3>Atendimento Especial</h3>
      <p>Equipa dedicada ao atendimento</p>
    </div>
  </section>

  <!-- Seção Sobre a Empresa -->
  <section class="about-us container">
    <h2>Sobre a AlugaTorres</h2>

    <!-- Descrição da empresa -->
    <p>
      A <strong>AlugaTorres</strong> é uma plataforma local dedicada a arrendamentos de imóveis como casas, apartamentos, quintas e etc.
      Na área de Torres Novas e arredores, Facilitamos a ligação entre proprietários que querem disponibilizar os seus
      espaços e visitantes que procuram estadias autênticas, seguras e com atendimento personalizado.
    </p>

    <!-- Missão da empresa -->
    <p>
      A nossa missão é valorizar o alojamento local, apoiar proprietários com ferramentas simples e dar aos hóspedes
      experiências memoráveis, com sugestões locais e suporte durante toda a sua estadia.
    </p>

    <!-- Botões de Ação (CTA) - Conforme Tipo de Utilizador -->
    <div class="about-cta">
      <?php if (!isset($_SESSION['user'])): ?>
        <!-- Visitante não logado: mostra ambos os botões -->
        <a href="pesquisa.php" class="primary-button">Procurar Alojamento</a>
        <a href="../proprietario/adicionar_casa.php" class="secondary-button">Registe a sua Casa</a>

      <?php elseif ($tipo_utilizador === 'arrendatario'): ?>
        <!-- Utilizador logado como arrendatário: mostra apenas procurar alojamento -->
        <a href="pesquisa.php" class="primary-button">Procurar Alojamento</a>

      <?php elseif ($tipo_utilizador === 'proprietario'): ?>
        <!-- Utilizador logado como proprietário: mostra apenas registar casa -->
        <a href="../proprietario/adicionar_casa.php" class="secondary-button">Registe a sua Casa</a>
      <?php endif; ?>
    </div>

  </section>

  <!-- Inclui o Rodapé da Página -->
  <?php include 'footer.php'; ?>

</main>

</body>

</html>