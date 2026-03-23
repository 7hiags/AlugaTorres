<?php

/**
 * Dashboard - Painel do Utilizador
 * Este arquivo exibe o painel pessoal do utilizador logado.
 * @author AlugaTorres
 */

require_once __DIR__ . '/init.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: ../backend/autenticacao/login.php");
    exit;
}

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
$user_name = $_SESSION['user'];
$tipo_utilizador = $_SESSION['tipo_utilizador'] ?? 'arrendatario';

if ($tipo_utilizador === 'proprietario') {
    // DASHBOARD DO PROPRIETÁRIO
    $query_casas = $conn->prepare("SELECT COUNT(*) as total_casas FROM casas WHERE proprietario_id = ? AND (aprovado = 1 OR aprovado IS NULL = 0)");
    $query_casas->bind_param("i", $user_id);
    $query_casas->execute();
    $total_casas = $query_casas->get_result()->fetch_assoc()['total_casas'] ?? 0;

    $query_reservas = $conn->prepare("SELECT COUNT(*) as total_reservas FROM reservas r JOIN casas c ON r.casa_id = c.id WHERE c.proprietario_id = ? AND r.status IN ('pendente','confirmada')");
    $query_reservas->bind_param("i", $user_id);
    $query_reservas->execute();
    $total_reservas = $query_reservas->get_result()->fetch_assoc()['total_reservas'] ?? 0;

    $query_receita = $conn->prepare("SELECT SUM(r.total) as total_receita FROM reservas r JOIN casas c ON r.casa_id = c.id WHERE c.proprietario_id = ? AND r.status NOT IN ('cancelada', 'rejeitada')");
    $query_receita->bind_param("i", $user_id);
    $query_receita->execute();
    $total_receita = $query_receita->get_result()->fetch_assoc()['total_receita'] ?? 0;

    $query_pendentes = $conn->prepare("SELECT COUNT(*) as reservas_pendentes FROM reservas r JOIN casas c ON r.casa_id = c.id WHERE c.proprietario_id = ? AND r.status = 'pendente'");
    $query_pendentes->bind_param("i", $user_id);
    $query_pendentes->execute();
    $reservas_pendentes = $query_pendentes->get_result()->fetch_assoc()['reservas_pendentes'] ?? 0;

    $query_casas_reservadas = $conn->prepare("SELECT r.*, c.titulo as casa_titulo, u.utilizador as arrendatario_nome FROM reservas r JOIN casas c ON r.casa_id = c.id JOIN utilizadores u ON r.arrendatario_id = u.id WHERE c.proprietario_id = ? AND r.status IN ('pendente', 'confirmada') ORDER BY r.data_checkin ASC LIMIT 5");
    $query_casas_reservadas->bind_param("i", $user_id);
    $query_casas_reservadas->execute();
    $casas_reservadas = $query_casas_reservadas->get_result();

    $dashboard_title = "Bem-vindo, " . htmlspecialchars($user_name) . "!";
    $dashboard_sub = "Aqui está um resumo das suas propriedades.";
    $btn_text = "Adicionar Nova Casa";
    $btn_link = "../proprietario/adicionar_casa.php";
    $stats = [
        ['icon' => 'fas fa-home', 'value' => $total_casas, 'label' => 'Total Casas', 'color' => 'primary', 'subtitle' => 'Propriedades'],
        ['icon' => 'fas fa-calendar-check', 'value' => $total_reservas, 'label' => 'Reservas Ativas', 'color' => 'warning', 'subtitle' => 'Confirmadas'],
        ['icon' => 'fas fa-clock', 'value' => $reservas_pendentes, 'label' => 'Reservas Pendentes', 'color' => 'info', 'subtitle' => 'Aguardam confirmação'],
        ['icon' => 'fas fa-euro-sign', 'value' => number_format($total_receita, 2, ',', '.'), 'label' => 'Receita Total', 'color' => 'success', 'subtitle' => 'EUR']
    ];
} else {
    // DASHBOARD DO ARRENDATÁRIO
    $query_reservas_ativas = $conn->prepare("SELECT COUNT(*) as reservas_ativas FROM reservas WHERE arrendatario_id = ? AND status IN ('pendente', 'confirmada')");
    $query_reservas_ativas->bind_param("i", $user_id);
    $query_reservas_ativas->execute();
    $reservas_ativas = $query_reservas_ativas->get_result()->fetch_assoc()['reservas_ativas'] ?? 0;

    $query_total_reservas = $conn->prepare("SELECT COUNT(*) as total_reservas FROM reservas WHERE arrendatario_id = ?");
    $query_total_reservas->bind_param("i", $user_id);
    $query_total_reservas->execute();
    $total_reservas_arrendatario = $query_total_reservas->get_result()->fetch_assoc()['total_reservas'] ?? 0;

    $query_concluidas = $conn->prepare("SELECT COUNT(*) as reservas_concluidas FROM reservas WHERE arrendatario_id = ? AND status = 'concluida'");
    $query_concluidas->bind_param("i", $user_id);
    $query_concluidas->execute();
    $reservas_concluidas = $query_concluidas->get_result()->fetch_assoc()['reservas_concluidas'] ?? 0;

    $query_canceladas = $conn->prepare("SELECT COUNT(*) as reservas_canceladas FROM reservas WHERE arrendatario_id = ? AND status IN ('cancelada', 'rejeitada')");
    $query_canceladas->bind_param("i", $user_id);
    $query_canceladas->execute();
    $reservas_canceladas = $query_canceladas->get_result()->fetch_assoc()['reservas_canceladas'] ?? 0;

    $query_total_gasto = $conn->prepare("SELECT SUM(total) as total_gasto FROM reservas WHERE arrendatario_id = ? AND status NOT IN ('cancelada', 'rejeitada')");
    $query_total_gasto->bind_param("i", $user_id);
    $query_total_gasto->execute();
    $total_gasto = $query_total_gasto->get_result()->fetch_assoc()['total_gasto'] ?? 0;

    $query_proximas = $conn->prepare("SELECT r.*, c.titulo as casa_titulo FROM reservas r JOIN casas c ON r.casa_id = c.id WHERE r.arrendatario_id = ? AND r.data_checkin >= CURDATE() AND r.status IN ('pendente', 'confirmada') ORDER BY r.data_checkin LIMIT 3");
    $query_proximas->bind_param("i", $user_id);
    $query_proximas->execute();
    $proximas_reservas = $query_proximas->get_result();

    $dashboard_title = "Bem-vindo, " . htmlspecialchars($user_name) . "!";
    $dashboard_sub = "Aqui está um resumo das suas atividades de reserva.";
    $btn_text = "Encontrar Alojamento";
    $btn_link = "pesquisa.php";
    $stats = [
        ['icon' => 'fas fa-calendar-check', 'value' => $reservas_ativas, 'label' => 'Reservas Ativas', 'color' => 'warning', 'subtitle' => 'Em curso'],
        ['icon' => 'fas fa-history', 'value' => $total_reservas_arrendatario, 'label' => 'Total Reservas', 'color' => 'primary', 'subtitle' => 'Todas'],
        ['icon' => 'fas fa-check-circle', 'value' => $reservas_concluidas, 'label' => 'Concluídas', 'color' => 'success', 'subtitle' => 'Finalizadas'],
        ['icon' => 'fas fa-times-circle', 'value' => $reservas_canceladas, 'label' => 'Canceladas', 'color' => 'danger', 'subtitle' => 'Rejeitadas'],
        ['icon' => 'fas fa-wallet', 'value' => number_format($total_gasto, 2, ',', '.'), 'label' => 'Total Gasto', 'color' => 'info', 'subtitle' => 'EUR']
    ];
}

$pageTitle = 'AlugaTorres | Dashboard';
$metaDescription = 'Painel pessoal conforme tipo de utilizador';
$extraHead = '<link rel="stylesheet" href="' . BASE_URL . 'assets/style/admin_style.css">';

require_once __DIR__ . '/head.php';
include 'header.php';
include 'sidebar.php';
?>

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
            <?php $colorClass = isset($stat['color']) ? $stat['color'] : 'primary'; ?>
            <div class="stat-card <?php echo $colorClass; ?>">
                <div class="stat-icon">
                    <i class="<?php echo $stat['icon']; ?>"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stat['value']; ?></h3>
                    <p><?php echo $stat['label']; ?></p>
                    <?php if (isset($stat['subtitle'])): ?>
                        <small><?php echo $stat['subtitle']; ?></small>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($tipo_utilizador === 'proprietario'): ?>
        <div class="recent-activity">
            <h2>Casas Reservadas</h2>
            <div class="activity-list">
                <?php if ($casas_reservadas && $casas_reservadas->num_rows > 0): ?>
                    <?php while ($reserva = $casas_reservadas->fetch_assoc()): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-home"></i>
                            </div>
                            <div class="activity-content">
                                <h4><?php echo htmlspecialchars($reserva['casa_titulo']); ?></h4>
                                <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($reserva['arrendatario_nome']); ?></p>
                                <p><i class="fas fa-calendar-alt"></i> Check-in: <?php echo date('d/m/Y', strtotime($reserva['data_checkin'])); ?> - Check-out: <?php echo date('d/m/Y', strtotime($reserva['data_checkout'])); ?></p>
                                <span class="status-badge status-<?php echo $reserva['status']; ?>"><?php echo ucfirst($reserva['status']); ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>Não há casas reservadas no momento.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

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
</body>

</html>