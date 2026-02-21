<?php
session_start();
require_once 'backend/db.php';

// Definir tipo de utilizador
$tipo_utilizador = $_SESSION['tipo_utilizador'] ?? 'arrendatario';

// Buscar casas disponíveis

$query = "SELECT c.*, u.utilizador as proprietario_nome
          FROM casas c
          JOIN utilizadores u ON c.proprietario_id = u.id
          WHERE c.disponivel = 1
          ORDER BY c.id DESC";


$result = $conn->query($query);
$casas = [];
while ($casa = $result->fetch_assoc()) {
  // Decodificar comodidades
  $comodidades = [];
  if (!empty($casa['comodidades'])) {
    $comodidades = json_decode($casa['comodidades'], true);
    if (!is_array($comodidades)) {
      $comodidades = [];
    }
  }

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
  <meta charset="UTF-8">
  <title>AlugaTorres | Pesquisa</title>
  <link rel="stylesheet" href="style/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="website icon" type="png" href="style/img/Logo_AlugaTorres_branco.png">
</head>

<body>
  <?php include 'header.php'; ?>
  <?php include 'sidebar.php'; ?>

  <div class="destinations-hero">
    <h2>Venha conhecer Torres Novas</h2>
    <p>Explore os pontos turísticos da nossa cidade e planeje sua próxima aventura</p>
    <div class="search-container">
      <input type="text" id="searchDestino" placeholder="Alguma casa em questão?">
      <button class="search-button"><i class="fas fa-search"></i> Buscar</button>
    </div>
  </div>

  <div class="destinations-container">
    <div class="filter-section">
      <h3>Filtrar por:</h3>
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

    <div class="destinations-grid">
      <?php if (empty($casas)): ?>
        <div class="no-results" style="grid-column: 1 / -1; text-align: center; padding: 50px;">
          <i class="fas fa-search" style="font-size: 3em; color: #ccc; margin-bottom: 20px;"></i>
          <h3>Nenhuma casa encontrada</h3>
          <p>Não há casas disponíveis no momento. Tente novamente mais tarde.</p>
        </div>
      <?php else: ?>
        <?php foreach ($casas as $casa): ?>
          <div class="destination-card card-visible" data-preco="<?php echo $casa['preco_noite']; ?>" data-tipo="<?php echo strtolower($casa['tipo_propriedade']); ?>" data-freguesia="<?php echo strtolower($casa['freguesia'] ?? ''); ?>" data-cidade="<?php echo strtolower($casa['cidade'] ?? ''); ?>">

            <div class="card-image">
              <img src="style/img/TorresNovas1.jpg" alt="<?php echo htmlspecialchars($casa['titulo']); ?>">
              <div class="card-badge"><?php echo ucfirst($casa['tipo_propriedade']); ?></div>
            </div>
            <div class="card-content">
              <h3><?php echo htmlspecialchars($casa['titulo']); ?></h3>
              <p class="card-description"><?php echo htmlspecialchars(substr($casa['descricao'], 0, 100)) . (strlen($casa['descricao']) > 100 ? '...' : ''); ?></p>
              <div class="card-features">
                <span><i class="fas fa-bed"></i> <?php echo $casa['quartos']; ?> quartos</span>
                <span><i class="fas fa-bath"></i> <?php echo $casa['banheiros']; ?> casas de banho</span>
                <span><i class="fas fa-users"></i> Até <?php echo $casa['capacidade']; ?> pessoas</span>
                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($casa['cidade']); ?></span>
                <span><i class="fas fa-home"></i> <?php echo htmlspecialchars($casa['freguesia']); ?></span>
              </div>
              <div class="card-price">
                <span class="price">€<?php echo number_format($casa['preco_noite'], 2, ',', '.'); ?>/noite</span>
                <?php if ($tipo_utilizador === 'arrendatario'): ?>
                  <a href="calendario.php?casa_id=<?php echo $casa['id']; ?>" class="book-button">Reservar Agora</a>
                <?php elseif ($tipo_utilizador === 'proprietario'): ?>
                  <a href="calendario.php?casa_id=<?php echo $casa['id']; ?>" class="book-button">Ver Detalhes</a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="newsletter-section">
    <div class="newsletter-content">
      <h3>Não Perca Nossas Ofertas!</h3>
      <p>Inscreva-se para receber atualizações de casas disponíveis.</p>
      <form class="newsletter-form" id="newsletter-form">
        <input type="email" name="email" placeholder="Seu melhor email" required>
        <button type="submit">Inscrever-se</button>
      </form>
      <div id="newsletter-message" style="display:none; margin-top: 15px; padding: 10px; border-radius: 5px;"></div>
    </div>
  </div>

  <?php include 'footer.php'; ?>

  <script src="js/script.js"></script>

</body>

</html>