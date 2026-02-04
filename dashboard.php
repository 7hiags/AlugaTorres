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
    header("Location: backend/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user'];
$tipo_utilizador = $_SESSION['tipo_utilizador'] ?? 'arrendatario';

if ($tipo_utilizador === 'proprietario') {
    // Lógica do proprietário
    $query_casas = $conn->prepare("SELECT COUNT(*) as total_casas FROM casas WHERE proprietario_id = ?");
    $query_casas->bind_param("i", $user_id);
    $query_casas->execute();
    $result_casas = $query_casas->get_result();
    $total_casas = $result_casas->fetch_assoc()['total_casas'] ?? 0;

    $query_reservas = $conn->prepare("SELECT COUNT(*) as total_reservas FROM reservas r JOIN casas c ON r.casa_id = c.id WHERE c.proprietario_id = ? AND r.status IN ('pendente','confirmada')");
    $query_reservas->bind_param("i", $user_id);
    $query_reservas->execute();
    $result_reservas = $query_reservas->get_result();
    $total_reservas = $result_reservas->fetch_assoc()['total_reservas'] ?? 0;

    $query_receita = $conn->prepare("SELECT SUM(r.total) as total_receita FROM reservas r JOIN casas c ON r.casa_id = c.id WHERE c.proprietario_id = ? AND r.status = 'concluida'");
    $query_receita->bind_param("i", $user_id);
    $query_receita->execute();
    $result_receita = $query_receita->get_result();
    $total_receita = $result_receita->fetch_assoc()['total_receita'] ?? 0;

    $dashboard_title = "Bem-vindo, " . htmlspecialchars($user_name) . "!";
    $dashboard_sub = "Aqui está um resumo das suas propriedades.";
    $btn_text = "Adicionar Nova Casa";
    $btn_link = "proprietario/adicionar_casa.php";
    $stats = [
        ['icon' => 'fas fa-home', 'value' => $total_casas, 'label' => 'Casas'],
        ['icon' => 'fas fa-calendar-check', 'value' => $total_reservas, 'label' => 'Reservas Ativas'],
        ['icon' => 'fas fa-euro-sign', 'value' => number_format($total_receita, 2, ',', '.'), 'label' => 'Receita Total']
    ];
} else {
    // Lógica do arrendatário
    $query_reservas_ativas = $conn->prepare("SELECT COUNT(*) as reservas_ativas FROM reservas WHERE arrendatario_id = ? AND status IN ('pendente', 'confirmada')");
    $query_reservas_ativas->bind_param("i", $user_id);
    $query_reservas_ativas->execute();
    $reservas_ativas = $query_reservas_ativas->get_result()->fetch_assoc()['reservas_ativas'] ?? 0;

    $query_total_gasto = $conn->prepare("SELECT SUM(total) as total_gasto FROM reservas WHERE arrendatario_id = ? AND status = 'concluida'");
    $query_total_gasto->bind_param("i", $user_id);
    $query_total_gasto->execute();
    $total_gasto = $query_total_gasto->get_result()->fetch_assoc()['total_gasto'] ?? 0;

    $query_proximas = $conn->prepare("SELECT r.*, c.titulo as casa_titulo FROM reservas r JOIN casas c ON r.casa_id = c.id WHERE r.arrendatario_id = ? AND r.data_checkin >= CURDATE() ORDER BY r.data_checkin LIMIT 3");
    $query_proximas->bind_param("i", $user_id);
    $query_proximas->execute();
    $proximas_reservas = $query_proximas->get_result();

    $dashboard_title = "Bem-vindo, " . htmlspecialchars($user_name) . "!";
    $dashboard_sub = "Aqui está um resumo das suas atividades de reserva.";
    $btn_text = "Encontrar Alojamento";
    $btn_link = "pesquisa.php";
    $stats = [
        ['icon' => 'fas fa-calendar-check', 'value' => $reservas_ativas, 'label' => 'Reservas Ativas'],
        ['icon' => 'fas fa-euro-sign', 'value' => number_format($total_gasto, 2, ',', '.'), 'label' => 'Total Gasto']
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlugaTorres | Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style/style.css">
    <link rel="website icon" type="png" href="style/img/Logo_AlugaTorres_branco.png">
</head>

<body>
    <?php include 'header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1 class="dashboard-title"><?php echo $dashboard_title; ?></h1>
            <p class="dashboard-sub"><?php echo $dashboard_sub ?? ''; ?></p>
            <a href="<?php echo $btn_link; ?>" class="btn-nova-<?php echo $tipo_utilizador === 'proprietario' ? 'casa' : 'reserva'; ?>">
                <i class="fas fa-<?php echo $tipo_utilizador === 'proprietario' ? 'plus' : 'search'; ?>"></i> <?php echo $btn_text; ?>
            </a>
        </div>


        <div class="stats-grid">
            <?php foreach ($stats as $stat): ?>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="<?php echo $stat['icon']; ?>"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stat['value']; ?></h3>
                        <p><?php echo $stat['label']; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($tipo_utilizador === 'arrendatario'): ?>
            <div class="recent-activity">
                <h2>Próximas Reservas</h2>
                <div class="activity-list">
                    <?php if ($proximas_reservas->num_rows > 0): ?>
                        <?php while ($reserva = $proximas_reservas->fetch_assoc()): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="activity-content">
                                    <h4><?php echo htmlspecialchars($reserva['casa_titulo']); ?></h4>
                                    <p>Check-in: <?php echo date('d/m/Y', strtotime($reserva['data_checkin'])); ?> - Check-out: <?php echo date('d/m/Y', strtotime($reserva['data_checkout'])); ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>Não há reservas próximas.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>

    <script src="backend/script.js"></script> <!-- sidebar script moved to sidebar.php -->

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