<?php
require_once __DIR__ . '/../backend/check_admin.php';

// Buscar estatísticas para o dashboard
$stats = [];

// Total de utilizadores
$result = $conn->query("SELECT COUNT(*) as total FROM utilizadores");
$stats['total_utilizadores'] = $result->fetch_assoc()['total'];

// Total de proprietários
$result = $conn->query("SELECT COUNT(*) as total FROM utilizadores WHERE tipo_utilizador = 'proprietario'");
$stats['total_proprietarios'] = $result->fetch_assoc()['total'];

// Total de arrendatários
$result = $conn->query("SELECT COUNT(*) as total FROM utilizadores WHERE tipo_utilizador = 'arrendatario'");
$stats['total_arrendatarios'] = $result->fetch_assoc()['total'];

// Total de casas
$result = $conn->query("SELECT COUNT(*) as total FROM casas");
$stats['total_casas'] = $result->fetch_assoc()['total'];

// Casas disponíveis
$result = $conn->query("SELECT COUNT(*) as total FROM casas WHERE disponivel = 1");
$stats['casas_disponiveis'] = $result->fetch_assoc()['total'];

// Casas em destaque
$result = $conn->query("SELECT COUNT(*) as total FROM casas WHERE destaque = 1");
$stats['casas_destaque'] = $result->fetch_assoc()['total'];

// Total de reservas
$result = $conn->query("SELECT COUNT(*) as total FROM reservas");
$stats['total_reservas'] = $result->fetch_assoc()['total'];

// Reservas pendentes
$result = $conn->query("SELECT COUNT(*) as total FROM reservas WHERE status = 'pendente'");
$stats['reservas_pendentes'] = $result->fetch_assoc()['total'];

// Reservas confirmadas
$result = $conn->query("SELECT COUNT(*) as total FROM reservas WHERE status = 'confirmada'");
$stats['reservas_confirmadas'] = $result->fetch_assoc()['total'];

// Reservas concluídas
$result = $conn->query("SELECT COUNT(*) as total FROM reservas WHERE status = 'concluida'");
$stats['reservas_concluidas'] = $result->fetch_assoc()['total'];

// Receita total estimada
$result = $conn->query("SELECT SUM(total) as total FROM reservas WHERE status IN ('confirmada', 'concluida')");
$stats['receita_total'] = $result->fetch_assoc()['total'] ?? 0;

// Novos utilizadores este mês
$result = $conn->query("SELECT COUNT(*) as total FROM utilizadores WHERE MONTH(data_registro) = MONTH(CURRENT_DATE()) AND YEAR(data_registro) = YEAR(CURRENT_DATE())");
$stats['novos_utilizadores_mes'] = $result->fetch_assoc()['total'];

// Novas reservas este mês
$result = $conn->query("SELECT COUNT(*) as total FROM reservas WHERE MONTH(data_reserva) = MONTH(CURRENT_DATE()) AND YEAR(data_reserva) = YEAR(CURRENT_DATE())");
$stats['novas_reservas_mes'] = $result->fetch_assoc()['total'];

// Últimos 5 utilizadores registados
$ultimos_utilizadores = $conn->query("SELECT id, utilizador, email, tipo_utilizador, data_registro as created_at FROM utilizadores ORDER BY data_registro DESC LIMIT 5");

// Últimas 5 reservas
$ultimas_reservas = $conn->query("
    SELECT r.id, r.status as estado, r.total as valor_total, r.data_reserva as created_at,
           c.titulo as casa_titulo,
           u.utilizador as arrendatario_nome
    FROM reservas r
    JOIN casas c ON r.casa_id = c.id
    JOIN utilizadores u ON r.arrendatario_id = u.id
    ORDER BY r.data_reserva DESC
    LIMIT 5
");


// Casas pendentes de aprovação (se existir campo aprovado)
try {
    $casas_pendentes = $conn->query("SELECT COUNT(*) as total FROM casas WHERE aprovado = 0 OR aprovado IS NULL");
    $stats['casas_pendentes'] = $casas_pendentes->fetch_assoc()['total'];
} catch (\Exception $e) {
    $stats['casas_pendentes'] = 0;
}

// Log da atividade
logAdminActivity('Acesso ao Dashboard', 'Visualização do painel administrativo');
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlugaTorres | Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="website icon" type="png" href="../style/img/Logo_AlugaTorres_branco.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php include '../header.php'; ?>
    <?php include '../sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-header">
            <h1><i class="fas fa-shield-alt"></i> Painel de Administração</h1>
            <p>Bem-vindo, <strong><?php echo htmlspecialchars($admin_nome); ?></strong>!</p>
        </div>

        <!-- Cards de Estatísticas -->


        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_utilizadores']); ?></h3>
                    <p>Total Utilizadores</p>
                    <small>+<?php echo $stats['novos_utilizadores_mes']; ?> este mês</small>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-icon"><i class="fas fa-home"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_casas']); ?></h3>
                    <p>Total Casas</p>
                    <small><?php echo $stats['casas_disponiveis']; ?> disponíveis</small>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_reservas']); ?></h3>
                    <p>Total Reservas</p>
                    <small>+<?php echo $stats['novas_reservas_mes']; ?> este mês</small>
                </div>
            </div>

            <div class="stat-card info">
                <div class="stat-icon"><i class="fas fa-euro-sign"></i></div>
                <div class="stat-info">
                    <h3>€<?php echo number_format($stats['receita_total'], 2, ',', '.'); ?></h3>
                    <p>Receita Total</p>
                    <small>Reservas confirmadas</small>
                </div>
            </div>
        </div>

        <!-- Estatísticas Detalhadas -->
        <div class="stats-row">
            <div class="stat-detail-card">
                <h4><i class="fas fa-user-tie"></i> Proprietários</h4>
                <p class="big-number"><?php echo number_format($stats['total_proprietarios']); ?></p>
            </div>
            <div class="stat-detail-card">
                <h4><i class="fas fa-user"></i> Arrendatários</h4>
                <p class="big-number"><?php echo number_format($stats['total_arrendatarios']); ?></p>
            </div>
            <div class="stat-detail-card">
                <h4><i class="fas fa-star"></i> Casas em Destaque</h4>
                <p class="big-number"><?php echo number_format($stats['casas_destaque']); ?></p>
            </div>
            <div class="stat-detail-card">
                <h4><i class="fas fa-clock"></i> Reservas Pendentes</h4>
                <p class="big-number"><?php echo number_format($stats['reservas_pendentes']); ?></p>
            </div>
        </div>

        <!-- Gráfico e Tabelas -->
        <div class="admin-content-grid">
            <!-- Gráfico de Reservas -->
            <div class="admin-card">
                <h3><i class="fas fa-chart-bar"></i> Reservas por Estado</h3>
                <div class="chart-container">
                    <canvas id="reservasChart"
                        data-pendentes="<?php echo $stats['reservas_pendentes']; ?>"
                        data-confirmadas="<?php echo $stats['reservas_confirmadas']; ?>"
                        data-concluidas="<?php echo $stats['reservas_concluidas']; ?>"
                        data-canceladas="<?php echo max(0, $stats['total_reservas'] - $stats['reservas_pendentes'] - $stats['reservas_confirmadas'] - $stats['reservas_concluidas']); ?>">
                    </canvas>
                </div>

            </div>


            <!-- Últimos Utilizadores -->
            <div class="admin-card">
                <h3><i class="fas fa-user-plus"></i> Últimos Utilizadores</h3>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $ultimos_utilizadores->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['utilizador']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['tipo_utilizador']; ?>">
                                        <?php echo ucfirst($user['tipo_utilizador']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            </tr>

                        <?php endwhile; ?>
                    </tbody>
                </table>
                <a href="utilizadores.php" class="btn-view-all">Ver todos <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>

        <!-- Últimas Reservas -->
        <div class="admin-card full-width">
            <h3><i class="fas fa-calendar-check"></i> Últimas Reservas</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Casa</th>
                        <th>Arrendatário</th>
                        <th>Valor</th>
                        <th>Estado</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($reserva = $ultimas_reservas->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $reserva['id']; ?></td>
                            <td><?php echo htmlspecialchars($reserva['casa_titulo']); ?></td>
                            <td><?php echo htmlspecialchars($reserva['arrendatario_nome']); ?></td>
                            <td>€<?php echo number_format($reserva['valor_total'], 2, ',', '.'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $reserva['estado']; ?>">
                                    <?php echo ucfirst($reserva['estado']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($reserva['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>

                </tbody>
            </table>
            <a href="reservas.php" class="btn-view-all">Ver todas <i class="fas fa-arrow-right"></i></a>
        </div>

        <!-- Ações Rápidas -->
        <div class="quick-actions">
            <h3><i class="fas fa-bolt"></i> Ações Rápidas</h3>
            <div class="actions-grid">
                <a href="utilizadores.php" class="action-card">
                    <i class="fas fa-users-cog"></i>
                    <span>Gerir Utilizadores</span>
                </a>
                <a href="casas.php" class="action-card">
                    <i class="fas fa-home"></i>
                    <span>Gerir Casas</span>
                </a>
                <a href="verificacoes.php" class="action-card">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Verificações Pendentes</span>
                    <?php if ($stats['casas_pendentes'] > 0): ?>
                        <span class="notification-badge"><?php echo $stats['casas_pendentes']; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>

    </main>



    <?php include '../footer.php'; ?>

    <script src="../js/script.js"></script>

</body>

</html>