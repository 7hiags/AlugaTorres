<?php

/**
 * ========================================
 * Página Inicial - AlugaTorres
 * ========================================
 * Este arquivo é a página principal do site AlugaTorres.
 * Apresenta um slider com imagens da região, funcionalidades
 * e informações sobre a plataforma de arrendamento.
 * 
 * @author AlugaTorres
 * @version 1.0
 */

// ============================================
// Inicialização da Sessão
// ============================================

// Inicia a sessão PHP para permitir acesso às variáveis de sessão
session_start();

// ============================================
// Inclusão de Arquivos Necessários
// ============================================

// Carrega o arquivo de conexão com o banco de dados
require_once 'backend/db.php';

// Obtém o tipo de utilizador da sessão (padrão: 'arrendatario' se não estiver definido)
$tipo_utilizador = $_SESSION['tipo_utilizador'] ?? 'arrendatario';

?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
  <!-- ========================================
       Meta Tags e Configurações do Documento
       ======================================== -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="AlugaTorres - Sua agência de viagens para destinos incríveis">

  <!-- Título da página -->
  <title>AlugaTorres | Inicio</title>

  <!-- ========================================
       Folhas de Estilo (CSS)
       ======================================== -->
  <!-- Font Awesome para ícones -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <!-- Estilos personalizados do site -->
  <link rel="stylesheet" href="style/style.css">
  <!-- Ícone do site -->
  <link rel="website icon" type="png" href="style/img/Logo_AlugaTorres_branco.png">
</head>

<body>
  <!-- ========================================
       Inclusão de Componentes Reutilizáveis
       ======================================== -->

  <!-- Header/Navegação principal -->
  <?php include 'header.php'; ?>

  <!-- Sidebar (menu lateral) -->
  <?php include 'sidebar.php'; ?>

  <!-- ========================================
       Conteúdo Principal da Página
       ======================================== -->
  <main>

    <!-- ========================================
         Slider de Imagens - Apresentação
         ======================================== -->
    <section class="slider-container">
      <div class="slider">
        <!-- Slide 1 - Cultura e História -->
        <div class="slide">
          <img src="style/img/TorresNovas3.jpg" alt="Praça de Torres Novas">
          <div class="slide-content">
            <h2>Explore a nossa cultura e história.</h2>
            <p>Aprecie os pontos turísticos e a cultura de nossa cidade.</p>
          </div>
        </div>

        <!-- Slide 2 - Paisagens e Arquitetura -->
        <div class="slide">
          <img src="style/img/TorresNovas1.jpg" alt="Castelo de Torres Novas">
          <div class="slide-content">
            <h2>Descubra novos lugares.</h2>
            <p>Explore nossas lindas paisagens e arquitetura única</p>
          </div>
        </div>

        <!-- Slide 3 - Culinária Local -->
        <div class="slide">
          <img src="style/img/baca.jpg" alt="Culinária de Torres Novas">
          <div class="slide-content">
            <h2>Conheça nossa culinária.</h2>
            <p>Conheça pratos fascinantes e deliciosos</p>
          </div>
        </div>
      </div>

      <!-- ========================================
           Controles de Navegação do Slider
           ======================================== -->
      <div class="slider-controls">
        <button class="prev-button"><i class="fas fa-chevron-left"></i></button>
        <div class="slider-dots"></div>
        <button class="next-button"><i class="fas fa-chevron-right"></i></button>
      </div>
    </section>

    <!-- ========================================
         Seção de Funcionalidades/Destaques
         ======================================== -->
    <section class="features">
      <!-- Card 1 - Destinos Exclusivos -->
      <div class="feature-card">
        <i class="fa-solid fa-location-arrow"></i>
        <h3>Destinos Exclusivos</h3>
        <p>Descubra lugares únicos da nossa cidade</p>
      </div>

      <!-- Card 2 - Viagem Segura -->
      <div class="feature-card">
        <i class="fas fa-shield-alt"></i>
        <h3>Viagem Segura</h3>
        <p>Sua segurança é prioridade em Torres Novas</p>
      </div>

      <!-- Card 3 - Atendimento Especial -->
      <div class="feature-card">
        <i class="fas fa-heart"></i>
        <h3>Atendimento Especial</h3>
        <p>Equipe dedicada ao atendimento</p>
      </div>
    </section>

    <!-- ========================================
         Seção Sobre a Empresa
         ======================================== -->
    <section class="about-us container">
      <h2>Sobre a AlugaTorres</h2>

      <!-- Descrição da empresa -->
      <p>
        A <strong>AlugaTorres</strong> é uma plataforma local de arrendamento dedicada a promover casas e apartamentos
        na área de Torres Novas e arredores. Facilitamos a ligação entre proprietários que querem disponibilizar os seus
        espaços e visitantes que procuram estadias autênticas, seguras e com atendimento personalizado.
      </p>

      <!-- Missão da empresa -->
      <p>
        A nossa missão é valorizar o alojamento local, apoiar proprietários com ferramentas simples e dar aos hóspedes
        experiências memoráveis, com sugestões locais e suporte durante toda a sua estadia.
      </p>

      <!-- ========================================
           Botões de Ação (CTA) - Conforme Tipo de Utilizador
           ======================================== -->
      <div class="about-cta">
        <?php if (!isset($_SESSION['user'])): ?>
          <!-- Visitante não logado: mostra ambos os botões -->
          <a href="pesquisa.php" class="primary-button">Procurar Alojamento</a>
          <a href="proprietario/adicionar_casa.php" class="secondary-button">Registe a sua Casa</a>

        <?php elseif ($tipo_utilizador === 'arrendatario'): ?>
          <!-- Utilizador logado como arrendatário: mostra apenas procurar alojamento -->
          <a href="pesquisa.php" class="primary-button">Procurar Alojamento</a>

        <?php elseif ($tipo_utilizador === 'proprietario'): ?>
          <!-- Utilizador logado como proprietário: mostra apenas registar casa -->
          <a href="proprietario/adicionar_casa.php" class="secondary-button">Registe a sua Casa</a>
        <?php endif; ?>
      </div>

    </section>

    <!-- ========================================
         Rodapé da Página
         ======================================== -->
    <?php include 'footer.php'; ?>

  </main>

  <!-- ========================================
       Scripts JavaScript
       ======================================== -->
  <!-- Script principal com funcionalidades interativas -->
  <script src="js/script.js"></script>

</body>

</html>