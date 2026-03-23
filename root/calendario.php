<?php
session_start();
require_once '../backend/db.php';
require_once 'init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../backend/autenticacao/login.php");
    exit;
}

// Verificar se o usuário ainda existe na base de dados
$stmt = $conn->prepare("SELECT id FROM utilizadores WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    session_destroy();
    header("Location: ../backend/autenticacao/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$tipo_utilizador = $_SESSION['tipo_utilizador'] ?? 'arrendatario';
$casa_id = isset($_GET['casa_id']) ? (int)$_GET['casa_id'] : null;
$casa = null;
$is_propria_casa = false;

// Se for arrendatário e tiver casa_id, buscar a casa
if ($tipo_utilizador === 'arrendatario' && $casa_id) {
    $query = $conn->prepare("SELECT id, titulo FROM casas WHERE id = ?");
    $query->bind_param("i", $casa_id);
    $query->execute();
    $result = $query->get_result();
    $casa = $result->fetch_assoc();
} elseif ($tipo_utilizador === 'proprietario' && $casa_id) {

    // Verificar se é a própria casa do proprietário
    $query = $conn->prepare("SELECT id, titulo FROM casas WHERE id = ? AND proprietario_id = ?");
    $query->bind_param("ii", $casa_id, $user_id);
    $query->execute();
    $result = $query->get_result();
    $casa_propria = $result->fetch_assoc();

    if ($casa_propria) {
        // É a própria casa
        $casa = $casa_propria;
        $is_propria_casa = true;
    } else {
        // É casa de outro proprietário - permitir visualização apenas
        $query = $conn->prepare("SELECT id, titulo FROM casas WHERE id = ?");
        $query->bind_param("i", $casa_id);
        $query->execute();
        $result = $query->get_result();
        $casa = $result->fetch_assoc();
        $is_propria_casa = false;
    }
}

$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$casa_id_url = $casa_id ? "&casa_id=$casa_id" : '';

?>
<?php
$pageTitle = 'AlugaTorres | Calendário';
$extraHead = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">';
require_once __DIR__ . '/head.php';
include 'header.php';
include 'sidebar.php';
?>

<style>
    .weather-current {
        transition: all 0.5s ease;
        border-radius: 8px;
        padding: 15px;
    }

    /* Loading state with site green gradient */
    .weather-current-loading {
        background: linear-gradient(135deg, var(--primary-green, #038e01) 0%, var(--success, #28a745) 50%, #56ab2f 100%) !important;
        color: white !important;
    }

    .weather-current-loading .loading {
        color: white !important;
    }

    .weather-current-loading .loading i {
        color: rgba(255, 255, 255, 0.9) !important;
    }

    /* Tema Diurno - Cores claras/quentes */
    .weather-current-day {
        background: linear-gradient(135deg, #2486ac 0%, #E0F6FF 100%) !important;
        color: #000000 !important;
    }

    .weather-current-day .weather-temp {
        color: #383838 !important;
    }

    .weather-current-day .weather-desc {
        color: #000000 !important;
    }

    .weather-current-day .weather-details {
        color: #000000 !important;
    }

    /* Tema Noturno - Cores escuras/frias */
    .weather-current-night {
        background: linear-gradient(135deg, #3131b1 0%, #3d616c 100%) !important;
        color: #ffffff !important;
    }

    .weather-current-night .weather-temp {
        color: #ffffff !important;
    }

    .weather-current-night .weather-desc {
        color: #e2e8f0 !important;
    }

    .weather-current-night .weather-desc img {
        filter: brightness(1.4) drop-shadow(0 0 8px rgba(255, 255, 255, 0.6)) drop-shadow(0 0 4px rgba(255, 217, 107, 0.8));
        /* Enhanced glow for all night icons */
    }

    /* Specific moon/clear night icons enhancement (01n,02n,03n,04n) */
    .weather-current-night .weather-desc img[src*="01n"],
    .weather-current-night .weather-desc img[src*="02n"],
    .weather-current-night .weather-desc img[src*="03n"] {
        filter: brightness(1.6) hue-rotate(50deg) drop-shadow(0 0 12px rgba(255, 235, 150, 0.9)) drop-shadow(0 0 6px rgba(255, 217, 107, 1));
        /* Bright yellow moon glow */
    }

    .weather-current-night .weather-details {
        color: #ffffff !important;
        background: rgba(255, 255, 255, 0.15) !important;
    }

    /* Todos os ícones Font Awesome no modo noturno */
    .weather-current-night i,
    .weather-current-night .weather-details i {
        color: #63b3ed !important;
        text-shadow: 0 0 5px rgba(99, 179, 237, 0.8);
    }

    /* Ícone de calendário na data também muda */
    .weather-current-night~.weather-date i,
    .weather-widget:has(.weather-current-night) .weather-date i {
        color: #63b3ed !important;
    }

    /* Ensure ALL night images have minimum visibility */
    .weather-current-night .weather-desc img {
        -webkit-filter: brightness(1.4) drop-shadow(0 0 8px rgba(255, 255, 255, 0.6));
        filter: brightness(1.4) drop-shadow(0 0 8px rgba(255, 255, 255, 0.6));
    }

    /* Estilos para preço especial */
    .price-warning {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
        border-radius: 5px;
        padding: 10px;
        margin: 10px 0;
        font-size: 0.9em;
        display: none;
    }

    .price-warning i {
        margin-right: 5px;
        color: #856404;
    }

    .btn-group {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }

    .btn-group .btn-reservar {
        flex: 1;
    }

    .btn-reservar:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .calendar-day.special-price .day-number::after {
        font-size: 0.8em;
        margin-left: 5px;
    }

    /* Mantendo estilos originais para dias passados */
    .calendar-day.past {
        opacity: 0.7;
        background-color: rgba(248, 215, 218, 0.3) !important;
        border-color: #f5c6cb !important;
    }

    .calendar-day.past .day-number {
        color: #721c24 !important;
    }

    .calendar-day.reserved {
        background-color: rgba(255, 193, 7, 0.1) !important;
        border: 2px solid #ff0707 !important;
    }

    .calendar-day.blocked {
        background-color: rgba(220, 53, 69, 0.1) !important;
        border: 2px solid #ffc107 !important;
    }

    .calendar-day.available {
        background-color: rgba(40, 167, 69, 0.1) !important;
        border: 2px solid #28a745 !important;
    }

    .legend-color.legend-special {
        background: linear-gradient(135deg, #ffd700, #ffa500);
    }

    .calendar-day.today-highlight {
        animation: pulse 3.5s infinite;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.5);
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
        }
    }

    /* Garantir que o resumo da reserva seja visível */
    .reservation-summary {
        background: white;
        padding: 15px;
        border-radius: 8px;
        margin: 15px 0;
        border: 1px solid #ddd;
        display: block;
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        margin: 8px 0;
        padding: 5px 0;
        border-bottom: 1px solid #eee;
    }

    .summary-item:last-child {
        border-bottom: none;
    }

    .summary-total {
        font-weight: bold;
        color: #038e01;
        font-size: 1.1em;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 2px solid #038e01;
    }

    /* Estilo para o resumo do tempo */
    .summary-weather {
        background: #f0f8ff;
        padding: 10px;
        border-radius: 5px;
        margin: 10px 0;
        font-size: 0.9em;
    }

    .summary-weather h5 {
        margin: 0 0 8px 0;
        color: #038e01;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .weather-day-summary {
        margin: 5px 0;
        padding: 3px 0;
        border-bottom: 1px dotted #ddd;
    }

    .weather-day-summary:last-child {
        border-bottom: none;
    }
</style>

<body>
    <div class="calendar-container">
        <div class="calendar-header">
            <h1 class="calendar-title">
                <?php if ($casa): ?>
                    Calendário: <?php echo htmlspecialchars($casa['titulo']); ?>
                <?php else: ?>
                    Calendário de Disponibilidade
                <?php endif; ?>
            </h1>

            <div class="calendar-nav">
                <a href="?mes=<?php echo $mes - 1 < 1 ? 12 : $mes - 1; ?>&ano=<?php echo $mes - 1 < 1 ? $ano - 1 : $ano;
                                                                                echo $casa_id_url; ?>" class="nav-btn">
                    <i class="fas fa-chevron-left"></i>
                </a>

                <div class="current-month">
                    <?php
                    $meses = [
                        1 => 'Janeiro',
                        2 => 'Fevereiro',
                        3 => 'Março',
                        4 => 'Abril',
                        5 => 'Maio',
                        6 => 'Junho',
                        7 => 'Julho',
                        8 => 'Agosto',
                        9 => 'Setembro',
                        10 => 'Outubro',
                        11 => 'Novembro',
                        12 => 'Dezembro'
                    ];
                    echo $meses[$mes] . ' ' . $ano;
                    ?>
                </div>

                <a href="?mes=<?php echo $mes + 1 > 12 ? 1 : $mes + 1; ?>&ano=<?php echo $mes + 1 > 12 ? $ano + 1 : $ano;
                                                                                echo $casa_id_url; ?>" class="nav-btn">
                    <i class="fas fa-chevron-right"></i>
                </a>

                <a href="?mes=<?php echo date('n'); ?>&ano=<?php echo date('Y');
                                                            echo $casa_id_url; ?>" class="btn-hoje">
                    Hoje
                </a>
            </div>
        </div>

        <div class="calendar-wrapper">
            <div class="calendar-main">
                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-color legend-available"></div>
                        <span>Disponível</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color legend-reserved"></div>
                        <span>Reservado</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color legend-blocked"></div>
                        <span>Bloqueado</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color legend-special"></div>
                        <span>Preço Especial 🔥</span>
                    </div>
                </div>
                <div class="calendar-grid" id="calendarGrid">
                    <!-- Cabeçalho dos dias -->
                    <div class="calendar-day-header">Dom</div>
                    <div class="calendar-day-header">Seg</div>
                    <div class="calendar-day-header">Ter</div>
                    <div class="calendar-day-header">Qua</div>
                    <div class="calendar-day-header">Qui</div>
                    <div class="calendar-day-header">Sex</div>
                    <div class="calendar-day-header">Sáb</div>
                    <!-- Dias serão preenchidos por JavaScript -->
                </div>
            </div>

            <div class="calendar-sidebar">
                <div class="weather-widget">
                    <h3 class="weather-title">
                        <i class="fas fa-cloud-sun"></i> Meteorologia - Torres Novas
                    </h3>

                    <!-- Data atual -->
                    <div id="weatherDate" class="weather-date" style="text-align: left; font-size: 0.95em; color: #666; margin-bottom: 10px; font-weight: 500; padding-left: 10px;">
                        <i class="fas fa-calendar-day" style="margin-right: 8px; color: #038e01;"></i><?php echo date('l, d \d\e F \d\e Y'); ?>
                    </div>

                    <!-- Indicador de fonte / estado da API -->
                    <div id="weatherSourceStatus" class="weather-source weather-source-loading">Carregando meteorologia...</div>

                    <div id="weatherCurrent" class="weather-current">
                        <div class="loading">
                            <i class="fas fa-sync fa-spin"></i>
                            <p>Carregando meteorologia...</p>
                        </div>
                    </div>
                </div>

                <?php if ($tipo_utilizador === 'arrendatario' && $casa_id): ?>
                    <div class="reservation-form sidebar-section" id="reservationForm" style="display: none;">
                        <h3><i class="fas fa-calendar-plus"></i> Fazer Reserva</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Check-in</label>
                                <input type="text" id="checkinDate" class="date-input" placeholder="Data de entrada">
                            </div>
                            <div class="form-group">
                                <label>Check-out</label>
                                <input type="text" id="checkoutDate" class="date-input" placeholder="Data de saída">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Número de Hóspedes</label>
                            <select id="numGuests" class="date-input">
                                <option value="1">1 pessoa</option>
                                <option value="2" selected>2 pessoas</option>
                                <option value="3">3 pessoas</option>
                                <option value="4">4 pessoas</option>
                                <option value="5">5 pessoas</option>
                                <option value="6">6 pessoas</option>
                            </select>
                        </div>

                        <div id="reservationSummary" class="reservation-summary" style="display: none;">
                            <!-- Resumo da reserva será carregado aqui -->
                        </div>

                        <button type="button" class="btn-reservar" id="btnReservar"><i class="fas fa-calendar-check"></i> Reservar Agora</button>
                    </div>
                <?php elseif ($tipo_utilizador === 'proprietario' && $casa_id): ?>
                    <?php if ($is_propria_casa): ?>
                        <div class="reservation-form sidebar-section">
                            <h3><i class="fas fa-calendar-alt"></i> Gerir Disponibilidade</h3>

                            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                                <h4><i class="fas fa-euro-sign"></i> Preço Especial</h4>
                                <p style="font-size: 0.9em; color: #666; margin-bottom: 10px;">Defina preços especiais para datas específicas.</p>

                                <div class="form-group" style="margin-bottom: 10px;">
                                    <label>Selecionar Datas</label>
                                    <input type="text" id="specialPriceDates" class="date-input" placeholder="Selecione as datas" style="width: 100%;">
                                </div>

                                <div class="form-group" style="margin-bottom: 10px;">
                                    <label>Preço Especial (€)</label>
                                    <input type="number" id="specialPrice" class="date-input" placeholder="Ex: 75" min="1" step="0.01" style="width: 100%;">
                                </div>

                                <div id="priceWarning" class="price-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span></span>
                                </div>

                                <!-- botões dinâmicos com base na seleção de datas -->
                                <div class="btn-group" id="specialPriceActions">
                                    <button type="button" class="btn-reservar" id="btnApplySpecialPrice" style="background: #28a745;">
                                        <i class="fas fa-check"></i> Aplicar Preço
                                    </button>
                                    <button type="button" class="btn-reservar" id="btnRemoveSpecialPrice" style="background: #dc3545; display: none;">
                                        <i class="fas fa-trash"></i> Remover Preço
                                    </button>
                                </div>
                            </div>

                            <!-- ===== NOVA SEÇÃO: BLOQUEIO EM LOTE ===== -->
                            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                                <h4><i class="fas fa-lock"></i> Bloquear/Desbloquear Datas</h4>
                                <p style="font-size: 0.9em; color: #666; margin-bottom: 10px;">Selecione múltiplas datas para bloquear ou desbloquear.</p>

                                <div class="form-group" style="margin-bottom: 10px;">
                                    <label>Selecionar Datas</label>
                                    <input type="text" id="blockDatesPicker" class="date-input" placeholder="Selecione as datas" style="width: 100%;">
                                </div>

                                <div id="blockWarning" class="price-warning" style="display: none;">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span></span>
                                </div>

                                <div class="btn-group" id="blockActions">
                                    <button type="button" class="btn-reservar" id="btnBlockDates" style="background: #ffc107;">
                                        <i class="fas fa-lock"></i> Bloquear Selecionadas
                                    </button>
                                    <button type="button" class="btn-reservar" id="btnUnblockDates" style="background: #28a745; display: none;">
                                        <i class="fas fa-unlock"></i> Desbloquear Selecionadas
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="reservation-form sidebar-section">
                            <h3><i class="fas fa-eye"></i> Modo Visualização</h3>
                            <p>Está a visualizar uma casa de outro proprietário. Pode consultar o calendário e disponibilidade, mas não pode fazer alterações.</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Seção de Avaliações -->
    <?php if ($casa_id && $casa): ?>
        <div class="avaliacoes-section" style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
            <div class="section-header" style="margin-bottom: 30px;">
                <h2 style="color: #038e01; margin-bottom: 10px;">
                    <i class="fas fa-star"></i> Avaliações
                </h2>
                <p style="color: #666;">Veja o que os hóspedes dizem sobre este alojamento</p>
            </div>

            <div id="avaliacoes-container" data-titulo="<?php echo htmlspecialchars($casa['titulo']); ?>">
            </div>
        </div>
    <?php endif; ?>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>

    <script>
        // Configurações globais
        const currentDate = new Date();
        const currentMonth = <?php echo $mes; ?>;
        const currentYear = <?php echo $ano; ?>;
        const casaId = <?php echo $casa_id ?: 'null'; ?>;
        const tipoUtilizador = '<?php echo $tipo_utilizador; ?>';
        const isPropriaCasa = <?php echo isset($is_propria_casa) && $is_propria_casa ? 'true' : 'false'; ?>;

        // Pass user info to JavaScript for permissions
        window.currentUserId = <?php echo $user_id; ?>;
        window.currentUserType = '<?php echo $tipo_utilizador; ?>';
        window.casaIdAtual = casaId;

        // Elementos DOM
        const calendarGrid = document.getElementById('calendarGrid');
        const weatherCurrent = document.getElementById('weatherCurrent');

        // Arrays de meses e dias
        const months = [
            'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
        ];

        const daysOfWeek = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

        // Variáveis para disponibilidade
        let availabilityData = {};
        let weatherData = {};
        let selectedDate = null;
        let clickTimeout = null;
        let flatpickrInstances = {};

        /**
         * FUNÇÃO ÚNICA E CENTRALIZADA PARA FORMATAÇÃO DE DATAS
         * Converte qualquer formato para YYYY-MM-DD
         */
        function normalizeDate(dateStr) {
            if (!dateStr || dateStr.trim() === '') return null;

            dateStr = dateStr.trim();

            // Se já estiver no formato YYYY-MM-DD
            if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr;

            // Tentar converter de DD/MM/YYYY ou DD-MM-YYYY
            const parts = dateStr.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
            if (parts) {
                return `${parts[3]}-${parts[2].padStart(2, '0')}-${parts[1].padStart(2, '0')}`;
            }

            // Tentar converter de outros formatos com Date
            const timestamp = Date.parse(dateStr);
            if (!isNaN(timestamp)) {
                const d = new Date(dateStr);
                return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
            }

            console.warn('Não foi possível normalizar a data:', dateStr);
            return null;
        }

        /**
         * Converte YYYY-MM-DD para DD/MM/YYYY para exibição
         */
        function formatDisplayDate(dateStr) {
            if (!dateStr) return '';
            if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
                const [y, m, d] = dateStr.split('-');
                return `${d}/${m}/${y}`;
            }
            return dateStr;
        }

        /**
         * Cria objeto Date seguro a partir de string
         */
        function createSafeDate(dateStr) {
            const iso = normalizeDate(dateStr);
            if (!iso) return null;
            const [y, m, d] = iso.split('-').map(Number);
            return new Date(y, m - 1, d);
        }

        /**
         * SISTEMA DE NOTIFICAÇÕES UNIFICADO
         */
        function showNotification(type, message) {
            if (typeof AlugaTorresNotifications !== 'undefined') {
                const methods = {
                    'success': () => AlugaTorresNotifications.success(message),
                    'warning': () => AlugaTorresNotifications.warning(message),
                    'error': () => AlugaTorresNotifications.error(message)
                };
                (methods[type] || methods.error)();
            } else {
                alert(message);
            }
        }

        /**
         * Debounce utility function
         */
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        async function loadAvailability() {
            try {
                const timestamp = new Date().getTime();
                const response = await fetch(`../backend_api/api_availability.php?casa_id=${casaId}&mes=${currentMonth}&ano=${currentYear}&_=${timestamp}`);

                if (!response.ok) throw new Error(`HTTP ${response.status}: Erro ao carregar disponibilidade`);

                const data = await response.json();
                availabilityData = data;

                // Regenerate calendar
                while (calendarGrid.children.length > 7) {
                    calendarGrid.removeChild(calendarGrid.lastChild);
                }
                generateCalendar(currentMonth, currentYear);

                // Auto-select hoje se estiver no mês atual
                const todayStr = new Date().toISOString().split('T')[0];
                if (availabilityData[todayStr]) {
                    setTimeout(() => {
                        const todayCell = calendarGrid.querySelector(`[data-date="${todayStr}"]`);
                        if (todayCell) {
                            todayCell.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                            todayCell.classList.add('today-highlight');
                            selectDay(new Date(todayStr), todayStr);
                        }
                    }, 100);
                }

                if (selectedDate) {
                    updateSidebarForSelectedDay(createSafeDate(selectedDate), selectedDate);
                }

                // Update UI
                updateSpecialPriceActions();
                updateBlockActions();

            } catch (error) {
                console.error('❌ loadAvailability failed:', error);
            }
        }

        function generateCalendar(month, year) {
            if (!calendarGrid) return;

            // Limpar calendário
            while (calendarGrid.children.length > 7) {
                calendarGrid.removeChild(calendarGrid.lastChild);
            }

            month = Math.max(1, Math.min(12, parseInt(month) || 1));
            year = parseInt(year) || new Date().getFullYear();

            const firstDay = new Date(Date.UTC(year, month - 1, 1));
            const lastDay = new Date(Date.UTC(year, month, 0));
            const firstDayIndex = firstDay.getUTCDay();
            const prevLastDay = new Date(Date.UTC(year, month - 1, 0)).getUTCDate();
            const todayStr = new Date().toISOString().split('T')[0];

            // Dias do mês anterior
            for (let i = firstDayIndex; i > 0; i--) {
                addCalendarDay(new Date(Date.UTC(year, month - 2, prevLastDay - i + 1)), true, todayStr);
            }

            // Dias do mês atual
            for (let i = 1; i <= lastDay.getUTCDate(); i++) {
                addCalendarDay(new Date(Date.UTC(year, month - 1, i)), false, todayStr);
            }

            // Dias do próximo mês
            const nextDays = 42 - (firstDayIndex + lastDay.getUTCDate());
            for (let i = 1; i <= nextDays; i++) {
                addCalendarDay(new Date(Date.UTC(year, month, i)), true, todayStr);
            }
        }

        function addCalendarDay(date, isOtherMonth, todayStr) {
            const day = document.createElement('div');
            day.className = 'calendar-day' + (isOtherMonth ? ' other-month' : '');

            const dateStr = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

            if (dateStr === todayStr) day.classList.add('today');
            if (dateStr < todayStr) day.classList.add('past');

            day.setAttribute('data-date', dateStr);

            // Número do dia
            const dayNumber = document.createElement('div');
            dayNumber.className = 'day-number';
            dayNumber.textContent = date.getDate();
            day.appendChild(dayNumber);

            // Status de disponibilidade (apenas futuro/hoje)
            if (dateStr >= todayStr && availabilityData[dateStr]) {
                const status = availabilityData[dateStr].status;

                // Adicionar classe de status com base no valor
                if (status === 'available') {
                    day.classList.add('available');
                } else if (status === 'reserved') {
                    day.classList.add('reserved');
                } else if (status === 'blocked') {
                    day.classList.add('blocked');
                }

                // Adicionar indicador visual
                const indicator = document.createElement('div');
                indicator.className = `day-status-indicator ${status}`;
                day.appendChild(indicator);

                // Preço especial - verificar se existe e adicionar elemento
                if (availabilityData[dateStr].special_price) {
                    day.classList.add('special-price');

                    // Criar elemento de preço
                    const priceElement = document.createElement('div');
                    priceElement.className = 'day-events';
                    priceElement.innerHTML = `${availabilityData[dateStr].special_price}€ 🔥`;
                    day.appendChild(priceElement);
                }
            } else if (dateStr >= todayStr) {
                // Data não está no array de disponibilidade, considerar disponível
                day.classList.add('available');
                const indicator = document.createElement('div');
                indicator.className = 'day-status-indicator available';
                day.appendChild(indicator);
            }

            // Meteorologia
            if (weatherData[dateStr]) {
                const temp = dateStr === todayStr ?
                    (weatherData[dateStr].temp_atual || weatherData[dateStr].temp_max) :
                    weatherData[dateStr].temp_max;

                const weather = document.createElement('div');
                weather.className = 'day-weather';
                weather.innerHTML = `<span style="font-size: 1.2em; font-weight: bold;">${Math.round(temp)}°</span>`;
                day.appendChild(weather);
            }

            // Evento de clique
            day.addEventListener('click', (e) => {
                e.preventDefault();
                if (clickTimeout) clearTimeout(clickTimeout);
                clickTimeout = setTimeout(() => selectDay(date, dateStr), 250);
            });

            calendarGrid.appendChild(day);
        }

        function selectDay(date, dateStr) {
            document.querySelectorAll('.calendar-day.selected').forEach(d => d.classList.remove('selected'));

            const clickedDay = calendarGrid.querySelector(`.calendar-day[data-date="${dateStr}"]`);
            if (clickedDay) {
                clickedDay.classList.add('selected');
                selectedDate = dateStr;
            }

            updateSidebarForSelectedDay(date, dateStr);
            updateWeatherWidgetForDate(dateStr);
        }

        function updateSidebarForSelectedDay(date, dateStr) {
            const dayName = daysOfWeek[date.getDay()];
            const monthName = months[date.getMonth()];
            const weatherWidget = document.querySelector('.weather-widget');

            if (!weatherWidget) return;

            // Remover seções antigas
            weatherWidget.querySelectorAll('.sidebar-section:not(#reservationForm)').forEach(s => s.remove());

            const avail = availabilityData[dateStr] || {
                status: 'available',
                special_price: null
            };

            // Info de disponibilidade
            const infoSection = document.createElement('div');
            infoSection.className = 'sidebar-section';
            infoSection.innerHTML = `
                <h4><i class="fas fa-info-circle"></i> Disponibilidade</h4>
                <p><strong>Status:</strong> ${getStatusText(avail.status)}</p>
                ${avail.special_price ? `<p><strong>Preço especial:</strong> ${avail.special_price}€ 🔥</p>` : ''}
            `;
            weatherWidget.appendChild(infoSection);

            const todayStr = new Date().toISOString().split('T')[0];
            const isPast = dateStr < todayStr;

            // Ações para arrendatário
            if (tipoUtilizador === 'arrendatario' && casaId) {
                const isAvailable = !availabilityData[dateStr] || availabilityData[dateStr].status === 'available';
                const isBlocked = availabilityData[dateStr]?.status === 'blocked';
                const isReserved = availabilityData[dateStr]?.status === 'reserved';

                const reservationForm = document.getElementById('reservationForm');
                if (reservationForm) {
                    if (isAvailable && !isPast) {
                        reservationForm.style.display = 'block';
                        document.querySelector('#reservationForm h3').innerHTML =
                            `<i class="fas fa-calendar-plus"></i> Fazer Reserva - ${dayName}, ${date.getDate()} de ${monthName}`;
                        // Garantir que inputs estão limpos para nova seleção
                        if (flatpickrInstances.checkin) flatpickrInstances.checkin.clear();
                        if (flatpickrInstances.checkout) flatpickrInstances.checkout.clear();
                        document.getElementById('reservationSummary').style.display = 'none';
                    } else {
                        reservationForm.style.display = 'none';
                        if (isPast) {
                            infoSection.innerHTML += `<p style="color: #dc3545;"><i class="fas fa-times-circle"></i> Esta data já passou.</p>`;
                        } else if (isBlocked) {
                            infoSection.innerHTML += `<p style="color: #dc3545;"><i class="fas fa-times-circle"></i> Esta data está bloqueada.</p>`;
                        } else if (isReserved) {
                            infoSection.innerHTML += `<p style="color: #dc3545;"><i class="fas fa-times-circle"></i> Esta data já está reservada.</p>`;
                        }
                    }
                }
            }
        }

        function getStatusText(status) {
            const map = {
                'available': 'Disponível',
                'reserved': 'Reservado',
                'blocked': 'Bloqueado',
                'pending': 'Pendente'
            };
            return map[status] || status;
        }

        /**
         * GESTÃO DE PREÇOS ESPECIAIS
         */
        function updateSpecialPriceActions() {

            const datesInput = document.getElementById('specialPriceDates');
            const applyBtn = document.getElementById('btnApplySpecialPrice');
            const removeBtn = document.getElementById('btnRemoveSpecialPrice');
            const priceWarning = document.getElementById('priceWarning');

            if (!datesInput || !applyBtn || !removeBtn) {
                return;
            }

            const datesRaw = datesInput.value.trim();

            if (!datesRaw) {
                applyBtn.style.display = 'block';
                removeBtn.style.display = 'none';
                if (priceWarning) priceWarning.style.display = 'none';
                applyBtn.innerHTML = '<i class="fas fa-check"></i> Aplicar Preço';
                return;
            }

            const dates = datesRaw.split(/[,;\s]+/).map(d => d.trim()).filter(d => d.length > 4);

            const withSpecial = [];
            const withoutSpecial = [];
            const invalidDates = [];

            dates.forEach((dateStr) => {
                const isoDate = normalizeDate(dateStr);
                if (!isoDate) {
                    invalidDates.push(dateStr);
                } else if (availabilityData[isoDate] && availabilityData[isoDate].special_price != null) {
                    withSpecial.push(dateStr);
                } else {
                    withoutSpecial.push(dateStr);
                }
            });

            if (priceWarning && invalidDates.length > 0) {
                priceWarning.style.display = 'block';
                priceWarning.querySelector('span').innerHTML =
                    `❌ ${invalidDates.length} data(s) inválidas: ${invalidDates.slice(0,2).join(', ')}${invalidDates.length > 2 ? '...' : ''}`;
                return;
            }

            if (priceWarning) {
                const warningSpan = priceWarning.querySelector('span');
                if (withSpecial.length > 0 && withoutSpecial.length > 0) {
                    priceWarning.style.display = 'block';
                    warningSpan.innerHTML = `⚠️ ${withSpecial.length}/${dates.length} datas têm preço especial`;
                } else if (withSpecial.length === dates.length && withSpecial.length > 0) {
                    priceWarning.style.display = 'block';
                    warningSpan.innerHTML = `✅ Todas as ${withSpecial.length} datas têm preço especial`;
                } else {
                    priceWarning.style.display = 'none';
                }
            }

            if (withSpecial.length > 0) {
                removeBtn.innerHTML = `<i class="fas fa-trash"></i> Remover (${withSpecial.length})`;
                removeBtn.style.display = 'block';
                removeBtn.disabled = false;

                if (withoutSpecial.length > 0) {
                    applyBtn.innerHTML = `<i class="fas fa-check"></i> Aplicar (${withoutSpecial.length})`;
                    applyBtn.style.display = 'block';
                } else {
                    applyBtn.style.display = 'none';
                }
            } else {
                applyBtn.innerHTML = `<i class="fas fa-check"></i> Aplicar (${withoutSpecial.length || dates.length})`;
                applyBtn.style.display = 'block';
                removeBtn.style.display = 'none';
            }
        }

        async function applySpecialPrice() {
            const priceInput = document.getElementById('specialPrice');
            const datesInput = document.getElementById('specialPriceDates');
            const applyBtn = document.getElementById('btnApplySpecialPrice');

            if (!datesInput.value) {
                showNotification('warning', 'Selecione as datas para aplicar o preço especial');
                return;
            }

            if (!priceInput.value) {
                showNotification('warning', 'Defina um preço especial');
                return;
            }

            const originalHTML = applyBtn.innerHTML;
            applyBtn.disabled = true;
            applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> A processar...';

            try {
                const dates = datesInput.value.split(',').map(d => d.trim()).filter(d => d);

                const datesToApply = [];
                const skippedDates = [];

                dates.forEach(dateStr => {
                    const iso = normalizeDate(dateStr);
                    if (iso && availabilityData[iso] && availabilityData[iso].special_price) {
                        skippedDates.push(dateStr);
                    } else {
                        datesToApply.push(dateStr);
                    }
                });

                if (datesToApply.length === 0) {
                    showNotification('warning', 'Todas as datas selecionadas já têm preço especial. Use "Remover" se deseja removê-los.');
                    return;
                }

                if (skippedDates.length > 0) {
                    if (!confirm(`${skippedDates.length} data(s) já têm preço especial e serão ignoradas. Deseja continuar?`)) {
                        return;
                    }
                }

                const response = await fetch('../backend_api/api_availability.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'special_price',
                        casa_id: casaId,
                        dates: datesToApply,
                        price: priceInput.value
                    })
                });

                const data = await response.json();

                if (data.success) {
                    await loadAvailability();
                    showNotification('success', `Preço especial aplicado a ${datesToApply.length} data(s)!`);
                    priceInput.value = '';
                    datesInput.value = '';
                    updateSpecialPriceActions();
                } else {
                    throw new Error(data.error || 'Erro ao aplicar preço');
                }
            } catch (error) {
                showNotification('error', error.message);
            } finally {
                applyBtn.innerHTML = originalHTML;
                applyBtn.disabled = false;
            }
        }

        async function removeSpecialPrice() {
            const datesInput = document.getElementById('specialPriceDates');
            const removeBtn = document.getElementById('btnRemoveSpecialPrice');

            if (!datesInput.value) {
                showNotification('warning', 'Selecione as datas para remover o preço especial');
                return;
            }

            const originalHTML = removeBtn.innerHTML;
            removeBtn.disabled = true;
            removeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> A processar...';

            try {
                const dates = datesInput.value.split(',').map(d => d.trim()).filter(d => d);

                const datesToRemove = [];
                const skippedDates = [];

                dates.forEach(dateStr => {
                    const iso = normalizeDate(dateStr);
                    if (iso) {
                        if (availabilityData[iso] && availabilityData[iso].special_price) {
                            datesToRemove.push(dateStr);
                        } else {
                            skippedDates.push(dateStr);
                        }
                    }
                });

                if (datesToRemove.length === 0) {
                    showNotification('warning', 'Nenhuma das datas selecionadas tem preço especial para remover');
                    return;
                }

                if (skippedDates.length > 0) {
                    if (!confirm(`${skippedDates.length} data(s) não têm preço especial e serão ignoradas. Deseja continuar?`)) {
                        return;
                    }
                }

                const response = await fetch('../backend_api/api_availability.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'special_price',
                        casa_id: casaId,
                        dates: datesToRemove,
                        price: null
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('success', data.message || `Preço especial removido de ${datesToRemove.length} data(s)!`);
                    datesInput.value = '';
                    document.getElementById('specialPrice').value = '';
                    await loadAvailability();
                    updateSpecialPriceActions();
                } else {
                    throw new Error(data.error || 'Erro ao remover preço');
                }
            } catch (error) {
                console.error('Erro na remoção:', error);
                showNotification('error', error.message);
            } finally {
                removeBtn.innerHTML = originalHTML;
                removeBtn.disabled = false;
            }
        }

        /**
         * GESTÃO DE BLOQUEIOS EM LOTE
         */
        function updateBlockActions() {
            const datesInput = document.getElementById('blockDatesPicker');
            const blockBtn = document.getElementById('btnBlockDates');
            const unblockBtn = document.getElementById('btnUnblockDates');
            const blockWarning = document.getElementById('blockWarning');

            if (!datesInput || !blockBtn || !unblockBtn) {
                console.warn('❌ Block elements missing');
                return;
            }

            const datesRaw = datesInput.value.trim();

            if (!datesRaw) {
                blockBtn.style.display = 'block';
                unblockBtn.style.display = 'none';
                if (blockWarning) blockWarning.style.display = 'none';
                blockBtn.innerHTML = '<i class="fas fa-lock"></i> Bloquear Selecionadas';
                return;
            }

            const dates = datesRaw.split(/[,;\s]+/).map(d => d.trim()).filter(d => d.length > 4);

            const blocked = [];
            const available = [];
            const invalidDates = [];

            dates.forEach((dateStr) => {
                const isoDate = normalizeDate(dateStr);

                if (!isoDate) {
                    invalidDates.push(dateStr);
                } else if (availabilityData[isoDate] && availabilityData[isoDate].status === 'blocked') {
                    blocked.push(dateStr);
                } else {
                    available.push(dateStr);
                }
            });

            if (blockWarning && invalidDates.length > 0) {
                blockWarning.style.display = 'block';
                blockWarning.querySelector('span').innerHTML =
                    `❌ ${invalidDates.length} data(s) inválidas: ${invalidDates.slice(0,2).join(', ')}${invalidDates.length > 2 ? '...' : ''}`;
                return;
            }

            if (blockWarning) {
                if (blocked.length > 0 && available.length > 0) {
                    blockWarning.style.display = 'block';
                    blockWarning.querySelector('span').innerHTML =
                        `⚠️ ${blocked.length} bloqueada(s), ${available.length} disponível(is)`;
                } else if (blocked.length === dates.length && blocked.length > 0) {
                    blockWarning.style.display = 'block';
                    blockWarning.querySelector('span').innerHTML =
                        `✅ Todas as ${blocked.length} datas estão bloqueadas`;
                } else if (available.length === dates.length && available.length > 0) {
                    blockWarning.style.display = 'block';
                    blockWarning.querySelector('span').innerHTML =
                        `✅ Todas as ${available.length} datas estão disponíveis`;
                } else {
                    blockWarning.style.display = 'none';
                }
            }

            if (blocked.length > 0) {
                unblockBtn.innerHTML = `<i class="fas fa-unlock"></i> Desbloquear (${blocked.length})`;
                unblockBtn.style.display = 'block';
                unblockBtn.disabled = false;

                if (available.length > 0) {
                    blockBtn.innerHTML = `<i class="fas fa-lock"></i> Bloquear (${available.length})`;
                    blockBtn.style.display = 'block';
                } else {
                    blockBtn.style.display = 'none';
                }
            } else {
                blockBtn.innerHTML = `<i class="fas fa-lock"></i> Bloquear (${available.length || dates.length})`;
                blockBtn.style.display = 'block';
                unblockBtn.style.display = 'none';
            }
        }

        async function blockSelectedDates() {
            const datesInput = document.getElementById('blockDatesPicker');
            const blockBtn = document.getElementById('btnBlockDates');

            if (!datesInput.value) {
                showNotification('warning', 'Selecione as datas para bloquear');
                return;
            }

            const originalHTML = blockBtn.innerHTML;
            blockBtn.disabled = true;
            blockBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> A bloquear...';

            try {
                const dates = datesInput.value.split(',').map(d => d.trim()).filter(d => d);

                const datesToBlock = [];
                const skippedDates = [];

                dates.forEach(dateStr => {
                    const iso = normalizeDate(dateStr);
                    if (iso) {
                        if (availabilityData[iso] && availabilityData[iso].status === 'blocked') {
                            skippedDates.push(dateStr);
                        } else {
                            datesToBlock.push(dateStr);
                        }
                    }
                });

                if (datesToBlock.length === 0) {
                    showNotification('warning', 'Todas as datas selecionadas já estão bloqueadas');
                    return;
                }

                if (skippedDates.length > 0) {
                    if (!confirm(`${skippedDates.length} data(s) já estão bloqueadas e serão ignoradas. Deseja continuar?`)) {
                        return;
                    }
                }

                const response = await fetch('../backend_api/api_availability.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'block',
                        casa_id: casaId,
                        dates: datesToBlock
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('success', data.message || `${datesToBlock.length} data(s) bloqueadas!`);
                    datesInput.value = '';
                    await loadAvailability();
                    updateBlockActions();
                } else {
                    throw new Error(data.error || 'Erro ao bloquear');
                }
            } catch (error) {
                showNotification('error', error.message);
            } finally {
                blockBtn.innerHTML = originalHTML;
                blockBtn.disabled = false;
            }
        }

        async function unblockSelectedDates() {
            const datesInput = document.getElementById('blockDatesPicker');
            const unblockBtn = document.getElementById('btnUnblockDates');

            if (!datesInput.value) {
                showNotification('warning', 'Selecione as datas para desbloquear');
                return;
            }

            const originalHTML = unblockBtn.innerHTML;
            unblockBtn.disabled = true;
            unblockBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> A desbloquear...';

            try {
                const dates = datesInput.value.split(',').map(d => d.trim()).filter(d => d);

                const datesToUnblock = [];
                const skippedDates = [];

                dates.forEach(dateStr => {
                    const iso = normalizeDate(dateStr);
                    if (iso) {
                        if (availabilityData[iso] && availabilityData[iso].status === 'blocked') {
                            datesToUnblock.push(dateStr);
                        } else {
                            skippedDates.push(dateStr);
                        }
                    }
                });

                if (datesToUnblock.length === 0) {
                    showNotification('warning', 'Nenhuma das datas selecionadas está bloqueada');
                    return;
                }

                if (skippedDates.length > 0) {
                    if (!confirm(`${skippedDates.length} data(s) não estão bloqueadas e serão ignoradas. Deseja continuar?`)) {
                        return;
                    }
                }

                const response = await fetch('../backend_api/api_availability.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'unblock',
                        casa_id: casaId,
                        dates: datesToUnblock
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('success', data.message || `${datesToUnblock.length} data(s) desbloqueadas!`);
                    datesInput.value = '';
                    await loadAvailability();
                    updateBlockActions();
                } else {
                    throw new Error(data.error || 'Erro ao desbloquear');
                }
            } catch (error) {
                showNotification('error', error.message);
            } finally {
                unblockBtn.innerHTML = originalHTML;
                unblockBtn.disabled = false;
            }
        }

        /**
         * GESTÃO DE BLOQUEIOS INDIVIDUAL
         */
        async function toggleBlockDate(dateStr) {
            const isBlocked = availabilityData[dateStr]?.status === 'blocked';
            const action = isBlocked ? 'unblock_single' : 'block_single';

            try {
                const response = await fetch('../backend_api/api_availability.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: action,
                        casa_id: casaId,
                        date: dateStr
                    })
                });

                const data = await response.json();
                if (data.success) {
                    showNotification('success', `Data ${isBlocked ? 'desbloqueada' : 'bloqueada'}!`);
                    await loadAvailability();
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                showNotification('error', error.message);
            }
        }

        /**
         * METEOROLOGIA
         */
        async function loadWeatherData() {
            async function setWeatherFromPrevisao(previsao, isDia = true) {
                if (!previsao?.length) throw new Error('Previsão vazia');

                const hoje = previsao.find(d => d.hoje) || previsao[0];

                weatherCurrent.classList.remove('weather-current-day', 'weather-current-night', 'weather-current-loading');
                weatherCurrent.classList.add(isDia ? 'weather-current-day' : 'weather-current-night');

                weatherCurrent.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div class="weather-temp">${Math.round(hoje.temperatura_atual || hoje.temperatura_media || hoje.temperatura_maxima || 0)}°C</div>
                            <div class="weather-desc">
                                <img src="${isDia ? (hoje.icone_dia || hoje.icone) : (hoje.icone_noite || hoje.icone) || ''}" alt="${hoje.descricao_pt || hoje.descricao || ''}" style="width: 70px; height: 70px;">
                                ${hoje.descricao_pt || hoje.descricao || ''}
                            </div>
                            <div>${hoje.dia_semana_pt || hoje.dia_semana || ''} • Torres Novas</div>
                        </div>
                        <div style="text-align: right;">
                            <div>Max: ${Math.round(hoje.temperatura_maxima || 0)}°C</div>
                            <div>Min: ${Math.round(hoje.temperatura_minima || 0)}°C</div>
                        </div>
                    </div>
                    <div class="weather-details">
                        <div><i class="fas fa-wind"></i> Vento: ${Math.round(hoje.vento_medio || hoje.vento || 0)} km/h</div>
                        <div><i class="fas fa-tint"></i> Humidade: ${Math.round(hoje.humidade_media || 0)}%</div>
                    </div>
                `;

                previsao.forEach(day => {
                    const key = (day.data || '').split('T')[0];
                    if (!key) return;
                    weatherData[key] = {
                        temp: day.temperatura_atual || 
                        day.temperatura_media || day.temperatura_maxima || 0,
                        temp_atual: day.temperatura_atual || null,
                        temp_min: day.temperatura_minima || 0,
                        temp_max: day.temperatura_maxima || 0,
                        temp_media: day.temperatura_media || null,
                        desc: day.descricao_pt || day.descricao || '',
                        icon: day.icone || '',
                        icone_dia: day.icone_dia || day.icone || '',
                        icone_noite: day.icone_noite || day.icone || '',
                        humidity: day.humidade_media || 0,
                        wind: day.vento_medio || day.vento || 0
                    };
                });

                generateCalendar(currentMonth, currentYear);

                const widgetDate = selectedDate || 
                (hoje.data?.split('T')[0]) || (previsao[0]?.data?.split('T')[0]);
                if (widgetDate) updateWeatherWidgetForDate(widgetDate);
            }

            try {
                const statusEl = document.getElementById('weatherSourceStatus');
                if (statusEl) {
                    statusEl.textContent = 'Carregando Open-Meteo...';
                    statusEl.className = 'weather-source weather-source-loading';
                }

                weatherCurrent.classList.add('weather-current-loading');
                await new Promise(resolve => setTimeout(resolve, 2000));

                const response = await fetch('https://api.open-meteo.com/v1/forecast?latitude=39.4811&longitude=-8.5394&current=temperature_2m&daily=temperature_2m_max,temperature_2m_min,weathercode,windspeed_10m_max&hourly=relative_humidity_2m&forecast_days=16&timezone=Europe/Lisbon');
                if (!response.ok) throw new Error('Erro ao carregar meteorologia');

                const apiData = await response.json();
                const previsao = processWeatherData(apiData);
                const horaAtual = new Date().getHours();
                const isDia = 6 <= horaAtual && horaAtual < 18;

                if (statusEl) {
                    statusEl.textContent = 'Open-Meteo';
                    statusEl.className = 'weather-source weather-source-success';
                }

                await setWeatherFromPrevisao(previsao, isDia);
            } catch (error) {
                console.error('Erro ao carregar meteorologia:', error);
                const statusEl = document.getElementById('weatherSourceStatus');
                if (statusEl) {
                    statusEl.textContent = 'Meteorologia indisponível';
                    statusEl.className = 'weather-source weather-source-error';
                }
                weatherCurrent.innerHTML = `
                    <div style="text-align: center; padding: 20px;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 2em; color: #ffc107;"></i>
                        <p>Não foi possível carregar a previsão do tempo</p>
                        <p style="font-size:0.9em; color:#666;">(Tente novamente mais tarde)</p>
                    </div>
                `;
            }
        }

        function processWeatherData(apiData) {
            const resultado = [];
            const hoje = new Date().toISOString().split('T')[0];

            const weatherCodesMap = {
                0: "céu limpo",
                1: "parcialmente limpo",
                2: "parcialmente nublado",
                3: "nublado",
                45: "nevoeiro",
                48: "nevoeiro com geada",
                51: "chuvisco leve",
                53: "chuvisco moderado",
                55: "chuvisco intenso",
                56: "chuvisco congelado leve",
                57: "chuvisco congelado intenso",
                61: "chuva leve",
                63: "chuva moderada",
                65: "chuva forte",
                66: "chuva congelada leve",
                67: "chuva congelada forte",
                71: "neve leve",
                73: "neve moderada",
                75: "neve forte",
                77: "grãos de neve",
                80: "aguaceiro leve",
                81: "aguaceiro moderado",
                82: "aguaceiro forte",
                85: "neve em aguaceiro leve",
                86: "neve em aguaceiro forte",
                95: "trovoada",
                96: "trovoada com granizo leve",
                99: "trovoada com granizo forte"
            };

            const iconMapping = {
                0: "01d",
                1: "02d",
                2: "03d",
                3: "04d",
                45: "50d",
                48: "50d",
                51: "09d",
                53: "09d",
                55: "09d",
                56: "09d",
                57: "09d",
                61: "10d",
                63: "10d",
                65: "10d",
                66: "10d",
                67: "10d",
                71: "13d",
                73: "13d",
                75: "13d",
                77: "13d",
                80: "09d",
                81: "09d",
                82: "09d",
                85: "13d",
                86: "13d",
                95: "11d",
                96: "11d",
                99: "11d"
            };

            const datas = apiData.daily.time;
            const tempMax = apiData.daily.temperature_2m_max;
            const tempMin = apiData.daily.temperature_2m_min;
            const currentTemp = apiData.current?.temperature_2m;
            const weatherCodes = apiData.daily.weathercode;
            const windSpeed = apiData.daily.windspeed_10m_max;
            const hourlyTimes = apiData.hourly?.time || [];
            const hourlyHumidity = apiData.hourly?.relative_humidity_2m || [];

            function calcularHumidadeMedia(dataStr) {
                if (!hourlyTimes.length) return 0;
                const valores = [];
                for (let i = 0; i < hourlyTimes.length; i++) {
                    if (hourlyTimes[i].startsWith(dataStr)) {
                        valores.push(hourlyHumidity[i]);
                    }
                }
                return valores.length ? Math.round(valores.reduce((s, v) => s + v, 0) / valores.length) : 0;
            }

            for (let i = 0; i < datas.length; i++) {
                const dataStr = datas[i];
                const dataObj = new Date(dataStr + 'T00:00:00');
                const weatherCode = weatherCodes[i];

                resultado.push({
                    data: dataStr,
                    dia_semana: dataObj.toLocaleDateString('pt-PT', {
                        weekday: 'long'
                    }),
                    dia_semana_pt: dataObj.toLocaleDateString('pt-PT', {
                        weekday: 'long'
                    }),
                    temperatura_minima: tempMin[i],
                    temperatura_maxima: tempMax[i],
                    temperatura_media: Math.round((tempMin[i] + tempMax[i]) / 2 * 10) / 10,
                    descricao: weatherCodesMap[weatherCode] || "condições desconhecidas",
                    descricao_pt: weatherCodesMap[weatherCode] || "condições desconhecidas",
                    icone: `http://openweathermap.org/img/wn/${iconMapping[weatherCode] || '01d'}@2x.png`,
                    icone_dia: `http://openweathermap.org/img/wn/${iconMapping[weatherCode] || '01d'}@2x.png`,
                    icone_noite: `http://openweathermap.org/img/wn/${(iconMapping[weatherCode] || '01d').replace('d', 'n')}@2x.png`,
                    humidade_media: calcularHumidadeMedia(dataStr),
                    vento_medio: windSpeed[i],
                    hoje: dataStr === hoje,
                    temperatura_atual: dataStr === hoje && currentTemp ? currentTemp : null,
                    numero_previsoes: 1
                });
            }

            return resultado;
        }

        function updateWeatherWidgetForDate(dateStr) {
            if (!dateStr) return;
            const key = dateStr.split('T')[0];
            const day = weatherData[key];

            const weatherDateEl = document.getElementById('weatherDate');
            if (weatherDateEl) {
                const dataObj = new Date(dateStr);
                const isNoite = weatherCurrent.classList.contains('weather-current-night');
                weatherDateEl.innerHTML = `<i class="fas fa-calendar-day" style="margin-right: 8px; color: ${isNoite ? '#63b3ed' : '#038e01'};"></i>${
                    dataObj.toLocaleDateString('pt-PT', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
                }`;
            }

            if (!day) {
                weatherCurrent.innerHTML = `<div style="text-align:center; padding: 10px;">Sem previsão para ${new Date(dateStr).toLocaleDateString('pt-PT')}</div>`;
                return;
            }

            const isNoite = weatherCurrent.classList.contains('weather-current-night');
            weatherCurrent.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div class="weather-temp">${Math.round(day.temp_atual || day.temp_media || day.temp)}°C</div>
                        <div class="weather-desc">
                            <img src="${isNoite ? (day.icone_noite || day.icon) : (day.icone_dia || day.icon) || ''}" alt="${day.desc || ''}" style="width: 70px; height: 70px;">
                            ${day.desc || ''}
                        </div>
                        <div>${new Date(dateStr).toLocaleDateString('pt-PT', { weekday: 'long' })} • Torres Novas</div>
                    </div>
                    <div style="text-align: right;">
                        <div>Max: ${Math.round(day.temp_max || 0)}°C</div>
                        <div>Min: ${Math.round(day.temp_min || 0)}°C</div>
                    </div>
                </div>
                <div class="weather-details">
                    <div><i class="fas fa-wind"></i> Vento: ${Math.round(day.wind || 0)} km/h</div>
                    <div><i class="fas fa-tint"></i> Humidade: ${Math.round(day.humidity || 0)}%</div>
                </div>
            `;

            if (selectedDate === key) {
                updateSidebarForSelectedDay(new Date(dateStr), key);
            }
        }

        /**
         * RESERVAS
         */
        async function updateReservationSummary() {
            const checkinRaw = document.getElementById('checkinDate').value;
            const checkoutRaw = document.getElementById('checkoutDate').value;
            const guests = document.getElementById('numGuests').value;
            const summaryDiv = document.getElementById('reservationSummary');

            if (!checkinRaw || !checkoutRaw) {
                if (summaryDiv) summaryDiv.style.display = 'none';
                return;
            }

            const checkin = normalizeDate(checkinRaw);
            const checkout = normalizeDate(checkoutRaw);

            if (!checkin || !checkout) return;

            const checkinDate = new Date(checkin);
            const checkoutDate = new Date(checkout);
            const nights = Math.ceil((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));

            if (nights <= 0) {
                showNotification('warning', 'Checkout deve ser após checkin');
                return;
            }

            try {
                const response = await fetch(`../backend_api/api_reservas.php?action=calculate&casa_id=${casaId}&checkin=${encodeURIComponent(checkin)}&checkout=${encodeURIComponent(checkout)}&hospedes=${guests}`);
                const data = await response.json();

                if (data.error) {
                    showNotification('error', data.error);
                    return;
                }

                let weatherInfo = '';
                const checkinWeather = weatherData[checkin];
                const checkoutWeather = weatherData[checkout];

                if (checkinWeather || checkoutWeather) {
                    weatherInfo = `
                        <div class="summary-weather">
                            <h5><i class="fas fa-cloud-sun"></i> Previsão do Tempo</h5>
                            ${checkinWeather ? `<div class="weather-day-summary"><strong>Check-in:</strong> ${Math.round(checkinWeather.temp)}°C - ${checkinWeather.desc}</div>` : ''}
                            ${checkoutWeather && checkin !== checkout ? `<div class="weather-day-summary"><strong>Check-out:</strong> ${Math.round(checkoutWeather.temp)}°C - ${checkoutWeather.desc}</div>` : ''}
                        </div>
                    `;
                }

                if (summaryDiv) {
                    summaryDiv.innerHTML = `
                        <h4>Resumo da Reserva</h4>
                        <div class="summary-item"><span>${nights} noite(s)</span><span>${data.preco_noite}€/noite</span></div>
                        <div class="summary-item"><span>${guests} hóspede(s)</span><span></span></div>
                        ${data.taxa_limpeza > 0 ? `<div class="summary-item"><span>Taxa de limpeza</span><span>${data.taxa_limpeza}€</span></div>` : ''}
                        ${data.taxa_seguranca > 0 ? `<div class="summary-item"><span>Taxa de segurança</span><span>${data.taxa_seguranca}€</span></div>` : ''}
                        ${weatherInfo}
                        <div class="summary-item summary-total"><span>Total</span><span>${data.total}€</span></div>
                    `;

                    summaryDiv.style.display = 'block';
                }
            } catch (error) {
                console.error('Erro ao calcular reserva:', error);
                showNotification('error', 'Erro ao calcular preços');
            }
        }

        async function makeReservation() {
            const btnReservar = document.getElementById('btnReservar');
            if (!btnReservar) return;

            const originalHTML = btnReservar.innerHTML;
            btnReservar.disabled = true;
            btnReservar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> A processar...';

            const checkinRaw = document.getElementById('checkinDate')?.value;
            const checkoutRaw = document.getElementById('checkoutDate')?.value;
            const guests = document.getElementById('numGuests')?.value;

            if (!checkinRaw || !checkoutRaw) {
                showNotification('warning', 'Selecione as datas');
                btnReservar.innerHTML = originalHTML;
                btnReservar.disabled = false;
                return;
            }

            const checkin = normalizeDate(checkinRaw);
            const checkout = normalizeDate(checkoutRaw);

            if (!checkin || !checkout) {
                showNotification('error', 'Formato de data inválido');
                btnReservar.innerHTML = originalHTML;
                btnReservar.disabled = false;
                return;
            }

            if (new Date(checkout) <= new Date(checkin)) {
                showNotification('error', 'Checkout deve ser após checkin');
                btnReservar.innerHTML = originalHTML;
                btnReservar.disabled = false;
                return;
            }

            try {
                const response = await fetch('../backend_api/api_reservas.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'create',
                        casa_id: casaId,
                        checkin: checkin,
                        checkout: checkout,
                        hospedes: parseInt(guests) || 1,
                        tipo_utilizador: tipoUtilizador
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('success', 'Reserva criada com sucesso!');
                    document.getElementById('checkinDate').value = '';
                    document.getElementById('checkoutDate').value = '';
                    document.getElementById('reservationSummary').style.display = 'none';
                    selectedDate = null;
                    document.querySelectorAll('.calendar-day.selected').forEach(d => d.classList.remove('selected'));
                    await loadAvailability();
                } else {
                    showNotification('error', data?.error || 'Erro ao criar reserva');
                }
            } catch (error) {
                showNotification('error', 'Erro: ' + error.message);
            } finally {
                btnReservar.innerHTML = originalHTML;
                btnReservar.disabled = false;
            }
        }

        /**
         * INICIALIZAÇÃO
         */
        document.addEventListener('DOMContentLoaded', function() {
            generateCalendar(currentMonth, currentYear);
            loadWeatherData();
            if (casaId) loadAvailability();

            // Configurar datepickers para reserva
            if (document.getElementById('checkinDate')) {
                flatpickrInstances.checkin = flatpickr('#checkinDate', {
                    locale: 'pt',
                    dateFormat: 'Y-m-d',
                    minDate: 'today',
                    onChange: (selectedDates, dateStr) => {
                        if (flatpickrInstances.checkout && selectedDates[0]) {
                            flatpickrInstances.checkout.set('minDate', selectedDates[0]);
                        }
                        updateReservationSummary();
                    }
                });

                flatpickrInstances.checkout = flatpickr('#checkoutDate', {
                    locale: 'pt',
                    dateFormat: 'Y-m-d',
                    minDate: 'today',
                    onChange: () => updateReservationSummary()
                });
            }

            // Configurar datepicker para preços especiais
            if (document.getElementById('specialPriceDates')) {
                flatpickrInstances.specialPrice = flatpickr('#specialPriceDates', {
                    locale: 'pt',
                    mode: 'multiple',
                    dateFormat: 'Y-m-d',
                    minDate: 'today',
                    onChange: () => setTimeout(() => updateSpecialPriceActions(), 100)
                });
            }

            // Configurar datepicker para bloqueio em lote
            if (document.getElementById('blockDatesPicker')) {
                flatpickrInstances.blockDates = flatpickr('#blockDatesPicker', {
                    locale: 'pt',
                    mode: 'multiple',
                    dateFormat: 'Y-m-d',
                    minDate: 'today',
                    onChange: () => setTimeout(() => updateBlockActions(), 100)
                });
            }

            // Event listeners
            document.getElementById('btnReservar')?.addEventListener('click', makeReservation);
            document.getElementById('btnApplySpecialPrice')?.addEventListener('click', applySpecialPrice);
            document.getElementById('btnRemoveSpecialPrice')?.addEventListener('click', removeSpecialPrice);
            document.getElementById('btnBlockDates')?.addEventListener('click', blockSelectedDates);
            document.getElementById('btnUnblockDates')?.addEventListener('click', unblockSelectedDates);
        });

        // Expor funções globalmente
        window.toggleBlockDate = toggleBlockDate;
        window.applySpecialPrice = applySpecialPrice;
        window.removeSpecialPrice = removeSpecialPrice;
        window.updateSpecialPriceActions = updateSpecialPriceActions;
        window.blockSelectedDates = blockSelectedDates;
        window.unblockSelectedDates = unblockSelectedDates;
        window.updateBlockActions = updateBlockActions;

        // Inicializar avaliações
        if (casaId && window.initAvaliacoesWidget) {
            setTimeout(() => initAvaliacoesWidget(casaId, 'avaliacoes-container'), 500);
        }
    </script>
</body>

</html>