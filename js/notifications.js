/**
 * Sistema de Notificações Toast do AlugaTorres
 * Substitui alerts padrão por notificações estilizadas
 */

(function () {
  "use strict";

  // Debug: confirmar que o script carregou
  console.log(
    "[AlugaTorres Notifications] Sistema de notificações inicializado",
  );

  // Container para as notificações
  let toastContainer = null;

  /**
   * Inicializa o container de notificações
   */
  function initToastContainer() {
    if (toastContainer) return;

    toastContainer = document.createElement("div");
    toastContainer.id = "toast-container";
    toastContainer.className = "toast-container";
    document.body.appendChild(toastContainer);
  }

  /**
   * Mostra uma notificação toast
   * @param {string} message - Mensagem a exibir
   * @param {string} type - Tipo: 'success', 'error', 'warning', 'info'
   * @param {number} duration - Duração em ms (padrão: 5000)
   */
  function showToast(message, type = "info", duration = 5000) {
    initToastContainer();

    const toast = document.createElement("div");
    toast.className = `toast toast-${type}`;

    // Ícones para cada tipo
    const icons = {
      success: "fa-check-circle",
      error: "fa-times-circle",
      warning: "fa-exclamation-triangle",
      info: "fa-info-circle",
    };

    // Títulos para cada tipo
    const titles = {
      success: "Sucesso!",
      error: "Erro!",
      warning: "Atenção!",
      info: "Informação",
    };

    toast.innerHTML = `
            <div class="toast-icon">
                <i class="fas ${icons[type] || icons.info}"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">${titles[type] || titles.info}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="AlugaTorresNotifications.close(this)">
                <i class="fas fa-times"></i>
            </button>
            <div class="toast-progress">
                <div class="toast-progress-bar"></div>
            </div>
        `;

    // Adiciona ao container
    toastContainer.appendChild(toast);

    // Animação de entrada
    requestAnimationFrame(() => {
      toast.classList.add("toast-show");
    });

    // Barra de progresso
    const progressBar = toast.querySelector(".toast-progress-bar");
    if (progressBar && duration > 0) {
      progressBar.style.animation = `toast-progress ${duration}ms linear`;
    }

    // Auto-remover após duração
    let autoCloseTimeout;
    if (duration > 0) {
      autoCloseTimeout = setTimeout(() => {
        closeToast(toast);
      }, duration);
    }

    // Pausar ao hover
    toast.addEventListener("mouseenter", () => {
      clearTimeout(autoCloseTimeout);
      progressBar.style.animationPlayState = "paused";
    });

    toast.addEventListener("mouseleave", () => {
      const remainingTime =
        (1 - parseFloat(getComputedStyle(progressBar).width) / 100) * duration;
      progressBar.style.animationPlayState = "running";
      autoCloseTimeout = setTimeout(() => {
        closeToast(toast);
      }, remainingTime);
    });

    // Guardar referência para fechamento manual
    toast._autoCloseTimeout = autoCloseTimeout;
  }

  /**
   * Fecha uma notificação específica
   * @param {HTMLElement} element - Elemento toast ou botão de fechar
   */
  function closeToast(element) {
    const toast = element.closest ? element.closest(".toast") : element;
    if (!toast) return;

    // Limpar timeout
    if (toast._autoCloseTimeout) {
      clearTimeout(toast._autoCloseTimeout);
    }

    // Animação de saída
    toast.classList.remove("toast-show");
    toast.classList.add("toast-hide");

    // Remover do DOM após animação
    setTimeout(() => {
      if (toast.parentNode) {
        toast.parentNode.removeChild(toast);
      }
    }, 300);
  }

  /**
   * Fecha todas as notificações
   */
  function closeAllToasts() {
    const toasts = document.querySelectorAll(".toast");
    toasts.forEach((toast) => closeToast(toast));
  }

  // API pública
  window.AlugaTorresNotifications = {
    show: showToast,
    success: (message, duration) => showToast(message, "success", duration),
    error: (message, duration) => showToast(message, "error", duration),
    warning: (message, duration) => showToast(message, "warning", duration),
    info: (message, duration) => showToast(message, "info", duration),
    close: closeToast,
    closeAll: closeAllToasts,
  };

  // Atalhos convenientes
  window.showNotification = showToast;
  window.showSuccess = (msg, dur) => showToast(msg, "success", dur);
  window.showError = (msg, dur) => showToast(msg, "error", dur);
  window.showWarning = (msg, dur) => showToast(msg, "warning", dur);
  window.showInfo = (msg, dur) => showToast(msg, "info", dur);
})();
