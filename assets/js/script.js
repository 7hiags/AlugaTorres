"use strict"; // Modo estrito para melhor qualidade de código

window.confirmModal = function (message) {
  return new Promise(function (resolve) {
    // Remover qualquer modal existente
    const existingModal = document.getElementById("globalConfirmModal");
    if (existingModal) {
      existingModal.remove();
    }

    // Criar o modal
    const modalHtml = `
            <div id="globalConfirmModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;">
                <div style="background: white; padding: 25px; border-radius: 10px; max-width: 450px; width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                    <h3 style="margin-top: 0; color: #dc3545;"><i class="fas fa-exclamation-triangle"></i> Confirmar</h3>
                    <p style="white-space: pre-line; color: #555;">${message.replace(/</g, "<").replace(/>/g, ">")}</p>
                    <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                        <button id="globalConfirmCancel" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">Cancelar</button>
                        <button id="globalConfirmOk" style="padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer;">Confirmar</button>
                    </div>
            </div>
        `;

    document.body.insertAdjacentHTML("beforeend", modalHtml);

    const modal = document.getElementById("globalConfirmModal");

    document.getElementById("globalConfirmCancel").onclick = function () {
      modal.remove();
      resolve(false);
    };

    document.getElementById("globalConfirmOk").onclick = function () {
      modal.remove();
      resolve(true);
    };

    // Fechar ao clicar fora do modal
    modal.addEventListener("click", function (e) {
      if (e.target === modal) {
        modal.remove();
        resolve(false);
      }
    });

    // Fechar ao pressionar ESC
    document.addEventListener("keydown", function escHandler(e) {
      if (e.key === "Escape") {
        modal.remove();
        document.removeEventListener("keydown", escHandler);
        resolve(false);
      }
    });
  });
};

// 1. Slider de Imagens (Página Inicial)
document.addEventListener("DOMContentLoaded", function () {
  const slider = document.querySelector(".slider");
  const slides = document.querySelectorAll(".slide");
  const dotsContainer = document.querySelector(".slider-dots");

  // Verificar se elementos do slider existem
  if (!slider || slides.length === 0 || !dotsContainer) {
    return; // Sair se não estiver na página com slider
  }

  let currentSlide = 0;
  let slideInterval;
  const intervalTime = 3000; // Reduced to 3s for faster cycling

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

  // Função para navegar para um slide específico
  function goToSlide(index) {
    slides[currentSlide].classList.remove("active");
    document.querySelectorAll(".dot")[currentSlide].classList.remove("active");

    currentSlide = index;

    // Loop infinito dos slides
    if (currentSlide >= slides.length) currentSlide = 0;
    if (currentSlide < 0) currentSlide = slides.length - 1;

    slides[currentSlide].classList.add("active");
    document.querySelectorAll(".dot")[currentSlide].classList.add("active");
  }

  // Função para avançar para o próximo slide
  function nextSlide() {
    goToSlide(currentSlide + 1);
  }

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

  // FAST STARTUP: First slide after 1s, then normal 3s intervals
  setTimeout(() => {
    nextSlide();
    startSlideshow();
  }, 1000);
});

// 2. Highlight do Link Atual no Menu
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

// 3. Animação dos Cards de Recursos
// Animação suave para elementos feature cards
function animateFeatureCards() {
  const cards = document.querySelectorAll(".feature-card");
  cards.forEach((card, index) => {
    setTimeout(() => {
      card.style.opacity = "1";
      card.style.transform = "translateY(0)";
    }, index * 200);
  });
}

// 4. Menu Mobile (Hamburger)
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

// 5. Animação do Header ao Scroll
// Adiciona animação ao scroll
window.addEventListener("scroll", function () {
  const header = document.querySelector("header");
  if (window.scrollY > 50) {
    header.classList.add("scrolled");
  } else {
    header.classList.remove("scrolled");
  }
});

// 6. Sistema de Filtros de Pesquisa
// Funcionalidade de busca e filtros para pesquisa.php
document.addEventListener("DOMContentLoaded", function () {
  // Elementos do DOM para busca e filtros
  const searchInput = document.getElementById("searchDestino");
  const searchButton = document.querySelector(".search-button");
  const priceRange = document.getElementById("priceRange");
  const priceValue = document.querySelector(".price-value");
  const filterCheckboxes = document.querySelectorAll(
    'input[name="quartos"], input[name="tipo_propriedade"], input[name="rating"]',
  );

  // Bidirectional price sync
  const priceInput = document.getElementById("priceInput");

  // Range updates input and label
  if (priceRange && priceValue) {
    priceRange.addEventListener("input", function () {
      const val = parseInt(this.value);
      priceValue.textContent = val + "€";
      if (priceInput) priceInput.value = val;
      filterCasas();
    });
  }

  // Input updates range and label
  if (priceInput) {
    priceInput.addEventListener("input", function () {
      const val = Math.max(0, Math.min(500, parseInt(this.value) || 0));
      this.value = val;
      if (priceRange) priceRange.value = val;
      if (priceValue) priceValue.textContent = val + "€";
      filterCasas();
    });
  }

  // Função principal para filtrar casas
  // Verifica múltiplos critérios: busca, preço, freguesia e tipo
  function filterCasas() {
    const cards = document.querySelectorAll(".destination-card");
    const searchTerm = searchInput
      ? searchInput.value.toLowerCase().trim()
      : "";
    const maxPrice = priceRange ? parseInt(priceRange.value) : 500;

    // Obter Quartos selecionados
    const selectedQuartos = Array.from(
      document.querySelectorAll('input[name="quartos"]:checked'),
    ).map((cb) => parseInt(cb.value));

    // Obter tipos selecionados
    const selectedTypes = Array.from(
      document.querySelectorAll('input[name="tipo_propriedade"]:checked'),
    ).map((cb) => cb.value.toLowerCase().trim());

    // Obter avaliações selecionadas
    const selectedRatings = Array.from(
      document.querySelectorAll('input[name="rating"]:checked'),
    ).map((cb) => parseInt(cb.value));

    let visibleCount = 0;

    // Iterar sobre cada card e aplicar filtros
    cards.forEach((card) => {
      const title = card.querySelector("h3").textContent.toLowerCase();
      const description = card.querySelector(".card-description")
        ? card.querySelector(".card-description").textContent.toLowerCase()
        : "";

      // Usar dataset.preco (não dataset.price)
      const preco = parseFloat(card.dataset.preco) || 0;
      const cidade = (card.dataset.cidade || "").toLowerCase();
      const tipo_propriedade = (
        card.dataset.tipo_propriedade || ""
      ).toLowerCase();
      const freguesia = (card.dataset.freguesia || "").toLowerCase().trim();
      const quartos = parseInt(card.dataset.quartos) || 0;
      const rating = parseFloat(card.dataset.rating) || 0;

      // Filtro de busca por texto (título, descrição ou cidade)
      const matchesSearch =
        !searchTerm ||
        title.includes(searchTerm) ||
        description.includes(searchTerm) ||
        cidade.includes(searchTerm);

      // Filtro de preço
      const matchesPrice = preco <= maxPrice;

      // Filtro de quartos (exato ou 5+ para 6)
      const matchesQuartos =
        selectedQuartos.length === 0 ||
        selectedQuartos.some((sq) =>
          sq === 6 ? quartos >= 5 : quartos === sq,
        );

      // Filtro de tipo
      const matchesType =
        selectedTypes.length === 0 ||
        selectedTypes.some(
          (st) =>
            tipo_propriedade.includes(st) || st.includes(tipo_propriedade),
        );

      // Filtro de avaliação (rating deve ser maior ou igual ao selecionado)
      const matchesRating =
        selectedRatings.length === 0 ||
        selectedRatings.some((sr) => rating >= sr);

      // Aplicar filtros
      if (
        matchesSearch &&
        matchesPrice &&
        matchesQuartos &&
        matchesType &&
        matchesRating
      ) {
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

// 7. Validação do Formulário de Adicionar Casa
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

// 8. Campo "Outro" no Tipo de Propriedade
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

// 9. Navegação do Perfil e Validação
// Navegação suave e validação para perfil.php
document.addEventListener("DOMContentLoaded", function () {
  // Navegação suave entre seções
  document.querySelectorAll(".profile-menu a").forEach((link) => {
    link.addEventListener("click", function (e) {
      const href = this.getAttribute("href");

      // Só aplicar comportamento de scroll suave para âncoras internas
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
    });
  });

  // Validação do formulário de alteração de senha
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

// 10. Modal e Filtros para Reservas
// Modal e filtros para reservas.php
document.addEventListener("DOMContentLoaded", function () {
  const detailsModal = document.getElementById("detailsModal");
  const modalDetailsContent = document.getElementById("modalDetailsContent");

  if (detailsModal) {
    // Função para mostrar detalhes da reserva em um modal
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
            <div class="detalhe-valor">${reserva.casa_titulo}</div>
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
            <div class="detalhe-valor">${
              statusMap[reserva.status] || reserva.status
            }</div>
          </div>
          <div class="detalhe-item">
            <div class="detalhe-label">Data da Reserva</div>
            <div class="detalhe-valor">${formatDateTime(reserva.data_reserva)}</div>
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

    // Função para fechar o modal
    // ✅ FIXED: closeModal usa class .show do CSS
    window.closeModal = function () {
      const detailsModal = document.getElementById("detailsModal");
      if (detailsModal) {
        detailsModal.classList.remove("show");
      }
    };

    // ✅ FIXED: showModal adiciona class .show (compatível com CSS existente)
    window.showModal = function (modalId) {
      const modal = document.getElementById(modalId);
      if (modal) {
        modal.classList.add("show");
      }
    };

    // Fechar modal ao clicar fora
    detailsModal.addEventListener("click", function (e) {
      if (e.target === detailsModal) {
        closeModal();
      }
    });

    // Funções auxiliares de formatação
    function formatDate(dateStr) {
      const date = new Date(dateStr);
      return date.toLocaleDateString("pt-PT");
    }

    function formatDateTime(dateTimeStr) {
      const date = new Date(dateTimeStr);
      return date.toLocaleString("pt-PT");
    }

    // Filtros de reservas
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

// 11. Formulário de Contacto
// Formulário de contato para contactos.php
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("form-contacto");

  if (form) {
    form.addEventListener("submit", async function (e) {
      e.preventDefault();

      const submitBtn = form.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> A processar...';

      const formData = new FormData(form);

      try {
        const res = await fetch("../backend/enviar_contacto.php", {
          method: "POST",
          body: formData,
        });

        const json = await res.json();

        // Reset button
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;

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
        // Reset button on error
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;

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

// 12. Filtros e Modal para Minhas Casas
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

  // Modal de eliminação
  const deleteModal = document.getElementById("deleteModal");
  const deleteForm = document.getElementById("deleteForm");
  const modalMessage = document.getElementById("modalMessage");
  const deleteCasaId = document.getElementById("deleteCasaId");

  if (deleteModal) {
    // Mostrar modal de confirmação de eliminação
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
    deleteForm.addEventListener("submit", async function (e) {
      const confirmed = await confirmModal(
        "Esta ação é irreversível. Continuar?",
      );
      if (!confirmed) {
        e.preventDefault();
        closeModal();
      }
    });
  }
});

// 13. Funcionalidade da Sidebar
// Funcionalidade do Sidebar
document.addEventListener("DOMContentLoaded", function () {
  console.log("[Sidebar] Iniciando verificação...");

  const profileToggle = document.getElementById("profile-toggle");
  const sidebar = document.getElementById("sidebar");
  const sidebarOverlay = document.getElementById("sidebar-overlay");
  const closeSidebar = document.getElementById("close-sidebar");

  console.log("[Sidebar] Elementos encontrados:", {
    profileToggle: !!profileToggle,
    sidebar: !!sidebar,
    sidebarOverlay: !!sidebarOverlay,
    closeSidebar: !!closeSidebar,
  });

  // Se os elementos não existirem nesta página, sair
  if (!sidebar || !sidebarOverlay) {
    console.log("[Sidebar] Elementos da sidebar não existem nesta página");
    return;
  }

  // Flag para controlar se o clique no toggle deve ser ignorado
  let isToggling = false;

  // Função para abrir a sidebar
  function openSidebar() {
    sidebar.classList.add("active");
    sidebarOverlay.classList.add("active");
    console.log("[Sidebar] Sidebar aberta");
  }

  // Função para fechar a sidebar
  function closeSidebarFunc() {
    sidebar.classList.remove("active");
    sidebarOverlay.classList.remove("active");
    console.log("[Sidebar] Sidebar fechada");
  }

  // Toggle da sidebar
  function toggleSidebar() {
    if (sidebar.classList.contains("active")) {
      closeSidebarFunc();
    } else {
      openSidebar();
    }
  }

  // Abrir sidebar ao clicar no botão de perfil
  if (profileToggle) {
    profileToggle.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      // Marcar que estamos a fazer toggle
      isToggling = true;

      toggleSidebar();

      // Resetar flag após um pequeno atraso para permitir que o documento verifique
      setTimeout(function () {
        isToggling = false;
      }, 50);
    });
  }

  // Fechar sidebar ao clicar no botão de fechar
  if (closeSidebar) {
    closeSidebar.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      closeSidebarFunc();
    });
  }

  // Fechar sidebar ao clicar no overlay
  sidebarOverlay.addEventListener("click", function (e) {
    e.stopPropagation();
    closeSidebarFunc();
  });

  // Fechar sidebar ao clicar fora
  document.addEventListener("click", function (e) {
    // Se não está ativa, não fazer nada
    if (!sidebar.classList.contains("active")) {
      return;
    }

    // Se o clique foi no toggle e estávamos a fazer toggle, ignorar
    if (isToggling) {
      return;
    }

    // Verificar se o click foi no toggle, na sidebar ou no overlay
    const clickedInSidebar = sidebar.contains(e.target);
    const clickedInToggle = profileToggle && profileToggle.contains(e.target);
    const clickedInOverlay = sidebarOverlay.contains(e.target);

    // Só fechar se o clique foi fora de todos esses elementos
    if (!clickedInSidebar && !clickedInToggle && !clickedInOverlay) {
      closeSidebarFunc();
    }
  });

  // Fechar sidebar ao pressionar ESC
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && sidebar.classList.contains("active")) {
      closeSidebarFunc();
    }
  });

  console.log("[Sidebar] Configuração concluída com sucesso");
});

// 14. Campo "Outro" Tipo de Propriedade
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

// 15. Campos de Hora (Check-in/Check-out)
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

// 16. Atualização do Ano no Footer
// Atualizar ano no footer
document.addEventListener("DOMContentLoaded", function () {
  const anoElement = document.getElementById("ano");
  if (anoElement) {
    anoElement.textContent = new Date().getFullYear();
  }
});

// 17. Verificação do Sistema de Notificações
// Verificar se o sistema de notificações carregou corretamente
document.addEventListener("DOMContentLoaded", function () {
  if (typeof AlugaTorresNotifications === "undefined") {
    console.error("[AlugaTorres] ERRO: Sistema de notificações não carregou!");
  } else {
    console.log("[AlugaTorres] Sistema de notificações pronto");
  }
});

// 18. Gráfico de Reservas (Chart.js)
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

// 19. Definição do Tipo de Utilizador
// Definir perfilTipoUsuario a partir de data attribute
document.addEventListener("DOMContentLoaded", function () {
  const body = document.body;
  if (body && body.dataset.tipoUtilizador) {
    window.perfilTipoUtilizador = body.dataset.tipoUtilizador;
  }
});

// 20. Funcionalidades de Admin
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

// 21. Gráficos de Estatísticas (Admin)
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

// 22. Sistema de Reservas (Arrendatário/Proprietário)
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

  // Carregar reservas da API
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
            <a href="../root/pesquisa.php" class="filtro-btn" style="margin-top: 15px;">
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
              ${
                USER_TYPE === "proprietario" && reserva.status === "pendente"
                  ? `
                <button class="acao-btn btn-confirmar" onclick="handleReservaAction('confirmar', ${reserva.id})" title="Aceitar">
                  <i class="fas fa-check"></i>
                </button>
                <button class="acao-btn btn-cancelar" onclick="handleReservaAction('rejeitar', ${reserva.id})" title="Rejeitar">
                  <i class="fas fa-times"></i>
                </button>
              `
                  : ""
              }

              ${
                USER_TYPE === "proprietario"
                  ? `
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

              <button class="acao-btn btn-delete" onclick="handleReservaAction('eliminar', ${reserva.id})">
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

  // Ações em reservas (confirmar, cancelar, etc.)
  window.handleReservaAction = async function (action, reservaId) {
    const confirmMessages = {
      confirmar: "Confirmar esta reserva?",
      cancel: "Cancelar esta reserva?",
      concluir: "Marcar como concluída?",
      rejeitar: "Rejeitar esta reserva?",
      eliminar: "Tem certeza que deseja ELIMINAR permanentemente esta reserva?",
    };

    const confirmed = await confirmModal(confirmMessages[action]);
    if (!confirmed) {
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

  // Filtros de reservas
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

  // Modal de detalhes
  // ✅ FIXED: Nova função para detalhes por ID (evita JSON onclick quebrado)
  window.showReservaDetailsById = async function (reservaId) {
    try {
      const response = await fetch(
        `${window.reservasConfig?.apiUrl}?action=details&id=${reservaId}`,
      );
      const data = await response.json();

      if (data.error || !data.reserva) {
        alert("Erro ao carregar detalhes da reserva");
        return;
      }

      // Reusa a função existente com os dados da API
      showReservaDetails(data.reserva);
    } catch (error) {
      console.error("Erro:", error);
      alert("Erro ao carregar detalhes. Tente novamente.");
    }
  };

  // ✅ FIXED: Event delegation para botões dinâmicos (funciona após AJAX load)
  document.addEventListener("click", function (e) {
    if (e.target.closest(".btn-detalhes")) {
      const btn = e.target.closest(".btn-detalhes");
      const reservaId = btn.dataset.reservaId;
      if (reservaId) {
        e.preventDefault();
        showReservaDetailsById(reservaId);
      }
    }
  });

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

  // Funções utilitárias
  function showError(message) {
    if (typeof AlugaTorresNotifications !== "undefined") {
      AlugaTorresNotifications.error(message);
    } else {
      alert(message);
    }
  }

  function showSuccess(message) {
    if (typeof AlugaTorresNotifications !== "undefined") {
      AlugaTorresNotifications.success(message);
    } else {
      alert(message);
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

// 23. Newsletter Subscription
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
        const response = await fetch("../backend/newsletter_subscribe.php", {
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

// 24. Sistema de Avaliações
// Sistema de Avaliações - funções para frontend
document.addEventListener("DOMContentLoaded", function () {
  // Configuração da API de avaliações
  window.AvaliacoesConfig = {
    apiUrl: "../backend_api/api_avaliacoes.php",
  };
});

// Função para renderizar estrelas de avaliação
window.renderRatingStars = function (
  rating,
  interactive = false,
  showHalfStars = true,
) {
  let html = '<div class="rating-stars">';
  for (let i = 1; i <= 5; i++) {
    let starClass = "far"; // estrela vazia
    if (showHalfStars) {
      // Mostrar estrelas meias
      if (i <= Math.floor(rating)) {
        starClass = "fas"; // estrela cheia
      } else if (i === Math.ceil(rating) && rating % 1 >= 0.5) {
        starClass = "fas"; // meia estrela (mostrar como cheia se tiver pelo menos 0.5)
      }
    } else {
      // Comportamento original (arredondar)
      const filled = i <= Math.round(rating) ? "filled" : "";
      starClass = filled ? "fas" : "far";
    }

    if (interactive) {
      html += `<i class="${starClass} fa-star star ${starClass === "fas" ? "filled" : ""}" data-rating="${i}" onclick="setRating(${i})"></i>`;
    } else {
      html += `<i class="${starClass} fa-star star ${starClass === "fas" ? "filled" : ""}"></i>`;
    }
  }
  html += "</div>";
  return html;
};

// Carregar avaliações de uma casa
window.loadAvaliacoes = async function (casaId, containerId) {
  const container = document.getElementById(containerId);
  if (!container) return;

  container.innerHTML =
    '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> A carregar avaliações...</div>';

  try {
    const response = await fetch(
      `${window.AvaliacoesConfig.apiUrl}?action=list&casa_id=${casaId}`,
    );
    const data = await response.json();

    if (data.error) {
      container.innerHTML = `<div class="error-message">${data.error}</div>`;
      return;
    }

    renderAvaliacoesList(container, data);
  } catch (error) {
    container.innerHTML = `<div class="error-message">Erro ao carregar avaliações</div>`;
    console.error("Erro ao carregar avaliações:", error);
  }
};

// Renderizar lista de avaliações
window.renderAvaliacoesList = function (container, data) {
  const { avaliacoes, media, total, distribuicao } = data;

  if (!avaliacoes || avaliacoes.length === 0) {
    container.innerHTML = `
      <div class="avaliacoes-empty" style="text-align: center; padding: 30px;">
        <i class="fas fa-star-half-alt" style="font-size: 3em; color: #ccc; margin-bottom: 15px;"></i>
        <h3>Ainda não há avaliações</h3>
        <p>Seja o primeiro a avaliar esta propriedade!</p>
      </div>
    `;
    return;
  }

  // Estatísticas
  let html = `
    <div class="avaliacoes-stats" style="display: flex; gap: 30px; margin-bottom: 30px; flex-wrap: wrap;">
      <div class="avaliacao-stat-media" style="text-align: center;">
        <div class="media-valor" style="font-size: 2.5em; font-weight: bold; color: #333;">${media}</div>
        ${window.renderRatingStars(media)}
        <div class="total-avaliacoes" style="color: #666; margin-top: 5px;">${total} avaliação${total !== 1 ? "s" : ""}</div>
      </div>
      <div class="avaliacao-stat-bars" style="flex: 1; min-width: 200px;">
  `;

  // Barras de distribuição
  for (let i = 5; i >= 1; i--) {
    const count = distribuicao[i] || 0;
    const percentage = total > 0 ? (count / total) * 100 : 0;
    html += `
      <div class="avaliacao-stat-bar" style="display: flex; align-items: center; justify-content: flex-end; gap: 8px; margin-bottom: 5px;">
        <span style="white-space: nowrap;">${i} <i class="fas fa-star" style="color: #ffc107;"></i></span>
        <div style="flex: 1; height: 8px; background: #eee; border-radius: 4px; overflow: hidden;">
          <div style="height: 100%; background: #ffc107; width: ${percentage}%;"></div>
        </div>
        <span style="width: 25px; text-align: right; color: #666;">${count}</span>
      </div>
    `;
  }

  html += `</div></div><div class="avaliacoes-lista">`;

  // Lista de avaliações
  avaliacoes.forEach((avaliacao) => {
    html += renderAvaliacaoCard(avaliacao);
  });

  html += "</div>";
  container.innerHTML = html;
};

// Renderizar um card de avaliação
window.renderAvaliacaoCard = function (avaliacao) {
  const data = new Date(avaliacao.data_criacao);
  const dataFormatada = data.toLocaleDateString("pt-PT");

  const fotoPerfil = avaliacao.foto_perfil
    ? `<img src="${avaliacao.foto_perfil}" alt="${avaliacao.arrendatario_nome}" class="avaliacao-avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">`
    : `<div class="avaliacao-avatar" style="width: 40px; height: 40px; border-radius: 50%; background: #ddd; display: flex; align-items: center; justify-content: center;"><i class="fas fa-user" style="color: #666;"></i></div>`;

  // Botões de editar/eliminar (apenas para o autor ou admin)
  let botoesAcao = "";
  const currentUserId = window.currentUserId || 0;
  const currentUserType = window.currentUserType || "";

  // Verificar se é o autor da avaliação ou admin
  const isAutor = avaliacao.arrendatario_id == currentUserId;
  const isAdmin = currentUserType === "admin";

  if (isAutor || isAdmin) {
    botoesAcao = `
      <div class="avaliacao-acoes" style="margin-top: 10px; display: flex; gap: 8px;">
        ${
          isAutor
            ? `
          <button onclick="window.editAvaliacao(${avaliacao.id}, ${avaliacao.rating}, '${escapeHtml(avaliacao.comentario || "").replace(/'/g, "\\'")}')" 
            style="padding: 5px 10px; background: #ffc107; color: #333; border: none; border-radius: 4px; cursor: pointer; font-size: 0.85em;">
            <i class="fas fa-edit"></i> Editar
          </button>
        `
            : ""
        }
        <button onclick="window.confirmDeleteAvaliacao(${avaliacao.id})" 
          style="padding: 5px 10px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.85em;">
          <i class="fas fa-trash"></i> Eliminar
        </button>
      </div>
    `;
  }

  return `
    <div class="avaliacao-card" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
      <div class="avaliacao-cabecalho" style="display: flex; gap: 15px; margin-bottom: 10px;">
        ${fotoPerfil}
        <div class="avaliacao-info" style="flex: 1;">
          <div class="avaliacao-autor" style="font-weight: 600;">${escapeHtml(avaliacao.arrendatario_nome)}</div>
          <div class="avaliacao-data" style="color: #999; font-size: 0.9em;">${dataFormatada}</div>
        </div>
        <div class="avaliacao-rating">
          ${window.renderRatingStars(avaliacao.rating)}
        </div>
      </div>
      ${avaliacao.comentario ? `<div class="avaliacao-comentario" style="color: #555; line-height: 1.5;">${escapeHtml(avaliacao.comentario)}</div>` : ""}
      ${botoesAcao}
      ${
        avaliacao.resposta
          ? `
        <div class="avaliacao-resposta" style="background: white; padding: 12px; border-radius: 6px; margin-top: 12px; border-left: 3px solid #038e01;">
          <div class="avaliacao-resposta-titulo" style="font-weight: 600; color: #038e01; margin-bottom: 5px;">
            <i class="fas fa-reply"></i> Resposta do proprietário
          </div>
          <div class="avaliacao-resposta-texto" style="color: #555;">${escapeHtml(avaliacao.resposta)}</div>
          <div class="avaliacao-resposta-data" style="color: #999; font-size: 0.85em; margin-top: 5px;">${new Date(avaliacao.resposta_data).toLocaleDateString("pt-PT")}</div>
        </div>
      `
          : ""
      }
    </div>
  `;
};

// Verificar se o utilizador pode avaliar uma casa
window.checkCanAvaliar = async function (casaId) {
  try {
    const response = await fetch(
      `${window.AvaliacoesConfig.apiUrl}?action=check&casa_id=${casaId}`,
    );
    return await response.json();
  } catch (error) {
    console.error("Erro ao verificar permissão:", error);
    return { pode_avaliar: false, motivo: "Erro de comunicação" };
  }
};

// Mostrar formulário de avaliação (modal)
window.showAvaliacaoForm = async function (casaId, casaTitulo) {
  const check = await window.checkCanAvaliar(casaId);

  if (!check.pode_avaliar) {
    if (check.ja_avaliou) {
      alert("Já avaliou esta propriedade. Pode atualizar a sua avaliação.");
    } else {
      alert(check.motivo || "Não pode avaliar esta propriedade.");
    }
    return;
  }

  const modal = document.createElement("div");
  modal.id = "avaliacaoModal";
  modal.style.cssText =
    "position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;";
  modal.innerHTML = `
    <div style="background: white; padding: 30px; border-radius: 10px; max-width: 450px; width: 90%; position: relative;">
      <button onclick="closeAvaliacaoModal()" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 1.2em; cursor: pointer; color: #666;">
        <i class="fas fa-times"></i>
      </button>
      <h3 style="color: #038e01; margin-bottom: 20px;">Avaliar ${escapeHtml(casaTitulo)}</h3>
      <form id="avaliacaoForm" onsubmit="submitAvaliacao(event, ${casaId})">
        <div style="margin-bottom: 20px;">
          <label style="display: block; margin-bottom: 10px; font-weight: 600;">Sua avaliação</label>
          <div id="ratingInput" style="font-size: 1.8em; cursor: pointer;">
            ${[1, 2, 3, 4, 5].map((i) => `<i class="fas fa-star" style="color: #ddd; margin-right: 5px;" data-rating="${i}" onmouseover="previewRating(${i})" onmouseout="resetRatingPreview()" onclick="setRating(${i})"></i>`).join("")}
          </div>
          <input type="hidden" id="ratingValue" required>
        </div>
        <div style="margin-bottom: 20px;">
          <label for="comentario" style="display: block; margin-bottom: 10px; font-weight: 600;">Comentário (opcional)</label>
          <textarea id="comentario" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-family: inherit; resize: vertical;" placeholder="Conte a sua experiência..."></textarea>
        </div>
        <button type="submit" style="width: 100%; padding: 12px; background: #038e01; color: white; border: none; border-radius: 5px; font-size: 1em; cursor: pointer;">
          <i class="fas fa-paper-plane"></i> Enviar Avaliação
        </button>
      </form>
    </div>
  `;

  document.body.appendChild(modal);
};

// Prévia de avaliação ao passar mouse
window.previewRating = function (rating) {
  const stars = document.querySelectorAll("#ratingInput i");
  stars.forEach((star, index) => {
    star.style.color = index < rating ? "#ffc107" : "#ddd";
  });
};

// Resetar avaliação anterior ao tirar mouse
window.resetRatingPreview = function () {
  const rating = document.getElementById("ratingValue").value;
  window.previewRating(rating || 0);
};

// Definir avaliação ao clicar
window.setRating = function (rating) {
  document.getElementById("ratingValue").value = rating;
  window.previewRating(rating);
};

// Submeter avaliação
window.submitAvaliacao = async function (event, casaId) {
  event.preventDefault();

  const rating = document.getElementById("ratingValue").value;
  const comentario = document.getElementById("comentario").value;

  if (!rating) {
    alert("Por favor, selecione uma avaliação.");
    return;
  }

  try {
    const response = await fetch(window.AvaliacoesConfig.apiUrl, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "create",
        casa_id: casaId,
        rating: parseInt(rating),
        comentario: comentario,
      }),
    });

    const data = await response.json();

    if (data.success) {
      alert("Obrigado! A sua avaliação foi enviada com sucesso.");
      closeAvaliacaoModal();

      const container = document.getElementById("avaliacoes-container");
      if (container) {
        loadAvaliacoes(casaId, "avaliacoes-container");
      }
    } else {
      alert(data.error || "Erro ao enviar avaliação.");
    }
  } catch (error) {
    alert("Erro de comunicação. Tente novamente.");
    console.error("Erro ao submeter avaliação:", error);
  }
};

// Fechar modal de avaliação
window.closeAvaliacaoModal = function () {
  const modal = document.getElementById("avaliacaoModal");
  if (modal) {
    modal.remove();
  }
};

// Inicializar widget de avaliações
window.initAvaliacoesWidget = async function (casaId, containerId) {
  if (!casaId) return;

  const container = document.getElementById(containerId);
  if (!container) return;

  await loadAvaliacoes(casaId, containerId);

  const check = await window.checkCanAvaliar(casaId);
  if (check.pode_avaliar) {
    const titulo = container.dataset.titulo || "esta propriedade";
    const btn = document.createElement("button");
    btn.style.cssText =
      "margin-top: 15px; padding: 10px 20px; background: #038e01; color: white; border: none; border-radius: 5px; cursor: pointer;";
    btn.innerHTML = '<i class="fas fa-star"></i> Avaliar';
    btn.onclick = () => showAvaliacaoForm(casaId, titulo);
    container.appendChild(btn);
  }
};

// Responder a avaliação (proprietário)
window.respondToAvaliacao = async function (avaliacaoId) {
  const resposta = prompt("Digite a sua resposta:");

  if (!resposta || !resposta.trim()) {
    return;
  }

  try {
    const response = await fetch(window.AvaliacoesConfig.apiUrl, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "respond",
        avaliacao_id: avaliacaoId,
        resposta: resposta.trim(),
      }),
    });

    const data = await response.json();

    if (data.success) {
      alert("Resposta enviada com sucesso!");
      location.reload();
    } else {
      alert(data.error || "Erro ao enviar resposta.");
    }
  } catch (error) {
    alert("Erro de comunicação.");
    console.error("Erro ao responder:", error);
  }
};

// Editar comentario de avaliação (arrendatário)
window.editAvaliacao = function (avaliacaoId, ratingAtual, comentarioAtual) {
  console.log(
    "editAvaliacao called:",
    avaliacaoId,
    ratingAtual,
    comentarioAtual,
  );

  // Remover modal existente se houver
  const existingModal = document.getElementById("editAvaliacaoModal");
  if (existingModal) {
    existingModal.remove();
  }

  const modal = document.createElement("div");
  modal.id = "editAvaliacaoModal";
  modal.style.cssText =
    "position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;";

  // Escapar aspas para evitar quebra do HTML
  const safeComentario = comentarioAtual
    ? comentarioAtual.replace(/"/g, '"').replace(/'/g, "&#39;")
    : "";

  modal.innerHTML = `
    <div style="background: white; padding: 30px; border-radius: 10px; max-width: 450px; width: 90%; position: relative;">
      <button onclick="window.closeEditAvaliacaoModal()" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 1.2em; cursor: pointer; color: #666;">
        <i class="fas fa-times"></i>
      </button>
      <h3 style="color: #ffc107; margin-bottom: 20px;"><i class="fas fa-edit"></i> Editar Avaliação</h3>
      <form id="editAvaliacaoForm" onsubmit="event.preventDefault(); window.submitEditAvaliacao(${avaliacaoId});">
        <div style="margin-bottom: 20px;">
          <label style="display: block; margin-bottom: 10px; font-weight: 600;">Sua avaliação</label>
          <div id="editRatingInput" style="font-size: 1.8em; cursor: pointer;">
            ${[1, 2, 3, 4, 5].map((i) => `<i class="fas fa-star" style="color: ${i <= ratingAtual ? "#ffc107" : "#ddd"}; margin-right: 5px;" data-rating="${i}" onmouseover="window.previewEditRating(${i})" onmouseout="window.resetEditRatingPreview()" onclick="window.setEditRating(${i})"></i>`).join("")}
          </div>
          <input type="hidden" id="editRatingValue" value="${ratingAtual}">
        </div>
        <div style="margin-bottom: 20px;">
          <label for="editComentario" style="display: block; margin-bottom: 10px; font-weight: 600;">Comentário (opcional)</label>
          <textarea id="editComentario" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-family: inherit; resize: vertical;" placeholder="Conte a sua experiência...">${safeComentario}</textarea>
        </div>
        <button type="submit" style="width: 100%; padding: 12px; background: #ffc107; color: #333; border: none; border-radius: 5px; font-size: 1em; cursor: pointer;">
          <i class="fas fa-save"></i> Guardar Alterações
        </button>
      </form>
    </div>
  `;

  document.body.appendChild(modal);
};

// Resetar avaliação anterior ao tirar o rato (edição)
window.previewEditRating = function (rating) {
  const stars = document.querySelectorAll("#editRatingInput i");
  stars.forEach((star, index) => {
    star.style.color = index < rating ? "#ffc107" : "#ddd";
  });
};

// Resetar avaliação anterior (edição)
window.resetEditRatingPreview = function () {
  const rating = document.getElementById("editRatingValue").value;
  window.previewEditRating(rating || 0);
};

// Definir avaliação ao clicar (edição)
window.setEditRating = function (rating) {
  document.getElementById("editRatingValue").value = rating;
  window.previewEditRating(rating);
};

// Submeter edição de avaliação
window.submitEditAvaliacao = async function (avaliacaoId) {
  const confirmed = await confirmModal(
    "Tem certeza que deseja guardar as alterações?",
  );

  if (!confirmed) {
    return;
  }

  const rating = document.getElementById("editRatingValue").value;
  const comentario = document.getElementById("editComentario").value;

  if (!rating) {
    if (typeof AlugaTorresNotifications !== "undefined") {
      AlugaTorresNotifications.error("Por favor, selecione uma avaliação.");
    } else {
      alert("Por favor, selecione uma avaliação.");
    }
    return;
  }

  try {
    const response = await fetch(window.AvaliacoesConfig.apiUrl, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "update",
        avaliacao_id: avaliacaoId,
        rating: parseInt(rating),
        comentario: comentario,
      }),
    });

    const data = await response.json();

    if (data.success) {
      if (typeof AlugaTorresNotifications !== "undefined") {
        AlugaTorresNotifications.success("Avaliação atualizada com sucesso!");
      } else {
        alert("Avaliação atualizada com sucesso!");
      }
      window.closeEditAvaliacaoModal();

      // Recarregar avaliações
      const container = document.getElementById("avaliacoes-container");
      if (container && window.casaIdAtual) {
        loadAvaliacoes(window.casaIdAtual, "avaliacoes-container");
      } else if (window.location.href.includes("calendario")) {
        // Tentar obter o casa_id da URL
        const urlParams = new URLSearchParams(window.location.search);
        const casaId = urlParams.get("casa_id");
        if (casaId) {
          loadAvaliacoes(casaId, "avaliacoes-container");
        }
      }
    } else {
      if (typeof AlugaTorresNotifications !== "undefined") {
        AlugaTorresNotifications.error(
          data.error || "Erro ao atualizar avaliação.",
        );
      } else {
        alert(data.error || "Erro ao atualizar avaliação.");
      }
    }
  } catch (error) {
    if (typeof AlugaTorresNotifications !== "undefined") {
      AlugaTorresNotifications.error("Erro de comunicação. Tente novamente.");
    } else {
      alert("Erro de comunicação. Tente novamente.");
    }
    console.error("Erro ao submeter avaliação:", error);
  }
};

// Fechar modal edição de avaliação
window.closeEditAvaliacaoModal = function () {
  const modal = document.getElementById("editAvaliacaoModal");
  if (modal) {
    modal.remove();
  }
};

// Confirmar eliminação de avaliação
window.confirmDeleteAvaliacao = async function (avaliacaoId) {
  const confirmed = await confirmModal(
    "Tem certeza que deseja eliminar esta avaliação? Esta ação não pode ser desfeita.",
  );

  if (!confirmed) {
    return;
  }

  fetch(window.AvaliacoesConfig.apiUrl, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      action: "delete",
      avaliacao_id: avaliacaoId,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Usar sistema de notificações se disponível
        if (typeof AlugaTorresNotifications !== "undefined") {
          AlugaTorresNotifications.success("Avaliação eliminada com sucesso!");
        } else {
          alert("Avaliação eliminada com sucesso!");
        }

        // Recarregar avaliações
        const container = document.getElementById("avaliacoes-container");
        if (container && window.casaIdAtual) {
          loadAvaliacoes(window.casaIdAtual, "avaliacoes-container");
        } else if (window.location.href.includes("calendario")) {
          const urlParams = new URLSearchParams(window.location.search);
          const casaId = urlParams.get("casa_id");
          if (casaId) {
            loadAvaliacoes(casaId, "avaliacoes-container");
          }
        }
      } else {
        if (typeof AlugaTorresNotifications !== "undefined") {
          AlugaTorresNotifications.error(
            data.error || "Erro ao eliminar avaliação.",
          );
        } else {
          alert(data.error || "Erro ao eliminar avaliação.");
        }
      }
    })
    .catch((error) => {
      if (typeof AlugaTorresNotifications !== "undefined") {
        AlugaTorresNotifications.error("Erro de comunicação. Tente novamente.");
      } else {
        alert("Erro de comunicação. Tente novamente.");
      }
      console.error("Erro ao eliminar avaliação:", error);
    });
};

// Função escapeHtml auxiliar
function escapeHtml(text) {
  if (!text) return "";
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}
