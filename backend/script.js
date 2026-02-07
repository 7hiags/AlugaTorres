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

  // Atualizar valor do preço
  if (priceRange && priceValue) {
    priceRange.addEventListener("input", function () {
      priceValue.textContent = "€" + this.value;
    });
  }

  // Função para filtrar casas
  function filterCasas() {
    const cards = document.querySelectorAll(".destination-card");
    const searchTerm = searchInput ? searchInput.value.toLowerCase() : "";
    const maxPrice = priceRange ? parseInt(priceRange.value) : 100;

    // Obter Freguesias selecionadas
    const selectedFreguesias = Array.from(
      document.querySelectorAll('input[name="freguesia"]:checked'),
    ).map((cb) => cb.value);
    // Obter tipos selecionados
    const selectedTypes = Array.from(
      document.querySelectorAll('input[name="type"]:checked'),
    ).map((cb) => cb.value);

    cards.forEach((card) => {
      const title = card.querySelector("h3").textContent.toLowerCase();
      const description = card
        .querySelector(".card-description")
        .textContent.toLowerCase();
      const price = parseInt(card.dataset.price);
      const cidade = card.dataset.cidade || "";
      const tipo = card.dataset.tipo || "";
      const freguesia = (card.dataset.freguesia || "").toLowerCase();

      // Filtros
      const matchesSearch =
        !searchTerm ||
        title.includes(searchTerm) ||
        description.includes(searchTerm) ||
        cidade.includes(searchTerm);
      const matchesPrice = price <= maxPrice;
      const matchesFreguesia =
        selectedFreguesias.length === 0 ||
        selectedFreguesias.includes(freguesia);
      const matchesType =
        selectedTypes.length === 0 ||
        selectedTypes.some((t) => tipo.includes(t));

      if (matchesSearch && matchesPrice && matchesFreguesia && matchesType) {
        card.style.display = "block";
      } else {
        card.style.display = "none";
      }
    });

    // Verificar se há resultados
    const visibleCards = document.querySelectorAll(
      '.destination-card[style*="display: block"]',
    );
    const noResults = document.querySelector(".no-results");

    if (visibleCards.length === 0 && cards.length > 0) {
      if (!noResults) {
        const grid = document.querySelector(".destinations-grid");
        const noResultsDiv = document.createElement("div");
        noResultsDiv.className = "no-results";
        noResultsDiv.style.cssText =
          "grid-column: 1 / -1; text-align: center; padding: 50px;";
        noResultsDiv.innerHTML = `
          <i class="fas fa-search" style="font-size: 3em; color: #ccc; margin-bottom: 20px;"></i>
          <h3>Nenhuma casa encontrada</h3>
          <p>Não há casas que correspondam aos seus filtros.</p>
        `;
        grid.appendChild(noResultsDiv);
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
      e.preventDefault();
      const targetId = this.getAttribute("href").substring(1);

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
  const resposta = document.getElementById("resposta");

  if (form) {
    form.addEventListener("submit", async function (e) {
      e.preventDefault();
      resposta.style.display = "none";
      resposta.className = "response-message";

      const formData = new FormData(form);

      try {
        const res = await fetch("contactos.php", {
          method: "POST",
          body: formData,
        });

        const json = await res.json();

        if (res.ok && json.status === "success") {
          resposta.classList.add("success");
          resposta.textContent =
            json.message || "Mensagem enviada com sucesso!";
          form.reset();
        } else {
          resposta.classList.add("error");
          resposta.textContent = json.message || "Erro ao enviar mensagem.";
        }
      } catch (err) {
        resposta.classList.add("error");
        resposta.textContent =
          "Erro de comunicação. Tente novamente mais tarde.";
      }

      resposta.style.display = "block";
      setTimeout(() => (resposta.style.display = "none"), 5000);
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
