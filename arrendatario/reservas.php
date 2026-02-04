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
$casa_id = isset($_GET['casa_id']) ? (int)$_GET['casa_id'] : null;
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'todas';

// Construir query conforme tipo de usuário
if ($tipo_utilizador === 'proprietario') {
    // Proprietário vê reservas das suas casas
    $query = "SELECT r.*, c.titulo as casa_titulo, u.utilizador as arrendatario_nome, u.email as arrendatario_email
              FROM reservas r
              JOIN casas c ON r.casa_id = c.id
              JOIN utilizadores u ON r.arrendatario_id = u.id
              WHERE c.proprietario_id = ?";

    if ($casa_id) {
        $query .= " AND r.casa_id = ?";
    }

    // Aplicar filtro
    if ($filtro !== 'todas') {
        $query .= " AND r.status = ?";
    }

    $query .= " ORDER BY r.data_reserva DESC";

    $stmt = $conn->prepare($query);

    if ($casa_id) {
        if ($filtro !== 'todas') {
            $stmt->bind_param("iis", $user_id, $casa_id, $filtro);
        } else {
            $stmt->bind_param("ii", $user_id, $casa_id);
        }
    } else {
        if ($filtro !== 'todas') {
            $stmt->bind_param("is", $user_id, $filtro);
        } else {
            $stmt->bind_param("i", $user_id);
        }
    }
} else {
    // Arrendatário vê suas próprias reservas
    $query = "SELECT r.*, c.titulo as casa_titulo, u.utilizador as proprietario_nome
              FROM reservas r
              JOIN casas c ON r.casa_id = c.id
              JOIN utilizadores u ON c.proprietario_id = u.id
              WHERE r.arrendatario_id = ?";

    // Aplicar filtro
    if ($filtro !== 'todas') {
        $query .= " AND r.status = ?";
    }

    $query .= " ORDER BY r.data_checkin DESC";

    $stmt = $conn->prepare($query);

    if ($filtro !== 'todas') {
        $stmt->bind_param("is", $user_id, $filtro);
    } else {
        $stmt->bind_param("i", $user_id);
    }
}

$stmt->execute();
$reservas_result = $stmt->get_result();

// Se proprietário, obter lista de casas para filtro
if ($tipo_utilizador === 'proprietario') {
    $casas_query = $conn->prepare("SELECT id, titulo FROM casas WHERE proprietario_id = ? ORDER BY titulo");
    $casas_query->bind_param("i", $user_id);
    $casas_query->execute();
    $casas_result = $casas_query->get_result();
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $reserva_id = $_POST['reserva_id'] ?? null;

    // Criar nova reserva
    if (isset($_POST['casa_id']) && isset($_POST['data_checkin']) && isset($_POST['data_checkout'])) {
        $casa_id_post = (int)$_POST['casa_id'];
        $data_checkin = $_POST['data_checkin'];
        $data_checkout = $_POST['data_checkout'];
        $hospedes = (int)($_POST['hospedes'] ?? 1);

        // Verificar se as datas são válidas
        $data_checkin_date = new DateTime($chaeckin);
        $data_checkout_date = new DateTime($data_checkout);
        $hoje = new DateTime();

        if ($data_checkin_date >= $data_checkout_date) {
            $error = 'Data de data_checkout deve ser posterior ao data_checkin';
        } elseif ($data_checkin_date < $hoje) {
            $error = 'Data de data_checkin não pode ser no passado';
        } else {
            // Verificar disponibilidade
            $stmt = $conn->prepare("
                SELECT COUNT(*) as conflitos FROM (
                    SELECT id FROM reservas
                    WHERE casa_id = ? AND status != 'cancelada' AND (
                        (data_checkin <= ? AND data_checkout > ?) OR
                        (data_checkin < ? AND data_checkout >= ?) OR
                        (data_checkin >= ? AND data_checkout <= ?)
                    )
                    UNION
                    SELECT id FROM bloqueios
                    WHERE casa_id = ? AND (
                        (data_inicio <= ? AND data_fim > ?) OR
                        (data_inicio < ? AND data_fim >= ?) OR
                        (data_inicio >= ? AND data_fim <= ?)
                    )
                ) as conflitos
            ");

            $stmt->bind_param(
                "ssssssssssssss",
                $casa_id_post,
                $data_checkout,
                $data_checkin,
                $data_checkout,
                $data_checkin,
                $data_checkin,
                $data_checkout,
                $casa_id_post,
                $data_checkout,
                $data_checkin,
                $data_checkout,
                $data_checkin,
                $data_checkin,
                $data_checkout
            );
            $stmt->execute();
            $result = $stmt->get_result();
            $conflitos = $result->fetch_assoc()['conflitos'];

            if ($conflitos > 0) {
                $error = 'Datas não disponíveis para reserva';
            } else {
                // Calcular preço
                $stmt = $conn->prepare("SELECT preco_noite, preco_limpeza, taxa_seguranca FROM casas WHERE id = ?");
                $stmt->bind_param("i", $casa_id_post);
                $stmt->execute();
                $casa = $stmt->get_result()->fetch_assoc();

                $data_checkin_dt = new DateTime($data_checkin);
                $data_checkout_dt = new DateTime($data_checkout);
                $noites = $data_checkin_dt->diff($data_checkout_dt)->days;

                $subtotal = $noites * $casa['preco_noite'];
                $total = $subtotal + $casa['preco_limpeza'] + $casa['taxa_seguranca'];

                // Criar reserva
                $stmt = $conn->prepare("
                    INSERT INTO reservas (casa_id, arrendatario_id, data_checkin, data_checkout, hospedes, preco_total, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'pendente')
                ");
                $stmt->bind_param("iissid", $casa_id_post, $user_id, $data_checkin, $data_checkout, $hospedes, $total);

                if ($stmt->execute()) {
                    $success = 'Reserva criada com sucesso!';
                    // Marcar para atualizar estatísticas
                    setcookie('atualizar_stats', 'true', time() + 300, '/'); // 5 minutos
                } else {
                    $error = 'Erro ao criar reserva: ' . $conn->error;
                }
            }
        }
    } elseif ($reserva_id && $action) {
        switch ($action) {
            case 'confirmar':
                $stmt = $conn->prepare("UPDATE reservas SET status = 'confirmada', data_confirmacao = NOW() WHERE id = ?");
                $stmt->bind_param("i", $reserva_id);
                $stmt->execute();
                break;

            case 'cancelar':
                $stmt = $conn->prepare("UPDATE reservas SET status = 'cancelada', data_cancelamento = NOW() WHERE id = ?");
                $stmt->bind_param("i", $reserva_id);
                $stmt->execute();
                break;

            case 'concluir':
                $stmt = $conn->prepare("UPDATE reservas SET status = 'concluida' WHERE id = ?");
                $stmt->bind_param("i", $reserva_id);
                $stmt->execute();
                break;

            case 'rejeitar':
                $stmt = $conn->prepare("UPDATE reservas SET status = 'rejeitada' WHERE id = ?");
                $stmt->bind_param("i", $reserva_id);
                $stmt->execute();
                break;
        }

        // Recarregar página
        header("Location: reservas.php" . ($casa_id ? "?casa_id=$casa_id" : ""));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlugaTorres | Reservas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../style/style.css">
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

        <?php if (isset($error) && $error): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success) && $success): ?>
            <div class="message success">
                <?php echo htmlspecialchars($success); ?>
                <div style="margin-top: 10px;">
                    <a href="../perfil.php" class="btn-save" style="background: #28a745;">
                        <i class="fas fa-chart-line"></i> Ver Estatísticas Atualizadas
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div class="filtros-container">
            <?php if ($tipo_utilizador === 'proprietario' && $casas_result->num_rows > 0): ?>
                <div class="filtro-group">
                    <span class="filtro-label">Filtrar por casa:</span>
                    <select class="filtro-select" id="casaFilter" onchange="filterByCasa(this.value)">
                        <option value="">Todas as casas</option>
                        <?php while ($casa = $casas_result->fetch_assoc()): ?>
                            <option value="<?php echo $casa['id']; ?>" <?php echo $casa_id == $casa['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($casa['titulo']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            <?php endif; ?>

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
            <button class="status-btn <?php echo $filtro === 'todas' ? 'active' : ''; ?>" onclick="filterByStatus('todas')">
                Todas
            </button>
            <button class="status-btn <?php echo $filtro === 'pendente' ? 'active' : ''; ?>" onclick="filterByStatus('pendente')">
                Pendentes
            </button>
            <button class="status-btn <?php echo $filtro === 'confirmada' ? 'active' : ''; ?>" onclick="filterByStatus('confirmada')">
                Confirmadas
            </button>
            <button class="status-btn <?php echo $filtro === 'concluida' ? 'active' : ''; ?>" onclick="filterByStatus('concluida')">
                Concluídas
            </button>
            <button class="status-btn <?php echo $filtro === 'cancelada' ? 'active' : ''; ?>" onclick="filterByStatus('cancelada')">
                Canceladas
            </button>
        </div>

        <div class="reservas-table-container">
            <?php if ($reservas_result->num_rows > 0): ?>
                <table class="reservas-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Propriedade</th>
                            <?php if ($tipo_utilizador === 'proprietario'): ?>
                                <th>Arrendatário</th>
                            <?php else: ?>
                                <th>Proprietário</th>
                            <?php endif; ?>
                            <th>Datas</th>
                            <th>Status</th>
                            <th>Valor</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($reserva = $reservas_result->fetch_assoc()): ?>
                            <tr>
                                <td class="reserva-id">#<?php echo str_pad($reserva['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <div class="reserva-casa"><?php echo htmlspecialchars($reserva['casa_titulo']); ?></div>
                                </td>
                                <td>
                                    <?php if ($tipo_utilizador === 'proprietario'): ?>
                                        <div><?php echo htmlspecialchars($reserva['arrendatario_nome']); ?></div>
                                        <div style="font-size: 0.8em; color: #666;"><?php echo htmlspecialchars($reserva['arrendatario_email']); ?></div>
                                    <?php else: ?>
                                        <div><?php echo htmlspecialchars($reserva['proprietario_nome']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="reserva-datas">
                                        <div><strong>Check-in:</strong> <?php echo date('d/m/Y', strtotime($reserva['data_data_checkin'])); ?></div>
                                        <div><strong>Check-out:</strong> <?php echo date('d/m/Y', strtotime($reserva['data_data_checkout'])); ?></div>
                                        <div><strong>Noites:</strong> <?php echo $reserva['noites']; ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="reserva-status status-<?php echo $reserva['status']; ?>">
                                        <?php
                                        $status_text = [
                                            'pendente' => 'Pendente',
                                            'confirmada' => 'Confirmada',
                                            'concluida' => 'Concluída',
                                            'cancelada' => 'Cancelada',
                                            'rejeitada' => 'Rejeitada'
                                        ];
                                        echo $status_text[$reserva['status']] ?? $reserva['status'];
                                        ?>
                                    </span>
                                </td>
                                <td class="reserva-valor"><?php echo number_format($reserva['total'], 2, ',', ' '); ?>€</td>
                                <td>
                                    <div class="reserva-acoes">
                                        <button class="acao-btn btn-detalhes" onclick="showReservaDetails(<?php echo htmlspecialchars(json_encode($reserva)); ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <?php if ($tipo_utilizador === 'proprietario'): ?>
                                            <?php if ($reserva['status'] === 'pendente'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="reserva_id" value="<?php echo $reserva['id']; ?>">
                                                    <input type="hidden" name="action" value="confirmar">
                                                    <button type="submit" class="acao-btn btn-confirmar" onclick="return confirm('Confirmar esta reserva?')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="reserva_id" value="<?php echo $reserva['id']; ?>">
                                                    <input type="hidden" name="action" value="rejeitar">
                                                    <button type="submit" class="acao-btn btn-cancelar" onclick="return confirm('Rejeitar esta reserva?')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php elseif ($reserva['status'] === 'confirmada' && strtotime($reserva['data_data_checkout']) <= time()): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="reserva_id" value="<?php echo $reserva['id']; ?>">
                                                    <input type="hidden" name="action" value="concluir">
                                                    <button type="submit" class="acao-btn btn-concluir" onclick="return confirm('Marcar como concluída?')">
                                                        <i class="fas fa-flag-checkered"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if ($reserva['status'] === 'pendente' || $reserva['status'] === 'confirmada'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="reserva_id" value="<?php echo $reserva['id']; ?>">
                                                    <input type="hidden" name="action" value="cancelar">
                                                    <button type="submit" class="acao-btn btn-cancelar" onclick="return confirm('Cancelar esta reserva?')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <button class="acao-btn btn-mensagem" onclick="window.location.href='../mensagens.php?reserva_id=<?php echo $reserva['id']; ?>'">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <div class="pagination">
                    <button class="page-btn active">1</button>
                    <button class="page-btn">2</button>
                    <button class="page-btn">3</button>
                    <span>...</span>
                    <button class="page-btn">5</button>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>Nenhuma reserva encontrada</h3>
                    <p><?php echo $tipo_utilizador === 'proprietario' ? 'Ainda não há reservas para suas propriedades.' : 'Você ainda não fez nenhuma reserva.'; ?></p>
                    <?php if ($tipo_utilizador === 'arrendatario'): ?>
                        <a href="../pesquisa.php" class="filtro-btn" style="margin-top: 15px;">
                            <i class="fas fa-search"></i> Buscar Alojamentos
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
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

    <script src="../backend/script.js"></script>
</body>

</html>