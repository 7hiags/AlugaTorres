<?php

/**
 * ========================================
 * Dashboard - Painel do Utilizador
 * ========================================
 * Este arquivo exibe o painel pessoal do utilizador logado.
 * O conteúdo varia conforme o tipo de utilizador:
 * - Proprietário: vê estatísticas das suas casas e reservas
 * - Arrendatário: vê as suas reservas e histórico
 * 
 * @author AlugaTorres
 * @version 1.0
 */

// ============================================
// Inicialização da Sessão
// ============================================

session_start();

// ============================================
// Inclusão de Arquivos Necessários
// ============================================

// Carrega o arquivo de conexão com o banco de dados
require_once 'backend/db.php';

// ============================================
// Verificação de Autenticação
// ============================================

// Verifica se o utilizador está autenticado
if (!isset($_SESSION['user_id'])) {
    // Se não estiver logado, redireciona para a página de login
    header("Location: backend/login.php");
    exit;
}

// ============================================
// Verificação de Utilizador Válido
// ============================================

// Verificar se o usuário ainda existe na base de dados
$stmt = $conn->prepare("SELECT id FROM utilizadores WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

// Se o utilizador não existir (foi deletedo), destrói a sessão
if ($result->num_rows === 0) {
    session_destroy();
    header("Location: backend/login.php");
    exit;
}

// ============================================
// Obtenção de Dados do Utilizador
// ============================================

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user'];
$tipo_utilizador = $_SESSION['tipo_utilizador'] ?? 'arrendatario';

// ============================================
// Lógica Específica por Tipo de Utilizador
// ============================================

if ($tipo_utilizador === 'proprietario') {
    // ------------------------------------------
    // DASHBOARD DO PROPRIETÁRIO
    // ------------------------------------------

    // 1. Contar total de casas do proprietário
    $query_casas = $conn->prepare("SELECT COUNT(*) as total_casas FROM casas WHERE proprietario_id = ?");
    $query_casas->bind_param("i", $user_id);
    $query_casas->execute();
    $result_casas = $query_casas->get_result();
    $total_casas = $result_casas->fetch_assoc()['total_casas'] ?? 0;

    // 2. Contar reservas ativas (pendentes e confirmadas)
    $query_reservas = $conn->prepare("SELECT COUNT(*) as total_reservas FROM reservas r JOIN casas c ON r.casa_id = c.id WHERE c.proprietario_id = ? AND r.status IN ('pendente','confirmada')");
    $query_reservas->bind_param("i", $user_id);
    $query_reservas->execute();
    $result_reservas = $query_reservas->get_result();
    $total_reservas = $result_reservas->fetch_assoc()['total_reservas'] ?? 0;

    // 3. Calcular receita total (excluindo canceladas e rejeitadas)
    $query_receita = $conn->prepare("SELECT SUM(r.total) as total_receita FROM reservas r JOIN casas c ON r.casa_id = c.id WHERE c.proprietario_id = ? AND r.status NOT IN ('cancelada', 'rejeitada')");
    $query_receita->bind_param("i", $user_id);
    $query_receita->execute();
    $result_receita = $query_receita->get_result();
    $total_receita = $result_receita->fetch_assoc()['total_receita'] ?? 0;

    // Configurações do dashboard do proprietário
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
    // ------------------------------------------
    // DASHBOARD DO ARRENDATÁRIO
    // ------------------------------------------

    // 1. Contar reservas ativas do arrendatário
    $query_reservas_ativas = $conn->prepare("SELECT COUNT(*) as reservas_ativas FROM reservas WHERE arrendatario_id = ? AND status IN ('pendente', 'confirmada')");
    $query_reservas_ativas->bind_param("i", $user_id);
    $query_reservas_ativas->execute();
    $reservas_ativas = $query_reservas_ativas->get_result()->fetch_assoc()['reservas_ativas'] ?? 0;

    // 2. Calcular total gasto em reservas
    $query_total_gasto = $conn->prepare("SELECT SUM(total) as total_gasto FROM reservas WHERE arrendatario_id = ? AND status NOT IN ('cancelada', 'rejeitada')");
    $query_total_gasto->bind_param("i", $user_id);
    $query_total_gasto->execute();
    $total_gasto = $query_total_gasto->get_result()->fetch_assoc()['total_gasto'] ?? 0;

    // 3. Buscar próximas reservas (check-in a partir de hoje)
    $query_proximas = $conn->prepare("SELECT r.*, c.titulo as casa_titulo FROM reservas r JOIN casas c ON r.casa_id = c.id WHERE r.arrendatario_id = ? AND r.data_checkin >= CURDATE() AND r.status IN ('pendente', 'confirmada') ORDER BY r.data_checkin LIMIT 3");
    $query_proximas->bind_param("i", $user_id);
    $query_proximas->execute();
    $proximas_reservas = $query_proximas->get_result();

    // Configurações do dashboard do arrendatário
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
    <!-- ========================================
         Meta Tags e Configurações
         ======================================== -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlugaTorres | Dashboard</title>

    <!-- ========================================
         Folhas de Estilo (CSS)
         ======================================== -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style/style.css">
    <link rel="website icon" type="png" href="style/img/Logo_AlugaTorres_branco.png">
</head>

<body>
    <!-- ========================================
         Inclusão de Componentes
         ======================================== -->
    <?php include 'header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <!-- ========================================
         Container Principal do Dashboard
         ======================================== -->
    <div class="dashboard-container">

        <!-- ========================================
             Cabeçalho do Dashboard
             ======================================== -->
        <div class="dashboard-header">
            <h1 class="dashboard-title"><?php echo $dashboard_title; ?></h1>
            <p class="dashboard-sub"><?php echo $dashboard_sub ?? ''; ?></p>
            <a href="<?php echo $btn_link; ?>" class="btn-nova-<?php echo $tipo_utilizador === 'proprietario' ? 'casa' : 'reserva'; ?>">
                <i class="fas fa-<?php echo $tipo_utilizador === 'proprietario' ? 'plus' : 'search'; ?>"></i> <?php echo $btn_text; ?>
            </a>
        </div>

        <!-- ========================================
             Grid de Estatísticas
             ======================================== -->
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

        <!-- ========================================
             Atividade Recente (Apenas para Arrendatários)
             ======================================== -->
        <?php if ($tipo_utilizador === 'arrendatario'): ?>
            <div class="recent-activity">
                <h2>Próximas Reservas</h2>
                <div class="activity-list">
                    <?php if ($proximas_reservas->num_rows > 0): ?>
                        <!-- Loop pelas reservas próximas -->
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
                        <!-- Mensagem quando não há reservas -->
                        <p>Não há reservas próximas.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- ========================================
         Rodapé da Página
         ======================================== -->
    <?php include 'footer.php'; ?>

    <!-- ========================================
         Scripts JavaScript
         ======================================== -->
    <script src="js/script.js"></script>

</body>

</html>