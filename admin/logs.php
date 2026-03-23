<?php
require_once __DIR__ . '/../backend/check_admin.php';

// Limpar logs antigos
if (isset($_POST['limpar_logs'])) {
    $dias = intval($_POST['dias'] ?? 30);
    $data_limite = date('Y-m-d H:i:s', strtotime("-$dias days"));

    $stmt = $conn->prepare("DELETE FROM admin_logs WHERE created_at < ?");
    $stmt->bind_param("s", $data_limite);
    $stmt->execute();
    $db_removidos = $stmt->affected_rows ?? 0;
    $stmt->close();

    // Também limpar arquivo
    $log_file = __DIR__ . 'admin_activity.log';
    if (file_exists($log_file)) {
        $logs = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $novos_logs = [];
        $data_cutoff = strtotime($data_limite);
        $arquivo_removidos = 0;
        foreach ($logs as $log) {
            if (preg_match('/\[(.*?)\]/', $log, $matches)) {
                $log_date = strtotime($matches[1]);
                if ($log_date === false || $log_date < $data_cutoff) {
                    $arquivo_removidos++;
                } else {
                    $novos_logs[] = $log;
                }
            } else {
                $novos_logs[] = $log;
            }
        }
        file_put_contents($log_file, implode("\n", $novos_logs) . "\n");
    }

    logAdminActivity('Limpar Logs', "DB:$db_removidos File:$arquivo_removidos ($dias dias)");
    $mensagem = "Removidos DB:$db_removidos Arquivo:$arquivo_removidos";
}

// Limpar TODOS
if (isset($_POST['limpar_todos'])) {
    $conn->query("TRUNCATE TABLE admin_logs");
    $log_file = __DIR__ . 'admin_activity.log';
    file_put_contents($log_file, '');
    logAdminActivity('Limpar TODOS Logs');
    $mensagem = 'TODOS logs removidos!';
}

// Buscar logs da base de dados
$logs_db = [];
try {
    $result = $conn->query("SELECT l.*, u.utilizador as admin_nome 
                           FROM admin_logs l 
                           LEFT JOIN utilizadores u ON l.admin_id = u.id 
                           ORDER BY l.created_at DESC 
                           LIMIT 100");
    while ($row = $result->fetch_assoc()) {
        $logs_db[] = $row;
    }
} catch (\Exception $e) {
    // Tabela pode não existir ainda
}

// Buscar logs do arquivo
$logs_arquivo = [];
$log_file = __DIR__ . 'admin_activity.log';
if (file_exists($log_file)) {
    $logs_arquivo = array_slice(file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -100);
    $logs_arquivo = array_reverse($logs_arquivo);
}

logAdminActivity('Acesso aos Logs');
?>

<?php
$pageTitle = 'AlugaTorres | Logs de Atividade';
$extraHead = '<link rel="stylesheet" href="' . BASE_URL . 'assets/style/admin_style.css">
<script src="' . BASE_URL . 'assets/js/notifications.js"></script>';

require_once __DIR__ . '/../root/head.php';
include '../root/header.php';
include '../root/sidebar.php';
?>

<body>
    <main class="admin-main">
        <div class="page-header">
            <h2><i class="fas fa-history"></i> Logs de Atividade</h2>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>

        <?php if (isset($mensagem)): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    AlugaTorresNotifications.success(<?php echo json_encode($mensagem); ?>);
                });
            </script>
        <?php endif; ?>


        <!-- Limpar Logs -->
        <div class="admin-card" style="margin-bottom: 30px;">
            <h3><i class="fas fa-broom"></i> Limpar Logs Antigos</h3>
            <form method="POST" style="display: flex; gap: 15px; align-items: flex-end;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Remover logs mais antigos que:</label>
                    <select name="dias" class="form-control">
                        <option value="7">7</option>
                        <option value="30" selected>30</option>
                        <option value="90">90</option>
                        <option value="180">180</option>
                        <option value="365">365</option>
                    </select>
                </div>
                <button type="submit" name="limpar_logs" class="btn btn-warning" onclick="return confirm('Limpar antigos?');">
                    <i class="fas fa-trash"></i> Limpar Antigos
                </button>
            </form>
            <form method="POST" onsubmit="return confirm('LIMPAR TODOS?');">
                <button type="submit" name="limpar_todos" class="btn btn-danger">
                    <i class="fas fa-broom"></i> LIMPAR TODOS
                </button>
            </form>
        </div>

        <!-- Logs da Base de Dados -->
        <?php if (!empty($logs_db)): ?>
            <div class="admin-card" style="margin-bottom: 30px;">
                <h3><i class="fas fa-database"></i> Logs da Base de Dados</h3>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Admin</th>
                            <th>Ação</th>
                            <th>Detalhes</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs_db as $log): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($log['admin_nome'] ?? 'Sistema'); ?> (#<?php echo $log['admin_id']; ?>)</td>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars($log['acao']); ?></span></td>
                                <td><?php echo htmlspecialchars($log['detalhes'] ?? '-'); ?></td>
                                <td><small><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Logs do Arquivo -->
        <div class="admin-card">
            <h3><i class="fas fa-file-alt"></i> Logs do Sistema (Arquivo)</h3>
            <?php if (empty($logs_arquivo)): ?>
                <div class="empty-state">
                    <i class="fas fa-file"></i>
                    <h4>Nenhum log encontrado</h4>
                </div>
            <?php else: ?>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; font-family: monospace; font-size: 0.85rem; max-height: 500px; overflow-y: auto;">
                    <?php foreach ($logs_arquivo as $log): ?>
                        <div style="padding: 5px 0; border-bottom: 1px solid #e0e0e0;">
                            <?php echo htmlspecialchars($log); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../root/footer.php'; ?>


</body>

</html>