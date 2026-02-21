<?php
require_once __DIR__ . '/../backend/check_admin.php';

// Processar ações
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        switch ($_POST['acao']) {
            case 'eliminar':
                $user_id = intval($_POST['user_id']);
                if ($user_id !== $admin_id) { // Não permitir auto-eliminação
                    $stmt = $conn->prepare("DELETE FROM utilizadores WHERE id = ? AND tipo_utilizador != 'admin'");
                    $stmt->bind_param("i", $user_id);
                    if ($stmt->execute()) {
                        logAdminActivity('Eliminar Utilizador', 'ID: ' . $user_id);
                        $mensagem = 'Utilizador eliminado com sucesso!';
                        $tipo_mensagem = 'success';
                    } else {
                        $mensagem = 'Erro ao eliminar utilizador.';
                        $tipo_mensagem = 'danger';
                    }
                } else {
                    $mensagem = 'Não pode eliminar a sua própria conta.';
                    $tipo_mensagem = 'warning';
                }
                break;

            case 'alterar_tipo':
                $user_id = intval($_POST['user_id']);
                $novo_tipo = $_POST['novo_tipo'];
                $tipos_permitidos = ['proprietario', 'arrendatario', 'admin'];

                if (in_array($novo_tipo, $tipos_permitidos) && $user_id !== $admin_id) {
                    $stmt = $conn->prepare("UPDATE utilizadores SET tipo_utilizador = ? WHERE id = ?");
                    $stmt->bind_param("si", $novo_tipo, $user_id);
                    if ($stmt->execute()) {
                        logAdminActivity('Alterar Tipo Utilizador', 'ID: ' . $user_id . ' -> ' . $novo_tipo);
                        $mensagem = 'Tipo de utilizador alterado com sucesso!';
                        $tipo_mensagem = 'success';
                    }
                }
                break;

            case 'banir':
                $user_id = intval($_POST['user_id']);
                if ($user_id !== $admin_id) {
                    $stmt = $conn->prepare("UPDATE utilizadores SET ativo = 0 WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    if ($stmt->execute()) {
                        logAdminActivity('Banir Utilizador', 'ID: ' . $user_id);
                        $mensagem = 'Utilizador banido com sucesso!';
                        $tipo_mensagem = 'success';
                    }
                }
                break;

            case 'desbanir':
                $user_id = intval($_POST['user_id']);
                $stmt = $conn->prepare("UPDATE utilizadores SET ativo = 1 WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    logAdminActivity('Desbanir Utilizador', 'ID: ' . $user_id);
                    $mensagem = 'Utilizador desbanido com sucesso!';
                    $tipo_mensagem = 'success';
                }
                break;
        }
    }
}

// Filtros
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';
$search = $_GET['search'] ?? '';

// Construir query
$where = [];
$params = [];
$types = '';

if ($filtro_tipo) {
    $where[] = "tipo_utilizador = ?";
    $params[] = $filtro_tipo;
    $types .= 's';
}

if ($filtro_estado === 'ativo') {
    $where[] = "(ativo = 1 OR ativo IS NULL)";
} elseif ($filtro_estado === 'banido') {
    $where[] = "ativo = 0";
}

if ($search) {
    $where[] = "(utilizador LIKE ? OR email LIKE ? OR telefone LIKE ?)";
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
$count_sql = "SELECT COUNT(*) as total FROM utilizadores $where_clause";
$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_result = $stmt->get_result()->fetch_assoc();
$total_utilizadores = $total_result['total'];
$total_paginas = ceil($total_utilizadores / $por_pagina);

// Buscar utilizadores
$sql = "SELECT * FROM utilizadores $where_clause ORDER BY data_registro DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

// Adicionar parâmetros de paginação
$params[] = $por_pagina;
$params[] = $offset;
$types .= 'ii';

$stmt->bind_param($types, ...$params);
$stmt->execute();
$utilizadores = $stmt->get_result();

// Estatísticas
$stats = [];
$result = $conn->query("SELECT COUNT(*) as total FROM utilizadores WHERE tipo_utilizador = 'proprietario'");
$stats['proprietarios'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM utilizadores WHERE tipo_utilizador = 'arrendatario'");
$stats['arrendatarios'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM utilizadores WHERE tipo_utilizador = 'admin'");
$stats['admins'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM utilizadores WHERE ativo = 0");
$stats['banidos'] = $result->fetch_assoc()['total'];

logAdminActivity('Acesso à Gestão de Utilizadores');
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlugaTorres | Gerir Utilizadores</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="website icon" type="png" href="../style/img/Logo_AlugaTorres_branco.png">
</head>

<body>
    <?php include '../header.php'; ?>
    <?php include '../sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-header">
            <h2><i class="fas fa-users-cog"></i> Gerir Utilizadores</h2>
            <div class="page-actions">
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>
        </div>

        <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $tipo_mensagem; ?>">
                <i class="fas fa-<?php echo $tipo_mensagem === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="stats-row">
            <div class="stat-detail-card">
                <h4><i class="fas fa-users"></i> Total</h4>
                <p class="big-number"><?php echo number_format($total_utilizadores); ?></p>
            </div>
            <div class="stat-detail-card">
                <h4><i class="fas fa-user-tie"></i> Proprietários</h4>
                <p class="big-number"><?php echo number_format($stats['proprietarios']); ?></p>
            </div>
            <div class="stat-detail-card">
                <h4><i class="fas fa-user"></i> Arrendatários</h4>
                <p class="big-number"><?php echo number_format($stats['arrendatarios']); ?></p>
            </div>
            <div class="stat-detail-card">
                <h4><i class="fas fa-shield-alt"></i> Admins</h4>
                <p class="big-number"><?php echo number_format($stats['admins']); ?></p>
            </div>
            <div class="stat-detail-card">
                <h4><i class="fas fa-ban"></i> Banidos</h4>
                <p class="big-number"><?php echo number_format($stats['banidos']); ?></p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-section">
            <form class="filters-form" method="GET">
                <div class="form-group search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" class="form-control" placeholder="Pesquisar por nome, email..." value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <div class="form-group">
                    <select name="tipo" class="form-control">
                        <option value="">Todos os tipos</option>
                        <option value="proprietario" <?php echo $filtro_tipo === 'proprietario' ? 'selected' : ''; ?>>Proprietários</option>
                        <option value="arrendatario" <?php echo $filtro_tipo === 'arrendatario' ? 'selected' : ''; ?>>Arrendatários</option>
                        <option value="admin" <?php echo $filtro_tipo === 'admin' ? 'selected' : ''; ?>>Administradores</option>
                    </select>
                </div>

                <div class="form-group">
                    <select name="estado" class="form-control">
                        <option value="">Todos os estados</option>
                        <option value="ativo" <?php echo $filtro_estado === 'ativo' ? 'selected' : ''; ?>>Ativos</option>
                        <option value="banido" <?php echo $filtro_estado === 'banido' ? 'selected' : ''; ?>>Banidos</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="utilizadores.php" class="btn btn-secondary"><i class="fas fa-times"></i> Limpar</a>
            </form>
        </div>

        <!-- Tabela de Utilizadores -->
        <div class="admin-card">
            <div class="export-buttons">
                <button class="export-btn" onclick="exportTableToCSV('utilizadores.csv')">
                    <i class="fas fa-download"></i> Exportar CSV
                </button>
            </div>

            <table class="admin-table" id="utilizadoresTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Registo</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $utilizadores->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['utilizador']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['telefone'] ?? '-'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['tipo_utilizador']; ?>">
                                    <?php echo ucfirst($user['tipo_utilizador']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (isset($user['ativo']) && $user['ativo'] == 0): ?>
                                    <span class="status-indicator">
                                        <span class="status-dot inactive"></span> Banido
                                    </span>
                                <?php else: ?>
                                    <span class="status-indicator">
                                        <span class="status-dot active"></span> Ativo
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($user['data_registro'])); ?></td>

                            <td>
                                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                    <?php if ($user['id'] !== $admin_id): ?>

                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza?');">
                                            <input type="hidden" name="acao" value="alterar_tipo">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <select name="novo_tipo" onchange="this.form.submit()" class="form-control" style="width: auto; display: inline; padding: 5px;">
                                                <option value="">Alterar tipo...</option>
                                                <option value="proprietario">Proprietário</option>
                                                <option value="arrendatario">Arrendatário</option>
                                                <option value="admin">Admin</option>
                                            </select>
                                        </form>

                                        <?php if (isset($user['ativo']) && $user['ativo'] == 0): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Desbanir este utilizador?');">
                                                <input type="hidden" name="acao" value="desbanir">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Banir este utilizador?');">
                                                <input type="hidden" name="acao" value="banir">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="POST" style="display: inline;" onsubmit="return confirm('ELIMINAR permanentemente?');">
                                            <input type="hidden" name="acao" value="eliminar">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <?php if ($total_utilizadores === 0): ?>
                <div class="empty-state">
                    <i class="fas fa-users-slash"></i>
                    <h4>Nenhum utilizador encontrado</h4>
                    <p>Tente ajustar os filtros de pesquisa</p>
                </div>
            <?php endif; ?>

            <!-- Paginação -->
            <?php if ($total_paginas > 1): ?>
                <div class="pagination">
                    <?php if ($pagina > 1): ?>
                        <a href="?pagina=<?php echo $pagina - 1; ?>&tipo=<?php echo $filtro_tipo; ?>&estado=<?php echo $filtro_estado; ?>&search=<?php echo urlencode($search); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $pagina - 2); $i <= min($total_paginas, $pagina + 2); $i++): ?>
                        <?php if ($i == $pagina): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?pagina=<?php echo $i; ?>&tipo=<?php echo $filtro_tipo; ?>&estado=<?php echo $filtro_estado; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($pagina < $total_paginas): ?>
                        <a href="?pagina=<?php echo $pagina + 1; ?>&tipo=<?php echo $filtro_tipo; ?>&estado=<?php echo $filtro_estado; ?>&search=<?php echo urlencode($search); ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal de Detalhes -->
    <div class="modal-overlay" id="modalDetalhes">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-user"></i> Detalhes do Utilizador</h3>
                <button class="modal-close" onclick="fecharModal()">&times;</button>
            </div>
            <div id="modalContent">
                <div class="spinner"></div>
            </div>
        </div>
    </div>

    <?php include '../footer.php'; ?>

    <script src="../js/script.js"></script>

</body>

</html>