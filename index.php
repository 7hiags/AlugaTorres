<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="AlugaTorres - Sua agência de viagens para destinos incríveis">
  <title>AlugaTorres | Home</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="style/style.css">
  <link rel="website icon" type="png" href="style/img/Logo_AlugaTorres_branco.png">
</head>

<body>
  <?php include 'header.php'; ?>
  <?php include 'sidebar.php'; ?>

  <main>
    <section class="slider-container">
      <div class="slider">
        <div class="slide">
          <img src="style/img/TorresNovas3.jpg" alt="Praça de Torres Novas">
          <div class="slide-content">
            <h2>Explore a nossa cultura e história.</h2>
            <p>Aprecie os pontos turísticos e a cultura de nossa cidade.</p>
          </div>
        </div>
        <div class="slide">
          <img src="style/img/TorresNovas1.jpg" alt="Castelo de Torres Novas">
          <div class="slide-content">
            <h2>Descubra novos lugares.</h2>
            <p>Explore nossas lindas paisagens e arquitetura única</p>
          </div>
        </div>
        <div class="slide">
          <img src="style/img/baca.jpg" alt="Culinária de Torres Novas">
          <div class="slide-content">
            <h2>Conheça nossa culinária.</h2>
            <p>Conheça pratos fascinantes e deliciosos</p>
          </div>
        </div>
      </div>

      <div class="slider-controls">
        <button class="prev-button"><i class="fas fa-chevron-left"></i></button>
        <div class="slider-dots"></div>
        <button class="next-button"><i class="fas fa-chevron-right"></i></button>
      </div>
    </section>

    <section class="features">
      <div class="feature-card">
        <i class="fa-solid fa-location-arrow"></i>
        <h3>Destinos Exclusivos</h3>
        <p>Descubra lugares únicos da nossa cidade</p>
      </div>
      <div class="feature-card">
        <i class="fas fa-shield-alt"></i>
        <h3>Viagem Segura</h3>
        <p>Sua segurança é prioridade em Torres Novas</p>
      </div>
      <div class="feature-card">
        <i class="fas fa-heart"></i>
        <h3>Atendimento Especial</h3>
        <p>Equipe dedicada ao atendimento</p>
      </div>
    </section>

    <section class="about-us container">
      <h2>Sobre a AlugaTorres</h2>
      <p>
        A <strong>AlugaTorres</strong> é uma plataforma local de arrendamento dedicada a promover casas e apartamentos
        na área de Torres Novas e arredores. Facilitamos a ligação entre proprietários que querem disponibilizar os seus
        espaços e visitantes que procuram estadias autênticas, seguras e com atendimento personalizado.
      </p>
      <p>
        A nossa missão é valorizar o alojamento local, apoiar proprietários com ferramentas simples e dar aos hóspedes
        experiências memoráveis, com sugestões locais e suporte durante toda a sua estadia.
      </p>
      <div class="about-cta">
        <a href="pesquisa.php" class="primary-button">Procurar Alojamento</a>
        <a href="backend/registro.php" class="secondary-button">Registe a sua Casa</a>
      </div>
    </section>

    <?php include 'footer.php'; ?>
  </main>
  <script src="backend/script.js"></script>
</body>

</html>