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

        /* Estilos específicos para botões de status - garantir que funcionem */
        .status-filtros {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .status-btn {
            padding: 8px 20px;
            border: 2px solid #ddd;
            background: #f0f0f0;
            color: #333;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9em;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .status-btn:hover {
            background: #e0e0e0;
            border-color: #038e01;
        }

        .status-btn.status-active {
            background: #038e01 !important;
            color: white !important;
            border-color: #038e01 !important;
            font-weight: 600;
        }

        .status-btn.status-active:hover {
            background: #00d85e !important;
            border-color: #00d85e !important;
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
            <button class="status-btn status-active" data-status="todas" onclick="filterByStatus('todas')">
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
        // Configuração para o sistema de reservas
        window.reservasConfig = {
            apiUrl: '../backend_api/api_reservas.php',
            userType: '<?php echo $tipo_utilizador; ?>'
        };
    </script>

</body>

</html>