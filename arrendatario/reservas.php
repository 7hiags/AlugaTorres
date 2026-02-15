<?php
session_start();
require_once '../backend/db.php';

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

// Obter reservas do utilizador
if ($tipo_utilizador === 'proprietario') {
    $query = $conn->prepare("
        SELECT r.*, c.titulo as casa_titulo, u.utilizador as arrendatario_nome
        FROM reservas r
        JOIN casas c ON r.casa_id = c.id
        JOIN utilizadores u ON r.arrendatario_id = u.id
        WHERE c.proprietario_id = ?
        ORDER BY r.data_criacao DESC
    ");
} else {
    $query = $conn->prepare("
        SELECT r.*, c.titulo as casa_titulo, u.utilizador as proprietario_nome
        FROM reservas r
        JOIN casas c ON r.casa_id = c.id
        JOIN utilizadores u ON c.proprietario_id = u.id
        WHERE r.arrendatario_id = ?
        ORDER BY r.data_criacao DESC
    ");
}
$query->bind_param("i", $user_id);
$query->execute();
$reservas = $query->get_result();
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlugaTorres | Minhas Reservas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="website icon" type="png" href="../style/img/Logo_AlugaTorres_branco.png">
</head>

<body>
    <?php include '../header.php'; ?>
    <?php include '../sidebar.php'; ?>

    <div class="reservas-container">
        <div class="reservas-header">
            <h1 class="reservas-title">Minhas Reservas</h1>
            <?php if ($tipo_utilizador === 'arrendatario'): ?>
                <a href="../pesquisa.php" class="btn-nova-reserva">
                    <i class="fas fa-search"></i> Encontrar Alojamento
                </a>
            <?php endif; ?>
        </div>

        <?php if ($reservas->num_rows > 0): ?>
            <div class="reservas-grid">
                <?php while ($reserva = $reservas->fetch_assoc()): ?>
                    <div class="reserva-card status-<?php echo $reserva['status']; ?>">
                        <div class="reserva-header">
                            <h3><?php echo htmlspecialchars($reserva['casa_titulo']); ?></h3>
                            <span class="reserva-status status-<?php echo $reserva['status']; ?>">
                                <?php echo ucfirst($reserva['status']); ?>
                            </span>
                        </div>

                        <div class="reserva-dates">
                            <div class="date-item">
                                <i class="fas fa-calendar-check"></i>
                                <div>
                                    <strong>Check-in</strong>
                                    <p><?php echo date('d/m/Y', strtotime($reserva['data_checkin'])); ?></p>
                                </div>
                            </div>
                            <div class="date-item">
                                <i class="fas fa-calendar-times"></i>
                                <div>
                                    <strong>Check-out</strong>
                                    <p><?php echo date('d/m/Y', strtotime($reserva['data_checkout'])); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="reserva-info">
                            <p><i class="fas fa-users"></i> <?php echo $reserva['num_hospedes']; ?> hóspede(s)</p>
                            <p><i class="fas fa-euro-sign"></i> Total: <?php echo number_format($reserva['total'], 2, ',', '.'); ?>€</p>
                            <?php if ($tipo_utilizador === 'proprietario'): ?>
                                <p><i class="fas fa-user"></i> Arrendatário: <?php echo htmlspecialchars($reserva['arrendatario_nome']); ?></p>
                            <?php else: ?>
                                <p><i class="fas fa-user"></i> Proprietário: <?php echo htmlspecialchars($reserva['proprietario_nome']); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="reserva-actions">
                            <a href="../calendario.php?casa_id=<?php echo $reserva['casa_id']; ?>" class="btn-action btn-view">
                                <i class="fas fa-calendar-alt"></i> Ver Calendário
                            </a>
                            <?php if ($reserva['status'] === 'pendente' || $reserva['status'] === 'confirmada'): ?>
                                <?php if ($tipo_utilizador === 'arrendatario'): ?>
                                    <button class="btn-action btn-cancel" onclick="cancelarReserva(<?php echo $reserva['id']; ?>)">
                                        <i class="fas fa-times"></i> Cancelar
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times fa-3x"></i>
                <h2>Nenhuma reserva encontrada</h2>
                <p>Você ainda não tem reservas.</p>
                <?php if ($tipo_utilizador === 'arrendatario'): ?>
                    <a href="../pesquisa.php" class="btn-nova-reserva">
                        <i class="fas fa-search"></i> Encontrar Alojamento
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../footer.php'; ?>

    <script src="../js/script.js"></script>

    <script>
        function cancelarReserva(reservaId) {
            if (confirm('Tem certeza que deseja cancelar esta reserva?')) {
                // Implementar lógica de cancelamento via AJAX
                fetch('../backend_API/api_reservas.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'cancel',
                            reserva_id: reservaId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Reserva cancelada com sucesso!');
                            location.reload();
                        } else {
                            alert('Erro ao cancelar reserva: ' + (data.error || 'Erro desconhecido'));
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        alert('Erro ao cancelar reserva');
                    });
            }
        }
    </script>
</body>

</html>