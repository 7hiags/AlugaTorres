<?php
require_once __DIR__ . '/../backend/check_admin.php';

// Processar ações
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $reserva_id = intval($_POST['reserva_id'] ?? 0);

    switch ($_POST['acao']) {
        case 'cancelar':
            $stmt = $conn->prepare("UPDATE reservas SET status = 'cancelada' WHERE id = ?");

            $stmt->bind_param("i", $reserva_id);
            if ($stmt->execute()) {
                logAdminActivity('Cancelar Reserva', 'ID: ' . $reserva_id);
                $mensagem = 'Reserva cancelada com sucesso!';
                $tipo_mensagem = 'success';
            }
            break;

        case 'confirmar':
            $stmt = $conn->prepare("UPDATE reservas SET status = 'confirmada' WHERE id = ?");

            $stmt->bind_param("i", $reserva_id);
            if ($stmt->execute()) {
                logAdminActivity('Confirmar Reserva', 'ID: ' . $reserva_id);
                $mensagem = 'Reserva confirmada!';
                $tipo_mensagem = 'success';
            }
            break;
    }
}

// Filtros
$filtro_estado = $_GET['estado'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];
$params = [];
$types = '';

if ($filtro_estado) {
    $where[] = "r.status = ?";

    $params[] = $filtro_estado;
    $types .= 's';
}

if ($search) {
    $where[] = "(c.titulo LIKE ? OR u.utilizador LIKE ? OR u2.utilizador LIKE ?)";
    $search_like = "%$search%";
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $types .= 'sss';
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Paginação
$por_pagina = 20;
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$offset = ($pagina - 1) * $por_pagina;

// Contar total
$count_sql = "SELECT COUNT(*) as total FROM reservas r JOIN casas c ON r.casa_id = c.id JOIN utilizadores u ON r.arrendatario_id = u.id JOIN utilizadores u2 ON c.proprietario_id = u2.id $where_clause";
$stmt = $conn->prepare($count_sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total / $por_pagina);

// Buscar reservas
$sql = "SELECT r.*, c.titulo as casa_titulo, c.localizacao, 
        u.utilizador as arrendatario_nome, u.email as arrendatario_email,
        u2.utilizador as proprietario_nome
        FROM reservas r 
        JOIN casas c ON r.casa_id = c.id 
        JOIN utilizadores u ON r.arrendatario_id = u.id
        JOIN utilizadores u2 ON c.proprietario_id = u2.id
        $where_clause 
        ORDER BY r.data_reserva DESC 

        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$params[] = $por_pagina;
$params[] = $offset;
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$reservas = $stmt->get_result();

// Estatísticas
$stats = [];
foreach (['pendente', 'confirmada', 'concluida', 'cancelada'] as $estado) {
    $result = $conn->query("SELECT COUNT(*) as total, COALESCE(SUM(total), 0) as valor FROM reservas WHERE status = '$estado'");

    $stats[$estado] = $result->fetch_assoc();
}

logAdminActivity('Acesso à Gestão de Reservas');
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>AlugaTorres | Gerir Reservas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="website icon" type="png" href="../style/img/Logo_AlugaTorres_branco.png">
</head>

<body>
    <?php include '../header.php';
    include '../sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-header">
            <h2><i class="fas fa-calendar-check"></i> Gerir Reservas</h2>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>

        <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $tipo_mensagem; ?>">
                <i class="fas fa-check-circle"></i> <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-detail-card">
                <h4><i class="fas fa-clock"></i> Pendentes</h4>
                <p class="big-number"><?php echo $stats['pendente']['total']; ?></p>
            </div>
            <div class="stat-detail-card">
                <h4><i class="fas fa-check"></i> Confirmadas</h4>
                <p class="big-number"><?php echo $stats['confirmada']['total']; ?></p>
            </div>
            <div class="stat-detail-card">
                <h4><i class="fas fa-flag-checkered"></i> Concluídas</h4>
                <p class="big-number"><?php echo $stats['concluida']['total']; ?></p>
            </div>
            <div class="stat-detail-card">
                <h4><i class="fas fa-times"></i> Canceladas</h4>
                <p class="big-number"><?php echo $stats['cancelada']['total']; ?></p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-section">
            <form class="filters-form" method="GET">
                <div class="form-group search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" class="form-control" placeholder="Pesquisar..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="form-group">
                    <select name="estado" class="form-control">
                        <option value="">Todos os estados</option>
                        <option value="pendente" <?php echo $filtro_estado === 'pendente' ? 'selected' : ''; ?>>Pendentes</option>
                        <option value="confirmada" <?php echo $filtro_estado === 'confirmada' ? 'selected' : ''; ?>>Confirmadas</option>
                        <option value="concluida" <?php echo $filtro_estado === 'concluida' ? 'selected' : ''; ?>>Concluídas</option>
                        <option value="cancelada" <?php echo $filtro_estado === 'cancelada' ? 'selected' : ''; ?>>Canceladas</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="reservas.php" class="btn btn-secondary"><i class="fas fa-times"></i> Limpar</a>
            </form>
        </div>

        <!-- Tabela -->
        <div class="admin-card">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Casa</th>
                        <th>Arrendatário</th>
                        <th>Proprietário</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Valor</th>
                        <th>Estado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = $reservas->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $r['id']; ?></td>
                            <td><?php echo htmlspecialchars($r['casa_titulo']); ?><br><small><?php echo $r['localizacao']; ?></small></td>
                            <td><?php echo htmlspecialchars($r['arrendatario_nome']); ?></td>
                            <td><?php echo htmlspecialchars($r['proprietario_nome']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($r['data_checkin'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($r['data_checkout'])); ?></td>
                            <td>€<?php echo number_format($r['valor_total'], 2, ',', '.'); ?></td>
                            <td><span class="badge badge-<?php echo $r['status']; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                            <td>
                                <?php if ($r['status'] === 'pendente'): ?>

                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Confirmar?');">
                                        <input type="hidden" name="acao" value="confirmar">
                                        <input type="hidden" name="reserva_id" value="<?php echo $r['id']; ?>">
                                        <button class="btn btn-sm btn-success"><i class="fas fa-check"></i></button>
                                    </form>
                                <?php endif; ?>
                                <?php if (in_array($r['status'], ['pendente', 'confirmada'])): ?>

                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Cancelar?');">
                                        <input type="hidden" name="acao" value="cancelar">
                                        <input type="hidden" name="reserva_id" value="<?php echo $r['id']; ?>">
                                        <button class="btn btn-sm btn-danger"><i class="fas fa-times"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <?php if ($total === 0): ?>
                <div class="empty-state"><i class="fas fa-calendar-times"></i>
                    <h4>Nenhuma reserva</h4>
                </div>
            <?php endif; ?>

            <!-- Paginação -->
            <?php if ($total_paginas > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <?php if ($i == $pagina): ?><span class="current"><?php echo $i; ?></span>
                        <?php else: ?><a href="?pagina=<?php echo $i; ?>&estado=<?php echo $filtro_estado; ?>"><?php echo $i; ?></a><?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <?php include '../footer.php'; ?>

    <script src="../js/script.js"></script>
</body>

</html>