<?php
require_once __DIR__ . '/../backend/check_admin.php';

$mensagem = '';
$tipo_mensagem = '';

// Função para carregar configurações do .env
function loadEnvConfig($filePath)
{
    $config = [];
    if (!file_exists($filePath)) {
        return $config;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $config[trim($key)] = trim($value);
        }
    }
    return $config;
}

// Guardar configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $env_file = __DIR__ . '/../backend/.env';

    if (isset($_POST['acao']) && $_POST['acao'] === 'email') {
        // Construir conteúdo do .env
        $env_content = "# Configurações de Email - AlugaTorres\n";
        $env_content .= "# Gerado automaticamente pelo painel de admin\n\n";

        $env_content .= "SMTP_HOST=" . ($_POST['smtp_host'] ?? '') . "\n";
        $env_content .= "SMTP_PORT=" . (intval($_POST['smtp_port'] ?? 587)) . "\n";
        $env_content .= "SMTP_USER=" . ($_POST['smtp_user'] ?? '') . "\n";
        $env_content .= "SMTP_PASS=" . ($_POST['smtp_pass'] ?? '') . "\n";
        $env_content .= "FROM_EMAIL=" . ($_POST['from_email'] ?? 'no-reply@alugatorres.local') . "\n";
        $env_content .= "FROM_NAME=" . ($_POST['from_name'] ?? 'AlugaTorres') . "\n";
        $env_content .= "SUPPORT_EMAIL=" . ($_POST['support_email'] ?? 'suportealugatorres@gmail.com') . "\n";
        $env_content .= "NEWSLETTER_EMAIL=" . ($_POST['newsletter_email'] ?? 'alugatorrespt@gmail.com') . "\n";
        $env_content .= "ADMIN_EMAIL=" . ($_POST['admin_email'] ?? 'admin@alugatorres.pt') . "\n";
        $env_content .= "MAILER=" . ($_POST['mailer'] ?? 'mail') . "\n";

        file_put_contents($env_file, $env_content);

        logAdminActivity('Atualizar Configurações', 'Email');
        $mensagem = 'Configurações de email atualizadas! (Ficheiro .env atualizado)';
        $tipo_mensagem = 'success';
    }
}

// Carregar configurações atuais do .env
$envConfig = loadEnvConfig(__DIR__ . '/../backend/.env');

// Valores padrão
$config = [
    'smtp_host' => $envConfig['SMTP_HOST'] ?? '',
    'smtp_port' => $envConfig['SMTP_PORT'] ?? 587,
    'smtp_user' => $envConfig['SMTP_USER'] ?? '',
    'smtp_pass' => $envConfig['SMTP_PASS'] ?? '',
    'from_email' => $envConfig['FROM_EMAIL'] ?? 'no-reply@alugatorres.local',
    'from_name' => $envConfig['FROM_NAME'] ?? 'AlugaTorres',
    'support_email' => $envConfig['SUPPORT_EMAIL'] ?? 'suportealugatorres@gmail.com',
    'newsletter_email' => $envConfig['NEWSLETTER_EMAIL'] ?? 'alugatorrespt@gmail.com',
    'admin_email' => $envConfig['ADMIN_EMAIL'] ?? 'admin@alugatorres.pt',
    'mailer' => $envConfig['MAILER'] ?? 'mail'
];

logAdminActivity('Acesso às Configurações');
?>

<?php
$pageTitle = 'AlugaTorres | Configurações';
$extraHead = '<link rel="stylesheet" href="' . BASE_URL . 'assets/style/admin_style.css">
  <script src="' . BASE_URL . 'assets/js/notifications.js"></script>';

require_once __DIR__ . '/../root/head.php';
include '../root/header.php';
include '../root/sidebar.php';
?>

<body>
    <main class="admin-main">
        <div class="page-header">
            <h2><i class="fas fa-cogs"></i> Definições do Sistema</h2>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>

        <?php if ($mensagem): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    <?php
                    $toastType = $tipo_mensagem === 'success' ? 'success' : ($tipo_mensagem === 'danger' ? 'error' : ($tipo_mensagem === 'warning' ? 'warning' : 'info'));
                    echo "AlugaTorresNotifications.{$toastType}(" . json_encode($mensagem) . ");";
                    ?>
                });
            </script>
        <?php endif; ?>


        <!-- Configurações de Email -->
        <div class="admin-card" style="margin-bottom: 30px;">
            <h3><i class="fas fa-envelope"></i> Definições de Email</h3>
            <form method="POST">
                <input type="hidden" name="acao" value="email">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label>Email de Suporte (formulário de contactos)</label>
                        <input type="email" name="support_email" class="form-control" value="<?php echo htmlspecialchars($config['support_email'] ?? 'suportealugatorres@gmail.com'); ?>">
                        <small style="color: #666;">Recebe as mensagens do formulário de contactos</small>
                    </div>
                    <div class="form-group">
                        <label>Email para Newsletters</label>
                        <input type="email" name="newsletter_email" class="form-control" value="<?php echo htmlspecialchars($config['newsletter_email'] ?? 'alugatorrespt@gmail.com'); ?>">
                        <small style="color: #666;">Envio de newsletters e notificações</small>
                    </div>
                    <div class="form-group">
                        <label>Email do Remetente</label>
                        <input type="email" name="from_email" class="form-control" value="<?php echo htmlspecialchars($config['from_email'] ?? 'no-reply@alugatorres.local'); ?>">
                    </div>
                    <div class="form-group">
                        <label>SMTP Host</label>
                        <input type="text" name="smtp_host" class="form-control" value="<?php echo htmlspecialchars($config['smtp_host'] ?? ''); ?>" placeholder="smtp.gmail.com">
                    </div>
                    <div class="form-group">
                        <label>SMTP Porta</label>
                        <input type="number" name="smtp_port" class="form-control" value="<?php echo htmlspecialchars($config['smtp_port'] ?? 587); ?>">
                    </div>
                    <div class="form-group">
                        <label>SMTP Utilizador</label>
                        <input type="text" name="smtp_user" class="form-control" value="<?php echo htmlspecialchars($config['smtp_user'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>SMTP Password</label>
                        <input type="password" name="smtp_pass" class="form-control" value="<?php echo htmlspecialchars($config['smtp_pass'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Email do Admin (notificações internas)</label>
                        <input type="email" name="admin_email" class="form-control" value="<?php echo htmlspecialchars($config['admin_email'] ?? 'admin@alugatorres.pt'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Nome do Remetente</label>
                        <input type="text" name="from_name" class="form-control" value="<?php echo htmlspecialchars($config['from_name'] ?? 'AlugaTorres'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Tipo de Envio</label>
                        <select name="mailer" class="form-control">
                            <option value="mail" <?php echo ($config['mailer'] ?? 'mail') === 'mail' ? 'selected' : ''; ?>>Mail() nativo</option>
                            <option value="smtp" <?php echo ($config['mailer'] ?? '') === 'smtp' ? 'selected' : ''; ?>>SMTP</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Definições</button>
            </form>
        </div>

        <!-- Informações do Sistema -->
        <div class="admin-card">
            <h3><i class="fas fa-info-circle"></i> Informações do Sistema</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <p><strong>Versão PHP:</strong> <?php echo phpversion(); ?></p>
                    <p><strong>Servidor:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></p>
                    <p><strong>Base de Dados:</strong> MySQL</p>
                </div>
                <div>
                    <p><strong>Diretório:</strong> <?php echo __DIR__; ?></p>
                    <p><strong>Data/Hora Servidor:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                    <p><strong>Timezone:</strong> <?php echo date_default_timezone_get(); ?></p>
                </div>
            </div>
        </div>
    </main>

    <?php include '../root/footer.php'; ?>
</body>

</html>