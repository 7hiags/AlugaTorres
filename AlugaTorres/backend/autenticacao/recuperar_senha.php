<?php

require_once __DIR__ . '/../../root/init.php';
require_once __DIR__ . '/../email_defin/email_utils.php';

$pageTitle = 'AlugaTorres | Recuperar Passe';

// Processar formulário de solicitação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Por favor, insira o seu email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido.';
    } else {
        // Verificar se o email existe na base de dados
        $stmt = $conn->prepare("SELECT id, utilizador FROM utilizadores WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Gerar código de 6 dígitos
            $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Gerar token único
            $token = bin2hex(random_bytes(32));

            // Inserir código na base de dados
            $stmt = $conn->prepare("INSERT INTO password_resets (user_id, email, token, code, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("isss", $user['id'], $email, $token, $codigo);

            if ($stmt->execute()) {
                // Enviar email com o código
                $subject = 'Código de Recuperação de Palavra-Passe - AlugaTorres';
                $body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <div style='background: linear-gradient(to right, #038e01, #00d85e); padding: 20px; border-radius: 10px 10px 0 0;'>
                        <h1 style='color: white; margin: 0; text-align: center;'>AlugaTorres</h1>
                    </div>
                    <div style='background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px;'>
                        <h2 style='color: #333; margin-top: 0;'>Recuperação de Palavra-Passe</h2>
                        <p style='color: #666; line-height: 1.6;'>Olá <strong>" . htmlspecialchars($user['utilizador']) . "</strong>,</p>
                        <p style='color: #666; line-height: 1.6;'>Recebemos um pedido para recuperar a sua palavra-passe. Utilize o seguinte código:</p>
                        <div style='background: white; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0; border: 2px solid #038e01;'>
                            <span style='font-size: 32px; font-weight: bold; color: #038e01; letter-spacing: 8px;'>" . $codigo . "</span>
                        </div>
                        <p style='color: #666; line-height: 1.6;'>Este código é válido por <strong>30 minutos</strong>.</p>
                        <p style='color: #999; font-size: 14px; line-height: 1.6;'>Se não solicitou esta recuperação, por favor ignore este email.</p>
                        <hr style='border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;'>
                        <p style='color: #999; font-size: 12px; text-align: center;'>© " . date('Y') . " AlugaTorres - Todos os direitos reservados</p>
                    </div>
                </div>
                ";

                $result = sendEmail($email, $subject, $body);

                if ($result['ok']) {
                    // Redirecionar para página de verificação com o token
                    header("Location: verificar_codigo.php?token=" . $token);
                    exit;
                } else {
                    $error = 'Erro ao enviar email. Por favor, tente novamente mais tarde.';
                }
            } else {
                $error = 'Erro ao processar pedido. Por favor, tente novamente.';
            }
        } else {
            // Redirecionar para uma página de "email enviado" mesmo assim
            header("Location: verificar_codigo.php?sent=1");
            exit;
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
        <h2><i class="fas fa-key"></i> Recuperar Palavra-Passe</h2>

        <?php if (isset($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="register-info">
            <h3><i class="fas fa-info-circle"></i> Como funciona</h3>
            <p>Introduza o seu email e enviaremos um código de verificação de 6 dígitos. Utilize esse código para criar uma nova palavra-passe.</p>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email:</label>
                <input type="email" name="email" required placeholder="Seu email de registo"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <button type="submit">
                <i class="fas fa-paper-plane"></i> Enviar Código
            </button>
        </form>

        <p style="text-align: center; margin-top: 20px;">
            <a href="login.php">
                <i class="fas fa-arrow-left"></i> Voltar ao Login
            </a>
        </p>
    </section>

    <?php include '../../root/footer.php'; ?>
</body>

</html>