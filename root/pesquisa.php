<?php

// inicialização comum e carregamento de helpers
require_once __DIR__ . '/init.php';

// Definir tipo de utilizador (padrão: arrendatario)
$tipo_utilizador = $_SESSION['tipo_utilizador'] ?? 'arrendatario';

// Procurar casas disponíveis (que estão marcadas como disponíveis)
// Casas em destaque aparecem primeiro
// Inclui média de avaliações (ou NULL se não houver avaliações)
$query = "SELECT c.*, c.custom_tipo, u.utilizador as proprietario_nome,
          (SELECT COALESCE(AVG(rating), NULL) FROM avaliacoes WHERE casa_id = c.id AND ativo = 1) as media_avaliacao,
          (SELECT COUNT(*) FROM avaliacoes WHERE casa_id = c.id AND ativo = 1) as total_avaliacoes
FROM casas c
          JOIN utilizadores u ON c.proprietario_id = u.id
WHERE c.disponivel = 1 AND c.aprovado = 1
          ORDER BY c.destaque DESC, c.titulo ASC";

// Executa a consulta
$result = $conn->query($query);

// Array para armazenar as casas formatadas
$casas = [];

// Lista completa de comodidades (matching forms)
$comodidade_keys = [
  'wifi',
  'tv',
  'ar_condicionado',
  'aquecimento',
  'cozinha',
  'frigorifico',
  'microondas',
  'maquina_lavar',
  'secador',
  'ferro',
  'estacionamento',
  'piscina',
  'jardim',
  'varanda',
  'churrasqueira',
  'acesso_cadeira_rodas',
  'elevador',
  'aquecedor',
  'ventilador',
  'cacifos'
];

// Percorre todos os resultados da query
while ($casa = $result->fetch_assoc()) {
  // Decodificar e mapear comodidades para assoc array (fix 0/1 display)
  $comodidades_raw = [];
  if (!empty($casa['comodidades'])) {
    $comodidades_raw = json_decode($casa['comodidades'], true);
    if (is_array($comodidades_raw)) {
      $comodidades_raw = array_unique($comodidades_raw); // No duplicates
    } else {
      $comodidades_raw = [];
    }
  }

  // Map to assoc {'key': true} for proper JS display
  $comodidades = [];
  foreach ($comodidade_keys as $key) {
    if (in_array($key, $comodidades_raw)) {
      $comodidades[$key] = true;
    }
  }

  // Formatar dados da casa para exibir
  $fotos = json_decode($casa['fotos'] ?? '[]', true);
  if (!is_array($fotos)) {
    $fotos = [];
  }

  $casas[] = [
    'id' => $casa['id'],
    'titulo' => $casa['titulo'],
    'morada' => $casa['morada'],
    'descricao' => $casa['descricao'],
    'tipo_propriedade' => $casa['custom_tipo'] ?? $casa['tipo_propriedade'],
    'quartos' => $casa['quartos'],
    'casas_de_banho' => $casa['casas_de_banho'],
    'capacidade' => $casa['capacidade'],
    'preco_noite' => $casa['preco_noite'],
    'preco_limpeza' => $casa['preco_limpeza'],
    'taxa_seguranca' => $casa['taxa_seguranca'],
    'hora_checkin' => $casa['hora_checkin'],
    'hora_checkout' => $casa['hora_checkout'],
    'comodidades' => $comodidades,
    'fotos' => $fotos,
    'foto_referencia' => !empty($fotos) ? $fotos[0] : null,
    'proprietario_nome' => $casa['proprietario_nome'],
    'destaque' => $casa['destaque'] ?? 0,
    'media_avaliacao' => $casa['media_avaliacao'] ? round($casa['media_avaliacao'], 1) : null,
    'total_avaliacoes' => (int)$casa['total_avaliacoes']
  ];
}

$pageTitle = 'AlugaTorres | Pesquisa';
$metaDescription = 'Pesquise entre as casas disponíveis';
require_once __DIR__ . '/head.php';
include 'header.php';
include 'sidebar.php';
?>
<!-- Banner Hero de Pesquisa -->
<div class="destinations-hero">
  <h2>Venha conhecer Torres Novas</h2>
  <p>Explore os pontos turísticos da nossa cidade e planeje sua próxima aventura</p>

  <!-- Barra de busca -->
  <div class="search-container">
    <input type="text" id="searchDestino" placeholder="Alguma casa em questão?">
    <button class="search-button"><i class="fas fa-search"></i> Procurar</button>
  </div>
</div>

<!-- Container Principal de Destinos -->
<div class="destinations-container">

  <!-- Seção de Filtros -->
  <div class="filter-section">
    <h3>Filtrar por:</h3>
    <!-- Filtro por Preço -->
    <div class="filter-group">
      <h4>Defina uma faixa de preços</h4>
      <input type="range" min="0" max="500" value="250" class="price-range" id="priceRange">
      <div class="price-labels">
        <span>0€</span>
        <span class="price-value">250€</span>
        <span>500€</span>
      </div>
      <div class="price-input">
        <input type="number" id="priceInput" min="0" max="500" value="250" step="5">
      </div>
    </div>

    <div class="line"></div>

    <!-- Filtro por Número de Quartos -->
    <div class="filter-group">
      <h4>Número de Quartos</h4>
      <label><input type="checkbox" name="quartos" value="1">1 Quarto</label>
      <label><input type="checkbox" name="quartos" value="2">2 Quartos</label>
      <label><input type="checkbox" name="quartos" value="3">3 Quartos</label>
      <label><input type="checkbox" name="quartos" value="4">4 Quartos</label>
      <label><input type="checkbox" name="quartos" value="5">5 Quartos</label>
      <label><input type="checkbox" name="quartos" value="6">5+ Quartos</label>
    </div> 

    <div class="line"></div>

    <div class="filter-group">
      <h4>Tipo de Propriedade</h4>
      <label><input type="checkbox" name="tipo_propriedade" value="casa">Casa</label>
      <label><input type="checkbox" name="tipo_propriedade" value="apartamento">Apartamento</label>
      <label><input type="checkbox" name="tipo_propriedade" value="vivenda">Vivenda</label>
      <label><input type="checkbox" name="tipo_propriedade" value="quarto">Quarto</label>
      <label><input type="checkbox" name="tipo_propriedade" value="quinta">Quinta</label>
      <label><input type="checkbox" name="tipo_propriedade" value="outro">Outro</label>
    </div>

    <div class="line"></div>

    <!-- Filtro por Avaliação -->
    <div class="filter-group">
      <h4>Avaliação</h4>
      <label class="rating-filter">
        <input type="checkbox" name="rating" value="5">
        <span class="rating-stars">
          <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
        </span>
        <span class="rating-text">5 estrelas</span>
      </label>
      <label class="rating-filter">
        <input type="checkbox" name="rating" value="4">
        <span class="rating-stars">
          <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
        </span>
        <span class="rating-text">4+ estrelas</span>
      </label>
      <label class="rating-filter">
        <input type="checkbox" name="rating" value="3">
        <span class="rating-stars">
          <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
        </span>
        <span class="rating-text">3+ estrelas</span>
      </label>
      <label class="rating-filter">
        <input type="checkbox" name="rating" value="2">
        <span class="rating-stars">
          <i class="fas fa-star"></i><i class="fas fa-star"></i>
        </span>
        <span class="rating-text">2+ estrelas</span>
      </label>
    </div>
  </div>

  <!-- Grid de Casas/Destinos -->
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
          data-tipo="<?php echo strtolower($casa['custom_tipo'] ?? $casa['tipo_propriedade']); ?>"
          data-quartos="<?php echo $casa['quartos']; ?>"
          data-freguesia="<?php echo strtolower($casa['quartos']); ?>"
          data-cidade="<?php echo strtolower($casa['cidade'] ?? ''); ?>"
          data-rating="<?php echo $casa['media_avaliacao'] ? floor($casa['media_avaliacao']) : 0; ?>">

          <!-- Imagem do Card -->
          <div class="card-image">
            <?php
            // Verificar se há múltiplas fotos
            $fotos = $casa['fotos'] ?? [];
            $tem_multiplas_fotos = is_array($fotos) && count($fotos) > 1;

            if ($tem_multiplas_fotos): ?>
              <!-- Slider de Fotos -->
              <div class="card-slider" data-casa-id="<?php echo $casa['id']; ?>">
                <div class="slider-images">
                  <?php foreach ($fotos as $index => $foto): ?>
                    <?php
                    // Corrigir caminho da foto se necessário
                    $foto_path = $foto;
                    if (!empty($foto_path) && strpos($foto_path, 'assets/') !== 0) {
                      $foto_path = 'assets/' . ltrim($foto_path, '/');
                    }
                    $caminho_foto = BASE_URL . $foto_path;
                    ?>
                    <img src="<?php echo htmlspecialchars($caminho_foto); ?>"
                      alt="<?php echo htmlspecialchars($casa['titulo']); ?> - Foto <?php echo $index + 1; ?>"
                      data-index="<?php echo $index; ?>">
                  <?php endforeach; ?>
                </div>
                <!-- Botões de navegação -->
                <button class="slider-nav slider-prev" onclick="changeSlide(this, -1)"><i class="fas fa-chevron-left"></i></button>
                <button class="slider-nav slider-next" onclick="changeSlide(this, 1)"><i class="fas fa-chevron-right"></i></button>
                <!-- Dots indicadores -->
                <div class="slider-dots">
                  <?php for ($i = 0; $i < count($fotos); $i++): ?>
                    <button class="slider-dot <?php echo $i === 0 ? 'active' : ''; ?>"
                      onclick="goToSlide(this, <?php echo $i; ?>)"></button>
                  <?php endfor; ?>
                </div>
                <!-- Contador de fotos -->
                <div class="slider-counter">1/<?php echo count($fotos); ?></div>
              </div>
            <?php else: ?>
              <!-- Foto única (comportamento original) -->
              <?php
              $foto_path = $casa['foto_referencia'] ?? '';
              if (!empty($foto_path) && strpos($foto_path, 'assets/') !== 0) {
                $foto_path = 'assets/' . ltrim($foto_path, '/');
              }
              $caminho_foto = !empty($casa['foto_referencia']) ? BASE_URL . $foto_path : BASE_URL . 'assets/style/img/TorresNovas1.jpg';
              ?>
              <img src="<?php echo htmlspecialchars($caminho_foto); ?>"
                alt="<?php echo htmlspecialchars($casa['titulo']); ?>"
                style="width: 100%; height: 200px; object-fit: cover;">
            <?php endif; ?>
            <div class="card-badge"><?php echo ucfirst($casa['custom_tipo'] ?? $casa['tipo_propriedade']); ?></div>
            <?php if (!empty($casa['destaque'])): ?>
              <div class="card-badge-destaque"><i class="fas fa-star"></i> Destaque</div>
            <?php endif; ?>
            <!-- Badge de Avaliação (canto inferior esquerdo) -->
            <div class="card-avaliacao-badge">
              <?php
              $media = $casa['media_avaliacao'] ?? 0;
              $estrelas_inteiras = floor($media);
              if ($estrelas_inteiras > 5) $estrelas_inteiras = 5;
              if ($estrelas_inteiras < 0) $estrelas_inteiras = 0;
              ?>
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="fas fa-star" style="font-size: 10px; <?php echo $i > $estrelas_inteiras ? 'color: #ddd;' : ''; ?>"></i>
              <?php endfor; ?>
              <?php if ($casa['total_avaliacoes'] > 0): ?>
                <span style="margin-left: 3px; font-size: 10px;">(<?php echo $casa['total_avaliacoes']; ?>)</span>
              <?php endif; ?>
            </div>
          </div>


          <!-- Conteúdo do Card -->
          <div class="card-content">
            <h3><?php echo htmlspecialchars($casa['titulo']); ?></h3>
            <p class="card-description"><?php echo htmlspecialchars(substr($casa['descricao'], 0, 100)) . (strlen($casa['descricao']) > 100 ? '...' : ''); ?></p>

            <!-- Características da casa -->
            <div class="card-features">
              <span><i class="fas fa-bed"></i> <?php echo $casa['quartos']; ?> quartos</span>
              <span><i class="fas fa-bath"></i> <?php echo $casa['casas_de_banho']; ?> casas de banho</span>
              <span><i class="fas fa-users"></i> Até <?php echo $casa['capacidade']; ?> pessoas</span>
              <span><i class="fas fa-map-marker-alt"></i> <strong><?php echo htmlspecialchars($casa['morada']); ?></strong></span>
            </div>
            <!-- Ícone de Comodidades - JS INLINED para evitar conflito -->
            <div style="margin-top: 8px; margin-bottom: 8px;">
              <span
                class="amenities-icon show-comodidades"
                data-casa-id="<?php echo $casa['id']; ?>"
                data-comodidades='<?php echo json_encode($casa['comodidades'], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                title="Ver comodidades da casa">
                <i class="fas fa-info-circle"></i> Comodidades
              </span>
            </div>

            <!-- Horários de check-in e check-out -->
            <div class="card-checkin-checkout" style="margin-top: 8px; display: flex; gap: 15px;">
              <span><i class="fas fa-clock"></i> Check-in: <?php echo substr($casa['hora_checkin'], 0, 5); ?></span>
              <span><i class="fas fa-clock"></i> Check-out: <?php echo substr($casa['hora_checkout'], 0, 5); ?></span>
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

<!-- Seção de Newsletter -->
<div class="newsletter-section">
  <div class="newsletter-content">
    <h3>Não Perca Nossas Ofertas!</h3>
    <p>Inscreva-se para receber atualizações de casas disponíveis.</p>

    <!-- Formulário de Newsletter -->
    <form class="newsletter-form" id="newsletter-form">
      <input type="email" name="email" placeholder="Seu melhor email" required>
      <button type="submit">Inscrever-se</button>
    </form>
    <div id="newsletter-message" style="display:none; margin-top: 15px; padding: 10px; border-radius: 5px;"></div>
  </div>
</div>

<div id="comodidadesModal" class="modal">
  <div class="modal-content">
    <button class="modal-close" onclick="closeComodidadesModal()">×</button>
    <h3 id="modalComodidadesTitle" class="modal-title">Comodidades</h3>
    <div id="modalComodidadesContent" class="comodidades-grid"></div>
  </div>
</div>

<!-- Rodapé da Página -->
<?php include 'footer.php'; ?>

<script>
  // Mapeamento de ícones para comodidades
  const iconMap = {
    'wifi': 'fa-wifi',
    'tv': 'fa-tv',
    'ar_condicionado': 'fa-snowflake',
    'aquecimento': 'fa-water',
    'cozinha': 'fa-utensils',
    'frigorifico': 'fa-fish',
    'microondas': 'fa-bolt',
    'maquina_lavar': 'fa-soap',
    'secador': 'fa-wind',
    'ferro': 'fa-tshirt',
    'estacionamento': 'fa-parking',
    'piscina': 'fa-water-ladder',
    'jardim': 'fa-tree',
    'varanda': 'fa-building',
    'churrasqueira': 'fa-fire-flame-curved',
    'acesso_cadeira_rodas': 'fa-wheelchair',
    'elevador': 'fa-elevator',
    'escadas': 'fa-stairs',
    'ventilador': 'fa-fan',
    'animais_permitidos': 'fa-paw'
  };

  // Função para abrir o modal
  function openComodidadesModal(casaId, comodidades) {
    const modal = document.getElementById('comodidadesModal');
    const title = document.getElementById('modalComodidadesTitle');
    const content = document.getElementById('modalComodidadesContent');

    if (!modal || !title || !content) {
      console.error('Elementos do modal não encontrados');
      return;
    }

    title.textContent = `Comodidades da Casa #${casaId}`;

    let html = '';
    let hasItems = false;

    for (const [key, value] of Object.entries(comodidades)) {
      if (value) {
        hasItems = true;
        const icon = iconMap[key] || 'fa-check-circle';
        const label = key
          .replace(/_/g, ' ')
          .replace(/\b\w/g, l => l.toUpperCase());

        html += `
        <div class="comodidade-item">
          <i class="fas ${icon} comodidade-icon"></i>
          <div class="comodidade-label">${label}</div>
        </div>
      `;
      }
    }

    content.innerHTML = html || '<p style="text-align:center;color:#666;padding:20px;">Nenhuma comodidade listada.</p>';

    // Mostrar o modal usando a classe .show (conforme seu CSS)
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
  }

  // Função para fechar o modal
  function closeComodidadesModal() {
    const modal = document.getElementById('comodidadesModal');
    if (modal) {
      modal.classList.remove('show');
      document.body.style.overflow = 'auto';
    }
  }

  // Inicialização quando o DOM estiver pronto
  document.addEventListener('DOMContentLoaded', function() {
    // Adicionar Font Awesome se necessário
    if (!document.querySelector('link[href*="font-awesome"]') && !document.querySelector('link[href*="fa"]')) {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
      document.head.appendChild(link);
    }

    // Configurar o modal
    const modal = document.getElementById('comodidadesModal');

    // Fechar modal ao clicar fora
    if (modal) {
      modal.addEventListener('click', function(e) {
        if (e.target === modal) {
          closeComodidadesModal();
        }
      });
    }

    // Fechar modal com tecla ESC
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeComodidadesModal();
      }
    });

    // Configurar os botões de comodidades
    document.querySelectorAll('.show-comodidades').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault(); // Prevenir comportamento padrão
        e.stopPropagation(); // Parar propagação do evento

        const casaId = this.dataset.casaId;

        // Verificar se os dados existem e são válidos
        if (!this.dataset.comodidades) {
          console.error('Dados de comodidades não encontrados');
          return;
        }

        try {
          const comodidades = JSON.parse(this.dataset.comodidades);
          openComodidadesModal(casaId, comodidades);
        } catch (error) {
          console.error('Erro ao processar comodidades:', error);
        }
      });
    });

    // Slider de Fotos
    document.querySelectorAll('.card-slider').forEach(slider => {
      const images = slider.querySelectorAll('.slider-images img');
      const dots = slider.querySelectorAll('.slider-dot');
      const counter = slider.querySelector('.slider-counter');

      if (images.length > 0) {
        slider.setAttribute('data-current', '0');
        if (counter) {
          counter.textContent = `1/${images.length}`;
        }
      }

      // Touch/swipe support
      let touchStartX = 0;
      let touchEndX = 0;

      slider.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
      }, {
        passive: true
      });

      slider.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
      }, {
        passive: true
      });

      function handleSwipe() {
        const swipeThreshold = 50;
        const diff = touchStartX - touchEndX;

        if (Math.abs(diff) > swipeThreshold) {
          if (diff > 0) {
            changeSlide(slider.querySelector('.slider-next'), 1);
          } else {
            changeSlide(slider.querySelector('.slider-prev'), -1);
          }
        }
      }
    });
  });

  // Funções do slider
  function changeSlide(button, direction) {
    if (!button) return;

    const slider = button.closest('.card-slider');
    if (!slider) return;

    const images = slider.querySelectorAll('.slider-images img');
    const dots = slider.querySelectorAll('.slider-dot');
    const counter = slider.querySelector('.slider-counter');

    if (images.length === 0) return;

    let currentIndex = parseInt(slider.getAttribute('data-current') || '0');
    const totalSlides = images.length;

    let newIndex = currentIndex + direction;
    if (newIndex < 0) newIndex = totalSlides - 1;
    if (newIndex >= totalSlides) newIndex = 0;

    updateSlider(slider, images, dots, counter, newIndex);
  }

  function goToSlide(button, index) {
    if (!button) return;

    const slider = button.closest('.card-slider');
    if (!slider) return;

    const images = slider.querySelectorAll('.slider-images img');
    const dots = slider.querySelectorAll('.slider-dot');
    const counter = slider.querySelector('.slider-counter');

    updateSlider(slider, images, dots, counter, index);
  }

  function updateSlider(slider, images, dots, counter, newIndex) {
    const sliderImages = slider.querySelector('.slider-images');
    if (sliderImages) {
      sliderImages.style.transform = `translateX(-${newIndex * 100}%)`;
    }

    dots.forEach((dot, i) => {
      dot.classList.toggle('active', i === newIndex);
    });

    if (counter) {
      counter.textContent = `${newIndex + 1}/${images.length}`;
    }

    slider.setAttribute('data-current', newIndex);
  }
</script>

</body>

</html>