<?php
require_once __DIR__ . '/../backend/check_admin.php';

$mensagem = '';
$tipo_mensagem = '';

// Guardar configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config_file = __DIR__ . '/../backend/email_config.php';

    if (isset($_POST['acao']) && $_POST['acao'] === 'email') {
        $novo_config = [
            'smtp_host' => $_POST['smtp_host'] ?? '',
            'smtp_port' => intval($_POST['smtp_port'] ?? 587),
            'smtp_user' => $_POST['smtp_user'] ?? '',
            'smtp_pass' => $_POST['smtp_pass'] ?? '',
            'from_email' => $_POST['from_email'] ?? 'no-reply@alugatorres.local',
            'from_name' => $_POST['from_name'] ?? 'AlugaTorres',
            'support_email' => $_POST['support_email'] ?? 'suportealugatorres@gmail.com',
            'newsletter_email' => $_POST['newsletter_email'] ?? 'alugatorrespt@gmail.com',
            'admin_email' => $_POST['admin_email'] ?? 'admin@alugatorres.pt',
            'mailer' => $_POST['mailer'] ?? 'mail'
        ];

        // Guardar configurações (em produção, usar base de dados ou arquivo seguro)
        $config_content = "<?php\nreturn " . var_export($novo_config, true) . ";\n";
        file_put_contents($config_file, $config_content);

        logAdminActivity('Atualizar Configurações', 'Email');
        $mensagem = 'Configurações de email atualizadas!';
        $tipo_mensagem = 'success';
    }
}

// Carregar configurações atuais
$config = include __DIR__ . '/../backend/email_config.php';

logAdminActivity('Acesso às Configurações');
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>AlugaTorres | Configurações</title>
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
            <h2><i class="fas fa-cogs"></i> Configurações do Sistema</h2>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>

        <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $tipo_mensagem; ?>">
                <i class="fas fa-check-circle"></i> <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <!-- Configurações de Email -->
        <div class="admin-card" style="margin-bottom: 30px;">
            <h3><i class="fas fa-envelope"></i> Configurações de Email</h3>
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

                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Configurações</button>
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

    <?php include '../footer.php'; ?>
    <script src="../js/script.js"></script>
</body>

</html>