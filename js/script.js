// Funcionalidade do Slider
document.addEventListener("DOMContentLoaded", function () {
  const slider = document.querySelector(".slider");
  const slides = document.querySelectorAll(".slide");
  const prevButton = document.querySelector(".prev-button");
  const nextButton = document.querySelector(".next-button");
  const dotsContainer = document.querySelector(".slider-dots");

  // Verificar se elementos do slider existem
  if (!slider || slides.length === 0 || !dotsContainer) {
    return; // Sair se não estiver na página com slider
  }

  let currentSlide = 0;
  let slideInterval;
  const intervalTime = 4000; // Tempo entre slides (4 segundos)

  // Criar dots para cada slide
  slides.forEach((_, index) => {
    const dot = document.createElement("div");
    dot.classList.add("dot");
    if (index === 0) dot.classList.add("active");
    dot.addEventListener("click", () => goToSlide(index));
    dotsContainer.appendChild(dot);
  });

  // Inicializar o primeiro slide
  slides[0].classList.add("active");

  // Função para ir para um slide específico
  function goToSlide(index) {
    slides[currentSlide].classList.remove("active");
    document.querySelectorAll(".dot")[currentSlide].classList.remove("active");

    currentSlide = index;

    if (currentSlide >= slides.length) currentSlide = 0;
    if (currentSlide < 0) currentSlide = slides.length - 1;

    slides[currentSlide].classList.add("active");
    document.querySelectorAll(".dot")[currentSlide].classList.add("active");
  }

  // Função para avançar para o próximo slide
  function nextSlide() {
    goToSlide(currentSlide + 1);
  }

  // Função para voltar ao slide anterior
  function prevSlide() {
    goToSlide(currentSlide - 1);
  }

  // Event listeners para os botões
  if (prevButton) prevButton.addEventListener("click", prevSlide);
  if (nextButton) nextButton.addEventListener("click", nextSlide);

  // Iniciar slideshow automático
  function startSlideshow() {
    if (slideInterval) {
      clearInterval(slideInterval);
    }
    slideInterval = setInterval(nextSlide, intervalTime);
  }

  // Pausar slideshow quando o mouse estiver sobre o slider
  slider.addEventListener("mouseenter", () => {
    clearInterval(slideInterval);
  });

  // Retomar slideshow quando o mouse sair do slider
  slider.addEventListener("mouseleave", startSlideshow);

  // Iniciar o slideshow
  startSlideshow();
});

// Adiciona classe active ao link da página atual
document.addEventListener("DOMContentLoaded", function () {
  const currentLocation = location.pathname;
  const navLinks = document.querySelectorAll("nav a");
  navLinks.forEach((link) => {
    if (link.getAttribute("href") === currentLocation.split("/").pop()) {
      link.classList.add("active");
    }
  });

  // Inicia a animação dos cards de recursos
  animateFeatureCards();
});

// Animação suave para elementos
function animateFeatureCards() {
  const cards = document.querySelectorAll(".feature-card");
  cards.forEach((card, index) => {
    setTimeout(() => {
      card.style.opacity = "1";
      card.style.transform = "translateY(0)";
    }, index * 200);
  });
}

// Hamburger menu functionality
document.addEventListener("DOMContentLoaded", function () {
  const hamburger = document.querySelector(".hamburger");
  const nav = document.querySelector(".main-nav");
  const navLinks = document.querySelectorAll(".main-nav a");

  if (hamburger && nav) {
    hamburger.addEventListener("click", function (event) {
      event.stopPropagation();
      hamburger.classList.toggle("active");
      nav.classList.toggle("active");
    });

    // Close menu when clicking on a nav link
    navLinks.forEach((link) => {
      link.addEventListener("click", function (event) {
        event.stopPropagation();
        hamburger.classList.remove("active");
        nav.classList.remove("active");
      });
    });

    // Close menu when clicking outside
    document.addEventListener("click", function (event) {
      if (!hamburger.contains(event.target) && !nav.contains(event.target)) {
        hamburger.classList.remove("active");
        nav.classList.remove("active");
      }
    });
  }
});

// Adiciona animação ao scroll
window.addEventListener("scroll", function () {
  const header = document.querySelector("header");
  if (window.scrollY > 50) {
    header.classList.add("scrolled");
  } else {
    header.classList.remove("scrolled");
  }
});

// Funcionalidade de busca e filtros para pesquisa.php
document.addEventListener("DOMContentLoaded", function () {
  // Funcionalidade de busca por texto
  const searchInput = document.getElementById("searchDestino");
  const searchButton = document.querySelector(".search-button");

  // Funcionalidade de filtros
  const priceRange = document.getElementById("priceRange");
  const priceValue = document.querySelector(".price-value");
  const filterCheckboxes = document.querySelectorAll(
    'input[name="freguesia"], input[name="type"]',
  );

  // Atualizar valor do preço em tempo real
  if (priceRange && priceValue) {
    priceRange.addEventListener("input", function () {
      priceValue.textContent = this.value + "€";
    });
  }

  // Função para filtrar casas
  function filterCasas() {
    const cards = document.querySelectorAll(".destination-card");
    const searchTerm = searchInput
      ? searchInput.value.toLowerCase().trim()
      : "";
    const maxPrice = priceRange ? parseInt(priceRange.value) : 500;

    // Obter Freguesias selecionadas (normalizadas para lowercase)
    const selectedFreguesias = Array.from(
      document.querySelectorAll('input[name="freguesia"]:checked'),
    ).map((cb) => cb.value.toLowerCase().trim());

    // Obter tipos selecionados
    const selectedTypes = Array.from(
      document.querySelectorAll('input[name="type"]:checked'),
    ).map((cb) => cb.value.toLowerCase().trim());

    let visibleCount = 0;

    cards.forEach((card) => {
      const title = card.querySelector("h3").textContent.toLowerCase();
      const description = card.querySelector(".card-description")
        ? card.querySelector(".card-description").textContent.toLowerCase()
        : "";

      // Usar dataset.preco (não dataset.price)
      const preco = parseFloat(card.dataset.preco) || 0;
      const cidade = (card.dataset.cidade || "").toLowerCase();
      const tipo = (card.dataset.tipo || "").toLowerCase();
      const freguesia = (card.dataset.freguesia || "").toLowerCase().trim();

      // Debug (remover em produção)
      console.log("Card:", {
        title: title.substring(0, 20),
        preco,
        freguesia,
        tipo,
        cidade,
      });

      // Filtro de busca por texto (título, descrição ou cidade)
      const matchesSearch =
        !searchTerm ||
        title.includes(searchTerm) ||
        description.includes(searchTerm) ||
        cidade.includes(searchTerm);

      // Filtro de preço
      const matchesPrice = preco <= maxPrice;

      // Filtro de freguesia (comparação case-insensitive)
      const matchesFreguesia =
        selectedFreguesias.length === 0 ||
        selectedFreguesias.some(
          (sf) => freguesia.includes(sf) || sf.includes(freguesia),
        );

      // Filtro de tipo
      const matchesType =
        selectedTypes.length === 0 ||
        selectedTypes.some((st) => tipo.includes(st) || st.includes(tipo));

      // Aplicar filtros
      if (matchesSearch && matchesPrice && matchesFreguesia && matchesType) {
        card.style.display = "block";
        card.classList.add("card-visible");
        visibleCount++;
      } else {
        card.style.display = "none";
        card.classList.remove("card-visible");
      }
    });

    // Gerenciar mensagem de "nenhum resultado"
    const grid = document.querySelector(".destinations-grid");
    let noResults = document.querySelector(".no-results");

    if (visibleCount === 0 && cards.length > 0) {
      if (!noResults) {
        noResults = document.createElement("div");
        noResults.className = "no-results";
        noResults.style.cssText =
          "grid-column: 1 / -1; text-align: center; padding: 50px;";
        noResults.innerHTML = `
          <i class="fas fa-search" style="font-size: 3em; color: #ccc; margin-bottom: 20px;"></i>
          <h3>Nenhuma casa encontrada</h3>
          <p>Não há casas que correspondam aos seus filtros. Tente ajustar os critérios de busca.</p>
        `;
        grid.appendChild(noResults);
      }
    } else if (noResults) {
      noResults.remove();
    }

    console.log("Filtro aplicado:", {
      visibleCount,
      total: cards.length,
      maxPrice,
      selectedFreguesias,
    });
  }

  // Event listeners para filtros
  if (searchInput) {
    searchInput.addEventListener("input", filterCasas);
  }

  if (searchButton) {
    searchButton.addEventListener("click", function (e) {
      e.preventDefault();
      filterCasas();
    });
  }

  if (priceRange) {
    priceRange.addEventListener("input", filterCasas);
  }

  filterCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", filterCasas);
  });

  // Executar filtro inicial para garantir estado correto
  if (document.querySelector(".destination-card")) {
    filterCasas();
  }

  // Atualizar ano no footer
  const anoElement = document.getElementById("ano");
  if (anoElement) {
    anoElement.textContent = new Date().getFullYear();
  }
});

// Validação do formulário de adicionar casa
document.addEventListener("DOMContentLoaded", function () {
  const casaForm = document.querySelector(".casa-form");
  if (casaForm) {
    casaForm.addEventListener("submit", function (e) {
      const preco = document.querySelector('input[name="preco_noite"]').value;
      if (parseFloat(preco) <= 0) {
        e.preventDefault();
        if (typeof AlugaTorresNotifications !== "undefined") {
          AlugaTorresNotifications.error(
            "O preço por noite deve ser maior que zero.",
          );
        } else {
          alert("O preço por noite deve ser maior que zero.");
        }
        return false;
      }

      const capacidade = document.querySelector(
        'input[name="capacidade"]',
      ).value;
      if (parseInt(capacidade) < 1) {
        e.preventDefault();
        if (typeof AlugaTorresNotifications !== "undefined") {
          AlugaTorresNotifications.error(
            "A capacidade deve ser pelo menos 1 hóspede.",
          );
        } else {
          alert("A capacidade deve ser pelo menos 1 hóspede.");
        }
        return false;
      }

      return true;
    });
  }
});

document.addEventListener("DOMContentLoaded", () => {
  const select = document.querySelector('select[name="tipo_propriedade"]');
  const campoOutro = document.getElementById("campo-outro");

  // Verificar se elementos existem
  if (!select || !campoOutro) {
    return; // Sair se não estiver na página com este formulário
  }

  const inputOutro = campoOutro.querySelector('input[name="outro_texto"]');

  if (!inputOutro) {
    return; // Sair se input não existir
  }

  function controlarCampoOutro() {
    if (select.value === "outro") {
      campoOutro.style.display = "block";
      inputOutro.required = true;
    } else {
      campoOutro.style.display = "none";
      inputOutro.required = false;
      inputOutro.value = "";
    }
  }

  // Dispara quando o usuário muda o select
  select.addEventListener("change", controlarCampoOutro);

  // Garante estado correto ao carregar a página (importante com PHP e post)
  controlarCampoOutro();
});

// Navegação suave e validação para perfil.php
document.addEventListener("DOMContentLoaded", function () {
  // Navegação suave entre seções
  document.querySelectorAll(".profile-menu a").forEach((link) => {
    link.addEventListener("click", function (e) {
      const href = this.getAttribute("href");

      // Só aplicar comportamento de scroll suave para âncoras internas (links que começam com #)
      if (href.startsWith("#")) {
        e.preventDefault();
        const targetId = href.substring(1);

        // Remover active de todos
        document.querySelectorAll(".profile-menu a").forEach((a) => {
          a.classList.remove("active");
        });

        // Adicionar active ao clicado
        this.classList.add("active");

        // Scroll para seção
        const targetSection = document.getElementById(targetId);
        if (targetSection) {
          window.scrollTo({
            top: targetSection.offsetTop - 20,
            behavior: "smooth",
          });
        }
      }
      // Se não começar com #, deixa o link navegar normalmente para a página externa
    });
  });

  // Form validation
  const passwordForm = document.querySelector(
    'form[action*="change_password"]',
  );
  if (passwordForm) {
    passwordForm.addEventListener("submit", function (e) {
      const novaSenha = this.querySelector('input[name="nova_senha"]').value;
      const confirmarSenha = this.querySelector(
        'input[name="confirmar_senha"]',
      ).value;

      if (novaSenha !== confirmarSenha) {
        e.preventDefault();
        if (typeof AlugaTorresNotifications !== "undefined") {
          AlugaTorresNotifications.error("As senhas não coincidem!");
        } else {
          alert("As senhas não coincidem!");
        }
        return false;
      }

      if (novaSenha.length < 6) {
        e.preventDefault();
        if (typeof AlugaTorresNotifications !== "undefined") {
          AlugaTorresNotifications.error(
            "A senha deve ter pelo menos 6 caracteres!",
          );
        } else {
          alert("A senha deve ter pelo menos 6 caracteres!");
        }
        return false;
      }

      return true;
    });
  }
});

// Modal e filtros para reservas.php
document.addEventListener("DOMContentLoaded", function () {
  const detailsModal = document.getElementById("detailsModal");
  const modalDetailsContent = document.getElementById("modalDetailsContent");

  if (detailsModal) {
    // Função para mostrar detalhes da reserva
    window.showReservaDetails = function (reserva) {
      const statusMap = {
        pendente: "Pendente",
        confirmada: "Confirmada",
        concluida: "Concluída",
        cancelada: "Cancelada",
        rejeitada: "Rejeitada",
      };

      const content = `
        <div class="modal-detalhes">
          <div class="detalhe-item">
            <div class="detalhe-label">ID da Reserva</div>
            <div class="detalhe-valor">#${String(reserva.id).padStart(
              5,
              "0",
            )}</div>
          </div>
          <div class="detalhe-item">
            <div class="detalhe-label">Propriedade</div>
            <div class="detalhe-valor">${reserva.casa_titulo}</div>
          </div>
          <div class="detalhe-item">
            <div class="detalhe-label">Check-in</div>
            <div class="detalhe-valor">${formatDate(reserva.data_checkin)}</div>
          </div>
          <div class="detalhe-item">
            <div class="detalhe-label">Check-out</div>
            <div class="detalhe-valor">${formatDate(
              reserva.data_checkout,
            )}</div>
          </div>
          <div class="detalhe-item">
            <div class="detalhe-label">Noites</div>
            <div class="detalhe-valor">${reserva.noites}</div>
          </div>
          <div class="detalhe-item">
            <div class="detalhe-label">Hóspedes</div>
            <div class="detalhe-valor">${reserva.total_hospedes}</div>
          </div>
          <div class="detalhe-item">
            <div class="detalhe-label">Status</div>
            <div class="detalhe-valor">${
              statusMap[reserva.status] || reserva.status
            }</div>
          </div>
          <div class="detalhe-item">
            <div class="detalhe-label">Data da Reserva</div>
            <div class="detalhe-valor">${formatDateTime(
              reserva.data_reserva,
            )}</div>
          </div>
        </div>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
          <h4>Valores</h4>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div>Preço por noite: <strong>${parseFloat(
              reserva.preco_noite,
            ).toFixed(2)}€</strong></div>
            <div>Subtotal: <strong>${parseFloat(reserva.subtotal).toFixed(
              2,
            )}€</strong></div>
            <div>Taxa de limpeza: <strong>${parseFloat(
              reserva.taxa_limpeza,
            ).toFixed(2)}€</strong></div>
            <div>Taxa de segurança: <strong>${parseFloat(
              reserva.taxa_seguranca,
            ).toFixed(2)}€</strong></div>
            <div style="grid-column: 1 / -1; border-top: 1px solid #ddd; padding-top: 10px; margin-top: 5px;">
              <strong>Total: ${parseFloat(reserva.total).toFixed(2)}€</strong>
            </div>
          </div>
        </div>
        
        ${
          reserva.notas
            ? `
          <div style="margin-bottom: 20px;">
            <h4>Notas</h4>
            <p>${reserva.notas}</p>
          </div>
        `
            : ""
        }
      `;

      modalDetailsContent.innerHTML = content;
      detailsModal.style.display = "flex";
    };

    // Função para fechar modal
    window.closeModal = function () {
      detailsModal.style.display = "none";
    };

    // Fechar modal ao clicar fora
    detailsModal.addEventListener("click", function (e) {
      if (e.target === detailsModal) {
        closeModal();
      }
    });

    // Funções auxiliares
    function formatDate(dateStr) {
      const date = new Date(dateStr);
      return date.toLocaleDateString("pt-PT");
    }

    function formatDateTime(dateTimeStr) {
      const date = new Date(dateTimeStr);
      return date.toLocaleString("pt-PT");
    }

    // Filtros
    window.filterByStatus = function (status) {
      const url = new URL(window.location.href);
      url.searchParams.set("filtro", status);
      window.location.href = url.toString();
    };

    window.filterByCasa = function (casaId) {
      const url = new URL(window.location.href);
      if (casaId) {
        url.searchParams.set("casa_id", casaId);
      } else {
        url.searchParams.delete("casa_id");
      }
      window.location.href = url.toString();
    };

    window.applyFilters = function () {
      const periodo = document.getElementById("periodoFilter").value;
      const casaId = document.getElementById("casaFilter")
        ? document.getElementById("casaFilter").value
        : null;
      if (casaId) {
        filterByCasa(casaId);
      }
    };
  }
});

// Formulário de contato para contactos.php
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("form-contacto");

  if (form) {
    form.addEventListener("submit", async function (e) {
      e.preventDefault();

      const formData = new FormData(form);

      try {
        const res = await fetch("contactos.php", {
          method: "POST",
          body: formData,
        });

        const json = await res.json();

        if (res.ok && json.status === "success") {
          // Usar toast notification
          if (typeof AlugaTorresNotifications !== "undefined") {
            AlugaTorresNotifications.success(
              json.message || "Mensagem enviada com sucesso!",
            );
          }
          form.reset();
        } else {
          // Usar toast notification
          if (typeof AlugaTorresNotifications !== "undefined") {
            AlugaTorresNotifications.error(
              json.message || "Erro ao enviar mensagem.",
            );
          }
        }
      } catch (err) {
        // Usar toast notification
        if (typeof AlugaTorresNotifications !== "undefined") {
          AlugaTorresNotifications.error(
            "Erro de comunicação. Tente novamente mais tarde.",
          );
        }
      }
    });
  }
});

// Filtros e modal para minhas_casas.php
document.addEventListener("DOMContentLoaded", function () {
  // Filtros
  const filtros = document.querySelectorAll(".filtro-btn");
  const casas = document.querySelectorAll(".casa-card");

  filtros.forEach((filtro) => {
    filtro.addEventListener("click", function () {
      // Remover classe active de todos
      filtros.forEach((f) => f.classList.remove("active"));
      // Adicionar ao clicado
      this.classList.add("active");

      const filtroSelecionado = this.dataset.filtro;

      casas.forEach((casa) => {
        switch (filtroSelecionado) {
          case "todas":
            casa.style.display = "block";
            break;
          case "disponiveis":
            casa.style.display =
              casa.dataset.disponivel === "sim" ? "block" : "none";
            break;
          case "indisponiveis":
            casa.style.display =
              casa.dataset.disponivel === "nao" ? "block" : "none";
            break;
          case "destaque":
            casa.style.display =
              casa.dataset.destaque === "sim" ? "block" : "none";
            break;
        }
      });
    });
  });

  // Modal
  const deleteModal = document.getElementById("deleteModal");
  const deleteForm = document.getElementById("deleteForm");
  const modalMessage = document.getElementById("modalMessage");
  const deleteCasaId = document.getElementById("deleteCasaId");

  if (deleteModal) {
    window.showDeleteModal = function (id, titulo) {
      modalMessage.textContent = `Tem certeza que deseja eliminar a propriedade "${titulo}"?`;
      deleteCasaId.value = id;
      deleteModal.style.display = "flex";
    };

    window.closeModal = function () {
      deleteModal.style.display = "none";
    };

    // Fechar modal ao clicar fora
    deleteModal.addEventListener("click", function (e) {
      if (e.target === deleteModal) {
        closeModal();
      }
    });

    // Confirmar eliminação
    deleteForm.addEventListener("submit", function (e) {
      if (!confirm("Esta ação é irreversível. Continuar?")) {
        e.preventDefault();
        closeModal();
      }
    });
  }
});

// Funcionalidade do Sidebar - Versão Robusta
(function () {
  "use strict";

  function initSidebar() {
    console.log("[Sidebar] === INICIANDO SIDEBAR ===");

    const profileToggle = document.getElementById("profile-toggle");
    const sidebar = document.getElementById("sidebar");
    const sidebarOverlay = document.getElementById("sidebar-overlay");
    const closeSidebar = document.getElementById("close-sidebar");

    console.log("[Sidebar] profile-toggle:", profileToggle);
    console.log("[Sidebar] sidebar:", sidebar);
    console.log("[Sidebar] sidebar-overlay:", sidebarOverlay);
    console.log("[Sidebar] close-sidebar:", closeSidebar);

    // Verificar se elementos essenciais existem
    if (!sidebar) {
      console.log("[Sidebar] ERRO: Elemento #sidebar não encontrado");
      return;
    }
    if (!sidebarOverlay) {
      console.log("[Sidebar] ERRO: Elemento #sidebar-overlay não encontrado");
      return;
    }

    console.log("[Sidebar] Elementos encontrados, configurando eventos...");

    // Abrir sidebar ao clicar no botão de perfil
    if (profileToggle) {
      console.log("[Sidebar] Configurando click no profile-toggle");
      profileToggle.addEventListener("click", function (e) {
        console.log("[Sidebar] Click no profile-toggle!");
        e.preventDefault();
        e.stopPropagation();
        sidebar.classList.toggle("active");
        sidebarOverlay.classList.toggle("active");
        console.log(
          "[Sidebar] Estado ativo:",
          sidebar.classList.contains("active"),
        );
      });
    } else {
      console.log("[Sidebar] AVISO: profile-toggle não encontrado");
    }

    // Fechar sidebar ao clicar no botão de fechar
    if (closeSidebar) {
      console.log("[Sidebar] Configurando click no close-sidebar");
      closeSidebar.addEventListener("click", function (e) {
        console.log("[Sidebar] Click no close-sidebar!");
        e.preventDefault();
        e.stopPropagation();
        sidebar.classList.remove("active");
        sidebarOverlay.classList.remove("active");
      });
    }

    // Fechar sidebar ao clicar no overlay
    sidebarOverlay.addEventListener("click", function () {
      console.log("[Sidebar] Click no overlay");
      sidebar.classList.remove("active");
      sidebarOverlay.classList.remove("active");
    });

    // Fechar sidebar ao clicar fora (no documento)
    document.addEventListener("click", function (e) {
      if (
        sidebar.classList.contains("active") &&
        !sidebar.contains(e.target) &&
        (!profileToggle || !profileToggle.contains(e.target))
      ) {
        console.log("[Sidebar] Click fora, fechando sidebar");
        sidebar.classList.remove("active");
        sidebarOverlay.classList.remove("active");
      }
    });

    // Fechar sidebar ao pressionar ESC
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && sidebar.classList.contains("active")) {
        console.log("[Sidebar] ESC pressionado, fechando sidebar");
        sidebar.classList.remove("active");
        sidebarOverlay.classList.remove("active");
      }
    });

    console.log("[Sidebar] === SIDEBAR INICIALIZADO COM SUCESSO ===");
  }

  // Inicializar quando DOM estiver pronto
  if (document.readyState === "loading") {
    console.log("[Sidebar] DOM ainda carregando, aguardando DOMContentLoaded");
    document.addEventListener("DOMContentLoaded", initSidebar);
  } else {
    console.log("[Sidebar] DOM já pronto, inicializando imediatamente");
    initSidebar();
  }
})();

// Mostrar/ocultar campo "outro" tipo de propriedade
document.addEventListener("DOMContentLoaded", function () {
  const selectTipo = document.querySelector('select[name="tipo_propriedade"]');
  if (selectTipo) {
    selectTipo.addEventListener("change", function () {
      const campoOutro = document.getElementById("campo-outro");
      if (campoOutro) {
        campoOutro.style.display = this.value === "outro" ? "block" : "none";
      }
    });
    // Disparar evento inicial para definir estado correto
    selectTipo.dispatchEvent(new Event("change"));
  }
});

// Função para combinar hora e minuto nos campos hidden (check-in/check-out)
document.addEventListener("DOMContentLoaded", function () {
  const checkinHora = document.querySelector(
    'select[name="hora_checkin_hora"]',
  );
  if (!checkinHora) return; // Sair se não estiver na página correta

  function updateTimeFields() {
    // Check-in
    const checkinHora = document.querySelector(
      'select[name="hora_checkin_hora"]',
    ).value;
    const checkinMinuto = document.querySelector(
      'select[name="hora_checkin_minuto"]',
    ).value;
    const checkinHidden = document.getElementById("hora_checkin_hidden");
    if (checkinHidden) {
      checkinHidden.value = checkinHora.padStart(2, "0") + ":" + checkinMinuto;
    }

    // Check-out
    const checkoutHora = document.querySelector(
      'select[name="hora_checkout_hora"]',
    ).value;
    const checkoutMinuto = document.querySelector(
      'select[name="hora_checkout_minuto"]',
    ).value;
    const checkoutHidden = document.getElementById("hora_checkout_hidden");
    if (checkoutHidden) {
      checkoutHidden.value =
        checkoutHora.padStart(2, "0") + ":" + checkoutMinuto;
    }
  }

  // Adicionar event listeners aos selects de hora
  document
    .querySelectorAll(
      'select[name*="hora_checkin"], select[name*="hora_checkout"]',
    )
    .forEach((select) => {
      select.addEventListener("change", updateTimeFields);
    });

  // Inicializar valores
  updateTimeFields();
});

// Atualizar ano no footer
document.addEventListener("DOMContentLoaded", function () {
  const anoElement = document.getElementById("ano");
  if (anoElement) {
    anoElement.textContent = new Date().getFullYear();
  }
});

// Verificar se o sistema de notificações carregou corretamente
document.addEventListener("DOMContentLoaded", function () {
  if (typeof AlugaTorresNotifications === "undefined") {
    console.error("[AlugaTorres] ERRO: Sistema de notificações não carregou!");
  } else {
    console.log("[AlugaTorres] Sistema de notificações pronto");
  }
});

// Gráfico de Reservas (apenas se Chart.js estiver disponível e canvas existir)
document.addEventListener("DOMContentLoaded", function () {
  const canvas = document.getElementById("reservasChart");
  if (!canvas || typeof Chart === "undefined") return;

  // Ler dados dos data attributes
  const pendentes = parseInt(canvas.dataset.pendentes) || 0;
  const confirmadas = parseInt(canvas.dataset.confirmadas) || 0;
  const concluidas = parseInt(canvas.dataset.concluidas) || 0;
  const canceladas = parseInt(canvas.dataset.canceladas) || 0;

  const ctx = canvas.getContext("2d");
  new Chart(ctx, {
    type: "doughnut",
    data: {
      labels: ["Pendentes", "Confirmadas", "Concluídas", "Canceladas"],
      datasets: [
        {
          data: [pendentes, confirmadas, concluidas, canceladas],
          backgroundColor: ["#ffc107", "#28a745", "#17a2b8", "#dc3545"],
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
    },
  });
});

// Definir perfilTipoUsuario a partir de data attribute
document.addEventListener("DOMContentLoaded", function () {
  const body = document.body;
  if (body && body.dataset.tipoUsuario) {
    window.perfilTipoUsuario = body.dataset.tipoUsuario;
  }
});

// Admin: Modal de Rejeição e Exportação CSV
document.addEventListener("DOMContentLoaded", function () {
  // Modal de Rejeição
  window.mostrarRejeicao = function (casaId) {
    const modal = document.getElementById("modalRejeicao");
    const input = document.getElementById("rejeitarCasaId");
    if (modal && input) {
      input.value = casaId;
      modal.classList.add("active");
    }
  };

  window.fecharModal = function () {
    const modal = document.getElementById("modalRejeicao");
    if (modal) {
      modal.classList.remove("active");
    }
  };

  // Fechar modal ao clicar fora
  const modalRejeicao = document.getElementById("modalRejeicao");
  if (modalRejeicao) {
    modalRejeicao.addEventListener("click", function (e) {
      if (e.target === this) {
        fecharModal();
      }
    });
  }

  // Exportar para CSV
  window.exportTableToCSV = function (filename) {
    const table = document.getElementById("casasTable");
    if (!table) return;

    const csv = [];
    const rows = table.querySelectorAll("tr");

    for (let i = 0; i < rows.length; i++) {
      const row = [];
      const cols = rows[i].querySelectorAll("td, th");

      for (let j = 0; j < cols.length - 1; j++) {
        row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
      }

      csv.push(row.join(","));
    }

    downloadCSV(csv.join("\n"), filename);
  };

  function downloadCSV(csv, filename) {
    const csvFile = new Blob([csv], {
      type: "text/csv;charset=utf-8;",
    });
    const downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
  }
});

// Admin: Gráficos de Estatísticas (com data attributes)
document.addEventListener("DOMContentLoaded", function () {
  if (typeof Chart === "undefined") return;

  // Gráfico de Reservas por Mês
  const reservasCanvas = document.getElementById("reservasChart");
  if (reservasCanvas && reservasCanvas.dataset.reservas) {
    const meses = [
      "Jan",
      "Fev",
      "Mar",
      "Abr",
      "Mai",
      "Jun",
      "Jul",
      "Ago",
      "Set",
      "Out",
      "Nov",
      "Dez",
    ];
    const reservasData = JSON.parse(reservasCanvas.dataset.reservas);

    new Chart(reservasCanvas, {
      type: "bar",
      data: {
        labels: meses,
        datasets: [
          {
            label: "Reservas",
            data: reservasData,
            backgroundColor: "rgba(102, 126, 234, 0.8)",
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
      },
    });
  }

  // Gráfico de Receitas por Mês
  const receitasCanvas = document.getElementById("receitasChart");
  if (receitasCanvas && receitasCanvas.dataset.receitas) {
    const meses = [
      "Jan",
      "Fev",
      "Mar",
      "Abr",
      "Mai",
      "Jun",
      "Jul",
      "Ago",
      "Set",
      "Out",
      "Nov",
      "Dez",
    ];
    const receitasData = JSON.parse(receitasCanvas.dataset.receitas);

    new Chart(receitasCanvas, {
      type: "line",
      data: {
        labels: meses,
        datasets: [
          {
            label: "Receitas (€)",
            data: receitasData,
            borderColor: "#28a745",
            backgroundColor: "rgba(40, 167, 69, 0.1)",
            fill: true,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
      },
    });
  }

  // Gráfico de Utilizadores por Mês
  const utilizadoresCanvas = document.getElementById("utilizadoresChart");
  if (utilizadoresCanvas && utilizadoresCanvas.dataset.utilizadores) {
    const meses = [
      "Jan",
      "Fev",
      "Mar",
      "Abr",
      "Mai",
      "Jun",
      "Jul",
      "Ago",
      "Set",
      "Out",
      "Nov",
      "Dez",
    ];
    const utilizadoresData = JSON.parse(
      utilizadoresCanvas.dataset.utilizadores,
    );

    new Chart(utilizadoresCanvas, {
      type: "bar",
      data: {
        labels: meses,
        datasets: [
          {
            label: "Novos Utilizadores",
            data: utilizadoresData,
            backgroundColor: "rgba(255, 193, 7, 0.8)",
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
      },
    });
  }
});

// Sistema de Reservas (arrendatario/reservas.php)
document.addEventListener("DOMContentLoaded", function () {
  // Verificar se estamos na página de reservas
  const reservasContent = document.getElementById("reservas-content");
  if (!reservasContent) return;

  // Configuração - ler de data attributes do body se disponíveis
  const API_URL =
    window.reservasConfig?.apiUrl || "../backend_api/api_reservas.php";
  const USER_TYPE = window.reservasConfig?.userType || "arrendatario";

  let currentFiltro = "todas";
  let currentCasaId = "";
  let currentPeriodo = "todos";

  // Inicialização
  loadReservas();
  if (USER_TYPE === "proprietario") {
    loadCasas();
  }

  // Carregar reservas
  async function loadReservas() {
    reservasContent.innerHTML =
      '<div class="loading-spinner"><i class="fas fa-spinner"></i></div>';

    try {
      const params = new URLSearchParams({
        action: "list",
        tipo: USER_TYPE === "proprietario" ? "proprietario" : "minhas",
        filtro: currentFiltro,
        periodo: currentPeriodo,
      });

      if (currentCasaId) {
        params.append("casa_id", currentCasaId);
      }

      const response = await fetch(`${API_URL}?${params}`);
      const data = await response.json();

      if (data.error) {
        showError(data.error);
        return;
      }

      renderReservas(data.reservas || []);
    } catch (error) {
      showError("Erro ao carregar reservas. Tente novamente.");
      console.error("Error:", error);
    }
  }

  // Carregar casas (proprietário)
  async function loadCasas() {
    try {
      const response = await fetch(`${API_URL}?action=get_casas`);
      const data = await response.json();

      if (data.casas && data.casas.length > 0) {
        const select = document.getElementById("casaFilter");
        if (select) {
          data.casas.forEach((casa) => {
            const option = document.createElement("option");
            option.value = casa.id;
            option.textContent = casa.titulo;
            select.appendChild(option);
          });
          const casaFilterGroup = document.getElementById("casa-filter-group");
          if (casaFilterGroup) {
            casaFilterGroup.style.display = "block";
          }
        }
      }
    } catch (error) {
      console.error("Erro ao carregar casas:", error);
    }
  }

  // Renderizar tabela de reservas
  function renderReservas(reservas) {
    if (reservas.length === 0) {
      reservasContent.innerHTML = `
        <div class="empty-state">
          <i class="fas fa-calendar-times"></i>
          <h3>Nenhuma reserva encontrada</h3>
          <p>${USER_TYPE === "proprietario" ? "Ainda não há reservas para suas propriedades." : "Você ainda não fez nenhuma reserva."}</p>
          ${
            USER_TYPE === "arrendatario"
              ? `
            <a href="../pesquisa.php" class="filtro-btn" style="margin-top: 15px;">
              <i class="fas fa-search"></i> Buscar Alojamentos
            </a>
          `
              : ""
          }
        </div>
      `;
      return;
    }

    const statusMap = {
      pendente: "Pendente",
      confirmada: "Confirmada",
      concluida: "Concluída",
      cancelada: "Cancelada",
      rejeitada: "Rejeitada",
    };

    let html = `
      <table class="reservas-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Propriedade</th>
            ${USER_TYPE === "proprietario" ? "<th>Arrendatário</th>" : "<th>Proprietário</th>"}
            <th>Datas</th>
            <th>Status</th>
            <th>Valor</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
    `;

    reservas.forEach((reserva) => {
      const podeConcluir =
        reserva.status === "confirmada" &&
        new Date(reserva.data_checkout) <= new Date();

      html += `
        <tr>
          <td class="reserva-id">#${String(reserva.id).padStart(5, "0")}</td>
          <td>
            <div class="reserva-casa">${escapeHtml(reserva.casa_titulo)}</div>
          </td>
          <td>
            ${
              USER_TYPE === "proprietario"
                ? `
              <div>${escapeHtml(reserva.arrendatario_nome)}</div>
              <div style="font-size: 0.8em; color: #666;">${escapeHtml(reserva.arrendatario_email || "")}</div>
            `
                : `
              <div>${escapeHtml(reserva.proprietario_nome)}</div>
            `
            }
          </td>
          <td>
            <div class="reserva-datas">
              <div><strong>Check-in:</strong> ${formatDate(reserva.data_checkin)}</div>
              <div><strong>Check-out:</strong> ${formatDate(reserva.data_checkout)}</div>
              <div><strong>Noites:</strong> ${reserva.noites}</div>
            </div>
          </td>
          <td>
            <span class="reserva-status status-${reserva.status}">
              ${statusMap[reserva.status] || reserva.status}
            </span>
          </td>
          <td class="reserva-valor">${parseFloat(reserva.total).toFixed(2).replace(".", ",")}€</td>
          <td>
            <div class="reserva-acoes">
              <button class="acao-btn btn-detalhes" onclick='showReservaDetails(${JSON.stringify(reserva)})'>
                <i class="fas fa-eye"></i>
              </button>

              ${
                USER_TYPE === "proprietario"
                  ? `
                ${
                  reserva.status === "pendente"
                    ? `
                  <button class="acao-btn btn-confirmar" onclick="handleReservaAction('confirmar', ${reserva.id})">
                    <i class="fas fa-check"></i>
                  </button>
                  <button class="acao-btn btn-cancelar" onclick="handleReservaAction('rejeitar', ${reserva.id})">
                    <i class="fas fa-times"></i>
                  </button>
                `
                    : ""
                }
                ${
                  podeConcluir
                    ? `
                  <button class="acao-btn btn-concluir" onclick="handleReservaAction('concluir', ${reserva.id})">
                    <i class="fas fa-flag-checkered"></i>
                  </button>
                `
                    : ""
                }
              `
                  : `
                ${
                  reserva.status === "pendente" ||
                  reserva.status === "confirmada"
                    ? `
                  <button class="acao-btn btn-cancelar" onclick="handleReservaAction('cancel', ${reserva.id})">
                    <i class="fas fa-times"></i>
                  </button>
                `
                    : ""
                }
              `
              }

              <button class="acao-btn btn-mensagem" onclick="window.location.href='../mensagens.php?reserva_id=${reserva.id}'">
                <i class="fas fa-envelope"></i>
              </button>

              <button class="acao-btn btn-detalhes" onclick="handleReservaAction('eliminar', ${reserva.id})">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
    });

    html += `
        </tbody>
      </table>
      <div class="pagination">
        <button class="page-btn active">1</button>
      </div>
    `;

    reservasContent.innerHTML = html;
  }

  // Ações em reservas
  window.handleReservaAction = async function (action, reservaId) {
    const confirmMessages = {
      confirmar: "Confirmar esta reserva?",
      cancel: "Cancelar esta reserva?",
      concluir: "Marcar como concluída?",
      rejeitar: "Rejeitar esta reserva?",
      eliminar: "Tem certeza que deseja ELIMINAR permanentemente esta reserva?",
    };

    if (!confirm(confirmMessages[action])) {
      return;
    }

    try {
      const response = await fetch(API_URL, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action,
          reserva_id: reservaId,
        }),
      });

      const data = await response.json();

      if (data.success) {
        showSuccess(data.message);
        loadReservas();
      } else {
        showError(data.error || "Erro ao processar ação");
      }
    } catch (error) {
      showError("Erro de comunicação. Tente novamente.");
      console.error("Error:", error);
    }
  };

  // Filtros
  window.filterByStatus = function (status) {
    currentFiltro = status;

    // Atualizar classes dos botões
    const botoes = document.querySelectorAll(".status-btn");
    botoes.forEach(function (btn) {
      btn.classList.remove("status-active");
    });

    const botaoClicado = document.querySelector(
      '.status-btn[data-status="' + status + '"]',
    );
    if (botaoClicado) {
      botaoClicado.classList.add("status-active");
    }

    loadReservas();
  };

  window.applyFilters = function () {
    const casaFilter = document.getElementById("casaFilter");
    const periodoFilter = document.getElementById("periodoFilter");

    currentCasaId = casaFilter?.value || "";
    currentPeriodo = periodoFilter?.value || "todos";
    loadReservas();
  };

  // Modal
  window.showReservaDetails = function (reserva) {
    const statusMap = {
      pendente: "Pendente",
      confirmada: "Confirmada",
      concluida: "Concluída",
      cancelada: "Cancelada",
      rejeitada: "Rejeitada",
    };

    const content = `
      <div class="modal-detalhes">
        <div class="detalhe-item">
          <div class="detalhe-label">ID da Reserva</div>
          <div class="detalhe-valor">#${String(reserva.id).padStart(5, "0")}</div>
        </div>
        <div class="detalhe-item">
          <div class="detalhe-label">Propriedade</div>
          <div class="detalhe-valor">${escapeHtml(reserva.casa_titulo)}</div>
        </div>
        <div class="detalhe-item">
          <div class="detalhe-label">Check-in</div>
          <div class="detalhe-valor">${formatDate(reserva.data_checkin)}</div>
        </div>
        <div class="detalhe-item">
          <div class="detalhe-label">Check-out</div>
          <div class="detalhe-valor">${formatDate(reserva.data_checkout)}</div>
        </div>
        <div class="detalhe-item">
          <div class="detalhe-label">Noites</div>
          <div class="detalhe-valor">${reserva.noites}</div>
        </div>
        <div class="detalhe-item">
          <div class="detalhe-label">Hóspedes</div>
          <div class="detalhe-valor">${reserva.total_hospedes}</div>
        </div>
        <div class="detalhe-item">
          <div class="detalhe-label">Status</div>
          <div class="detalhe-valor">${statusMap[reserva.status] || reserva.status}</div>
        </div>
        <div class="detalhe-item">
          <div class="detalhe-label">Data da Reserva</div>
          <div class="detalhe-valor">${formatDateTime(reserva.data_reserva)}</div>
        </div>
      </div>
      
      <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
        <h4>Valores</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
          <div>Preço por noite: <strong>${parseFloat(reserva.preco_noite).toFixed(2)}€</strong></div>
          <div>Subtotal: <strong>${parseFloat(reserva.subtotal).toFixed(2)}€</strong></div>
          <div>Taxa de limpeza: <strong>${parseFloat(reserva.taxa_limpeza).toFixed(2)}€</strong></div>
          <div>Taxa de segurança: <strong>${parseFloat(reserva.taxa_seguranca).toFixed(2)}€</strong></div>
          <div style="grid-column: 1 / -1; border-top: 1px solid #ddd; padding-top: 10px; margin-top: 5px;">
            <strong>Total: ${parseFloat(reserva.total).toFixed(2)}€</strong>
          </div>
        </div>
      </div>
      
      ${
        reserva.notas
          ? `
        <div style="margin-bottom: 20px;">
          <h4>Notas</h4>
          <p>${escapeHtml(reserva.notas)}</p>
        </div>
      `
          : ""
      }
    `;

    const modalDetailsContent = document.getElementById("modalDetailsContent");
    const detailsModal = document.getElementById("detailsModal");

    if (modalDetailsContent) modalDetailsContent.innerHTML = content;
    if (detailsModal) detailsModal.style.display = "flex";
  };

  window.closeModal = function () {
    const detailsModal = document.getElementById("detailsModal");
    if (detailsModal) detailsModal.style.display = "none";
  };

  // Fechar modal ao clicar fora
  const detailsModal = document.getElementById("detailsModal");
  if (detailsModal) {
    detailsModal.addEventListener("click", function (e) {
      if (e.target === this) {
        closeModal();
      }
    });
  }

  // Utilitários
  function showError(message) {
    const container = document.getElementById("message-container");
    if (container) {
      container.innerHTML = `<div class="error-message">${escapeHtml(message)}</div>`;
      setTimeout(() => (container.innerHTML = ""), 5000);
    }
  }

  function showSuccess(message) {
    const container = document.getElementById("message-container");
    if (container) {
      container.innerHTML = `<div class="success-message">${escapeHtml(message)}</div>`;
      setTimeout(() => (container.innerHTML = ""), 5000);
    }
  }

  function escapeHtml(text) {
    if (!text) return "";
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  function formatDate(dateStr) {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    return date.toLocaleDateString("pt-PT");
  }

  function formatDateTime(dateTimeStr) {
    if (!dateTimeStr) return "";
    const date = new Date(dateTimeStr);
    return date.toLocaleString("pt-PT");
  }
});

// Newsletter subscription - "Não Perca Nossas Ofertas"
document.addEventListener("DOMContentLoaded", function () {
  const newsletterForm = document.getElementById("newsletter-form");
  const newsletterMessage = document.getElementById("newsletter-message");

  if (newsletterForm) {
    newsletterForm.addEventListener("submit", async function (e) {
      e.preventDefault();

      const emailInput = newsletterForm.querySelector('input[name="email"]');
      const email = emailInput.value.trim();

      if (!email) {
        showNewsletterMessage("Por favor, insira um email válido.", "error");
        return;
      }

      // Disable button during submission
      const submitBtn = newsletterForm.querySelector('button[type="submit"]');
      const originalBtnText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> A processar...';

      try {
        const response = await fetch("backend/newsletter_subscribe.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ email: email }),
        });

        const data = await response.json();

        if (data.status === "success") {
          showNewsletterMessage(
            "Obrigado! Subscreveu com sucesso a newsletter.",
            "success",
          );
          newsletterForm.reset();
        } else if (data.status === "info") {
          // Email já está subscrito
          showNewsletterMessage(
            data.message || "Este email já está subscrito!",
            "warning",
          );
        } else {
          showNewsletterMessage(
            data.message || "Erro ao processar subscrição.",
            "error",
          );
        }
      } catch (error) {
        showNewsletterMessage(
          "Erro de comunicação. Tente novamente mais tarde.",
          "error",
        );
        console.error("Newsletter error:", error);
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
      }
    });
  }

  function showNewsletterMessage(message, type) {
    // Usar o sistema de toast notifications
    if (typeof AlugaTorresNotifications !== "undefined") {
      if (type === "success") {
        AlugaTorresNotifications.success(message, 5000);
      } else if (type === "warning") {
        AlugaTorresNotifications.warning(message, 5000);
      } else {
        AlugaTorresNotifications.error(message, 5000);
      }
    } else {
      // Fallback se o sistema de toast não estiver disponível
      if (newsletterMessage) {
        newsletterMessage.textContent = message;
        newsletterMessage.className =
          type === "success" ? "newsletter-success" : "newsletter-error";
        newsletterMessage.style.display = "block";

        setTimeout(() => {
          newsletterMessage.style.display = "none";
        }, 5000);
      }
    }
  }
});
