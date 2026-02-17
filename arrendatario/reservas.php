<?php
session_start();
require_once '../backend/db.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    header("Location: ../backend/login.php");
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
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlugaTorres | Reservas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="website icon" type="png" href="../style/img/Logo_AlugaTorres_branco.png">
    <style>
        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 50px;
        }

        .loading-spinner i {
            font-size: 2em;
            color: #007bff;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
    </style>
</head>

<body>
    <?php include '../header.php'; ?>
    <?php include '../sidebar.php'; ?>

    <div class="reservas-container">
        <div class="reservas-header">
            <h1 class="reservas-title">
                <?php echo $tipo_utilizador === 'proprietario' ? 'Gestão de Reservas' : 'Minhas Reservas'; ?>
            </h1>
        </div>

        <!-- Mensagens dinâmicas -->
        <div id="message-container"></div>

        <div class="filtros-container">
            <!-- Filtro por casa (apenas proprietário) -->
            <div class="filtro-group" id="casa-filter-group" style="display: none;">
                <span class="filtro-label">Filtrar por casa:</span>
                <select class="filtro-select" id="casaFilter">
                    <option value="">Todas as casas</option>
                </select>
            </div>

            <div class="filtro-group">
                <span class="filtro-label">Período:</span>
                <select class="filtro-select" id="periodoFilter">
                    <option value="todos">Todos</option>
                    <option value="hoje">Hoje</option>
                    <option value="semana">Esta semana</option>
                    <option value="mes">Este mês</option>
                    <option value="futuro">Futuras</option>
                    <option value="passado">Passadas</option>
                </select>
            </div>

            <button class="filtro-btn" onclick="applyFilters()">
                <i class="fas fa-filter"></i> Aplicar Filtros
            </button>
        </div>

        <div class="status-filtros">
            <button class="status-btn active" data-status="todas" onclick="filterByStatus('todas')">
                Todas
            </button>
            <button class="status-btn" data-status="pendente" onclick="filterByStatus('pendente')">
                Pendentes
            </button>
            <button class="status-btn" data-status="confirmada" onclick="filterByStatus('confirmada')">
                Confirmadas
            </button>
            <button class="status-btn" data-status="concluida" onclick="filterByStatus('concluida')">
                Concluídas
            </button>
            <button class="status-btn" data-status="cancelada" onclick="filterByStatus('cancelada')">
                Canceladas
            </button>
        </div>

        <div class="reservas-table-container" id="reservas-content">
            <!-- Loading spinner -->
            <div class="loading-spinner" id="loading">
                <i class="fas fa-spinner"></i>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes -->
    <div class="modal" id="detailsModal">
        <div class="modal-content">
            <h3 class="modal-title">Detalhes da Reserva</h3>
            <div id="modalDetailsContent">
                <!-- Conteúdo será carregado por JavaScript -->
            </div>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Fechar
                </button>
            </div>
        </div>
    </div>

    <?php include '../footer.php'; ?>

    <script src="../js/script.js"></script>
    <script>
        // Configuração
        const API_URL = '../backend_api/api_reservas.php';
        const USER_TYPE = '<?php echo $tipo_utilizador; ?>';
        let currentFiltro = 'todas';
        let currentCasaId = '';
        let currentPeriodo = 'todos';

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            loadReservas();
            if (USER_TYPE === 'proprietario') {
                loadCasas();
            }
        });

        // Carregar reservas
        async function loadReservas() {
            const container = document.getElementById('reservas-content');
            container.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner"></i></div>';

            try {
                const params = new URLSearchParams({

                    action: 'list',
                    tipo: USER_TYPE === 'proprietario' ? 'proprietario' : 'minhas',
                    filtro: currentFiltro,
                    periodo: currentPeriodo
                });

                if (currentCasaId) {
                    params.append('casa_id', currentCasaId);
                }

                const response = await fetch(`${API_URL}?${params}`);
                const data = await response.json();

                if (data.error) {
                    showError(data.error);
                    return;
                }

                renderReservas(data.reservas || []);
            } catch (error) {
                showError('Erro ao carregar reservas. Tente novamente.');
                console.error('Error:', error);
            }
        }

        // Carregar casas (proprietário)
        async function loadCasas() {
            try {
                const response = await fetch(`${API_URL}?action=get_casas`);
                const data = await response.json();

                if (data.casas && data.casas.length > 0) {
                    const select = document.getElementById('casaFilter');
                    data.casas.forEach(casa => {
                        const option = document.createElement('option');
                        option.value = casa.id;
                        option.textContent = casa.titulo;
                        select.appendChild(option);
                    });
                    document.getElementById('casa-filter-group').style.display = 'block';
                }
            } catch (error) {
                console.error('Erro ao carregar casas:', error);
            }
        }

        // Renderizar tabela de reservas
        function renderReservas(reservas) {
            const container = document.getElementById('reservas-content');

            if (reservas.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h3>Nenhuma reserva encontrada</h3>
                        <p>${USER_TYPE === 'proprietario' ? 'Ainda não há reservas para suas propriedades.' : 'Você ainda não fez nenhuma reserva.'}</p>
                        ${USER_TYPE === 'arrendatario' ? `
                            <a href="../pesquisa.php" class="filtro-btn" style="margin-top: 15px;">
                                <i class="fas fa-search"></i> Buscar Alojamentos
                            </a>
                        ` : ''}
                    </div>
                `;
                return;
            }

            const statusMap = {
                pendente: 'Pendente',
                confirmada: 'Confirmada',
                concluida: 'Concluída',
                cancelada: 'Cancelada',
                rejeitada: 'Rejeitada'
            };

            let html = `
                <table class="reservas-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Propriedade</th>
                            ${USER_TYPE === 'proprietario' ? '<th>Arrendatário</th>' : '<th>Proprietário</th>'}
                            <th>Datas</th>
                            <th>Status</th>
                            <th>Valor</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            reservas.forEach(reserva => {
                const podeConcluir = reserva.status === 'confirmada' && new Date(reserva.data_checkout) <= new Date();


                html += `
                    <tr>
                        <td class="reserva-id">#${String(reserva.id).padStart(5, '0')}</td>
                        <td>
                            <div class="reserva-casa">${escapeHtml(reserva.casa_titulo)}</div>
                        </td>
                        <td>
                            ${USER_TYPE === 'proprietario' ? `
                                <div>${escapeHtml(reserva.arrendatario_nome)}</div>
                                <div style="font-size: 0.8em; color: #666;">${escapeHtml(reserva.arrendatario_email || '')}</div>
                            ` : `
                                <div>${escapeHtml(reserva.proprietario_nome)}</div>
                            `}
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
                        <td class="reserva-valor">${parseFloat(reserva.total).toFixed(2).replace('.', ',')}€</td>
                        <td>
                            <div class="reserva-acoes">
                                <button class="acao-btn btn-detalhes" onclick='showReservaDetails(${JSON.stringify(reserva)})'>
                                    <i class="fas fa-eye"></i>
                                </button>

                                ${USER_TYPE === 'proprietario' ? `
                                    ${reserva.status === 'pendente' ? `
                                        <button class="acao-btn btn-confirmar" onclick="handleReservaAction('confirmar', ${reserva.id})">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="acao-btn btn-cancelar" onclick="handleReservaAction('rejeitar', ${reserva.id})">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    ` : ''}
                                    ${podeConcluir ? `
                                        <button class="acao-btn btn-concluir" onclick="handleReservaAction('concluir', ${reserva.id})">
                                            <i class="fas fa-flag-checkered"></i>
                                        </button>
                                    ` : ''}
                                ` : `
                                    ${(reserva.status === 'pendente' || reserva.status === 'confirmada') ? `
                                        <button class="acao-btn btn-cancelar" onclick="handleReservaAction('cancel', ${reserva.id})">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    ` : ''}
                                `}

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

            container.innerHTML = html;
        }

        // Ações em reservas
        async function handleReservaAction(action, reservaId) {
            const confirmMessages = {
                confirmar: 'Confirmar esta reserva?',
                cancel: 'Cancelar esta reserva?',
                concluir: 'Marcar como concluída?',
                rejeitar: 'Rejeitar esta reserva?',
                eliminar: 'Tem certeza que deseja ELIMINAR permanentemente esta reserva?'
            };

            if (!confirm(confirmMessages[action])) {
                return;
            }

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action,
                        reserva_id: reservaId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess(data.message);
                    loadReservas();
                } else {
                    showError(data.error || 'Erro ao processar ação');
                }
            } catch (error) {
                showError('Erro de comunicação. Tente novamente.');
                console.error('Error:', error);
            }
        }

        // Filtros
        function filterByStatus(status) {
            currentFiltro = status;

            // Atualizar botões ativos
            document.querySelectorAll('.status-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.status === status);
            });

            loadReservas();
        }

        function applyFilters() {
            currentCasaId = document.getElementById('casaFilter')?.value || '';
            currentPeriodo = document.getElementById('periodoFilter').value;
            loadReservas();
        }

        // Modal
        function showReservaDetails(reserva) {
            const statusMap = {
                pendente: 'Pendente',
                confirmada: 'Confirmada',
                concluida: 'Concluída',
                cancelada: 'Cancelada',
                rejeitada: 'Rejeitada'
            };

            const content = `
                <div class="modal-detalhes">
                    <div class="detalhe-item">
                        <div class="detalhe-label">ID da Reserva</div>
                        <div class="detalhe-valor">#${String(reserva.id).padStart(5, '0')}</div>
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
                
                ${reserva.notas ? `
                    <div style="margin-bottom: 20px;">
                        <h4>Notas</h4>
                        <p>${escapeHtml(reserva.notas)}</p>
                    </div>
                ` : ''}
            `;

            document.getElementById('modalDetailsContent').innerHTML = content;
            document.getElementById('detailsModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }

        // Utilitários
        function showError(message) {
            const container = document.getElementById('message-container');
            container.innerHTML = `<div class="error-message">${escapeHtml(message)}</div>`;
            setTimeout(() => container.innerHTML = '', 5000);
        }

        function showSuccess(message) {
            const container = document.getElementById('message-container');
            container.innerHTML = `<div class="success-message">${escapeHtml(message)}</div>`;
            setTimeout(() => container.innerHTML = '', 5000);
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleDateString('pt-PT');
        }


        function formatDateTime(dateTimeStr) {
            if (!dateTimeStr) return '';
            const date = new Date(dateTimeStr);
            return date.toLocaleString('pt-PT');
        }


        // Fechar modal ao clicar fora
        document.getElementById('detailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>

</html>