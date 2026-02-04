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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_settings') {
        $notificacoes_email = isset($_POST['notificacoes_email']) ? 1 : 0;
        $notificacoes_sms = isset($_POST['notificacoes_sms']) ? 1 : 0;
        $idioma = $_POST['idioma'] ?? 'pt';
        $moeda = $_POST['moeda'] ?? 'EUR';

        // Verificar se já existem configurações
        $stmt = $conn->prepare("SELECT id FROM configuracoes_usuario WHERE utilizador_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Atualizar
            $stmt = $conn->prepare("UPDATE configuracoes_usuario SET notificacoes_email = ?, notificacoes_sms = ?, idioma = ?, moeda = ? WHERE utilizador_id = ?");
            $stmt->bind_param("iissi", $notificacoes_email, $notificacoes_sms, $idioma, $moeda, $user_id);
        } else {
            // Inserir
            $stmt = $conn->prepare("INSERT INTO configuracoes_usuario (utilizador_id, notificacoes_email, notificacoes_sms, idioma, moeda) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iissi", $user_id, $notificacoes_email, $notificacoes_sms, $idioma, $moeda);
        }

        if ($stmt->execute()) {
            $success = 'Configurações atualizadas com sucesso!';
        } else {
            $error = 'Erro ao atualizar configurações.';
        }
    }
}

// Obter configurações atuais
$stmt = $conn->prepare("SELECT * FROM configuracoes_usuario WHERE utilizador_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$config = $result->fetch_assoc();

if (!$config) {
    $config = [
        'notificacoes_email' => 1,
        'notificacoes_sms' => 0,
        'idioma' => 'pt',
        'moeda' => 'EUR'
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlugaTorres | Configurações</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style/style.css">
    <link rel="website icon" type="png" href="style/img/Logo_AlugaTorres_branco.png">
</head>

<body>
    <?php include 'header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <div class="form-container">
        <div class="form-header">
            <h1 class="form-title">Configurações</h1>
            <p class="form-subtitle">Personalize suas preferências e configurações da conta</p>
        </div>

        <?php if ($error): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="message success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="casa-form">
            <input type="hidden" name="action" value="update_settings">

            <!-- Seção: Notificações -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-bell"></i> Notificações</h2>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="notificacoes_email" name="notificacoes_email"
                            <?php echo $config['notificacoes_email'] ? 'checked' : ''; ?>>
                        <label for="notificacoes_email">Receber notificações por e-mail</label>
                    </div>
                    <small class="form-help">Receba atualizações sobre reservas, mensagens e promoções</small>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="notificacoes_sms" name="notificacoes_sms"
                            <?php echo $config['notificacoes_sms'] ? 'checked' : ''; ?>>
                        <label for="notificacoes_sms">Receber notificações por SMS</label>
                    </div>
                    <small class="form-help">Receba lembretes importantes por mensagem de texto</small>
                </div>
            </div>

            <!-- Seção: Preferências -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-globe"></i> Preferências</h2>

                <div class="form-group">
                    <label>Idioma</label>
                    <select name="idioma" class="form-control">
                        <option value="pt" <?php echo $config['idioma'] === 'pt' ? 'selected' : ''; ?>>Português</option>
                        <option value="en" <?php echo $config['idioma'] === 'en' ? 'selected' : ''; ?>>English</option>
                        <option value="es" <?php echo $config['idioma'] === 'es' ? 'selected' : ''; ?>>Español</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Moeda</label>
                    <select name="moeda" class="form-control">
                        <option value="EUR" <?php echo $config['moeda'] === 'EUR' ? 'selected' : ''; ?>>Euro (€)</option>
                        <option value="USD" <?php echo $config['moeda'] === 'USD' ? 'selected' : ''; ?>>Dólar ($)</option>
                        <option value="GBP" <?php echo $config['moeda'] === 'GBP' ? 'selected' : ''; ?>>Libra (£)</option>
                    </select>
                </div>
            </div>

            <!-- Botões -->
            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Salvar Configurações
                </button>
                <a href="dashboard.php" class="btn-cancel">
                    <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
                </a>
            </div>
        </form>
    </div>

    <script src="backend/script.js"></script>

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