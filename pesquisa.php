<?php

/**
 * ========================================
 * Página de Pesquisa - AlugaTorres
 * ========================================
 * Este arquivo exibe as casas disponíveis para arrendamento.
 * Permite filtrar por freguesia e preço, além de buscar por destino.
 * 
 * @author AlugaTorres
 * @version 1.0
 */

// ============================================
// Inicialização da Sessão
// ============================================

session_start();

// ============================================
// Inclusão de Arquivos Necessários
// ============================================

// Carrega o arquivo de conexão com o banco de dados
require_once 'backend/db.php';

// ============================================
// Configurações do Utilizador
// ============================================

// Definir tipo de utilizador (padrão: arrendatario)
$tipo_utilizador = $_SESSION['tipo_utilizador'] ?? 'arrendatario';

// ============================================
// Consulta ao Banco de Dados
// ============================================

// Buscar casas disponíveis (que estão marcadas como disponíveis)
$query = "SELECT c.*, u.utilizador as proprietario_nome
          FROM casas c
          JOIN utilizadores u ON c.proprietario_id = u.id
          WHERE c.disponivel = 1
          ORDER BY c.id DESC";

// Executa a consulta
$result = $conn->query($query);

// ============================================
// Processamento dos Resultados
// ============================================

// Array para armazenar as casas formatadas
$casas = [];

// Percorre todos os resultados da query
while ($casa = $result->fetch_assoc()) {
  // ------------------------------------------
  // Decodificar comodidades (JSON -> Array)
  // ------------------------------------------
  $comodidades = [];
  if (!empty($casa['comodidades'])) {
    $comodidades = json_decode($casa['comodidades'], true);
    if (!is_array($comodidades)) {
      $comodidades = [];
    }
  }

  // ------------------------------------------
  // Formatar dados da casa para exibir
  // ------------------------------------------
  $casas[] = [
    'id' => $casa['id'],
    'titulo' => $casa['titulo'],
    'descricao' => $casa['descricao'],
    'cidade' => $casa['cidade'],
    'tipo_propriedade' => $casa['tipo_propriedade'],
    'quartos' => $casa['quartos'],
    'banheiros' => $casa['banheiros'],
    'freguesia' => $casa['freguesia'],
    'capacidade' => $casa['capacidade'],
    'preco_noite' => $casa['preco_noite'],
    'preco_limpeza' => $casa['preco_limpeza'],
    'taxa_seguranca' => $casa['taxa_seguranca'],
    'comodidades' => $comodidades,
    'proprietario_nome' => $casa['proprietario_nome']
  ];
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
  <!-- ========================================
       Meta Tags e Configurações
       ======================================== -->
  <meta charset="UTF-8">
  <title>AlugaTorres | Pesquisa</title>

  <!-- ========================================
       Folhas de Estilo (CSS)
       ======================================== -->
  <link rel="stylesheet" href="style/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="website icon" type="png" href="style/img/Logo_AlugaTorres_branco.png">
</head>

<body>
  <!-- ========================================
       Inclusão de Componentes
       ======================================== -->
  <?php include 'header.php'; ?>
  <?php include 'sidebar.php'; ?>

  <!-- ========================================
       Banner Hero de Pesquisa
       ======================================== -->
  <div class="destinations-hero">
    <h2>Venha conhecer Torres Novas</h2>
    <p>Explore os pontos turísticos da nossa cidade e planeje sua próxima aventura</p>

    <!-- Barra de busca -->
    <div class="search-container">
      <input type="text" id="searchDestino" placeholder="Alguma casa em questão?">
      <button class="search-button"><i class="fas fa-search"></i> Buscar</button>
    </div>
  </div>

  <!-- ========================================
       Container Principal de Destinos
       ======================================== -->
  <div class="destinations-container">

    <!-- ========================================
         Seção de Filtros
         ======================================== -->
    <div class="filter-section">
      <h3>Filtrar por:</h3>

      <!-- Filtro por Freguesia -->
      <div class="filter-group">
        <h4>Freguesias</h4>
        <label><input type="checkbox" name="freguesia" value="assentiz"> Assentiz</label>
        <label><input type="checkbox" name="freguesia" value="chancelaria"> Chancelaria</label>
        <label><input type="checkbox" name="freguesia" value="meia-via"> Meia Via</label>
        <label><input type="checkbox" name="freguesia" value="pedrogao"> Pedrógão</label>
        <label><input type="checkbox" name="freguesia" value="riachos"> Riachos</label>
        <label><input type="checkbox" name="freguesia" value="UF-brogueira-Parceiros-Alcorochel"> Brogueira/Parceiros/Alcorochel</label>
        <label><input type="checkbox" name="freguesia" value="UF-olaia-paco"> Olaia/Paço</label>
        <label><input type="checkbox" name="freguesia" value="UFT-santamaria-salvador-santiago"> Santa Maria/Salvador/Santiago</label>
        <label><input type="checkbox" name="freguesia" value="UFT-saopedro-lapas-ribeirab"> São Pedro/Lapas/Ribeira Branca</label>
        <label><input type="checkbox" name="freguesia" value="UF-zibreira"> Zibreira</label>
      </div>

      <!-- Filtro por Preço -->
      <div class="filter-group">
        <h4>Preço</h4>
        <input type="range" min="0" max="500" value="250" class="price-range" id="priceRange">
        <div class="price-labels">
          <span>0€</span>
          <span class="price-value">250€</span>
          <span>500€+</span>
        </div>
      </div>
    </div>

    <!-- ========================================
         Grid de Casas/Destinos
         ======================================== -->
    <div class="destinations-grid">
      <?php if (empty($casas)): ?>
        <!-- Mensagem quando não há resultados -->
        <div class="no-results" style="grid-column: 1 / -1; text-align: center; padding: 50px;">
          <i class="fas fa-search" style="font-size: 3em; color: #ccc; margin-bottom: 20px;"></i>
          <h3>Nenhuma casa encontrada</h3>
          <p>Não há casas disponíveis no momento. Tente novamente mais tarde.</p>
        </div>
      <?php else: ?>
        <!-- Loop pelas casas disponíveis -->
        <?php foreach ($casas as $casa): ?>
          <!-- Card de Casa -->
          <div class="destination-card card-visible"
            data-preco="<?php echo $casa['preco_noite']; ?>"
            data-tipo="<?php echo strtolower($casa['tipo_propriedade']); ?>"
            data-freguesia="<?php echo strtolower($casa['freguesia'] ?? ''); ?>"
            data-cidade="<?php echo strtolower($casa['cidade'] ?? ''); ?>">

            <!-- Imagem do Card -->
            <div class="card-image">
              <img src="style/img/TorresNovas1.jpg" alt="<?php echo htmlspecialchars($casa['titulo']); ?>">
              <div class="card-badge"><?php echo ucfirst($casa['tipo_propriedade']); ?></div>
            </div>

            <!-- Conteúdo do Card -->
            <div class="card-content">
              <h3><?php echo htmlspecialchars($casa['titulo']); ?></h3>
              <p class="card-description"><?php echo htmlspecialchars(substr($casa['descricao'], 0, 100)) . (strlen($casa['descricao']) > 100 ? '...' : ''); ?></p>

              <!-- Características da casa -->
              <div class="card-features">
                <span><i class="fas fa-bed"></i> <?php echo $casa['quartos']; ?> quartos</span>
                <span><i class="fas fa-bath"></i> <?php echo $casa['banheiros']; ?> casas de banho</span>
                <span><i class="fas fa-users"></i> Até <?php echo $casa['capacidade']; ?> pessoas</span>
                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($casa['cidade']); ?></span>
                <span><i class="fas fa-home"></i> <?php echo htmlspecialchars($casa['freguesia']); ?></span>
              </div>

              <!-- Preço e Botão -->
              <div class="card-price">
                <span class="price">€<?php echo number_format($casa['preco_noite'], 2, ',', '.'); ?>/noite</span>
                <?php if ($tipo_utilizador === 'arrendatario'): ?>
                  <!-- Botão para arrendatários -->
                  <a href="calendario.php?casa_id=<?php echo $casa['id']; ?>" class="book-button">Reservar Agora</a>
                <?php elseif ($tipo_utilizador === 'proprietario'): ?>
                  <!-- Botão para proprietários -->
                  <a href="calendario.php?casa_id=<?php echo $casa['id']; ?>" class="book-button">Ver Detalhes</a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- ========================================
       Seção de Newsletter
       ======================================== -->
  <div class="newsletter-section">
    <div class="newsletter-content">
      <h3>Não Perca Nossas Ofertas!</h3>
      <p>Inscreva-se para receber atualizações de casas disponíveis.</p>

      <!-- Formulário de Newsletter -->
      <form class="newsletter-form" id="newsletter-form">
        <input type="email" name="email" placeholder="Seu melhor email" required>
        <button type="submit">Inscrever-se</button>
      </form>

      <!-- Mensagem de feedback -->
      <div id="newsletter-message" style="display:none; margin-top: 15px; padding: 10px; border-radius: 5px;"></div>
    </div>
  </div>

  <!-- ========================================
       Rodapé da Página
       ======================================== -->
  <?php include 'footer.php'; ?>

  <!-- ========================================
       Scripts JavaScript
       ======================================== -->
  <script src="js/script.js"></script>

</body>

</html>