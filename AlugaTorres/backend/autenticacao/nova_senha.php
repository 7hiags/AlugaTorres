<?php

require_once __DIR__ . '/../../root/init.php';
require_once __DIR__ . '/../email_defin/email_utils.php';

$pageTitle = 'Nova Palavra-Passe - AlugaTorres';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Verificar se o token é válido antes de processar
if (empty($token)) {
    header("Location: recuperar_senha.php");
    exit;
}

// Verificar token na base de dados
$stmt = $conn->prepare("
    SELECT pr.id, pr.user_id, pr.email, pr.used, u.utilizador 
    FROM password_resets pr 
    JOIN utilizadores u ON pr.user_id = u.id 
    WHERE pr.token = ? AND pr.used = 0 AND pr.created_at > NOW() - INTERVAL 30 MINUTE
");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $error = 'Link expirado ou inválido. Por favor, solicite um novo código.';
}

// Processar definição de nova senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    if (strlen($nova_senha) < 8) {
        $error = 'A palavra-passe deve ter pelo menos 6 caracteres.';
    } elseif ($nova_senha !== $confirmar_senha) {
        $error = 'As palavras-passe não coincidem.';
    } else {
        // Obter user_id do token
        $reset = $result->fetch_assoc();
        $user_id = $reset['user_id'];

        $hash_nova = password_hash($nova_senha, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE utilizadores SET palavrapasse_hash = ? WHERE id = ?");
        $stmt->bind_param("si", $hash_nova, $user_id);

        if ($stmt->execute()) {
            // Marcar código como utilizado
            $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();

            // Enviar email de confirmação de alteração de palavra-passe
            $email = $reset['email'];
            $nome_utilizador = $reset['utilizador'];

            $subject = 'Palavra-Passe Alterada com Sucesso - AlugaTorres';
            $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background: linear-gradient(to right, #038e01, #00d85e); padding: 20px; border-radius: 10px 10px 0 0;'>
                    <h1 style='color: white; margin: 0; text-align: center;'>AlugaTorres</h1>
                </div>
                <div style='background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px;'>
                    <h2 style='color: #333; margin-top: 0;'>Palavra-Passe Alterada</h2>
                    <p style='color: #666; line-height: 1.6;'>Olá <strong>" . htmlspecialchars($nome_utilizador) . "</strong>,</p>
                    <p style='color: #666; line-height: 1.6;'>A sua palavra-passe foi alterada com sucesso!</p>
                    <div style='background: white; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #038e01;'>
                        <p style='color: #666; margin: 0;'><i class='fas fa-check-circle'></i> Se foi você quem alterou a palavra-passe, pode ignorar esta mensagem.</p>
                    </div>
                    <p style='color: #d9534f; line-height: 1.6;'><strong>Se não foi você quem alterou a palavra-passe</strong>, contacte-nos imediatamente em <a href='mailto:suportealugatorres@gmail.com'>suportealugatorres@gmail.com</a> para proteger a sua conta.</p>
                    <hr style='border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;'>
                    <p style='color: #999; font-size: 12px; text-align: center;'>© " . date('Y') . " AlugaTorres - Todos os direitos reservados</p>
                </div>
            </div>
            ";

            sendEmail($email, $subject, $body);

            // Encerrar sessão atual para forçar login com nova palavra-passe
            session_destroy();
            session_start();
            $_SESSION['password_changed'] = true;
            header("Location: login.php");
            exit;
        } else {
            $error = 'Erro ao atualizar palavra-passe. Por favor, tente novamente.';
        }
    }
}
?>

<?php include __DIR__ . '/../../root/head.php'; 
    include '../../root/header.php';
    include '../../root/sidebar.php';
?>

<body>
    <section class="login">
        <h2><i class="fas fa-lock"></i> Nova Palavra-Passe</h2>

        <?php if ($success): ?>
            <div class="message success" style="max-width: 100%; margin: 20px 0;">
                <i class="fas fa-check-circle"></i>
                <p style="font-size: 1.1em; margin: 10px 0;"><strong>Palavra-passe alterada com sucesso!</strong></p>
                <p>Agora pode fazer login com a sua nova palavra-passe.</p>
            </div>
            <p style="text-align: center;">
                <a href="login.php" class="btn-save" style="text-decoration: none;">
                    <i class="fas fa-sign-in-alt"></i> Ir para Login
                </a>
            </p>
        <?php elseif ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <p style="text-align: center; margin-top: 20px;">
                <a href="recuperar_senha.php">
                    <i class="fas fa-redo"></i> Solicitar novo código
                </a>
            </p>
        <?php else: ?>
            <div class="register-info">
                <h3><i class="fas fa-key"></i> Defina a Nova Palavra-Passe</h3>
                <p>Crie uma nova palavra-passe segura para a sua conta.</p>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Nova Palavra-Passe:</label>
                    <input type="password" name="nova_senha" required minlength="6"
                        placeholder="Mínimo 8 caracteres">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Confirmar Palavra-Passe:</label>
                    <input type="password" name="confirmar_senha" required minlength="6"
                        placeholder="Repita a palavra-passe">
                </div>

                <button type="submit">
                    <i class="fas fa-save"></i> Guardar Palavra-Passe
                </button>
            </form>

            <p style="text-align: center; margin-top: 20px;">
                <a href="login.php">
                    <i class="fas fa-arrow-left"></i> Cancelar e Voltar ao Login
                </a>
            </p>
        <?php endif; ?>
    </section>

    <?php include '../../root/footer.php'; ?>
</body>

</html>