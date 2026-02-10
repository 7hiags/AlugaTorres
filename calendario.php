<?php
session_start();
require_once 'backend/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: backend/login.php");
    exit;
}

// Verificar se o usuário ainda existe na base de dados
$stmt = $conn->prepare("SELECT id FROM utilizadores WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    session_destroy();
    header("Location: ../backend/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$tipo_utilizador = $_SESSION['tipo_utilizador'] ?? 'arrendatario';
$casa_id = isset($_GET['casa_id']) ? (int)$_GET['casa_id'] : null;
$casa = null;

if ($tipo_utilizador === 'proprietario' && $casa_id) {
    $query = $conn->prepare("SELECT id, titulo FROM casas WHERE id = ? AND proprietario_id = ?");
    $query->bind_param("ii", $casa_id, $user_id);
    $query->execute();
    $result = $query->get_result();
    $casa = $result->fetch_assoc();

    if (!$casa) {
        header("Location: dashboard.php");
        exit;
    }
}

$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$casa_id_url = $casa_id ? "&casa_id=$casa_id" : '';
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlugaTorres | Calendário</title>
    <link rel="stylesheet" href="style/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="website icon" type="png" href="style/img/Logo_AlugaTorres_branco.png">
</head>


<body>
    <?php include 'header.php'; ?>
    <?php include 'sidebar.php'; ?>

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

                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-color legend-today"></div>
                        <span>Hoje</span>
                    </div>
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
                </div>
            </div>

            <div class="calendar-sidebar">
                <div class="weather-widget">
                    <h3 class="weather-title">
                        <i class="fas fa-cloud-sun"></i> Meteorologia - Torres Novas
                    </h3>

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
                    <div class="reservation-form">
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
                    <div class="reservation-form">
                        <h3><i class="fas fa-calendar-alt"></i> Gerir Disponibilidade</h3>
                        <p>Clique em um dia do calendário para bloquear ou desbloquear datas.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>



    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
    <script src="backend/script.js"></script>

    <script>
        // Configurações globais
        const currentDate = new Date();
        const currentMonth = <?php echo $mes; ?>;
        const currentYear = <?php echo $ano; ?>;
        const casaId = <?php echo $casa_id ?: 'null'; ?>;
        const tipoUtilizador = '<?php echo $tipo_utilizador; ?>';

        // Elementos DOM
        const calendarGrid = document.getElementById('calendarGrid');
        const weatherCurrent = document.getElementById('weatherCurrent');
        const weatherForecast = document.getElementById('weatherForecast');

        // Arrays de meses e dias
        const months = [
            'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
        ];

        const daysOfWeek = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

        // Variáveis para disponibilidade
        let availabilityData = {};
        let weatherData = {};
        let selectedDate = null; // Data selecionada
        let clickTimeout = null; // Timeout para diferenciar single e double click

        // Função auxiliar para normalizar datas para YYYY-MM-DD
        function toISO(dateStr) {
            if (!dateStr) return null;
            // Já está no formato ISO (YYYY-MM-DD)
            if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr;

            // Formatos comuns: DD/MM/YYYY ou DD-MM-YYYY
            const m = dateStr.match(/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/);
            if (m) {
                return `${m[3]}-${m[2].padStart(2, '0')}-${m[1].padStart(2, '0')}`;
            }

            // Tentar parse com Date e formatar
            const d = new Date(dateStr);
            if (!isNaN(d)) return d.toISOString().split('T')[0];

            // Não foi possível normalizar
            return null;
        }

        // Inicializar calendário
        document.addEventListener('DOMContentLoaded', function() {
            // Carregar calendário
            generateCalendar(currentMonth, currentYear);

            // Carregar meteorologia
            loadWeatherData();

            // Carregar disponibilidade se houver casa_id
            if (casaId) {
                loadAvailability();
            }

            // Configurar datepickers com auto-formatação
            if (document.getElementById('checkinDate')) {
                const checkinPicker = flatpickr('#checkinDate', {
                    locale: 'pt',
                    dateFormat: 'd/m/Y',
                    minDate: 'today',
                    onChange: function(selectedDates, dateStr) {
                        // Atualizar mínimo do checkout
                        if (checkoutPicker) {
                            checkoutPicker.set('minDate', selectedDates[0]);
                        }
                        updateReservationSummary();
                    }
                });

                const checkoutPicker = flatpickr('#checkoutDate', {
                    locale: 'pt',
                    dateFormat: 'd/m/Y',
                    minDate: 'today',
                    onChange: function(selectedDates, dateStr) {
                        updateReservationSummary();
                    }
                });



            }

            // Configurar datepickers para proprietário
            if (document.getElementById('blockDates')) {
                flatpickr('#blockDates', {
                    locale: 'pt',
                    mode: 'multiple',
                    dateFormat: 'd-m-Y',
                    minDate: 'today'
                });
            }

            if (document.getElementById('unblockDates')) {
                flatpickr('#unblockDates', {
                    locale: 'pt',
                    mode: 'multiple',
                    dateFormat: 'd-m-Y',
                    minDate: 'today'
                });
            }

            if (document.getElementById('specialPriceDates')) {
                flatpickr('#specialPriceDates', {
                    locale: 'pt',
                    mode: 'multiple',
                    dateFormat: 'd-m-Y',
                    minDate: 'today'
                });
            }

            // Botão de reserva
            if (document.getElementById('btnReservar')) {
                document.getElementById('btnReservar').addEventListener('click', makeReservation);
            }

            // Botões para proprietário no sidebar
            // Os botões são adicionados dinamicamente no updateSidebarForSelectedDay
        });

        function generateCalendar(month, year) {
            // Limpar calendário (mantendo cabeçalhos)
            while (calendarGrid.children.length > 7) {
                calendarGrid.removeChild(calendarGrid.lastChild);
            }

            // Primeiro dia do mês
            const firstDay = new Date(year, month - 1, 1);
            const lastDay = new Date(year, month, 0);

            // Dia da semana do primeiro dia (0 = Domingo, 1 = Segunda, etc.)
            const firstDayIndex = firstDay.getDay();

            // Último dia do mês anterior
            const prevLastDay = new Date(year, month - 1, 0).getDate();

            // Adicionar dias do mês anterior
            for (let i = firstDayIndex; i > 0; i--) {
                const day = prevLastDay - i + 1;
                const date = new Date(year, month - 2, day);
                addCalendarDay(date, true);
            }

            // Adicionar dias do mês atual
            for (let i = 1; i <= lastDay.getDate(); i++) {
                const date = new Date(year, month - 1, i);
                addCalendarDay(date, false);
            }

            // Adicionar dias do próximo mês
            const totalCells = 42; // 6 semanas * 7 dias
            const nextDays = totalCells - (firstDayIndex + lastDay.getDate());

            for (let i = 1; i <= nextDays; i++) {
                const date = new Date(year, month, i);
                addCalendarDay(date, true);
            }
        }

        function addCalendarDay(date, isOtherMonth) {
            const day = document.createElement('div');
            day.className = 'calendar-day';

            if (isOtherMonth) {
                day.classList.add('other-month');
            }

            // Verificar se é hoje (comparar como strings YYYY-MM-DD para evitar problemas de timezone)
            const today = new Date();
            const todayStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
            const dateStrLocal = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
            if (todayStr === dateStrLocal) {
                day.classList.add('today');
            }



            // Número do dia
            const dayNumber = document.createElement('div');
            dayNumber.className = 'day-number';
            dayNumber.textContent = date.getDate();
            day.appendChild(dayNumber);

            // Formatar data para YYYY-MM-DD
            const dateStr = date.toISOString().split('T')[0];

            // Adicionar informação de disponibilidade
            if (availabilityData[dateStr]) {
                const status = availabilityData[dateStr].status;

                // Adicionar indicador visual (quadradinho colorido)
                const indicator = document.createElement('div');
                indicator.className = `day-status-indicator ${status}`;
                day.appendChild(indicator);


                if (status === 'reserved') {
                    day.classList.add('reserved');
                    const event = document.createElement('div');
                    event.className = 'day-events';
                    event.innerHTML = '<span class="event-indicator event-reservation"></span> Reservado';
                    day.appendChild(event);
                } else if (status === 'blocked') {
                    day.classList.add('disabled');
                    const event = document.createElement('div');
                    event.className = 'day-events';
                    event.innerHTML = '<span class="event-indicator event-blocked"></span> Bloqueado';
                    day.appendChild(event);
                } else if (status === 'available') {
                    day.classList.add('available');
                }

                // Adicionar preço especial
                if (availabilityData[dateStr].special_price) {
                    const price = document.createElement('div');
                    price.className = 'day-events';
                    price.innerHTML = `<i class="fas fa-euro-sign"></i> ${availabilityData[dateStr].special_price}€`;
                    price.style.color = '#28a745';
                    price.style.fontWeight = 'bold';
                    day.appendChild(price);
                }
            } else {
                // Data não está no array, então está disponível
                day.classList.add('available');
                // Adicionar indicador verde para disponível
                const indicator = document.createElement('div');
                indicator.className = 'day-status-indicator available';
                day.appendChild(indicator);
            }




            // Adicionar meteorologia se disponível
            if (weatherData[dateStr]) {
                const weather = document.createElement('div');
                weather.className = 'day-weather';
                weather.innerHTML = `<span style="font-size: 1.2em; font-weight: bold;">${Math.round(weatherData[dateStr].temp)}°</span>`;
                day.appendChild(weather);
            }

            // Adicionar eventos de clique (single e double)
            day.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                // Limpar timeout anterior
                if (clickTimeout) {
                    clearTimeout(clickTimeout);
                }

                // Definir novo timeout para single click
                clickTimeout = setTimeout(() => {
                    selectDay(date, dateStr);
                }, 250); // 250ms para diferenciar single de double click
            });



            calendarGrid.appendChild(day);
        }



        function getStatusText(status) {
            const statusMap = {
                'available': 'Disponível',
                'reserved': 'Reservado',
                'blocked': 'Bloqueado',
                'pending': 'Pendente'
            };
            return statusMap[status] || status;
        }

        async function loadWeatherData() {
            // Tenta a API local primeiro (quando o Flask estiver em execução)
            async function tryLocal() {
                try {
                    const res = await fetch('http://localhost:5000/api/meteorologia/previsao');
                    if (!res.ok) throw new Error('Local weather API returned ' + res.status);
                    const json = await res.json();
                    if (json && Array.isArray(json.previsao) && json.previsao.length > 0) {
                        return json.previsao;
                    }
                } catch (e) {
                    console.warn('Local weather API not available:', e.message || e);
                }
                return null;
            }

            // Normaliza e popula os widgets a partir de um array de dias (cada dia com keys: data, dia_semana_pt, temperatura_minima, temperatura_maxima, descricao_pt, icone, vento_medio, humidade_media, hoje)
            function setWeatherFromPrevisao(previsao) {
                if (!previsao || previsao.length === 0) {
                    throw new Error('Previsão vazia');
                }

                const hoje = previsao.find(d => d.hoje) || previsao[0];

                weatherCurrent.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div class="weather-temp">${Math.round(hoje.temperatura_maxima || hoje.temperatura_atual || 0)}°C</div>
                            <div class="weather-desc">
                                <img src="${hoje.icone || ''}" alt="${hoje.descricao_pt || hoje.descricao || ''}" style="width: 70px; height: 70px;">
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

                // A lista de 5 dias foi removida — o widget mostra apenas a previsão do dia selecionado.
                // (Anteriormente aqui eram criados os elementos para a lista de 5 dias.)

                // Armazenar dados para o calendário (usar chaves ISO YYYY-MM-DD)
                previsao.forEach(day => {
                    const key = (day.data || '').split('T')[0];
                    if (!key) return;
                    weatherData[key] = {
                        temp: day.temperatura_maxima || day.temperatura_atual || 0,
                        temp_min: day.temperatura_minima || 0,
                        temp_max: day.temperatura_maxima || 0,
                        desc: day.descricao_pt || day.descricao || '',
                        icon: day.icone || '',
                        humidity: day.humidade_media || 0,
                        wind: day.vento_medio || day.vento || 0
                    };
                });

                // Regenerar calendário para incluir meteorologia real
                generateCalendar(currentMonth, currentYear);

                // Atualizar o widget para mostrar a previsão do dia selecionado (ou hoje por omissão)
                const widgetDate = (selectedDate || (hoje && (hoje.data || '').split('T')[0]) || (previsao[0] && (previsao[0].data || '').split('T')[0]));
                if (widgetDate) {
                    updateWeatherWidgetForDate(widgetDate);
                }
            }

            try {
                // Indicar tentativa local
                const statusEl = document.getElementById('weatherSourceStatus');
                if (statusEl) {
                    statusEl.textContent = 'A tentar API local...';
                    statusEl.className = 'weather-source weather-source-loading';
                }

                // Tentar API local
                const localPrevisao = await tryLocal();
                if (localPrevisao) {
                    if (statusEl) {
                        statusEl.textContent = 'Fonte: API local';
                        statusEl.className = 'weather-source weather-source-success';
                    }
                    setWeatherFromPrevisao(localPrevisao);
                    return;
                }

                // Indicar fallback
                if (statusEl) {
                    statusEl.textContent = 'API local indisponível, a usar Open‑Meteo (fallback)';
                    statusEl.className = 'weather-source weather-source-warning';
                }

                // Fallback para Open-Meteo (incluindo dados horários para humidade)
                const response = await fetch('https://api.open-meteo.com/v1/forecast?latitude=39.4811&longitude=-8.5394&daily=temperature_2m_max,temperature_2m_min,weathercode,windspeed_10m_max&hourly=relative_humidity_2m&forecast_days=16&timezone=Europe/Lisbon');
                if (!response.ok) throw new Error('Erro ao carregar meteorologia');

                const apiData = await response.json();
                const previsao = processWeatherData(apiData); // já converte para o formato utilizado

                if (statusEl) {
                    statusEl.textContent = 'Fonte: Open‑Meteo (pública)';
                    statusEl.className = 'weather-source weather-source-success';
                }

                setWeatherFromPrevisao(previsao);

            } catch (error) {
                console.error('Erro ao carregar meteorologia:', error);
                const statusEl2 = document.getElementById('weatherSourceStatus');
                if (statusEl2) {
                    statusEl2.textContent = 'Meteorologia indisponível';
                    statusEl2.className = 'weather-source weather-source-error';
                }
                weatherCurrent.innerHTML = `
                    <div style="text-align: center; padding: 20px;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 2em; color: #ffc107;"></i>
                        <p>Não foi possível carregar a previsão do tempo</p>
                        <p style="font-size:0.9em; color:#666;">(Tente novamente mais tarde ou inicie o servidor local)</p>
                    </div>
                `;
            }
        }

        async function loadAvailability() {
            try {
                const response = await fetch(`backend/api_availability.php?casa_id=${casaId}&mes=${currentMonth}&ano=${currentYear}`);
                if (!response.ok) throw new Error('Erro ao carregar disponibilidade');

                const data = await response.json();
                availabilityData = data;

                // Gerar calendário novamente para incluir disponibilidade
                generateCalendar(currentMonth, currentYear);

                // Atualizar sidebar se houver um dia selecionado
                if (selectedDate) {
                    const selectedDateObj = new Date(selectedDate);
                    updateSidebarForSelectedDay(selectedDateObj, selectedDate);
                }
            } catch (error) {
                console.error('Erro ao carregar disponibilidade:', error);
            }
        }


        // Funções para proprietário
        async function blockDates() {
            const datesInput = document.getElementById('blockDates');
            if (!datesInput.value) {
                if (typeof AlugaTorresNotifications !== 'undefined') {
                    AlugaTorresNotifications.warning('Selecione as datas para bloquear');
                } else {
                    alert('Selecione as datas para bloquear');
                }
                return;
            }


            // Para inputs de data múltipla, o valor já é uma string separada por vírgulas
            const dates = datesInput.value.split(',');

            try {
                const response = await fetch('backend/calendario_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'block',
                        casa_id: casaId,
                        dates: dates,
                        tipo_utiliador: tipoUtilizador
                    })
                });

                const data = await response.json();
                if (data.success) {
                    if (typeof AlugaTorresNotifications !== 'undefined') {
                        AlugaTorresNotifications.success('Datas bloqueadas com sucesso!');
                    } else {
                        alert('Datas bloqueadas com sucesso!');
                    }
                    loadAvailability();
                    datesInput.value = '';
                } else {
                    throw new Error(data.error || 'Erro ao bloquear datas');
                }
            } catch (error) {
                if (typeof AlugaTorresNotifications !== 'undefined') {
                    AlugaTorresNotifications.error('Erro ao bloquear datas: ' + error.message);
                } else {
                    alert('Erro ao bloquear datas: ' + error.message);
                }
            }

        }

        async function unblockDates() {
            const datesInput = document.getElementById('unblockDates');
            if (!datesInput.value) {
                if (typeof AlugaTorresNotifications !== 'undefined') {
                    AlugaTorresNotifications.warning('Selecione as datas para desbloquear');
                } else {
                    alert('Selecione as datas para desbloquear');
                }
                return;
            }


            const dates = datesInput.value.split(',');

            try {
                const response = await fetch('backend/calendario_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'unblock',
                        casa_id: casaId,
                        dates: dates,
                        tipo_utiliador: tipoUtilizador
                    })
                });

                const data = await response.json();
                if (data.success) {
                    if (typeof AlugaTorresNotifications !== 'undefined') {
                        AlugaTorresNotifications.success('Datas desbloqueadas com sucesso!');
                    } else {
                        alert('Datas desbloqueadas com sucesso!');
                    }
                    loadAvailability();
                    datesInput.value = '';
                } else {
                    throw new Error(data.error || 'Erro ao desbloquear datas');
                }
            } catch (error) {
                if (typeof AlugaTorresNotifications !== 'undefined') {
                    AlugaTorresNotifications.error('Erro ao desbloquear datas: ' + error.message);
                } else {
                    alert('Erro ao desbloquear datas: ' + error.message);
                }
            }

        }

        async function applySpecialPrice() {
            const priceInput = document.getElementById('specialPrice');
            const datesInput = document.getElementById('specialPriceDates');

            if (!datesInput.value) {
                if (typeof AlugaTorresNotifications !== 'undefined') {
                    AlugaTorresNotifications.warning('Selecione as datas para aplicar o preço especial');
                } else {
                    alert('Selecione as datas para aplicar o preço especial');
                }
                return;
            }


            const dates = datesInput.value.split(',');
            const price = priceInput.value || null;

            try {
                const response = await fetch('backend/calendario_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'special_price',
                        casa_id: casaId,
                        dates: dates,
                        price: price,
                        tipo_utiliador: tipoUtilizador
                    })
                });

                const data = await response.json();
                if (data.success) {
                    if (typeof AlugaTorresNotifications !== 'undefined') {
                        AlugaTorresNotifications.success('Preço especial aplicado com sucesso!');
                    } else {
                        alert('Preço especial aplicado com sucesso!');
                    }
                    loadAvailability();
                    priceInput.value = '';
                    datesInput.value = '';
                } else {
                    throw new Error(data.error || 'Erro ao aplicar preço especial');
                }
            } catch (error) {
                if (typeof AlugaTorresNotifications !== 'undefined') {
                    AlugaTorresNotifications.error('Erro ao aplicar preço especial: ' + error.message);
                } else {
                    alert('Erro ao aplicar preço especial: ' + error.message);
                }
            }

        }



        function selectDay(date, dateStr) {
            // Remover seleção anterior
            const selectedDays = document.querySelectorAll('.calendar-day.selected');
            selectedDays.forEach(day => day.classList.remove('selected'));

            // Adicionar seleção ao dia clicado
            const clickedDay = Array.from(calendarGrid.children).find(day => {
                const dayNumber = day.querySelector('.day-number');
                return dayNumber && parseInt(dayNumber.textContent) === date.getDate() &&
                    !day.classList.contains('other-month');
            });

            if (clickedDay) {
                clickedDay.classList.add('selected');
                selectedDate = dateStr;
            }

            // Atualizar sidebar com informações do dia selecionado
            updateSidebarForSelectedDay(date, dateStr);

            // Atualizar widget de meteorologia para a data selecionada
            updateWeatherWidgetForDate(dateStr);
        }

        function updateSidebarForSelectedDay(date, dateStr) {
            const dayName = daysOfWeek[date.getDay()];
            const monthName = months[date.getMonth()];

            // Atualizar título do sidebar
            const sidebarTitle = document.querySelector('.calendar-sidebar h3');
            if (sidebarTitle) {
                sidebarTitle.innerHTML = `<i class="fas fa-calendar-day"></i> ${dayName}, ${date.getDate()} de ${monthName}`;
            }

            // Atualizar informações no sidebar
            let sidebarContent = '';

            // Informações de disponibilidade
            if (availabilityData[dateStr]) {
                const avail = availabilityData[dateStr];
                sidebarContent += `
                    <div class="sidebar-section">
                        <h4><i class="fas fa-info-circle"></i> Disponibilidade</h4>
                        <p><strong>Status:</strong> ${getStatusText(avail.status)}</p>
                        ${avail.special_price ? `<p><strong>Preço especial:</strong> ${avail.special_price}€</p>` : ''}
                        ${avail.notes ? `<p><strong>Notas:</strong> ${avail.notes}</p>` : ''}
                    </div>
                `;
            }

            // Informações meteorológicas
            if (weatherData[dateStr]) {
                const weather = weatherData[dateStr];
                sidebarContent += `
                    <div class="sidebar-section">
                        <h4><i class="fas fa-cloud-sun"></i> Previsão do Tempo</h4>
                        <p><strong>Temperatura:</strong> ${Math.round(weather.temp)}°C</p>
                        <p><strong>Condição:</strong> ${weather.desc}</p>
                        <p><strong>Humidade:</strong> ${weather.humidity}%</p>
                        <p><strong>Vento:</strong> ${weather.wind} km/h</p>
                    </div>
                `;
            }

            // Ações conforme tipo de usuário
            if (tipoUtilizador === 'proprietario' && casaId) {
                const isBlocked = availabilityData[dateStr] && availabilityData[dateStr].status === 'blocked';
                const buttonText = isBlocked ? 'Desbloquear Data' : 'Bloquear Data';
                const buttonIcon = isBlocked ? 'fa-unlock' : 'fa-lock';
                const buttonColor = isBlocked ? '#28a745' : '#ffc107';
                sidebarContent += `
                    <div class="sidebar-section">
                        <h4><i class="fas fa-cogs"></i> Ações</h4>
                        <button onclick="toggleBlockDate('${dateStr}')" class="btn-reservar" style="margin-bottom: 10px; width: 100%; background: ${buttonColor};">
                            <i class="fas ${buttonIcon}"></i> ${buttonText}
                        </button>
                    </div>
                `;
            } else if (tipoUtilizador === 'arrendatario' && casaId) {
                const isAvailable = !availabilityData[dateStr] || availabilityData[dateStr].status === 'available';
                const isBlocked = availabilityData[dateStr] && availabilityData[dateStr].status === 'blocked';
                const isReserved = availabilityData[dateStr] && availabilityData[dateStr].status === 'reserved';

                if (isBlocked || isReserved) {
                    // Data bloqueada ou reservada - mostrar apenas mensagem de indisponível
                    sidebarContent += `
                        <div class="sidebar-section">
                            <h4><i class="fas fa-calendar-plus"></i> Reserva</h4>
                            <p style="color: #dc3545; font-weight: bold;">
                                <i class="fas fa-times-circle"></i> 
                                Esta data está indisponível para reserva.
                            </p>
                            ${isBlocked ? '<p><small>Esta data está bloqueada pelo proprietário.</small></p>' : ''}
                            ${isReserved ? '<p><small>Esta data já está reservada.</small></p>' : ''}
                        </div>
                    `;
                } else {
                    // Data disponível - mostrar mensagem
                    sidebarContent += `
                        <div class="sidebar-section">
                            <h4><i class="fas fa-calendar-plus"></i> Reserva</h4>
                            <p style="color: #28a745;">
                                <i class="fas fa-check-circle"></i>
                                Esta data está disponível para reserva.
                            </p>
                        </div>
                    `;
                }
            }


            // Atualizar conteúdo do sidebar (depois do widget de meteorologia)
            const weatherWidget = document.querySelector('.weather-widget');
            if (weatherWidget) {
                // Remover conteúdo anterior do sidebar (exceto o widget de meteorologia)
                const sidebarSections = weatherWidget.querySelectorAll('.sidebar-section');
                sidebarSections.forEach(section => section.remove());

                // Adicionar novo conteúdo
                if (sidebarContent) {
                    weatherWidget.insertAdjacentHTML('beforeend', sidebarContent);
                }
            }
        }


        function selectDateForReservation(dateStr) {
            const checkinInput = document.getElementById('checkinDate');
            const checkoutInput = document.getElementById('checkoutDate');

            // Converter de YYYY-MM-DD para DD/MM/YYYY
            function formatToDisplay(dateISO) {
                if (!dateISO) return '';
                const parts = dateISO.split('-');
                if (parts.length === 3) {
                    return `${parts[2]}/${parts[1]}/${parts[0]}`;
                }
                return dateISO;
            }

            const formattedDate = formatToDisplay(dateStr);

            if (!checkinInput.value) {
                checkinInput.value = formattedDate;
            } else if (!checkoutInput.value) {
                checkoutInput.value = formattedDate;
            } else {
                checkinInput.value = formattedDate;
                checkoutInput.value = '';
            }

            updateReservationSummary();
        }


        // Funções para ações do proprietário no sidebar
        async function toggleBlockDate(dateStr) {
            const isBlocked = availabilityData[dateStr] && availabilityData[dateStr].status === 'blocked';

            try {
                const response = await fetch('backend/calendario_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: isBlocked ? 'unblock' : 'block',
                        casa_id: casaId,
                        dates: [dateStr],
                        tipo_utiliador: tipoUtilizador
                    })
                });

                const data = await response.json();
                if (data.success) {
                    const msg = `Data ${isBlocked ? 'desbloqueada' : 'bloqueada'} com sucesso!`;
                    if (typeof AlugaTorresNotifications !== 'undefined') {
                        AlugaTorresNotifications.success(msg);
                    } else {
                        alert(msg);
                    }
                    loadAvailability();
                } else {
                    throw new Error(data.error || `Erro ao ${isBlocked ? 'desbloquear' : 'bloquear'} data`);
                }
            } catch (error) {
                const msg = `Erro ao ${isBlocked ? 'desbloquear' : 'bloquear'} data: ` + error.message;
                if (typeof AlugaTorresNotifications !== 'undefined') {
                    AlugaTorresNotifications.error(msg);
                } else {
                    alert(msg);
                }
            }

        }

        async function saveSpecialPrice(dateStr) {
            const priceInput = document.getElementById('sidebarSpecialPrice');
            const price = priceInput.value || null;

            try {
                const response = await fetch('backend/calendario_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'special_price',
                        casa_id: casaId,
                        dates: [dateStr],
                        price: price,
                        tipo_utiliador: tipoUtilizador
                    })
                });

                const data = await response.json();
                if (data.success) {
                    if (typeof AlugaTorresNotifications !== 'undefined') {
                        AlugaTorresNotifications.success('Preço especial salvo com sucesso!');
                    } else {
                        alert('Preço especial salvo com sucesso!');
                    }
                    loadAvailability();
                } else {
                    throw new Error(data.error || 'Erro ao salvar preço especial');
                }
            } catch (error) {
                if (typeof AlugaTorresNotifications !== 'undefined') {
                    AlugaTorresNotifications.error('Erro ao salvar preço especial: ' + error.message);
                } else {
                    alert('Erro ao salvar preço especial: ' + error.message);
                }
            }

        }

        function processWeatherData(apiData) {
            const resultado = [];
            const hoje = new Date().toISOString().split('T')[0];

            // Mapeamento dos códigos WMO para descrições
            const weatherCodesMap = {
                0: "céu limpo",
                1: "principalmente limpo",
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

            // Mapeamento para ícones
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

            // Extrai arrays de dados diários
            const datas = apiData.daily.time;
            const tempMax = apiData.daily.temperature_2m_max;
            const tempMin = apiData.daily.temperature_2m_min;
            const weatherCodes = apiData.daily.weathercode;
            const windSpeed = apiData.daily.windspeed_10m_max;

            // Extrai dados horários de humidade (se disponíveis)
            const hourlyTimes = apiData.hourly ? apiData.hourly.time : [];
            const hourlyHumidity = apiData.hourly ? apiData.hourly.relative_humidity_2m : [];

            // Função para calcular humidade média do dia
            function calcularHumidadeMedia(dataStr) {
                if (!hourlyTimes.length || !hourlyHumidity.length) return 0;

                // Filtrar valores horários que pertencem a esta data
                const valoresDia = [];
                for (let j = 0; j < hourlyTimes.length; j++) {
                    if (hourlyTimes[j].startsWith(dataStr)) {
                        valoresDia.push(hourlyHumidity[j]);
                    }
                }

                if (valoresDia.length === 0) return 0;

                // Calcular média
                const soma = valoresDia.reduce((acc, val) => acc + val, 0);
                return Math.round(soma / valoresDia.length);
            }

            // Processa cada dia
            for (let i = 0; i < datas.length; i++) {
                const dataStr = datas[i];
                const dataObj = new Date(dataStr + 'T00:00:00');

                // Mapeia weather code para descrição
                const weatherDesc = weatherCodesMap[weatherCodes[i]] || "condições desconhecidas";
                const iconCode = iconMapping[weatherCodes[i]] || "01d";

                // Calcular humidade média para o dia
                const humidadeMedia = calcularHumidadeMedia(dataStr);

                // Cria objeto do dia
                const diaInfo = {
                    'data': dataStr,
                    'dia_semana': dataObj.toLocaleDateString('pt-PT', {
                        weekday: 'long'
                    }),
                    'dia_semana_pt': dataObj.toLocaleDateString('pt-PT', {
                        weekday: 'long'
                    }),
                    'temperatura_minima': tempMin[i],
                    'temperatura_maxima': tempMax[i],
                    'temperatura_media': Math.round((tempMin[i] + tempMax[i]) / 2 * 10) / 10,
                    'descricao': weatherDesc,
                    'descricao_pt': weatherDesc,
                    'icone': `http://openweathermap.org/img/wn/${iconCode}@2x.png`,
                    'humidade_media': humidadeMedia,
                    'vento_medio': windSpeed[i],
                    'hoje': dataStr === hoje,
                    'numero_previsoes': 1
                };
                resultado.push(diaInfo);
            }

            return resultado;
        }

        // Atualiza o content do widget para uma data específica (usado ao selecionar um dia no calendário)
        function updateWeatherWidgetForDate(dateStr) {
            if (!dateStr) return;
            const key = (dateStr || '').split('T')[0];
            const day = weatherData[key];



            if (!day) {
                weatherCurrent.innerHTML = `
                    <div style="text-align:center; padding: 10px;">
                        <p>Sem previsão para ${new Date(dateStr).toLocaleDateString('pt-PT')}</p>
                    </div>
                `;
                return;
            }

            weatherCurrent.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div class="weather-temp">${Math.round(day.temp_max || day.temp)}°C</div>
                        <div class="weather-desc">
                            <img src="${day.icon || ''}" alt="${day.desc || ''}" style="width: 70px; height: 70px;">
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

            // Se esse dia estiver selecionado no calendário, garantir que a sidebar está atualizada
            if (selectedDate === key) {
                updateSidebarForSelectedDay(new Date(dateStr), key);
            }
        }

        async function updateReservationSummary() {
            const checkinRaw = document.getElementById('checkinDate').value;
            const checkoutRaw = document.getElementById('checkoutDate').value;
            const guests = document.getElementById('numGuests').value;
            const summaryDiv = document.getElementById('reservationSummary');

            if (!checkinRaw || !checkoutRaw) {
                summaryDiv.style.display = 'none';
                return;
            }

            const checkin = toISO(checkinRaw);
            const checkout = toISO(checkoutRaw);
            if (!checkin || !checkout) {
                summaryDiv.style.display = 'none';
                return;
            }

            // Calcular noites (usar datas ISO)
            const checkinDate = new Date(checkin);
            const checkoutDate = new Date(checkout);
            const nights = Math.ceil((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));

            if (nights <= 0) {
                if (typeof AlugaTorresNotifications !== 'undefined') {
                    AlugaTorresNotifications.warning('Data de checkout deve ser após data de checkin');
                } else {
                    alert('Data de checkout deve ser após data de checkin');
                }
                return;
            }


            try {
                // Obter preços e disponibilidade (usar formato YYYY-MM-DD)
                const response = await fetch(`backend/api_reservas.php?action=calculate&casa_id=${casaId}&checkin=${encodeURIComponent(checkin)}&checkout=${encodeURIComponent(checkout)}&hospedes=${guests}`);
                const data = await response.json();

                if (data.error) {
                    if (typeof AlugaTorresNotifications !== 'undefined') {
                        AlugaTorresNotifications.error(data.error);
                    } else {
                        alert(data.error);
                    }
                    return;
                }


                // Obter informações meteorológicas para as datas selecionadas (usar chaves ISO)
                let weatherInfo = '';
                const checkinWeather = weatherData[checkin];
                const checkoutWeather = weatherData[checkout];

                if (checkinWeather || checkoutWeather) {
                    weatherInfo = `
                        <div class="summary-weather">
                            <h5><i class="fas fa-cloud-sun"></i> Previsão do Tempo</h5>
                            ${checkinWeather ? `
                                <div class="weather-day-summary">
                                    <strong>Check-in (${new Date(checkin).toLocaleDateString('pt-PT')}):</strong>
                                    ${Math.round(checkinWeather.temp)}°C - ${checkinWeather.desc}
                                </div>
                            ` : ''}
                            ${checkoutWeather && checkin !== checkout ? `
                                <div class="weather-day-summary">
                                    <strong>Check-out (${new Date(checkout).toLocaleDateString('pt-PT')}):</strong>
                                    ${Math.round(checkoutWeather.temp)}°C - ${checkoutWeather.desc}
                                </div>
                            ` : ''}
                        </div>
                    `;
                }

                summaryDiv.innerHTML = `
                    <h4>Resumo da Reserva</h4>
                    <div class="summary-item">
                        <span>${nights} noite(s)</span>
                        <span>${data.preco_noite}€/noite</span>
                    </div>
                    <div class="summary-item">
                        <span>${guests} hóspede(s)</span>
                        <span></span>
                    </div>
                    ${data.taxa_limpeza > 0 ? `
                        <div class="summary-item">
                            <span>Taxa de limpeza</span>
                            <span>${data.taxa_limpeza}€</span>
                        </div>
                    ` : ''}
                    ${data.taxa_seguranca > 0 ? `
                        <div class="summary-item">
                            <span>Taxa de segurança</span>
                            <span>${data.taxa_seguranca}€</span>
                        </div>
                    ` : ''}
                    ${weatherInfo}
                    <div class="summary-item summary-total">
                        <span>Total</span>
                        <span>${data.total}€</span>
                    </div>
                `;

                summaryDiv.style.display = 'block';

            } catch (error) {
                console.error('Erro ao calcular reserva:', error);
            }
        }

        async function makeReservation() {
            console.log('=== INICIANDO RESERVA ===');

            const checkinRaw = document.getElementById('checkinDate').value;
            const checkoutRaw = document.getElementById('checkoutDate').value;
            const guests = document.getElementById('numGuests').value;

            console.log('Valores dos inputs:');
            console.log('- checkinRaw:', checkinRaw);
            console.log('- checkoutRaw:', checkoutRaw);
            console.log('- guests:', guests);
            console.log('- casaId:', casaId);

            if (!checkinRaw || !checkoutRaw) {
                if (typeof AlugaTorresNotifications !== 'undefined') {
                    AlugaTorresNotifications.warning('Selecione as datas de checkin e checkout');
                } else {
                    alert('Selecione as datas de checkin e checkout');
                }
                console.log('ERRO: Datas não preenchidas');
                return;
            }


            const checkin = toISO(checkinRaw);
            const checkout = toISO(checkoutRaw);

            console.log('Após conversão toISO:');
            console.log('- checkin (ISO):', checkin);
            console.log('- checkout (ISO):', checkout);

            if (!checkin || !checkout) {
                if (typeof AlugaTorresNotifications !== 'undefined') {
                    AlugaTorresNotifications.error('Formato de data inválido');
                } else {
                    alert('Formato de data inválido');
                }
                console.log('ERRO: Formato de data inválido após conversão');
                return;
            }


            const requestBody = {

                action: 'create',
                casa_id: casaId,
                checkin: checkin,
                checkout: checkout,
                hospedes: parseInt(guests),
                tipo_utiliador: tipoUtilizador
            };

            console.log('Enviando requisição:', requestBody);

            try {
                const response = await fetch('backend/api_reservas.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestBody)
                });

                console.log('Status da resposta:', response.status);

                const responseText = await response.text();
                console.log('Resposta bruta:', responseText);

                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    console.error('Erro ao parsear JSON:', e);
                    if (typeof AlugaTorresNotifications !== 'undefined') {
                        AlugaTorresNotifications.error('Erro: Resposta inválida do servidor');
                    } else {
                        alert('Erro: Resposta inválida do servidor');
                    }
                    return;
                }


                console.log('Dados parseados:', data);

                if (data.success) {
                    if (typeof AlugaTorresNotifications !== 'undefined') {
                        AlugaTorresNotifications.success('Reserva criada com sucesso! ID: ' + data.reserva_id);
                    } else {
                        alert('Reserva criada com sucesso! ID: ' + data.reserva_id);
                    }
                    // Limpar formulário
                    document.getElementById('checkinDate').value = '';
                    document.getElementById('checkoutDate').value = '';
                    document.getElementById('reservationSummary').style.display = 'none';
                    // Recarregar disponibilidade
                    loadAvailability();
                } else {
                    if (typeof AlugaTorresNotifications !== 'undefined') {
                        AlugaTorresNotifications.error('Erro: ' + (data.error || 'Erro desconhecido'));
                    } else {
                        alert('Erro: ' + (data.error || 'Erro desconhecido'));
                    }
                    console.error('Erro retornado pela API:', data.error);
                }
            } catch (error) {
                console.error('Erro na requisição:', error);
                if (typeof AlugaTorresNotifications !== 'undefined') {
                    AlugaTorresNotifications.error('Erro ao criar reserva: ' + error.message);
                } else {
                    alert('Erro ao criar reserva: ' + error.message);
                }
            }

        }
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const profileToggle = document.getElementById("profile-toggle");
            const sidebar = document.getElementById("sidebar");
            const sidebarOverlay = document.getElementById("sidebar-overlay");
            const closeSidebar = document.getElementById("close-sidebar");

            if (profileToggle) {
                profileToggle.addEventListener("click", function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    sidebar.classList.toggle("active");
                    sidebarOverlay.classList.toggle("active");
                });
            }

            if (closeSidebar) {
                closeSidebar.addEventListener("click", function() {
                    sidebar.classList.remove("active");
                    sidebarOverlay.classList.remove("active");
                });
            }

            // Close sidebar when clicking outside
            document.addEventListener("click", function(event) {
                if (
                    !sidebar.contains(event.target) &&
                    !profileToggle.contains(event.target)
                ) {
                    sidebar.classList.remove("active");
                    sidebarOverlay.classList.remove("active");
                }
            });
        });
    </script>
</body>


</html>