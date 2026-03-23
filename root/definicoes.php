<?php
require_once __DIR__ . '/init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../backend/login.php");
    exit;
}

// Verificar se o usuário ainda existe na base de dados
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
$tipo_utilizador = $_SESSION['tipo_utilizador'] ?? 'arrendatario';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_notifications') {
        $email_notif = isset($_POST['email_notif']) ? 1 : 0;
        $sms_notif = isset($_POST['sms_notif']) ? 1 : 0;
        $promo_notif = isset($_POST['promo_notif']) ? 1 : 0;
        $lingua = $_POST['lingua'] ?? 'portuguese';

        $stmt = $conn->prepare("
            INSERT INTO definicoes_utilizador (utilizador_id, email_notificacoes, sms_notificacoes, promocoes, lingua) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            email_notificacoes = VALUES(email_notificacoes),
            sms_notificacoes = VALUES(sms_notificacoes),
            promocoes = VALUES(promocoes),
            lingua = VALUES(lingua)
        ");
        $stmt->bind_param("iiiis", $user_id, $email_notif, $sms_notif, $promo_notif, $lingua);

        if ($stmt->execute()) {
            notifySuccess('Definições atualizadas com sucesso!');
        } else {
            notifyError('Erro ao atualizar definições: ' . $conn->error);
        }
    }
}

// Obter definições atuais
$stmt = $conn->prepare("SELECT * FROM definicoes_utilizador WHERE utilizador_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$config = $stmt->get_result()->fetch_assoc();

// Valores padrão se não existir registo de definições para o utilizador
if (!$config) {
    $config = [
        'email_notificacoes' => 1,
        'sms_notificacoes' => 0,
        'promocoes' => 1,
        'lingua' => 'portuguese'
    ];
}

$pageTitle = 'AlugaTorres | Definições';
$metaDescription = 'Altere preferências e definições da conta';

    require_once __DIR__ . '/head.php';
    include 'header.php';
    include 'sidebar.php'; 
?>

<body data-lang="<?php echo $config['lingua'] ?? 'portuguese'; ?>">
    <div class="settings-container">
        <div class="settings-header">
            <h1 class="settings-title"><i class="fas fa-cog"></i> Definições</h1>
            <p class="settings-subtitle">Personalize as suas preferências e definições da conta</p>
        </div>

        <div class="settings-grid">
            <!-- Notificações -->
            <div class="settings-card">
                <h3><i class="fas fa-bell"></i> Notificações</h3>
                <form id="notifications-form" method="POST" action="definicoes.php">
                    <input type="hidden" name="action" value="update_notifications">

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="email_notif" <?php echo $config['email_notificacoes'] ? 'checked' : ''; ?>>
                            <span>Receber notificações por email</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="sms_notif" <?php echo $config['sms_notificacoes'] ? 'checked' : ''; ?>>
                            <span>Receber notificações por SMS</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="promo_notif" <?php echo $config['promocoes'] ? 'checked' : ''; ?>>
                            <span>Receber promoções e ofertas especiais</span>
                        </label>
                    </div>

                    <div class="form-group" style="margin-top: 20px;">
                        <label class="form-label"><i class="fas fa-globe"></i> Idioma</label>
                        <select name="lingua" id="lingua-select" class="form-input" disabled>
                            <option value="portuguese" selected>Português</option>
                        </select>
                        <small style="color: #666;">O site está disponível apenas em português</small>
                    </div>
                </form>
            </div>

            <!-- Segurança -->
            <div class="settings-card">
                <h3><i class="fas fa-shield-alt"></i> Segurança</h3>
                <div class="settings-links">
                    <a href="perfil.php#alterar-senha" class="settings-link">
                        <i class="fas fa-key"></i> Alterar Palavra-passe
                    </a>
                    <a href="perfil.php" class="settings-link">
                        <i class="fas fa-user-edit"></i> Editar Perfil
                    </a>
                    <a href="eliminar_conta.php" class="settings-link danger">
                        <i class="fas fa-trash-alt"></i> Eliminar Conta
                    </a>
                </div>
            </div>

            <!-- Informações da Conta -->
            <div class="settings-card">
                <h3><i class="fas fa-info-circle"></i> Informações da Conta</h3>
                <div class="account-info">
                    <p><strong>Utilizador:</strong> <?php echo htmlspecialchars($_SESSION['user'] ?? 'N/A'); ?></p>
                    <p><strong>Tipo de Utilizador:</strong> <?php echo ucfirst($tipo_utilizador); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email'] ?? 'N/A'); ?></p>
                </div>
            </div>
        </div>

        <div class="settings-actions">
            <button type="submit" form="notifications-form" class="btn-save" onclick="saveLanguagePreference()">
                <i class="fas fa-save"></i> Guardar Alterações
            </button>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        function saveLanguagePreference() {
            const select = document.getElementById('lingua-select');
            if (select) {
                const lang = select.value;
                sessionStorage.setItem('lingua', lang);
                document.body.setAttribute('data-lang', lang);
            }
        }
    </script>


</body>

</html>