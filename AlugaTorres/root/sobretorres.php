<?php
require_once __DIR__ . '/init.php';
$pageTitle = 'AlugaTorres | Sobre Torres';
$metaDescription = 'Sobre Torres Novas - AlugaTorres';

require_once __DIR__ . '/head.php'; 
include 'header.php';
include 'sidebar.php'; 
?>

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
          <img src="../assets/style/img/TorresNovas1.jpg" alt="Castelo de Torres Novas">
        </div>
        <div class="image-slot">
          <img src="../assets/style/img/TorresNovas2.jpg" alt="Centro Histórico de Torres Novas">
        </div>
        <div class="image-slot">
          <img src="../assets/style/img/TorresNovas3.jpg" alt="Parque do Mouchão">
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
      <p style="text-align: left;">
        <strong>Torres Novas promove eventos culturais ao longo do ano como:</strong><br>
      </p>
      <p>
        <strong>Feiras de Época/Memórias da História:</strong> Recriações históricas que transportam os visitantes para o passado da cidade;<br><br>
        <strong>Feira de Março (São Gregório):</strong> Evento anual que celebra a chegada da primavera com artesanato, gastronomia e música ao vivo;<br><br>
        <strong>Festas do Almonda:</strong> Celebrações anuais que reúnem a comunidade, com música, cultura e tradição popular;<br><br>
        <strong>Festival Gastronómico das Couves com Feijões:</strong> Evento de destaque no outono/inverno, promovendo a gastronomia local em dezenas de restaurantes aderentes.<br><br>
        Verifique a agenda local para coincidir a sua visita com alguma festa tradicional ou mercado especial.
      </p>

      <p class="note">
        Este é um resumo introdutório da nossa cidade — procure mais informações com nossa equipa.
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

</body>

</html>