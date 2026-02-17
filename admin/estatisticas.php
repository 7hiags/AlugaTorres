<?php
require_once __DIR__ . '/../backend/check_admin.php';

// Período para estatísticas
$periodo = $_GET['periodo'] ?? 'mes';
$ano = intval($_GET['ano'] ?? date('Y'));
$mes = intval($_GET['mes'] ?? date('m'));

// Dados para gráficos
$reservas_por_mes = [];
$receitas_por_mes = [];
$utilizadores_por_mes = [];

for ($i = 1; $i <= 12; $i++) {
    // Reservas
    $result = $conn->query("SELECT COUNT(*) as total FROM reservas WHERE MONTH(data_reserva) = $i AND YEAR(data_reserva) = $ano");
    $reservas_por_mes[] = $result->fetch_assoc()['total'];

    // Receitas
    $result = $conn->query("SELECT COALESCE(SUM(total), 0) as total FROM reservas WHERE MONTH(data_reserva) = $i AND YEAR(data_reserva) = $ano AND status IN ('confirmada', 'concluida')");
    $receitas_por_mes[] = $result->fetch_assoc()['total'];

    // Utilizadores
    $result = $conn->query("SELECT COUNT(*) as total FROM utilizadores WHERE MONTH(data_registro) = $i AND YEAR(data_registro) = $ano");

    $utilizadores_por_mes[] = $result->fetch_assoc()['total'];
}

// Top casas mais reservadas
$top_casas = $conn->query("
    SELECT c.titulo, c.morada as localizacao, COUNT(r.id) as total_reservas, SUM(r.total) as receita_total
    FROM casas c
    LEFT JOIN reservas r ON c.id = r.casa_id AND r.status IN ('confirmada', 'concluida')
    GROUP BY c.id
    ORDER BY total_reservas DESC
    LIMIT 10
");


// Top proprietários
$top_proprietarios = $conn->query("
    SELECT u.utilizador, u.email, COUNT(c.id) as total_casas, COUNT(r.id) as total_reservas
    FROM utilizadores u
    LEFT JOIN casas c ON u.id = c.proprietario_id
    LEFT JOIN reservas r ON c.id = r.casa_id AND r.status IN ('confirmada', 'concluida')
    WHERE u.tipo_utilizador = 'proprietario'
    GROUP BY u.id
    ORDER BY total_reservas DESC
    LIMIT 10
");


logAdminActivity('Acesso às Estatísticas');
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>AlugaTorres | Estatísticas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="website icon" type="png" href="../style/img/Logo_AlugaTorres_branco.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php include '../header.php';
    include '../sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-header">
            <h2><i class="fas fa-chart-line"></i> Estatísticas</h2>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>

        <!-- Filtros -->
        <div class="filters-section">
            <form class="filters-form" method="GET">
                <div class="form-group">
                    <select name="ano" class="form-control">
                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $ano == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Atualizar</button>
            </form>
        </div>

        <!-- Gráficos -->
        <div class="admin-content-grid">
            <div class="admin-card">
                <h3><i class="fas fa-calendar-alt"></i> Reservas por Mês</h3>
                <div class="chart-container">
                    <canvas id="reservasChart"></canvas>
                </div>
            </div>
            <div class="admin-card">
                <h3><i class="fas fa-euro-sign"></i> Receitas por Mês</h3>
                <div class="chart-container">
                    <canvas id="receitasChart"></canvas>
                </div>
            </div>
        </div>

        <div class="admin-card" style="margin-bottom: 30px;">
            <h3><i class="fas fa-user-plus"></i> Novos Utilizadores por Mês</h3>
            <div class="chart-container">
                <canvas id="utilizadoresChart"></canvas>
            </div>
        </div>

        <!-- Top Casas -->
        <div class="admin-content-grid">
            <div class="admin-card">
                <h3><i class="fas fa-trophy"></i> Top 10 Casas Mais Reservadas</h3>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Casa</th>
                            <th>Reservas</th>
                            <th>Receita</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($casa = $top_casas->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($casa['titulo']); ?><br><small><?php echo $casa['localizacao']; ?></small></td>
                                <td><?php echo $casa['total_reservas']; ?></td>
                                <td>€<?php echo number_format($casa['receita_total'] ?? 0, 2, ',', '.'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="admin-card">
                <h3><i class="fas fa-crown"></i> Top 10 Proprietários</h3>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Proprietário</th>
                            <th>Casas</th>
                            <th>Reservas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($prop = $top_proprietarios->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($prop['utilizador']); ?></td>
                                <td><?php echo $prop['total_casas']; ?></td>
                                <td><?php echo $prop['total_reservas']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <?php include '../footer.php'; ?>

    <script src="../js/script.js"></script>
    <script>
        const meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        const reservasData = <?php echo json_encode($reservas_por_mes); ?>;
        const receitasData = <?php echo json_encode($receitas_por_mes); ?>;
        const utilizadoresData = <?php echo json_encode($utilizadores_por_mes); ?>;

        // Gráfico de Reservas
        new Chart(document.getElementById('reservasChart'), {
            type: 'bar',
            data: {
                labels: meses,
                datasets: [{
                    label: 'Reservas',
                    data: reservasData,
                    backgroundColor: 'rgba(102, 126, 234, 0.8)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Gráfico de Receitas
        new Chart(document.getElementById('receitasChart'), {
            type: 'line',
            data: {
                labels: meses,
                datasets: [{
                    label: 'Receitas (€)',
                    data: receitasData,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Gráfico de Utilizadores
        new Chart(document.getElementById('utilizadoresChart'), {
            type: 'bar',
            data: {
                labels: meses,
                datasets: [{
                    label: 'Novos Utilizadores',
                    data: utilizadoresData,
                    backgroundColor: 'rgba(255, 193, 7, 0.8)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>

</html>