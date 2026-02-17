<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Sobre Torres Novas - AlugaTorres">
  <title>AlugaTorres | Sobre Torres</title>
  <link rel="stylesheet" href="style/style.css">
  <link rel="website icon" type="png" href="style/img/Logo_AlugaTorres_branco.png">
</head>

<body>
  <?php include 'header.php'; ?>
  <?php include 'sidebar.php'; ?>

  <main class="page-container">
    <section class="about-torres">
      <div class="container">
        <h2>Sobre Torres Novas</h2>
        <p>
          Torres Novas é uma cidade rica em história, situada no coração do Ribatejo. Com um centro histórico bem preservado,
          oferece aos visitantes um equilíbrio entre património, natureza e gastronomia local. Aqui encontra castelos,
          jardins, mercados tradicionais e uma comunidade acolhedora.
        </p>

        <div class="image-grid">
          <div class="image-slot">
            <img src="style/img/TorresNovas1.jpg" alt="Castelo de Torres Novas">
          </div>
          <div class="image-slot">
            <img src="style/img/TorresNovas2.jpg" alt="Centro Histórico de Torres Novas">
          </div>
          <div class="image-slot">
            <img src="style/img/TorresNovas3.jpg" alt="Parque do Mouchão">
          </div>
        </div>

        <h3>Pontos Turísticos</h3>
        <ul class="tourist-points">
          <li>
            <strong>Castelo de Torres Novas</strong> — Forte medieval que domina o centro histórico e oferece uma perspetiva
            sobre a história defensiva da região.
          </li>
          <li>
            <strong>Parque do Mouchão & Rio Almonda</strong> — Zona verde urbana junto ao rio, ideal para passeios, piqueniques
            e para observar a fauna e flora locais.
          </li>
          <li>
            <strong>Centro Histórico</strong> — Ruas e praças com arquitetura tradicional, igrejas e pequenas lojas de produtos regionais.
          </li>
          <li>
            <strong>Mercados e Feiras Locais</strong> — Experimente o mercado semanal para provar produtos frescos e conversar com os produtores.
          </li>
        </ul>

        <h3>Gastronomia Típica</h3>
        <p>
          A região é conhecida por produtos do campo: queijos, enchidos, pães e doces conventuais. Nos restaurantes locais
          encontrará pratos tradicionais portugueses preparados com ingredientes locais e sazonais. Não deixe de provar
          os doces regionais e o pão tradicional acompanhado de um bom queijo.
        </p>

        <h3>Eventos e Cultura</h3>
        <p>
          Torres Novas promove eventos culturais ao longo do ano: feiras, festas populares e exposições. Verifique a agenda
          local para coincidir a sua visita com alguma festa tradicional ou mercado especial.
        </p>

        <p class="note">
          Este é um resumo introdutório da nossa cidade  — procure mais informações com nossa equipa.
        </p>
      </div>
    </section>

    <section class="visit-info container">
      <h3>Planeie a sua visita</h3>
      <p>
        Recomendamos explorar o centro a pé, reservar um dia para os parques e mercados, e aproveitar a oferta gastronómica
        nos restaurantes locais. Se procura alojamento, utilize a nossa pesquisa para encontrar casas e apartamentos na zona.
      </p>

      <div class="contact-cta">
        <a href="pesquisa.php" class="primary-button">Procurar Alojamento</a>
        <a href="contactos.php" class="secondary-button">Contacte-nos para Dicas Locais</a>
      </div>
    </section>

  </main>

  <?php include 'footer.php'; ?>

  <!-- scripts -->
  <script src="js/script.js"></script>


</body>

</html>