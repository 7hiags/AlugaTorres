<?php

require_once __DIR__ . '/../../root/init.php';

$pageTitle = 'Verificar Código - AlugaTorres';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Processar verificação de código
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? '');
    $token = $_POST['token'] ?? '';

    if (empty($codigo) || strlen($codigo) !== 6 || !is_numeric($codigo)) {
        $error = 'Por favor, insira o código de 6 dígitos.';
    } elseif (empty($token)) {
        $error = 'Token inválido.';
    } else {
        // Verificar código na base de dados
        $stmt = $conn->prepare("
            SELECT pr.id, pr.user_id, pr.email, pr.used, u.utilizador 
            FROM password_resets pr 
            JOIN utilizadores u ON pr.user_id = u.id 
            WHERE pr.token = ? AND pr.code = ? AND pr.created_at > NOW() - INTERVAL 30 MINUTE
        ");
        $stmt->bind_param("ss", $token, $codigo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $reset = $result->fetch_assoc();

            if ($reset['used']) {
                $error = 'Este código já foi utilizado. Solicite um novo.';
            } else {
                // Código válido - redirecionar para definição de nova palavra-passe
                header("Location: nova_senha.php?token=" . $token);
                exit;
            }
        } else {
            $error = 'Código inválido ou expirado. Por favor, tente novamente.';
        }
    }
}

// Verificar se foi redirecionado sem token (email não encontrado)
$sent = $_GET['sent'] ?? 0;
?>

<?php include __DIR__ . '/../../root/head.php'; 
    include '../../root/header.php';
    include '../../root/sidebar.php';
?>

<body>
    <section class="login">
        <h2><i class="fas fa-shield-alt"></i> Verificar Código</h2>

        <?php if ($sent): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                <p>Se o email existir no nosso sistema, receberá um código de verificação em breve.</p>
                <p style="font-size: 0.9em; margin-top: 10px;">Verifique a sua caixa de spam se não receber o email em alguns minutos.</p>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($token)): ?>
            <div class="register-info">
                <h3><i class="fas fa-lock"></i> Código de Verificação</h3>
                <p>Introduza o código de 6 dígitos enviado para o seu email.</p>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="form-group">
                    <label><i class="fas fa-code"></i> Código:</label>
                    <input type="text" name="codigo" required placeholder="000000"
                        maxlength="6" pattern="[0-9]{6}"
                        style="text-align: center; letter-spacing: 8px; font-size: 24px; font-weight: bold;"
                        autocomplete="off">
                </div>

                <button type="submit">
                    <i class="fas fa-check"></i> Verificar Código
                </button>
            </form>

            <p style="text-align: center; margin-top: 20px; color: #666;">
                <small>Código válido por 30 minutos</small>
            </p>

            <p style="text-align: center; margin-top: 15px;">
                <a href="recuperar_senha.php">
                    <i class="fas fa-redo"></i> Solicitar novo código
                </a>
            </p>
        <?php elseif ($sent): ?>
            <p style="text-align: center; margin-top: 20px;">
                <a href="recuperar_senha.php">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </p>
        <?php else: ?>
            <div class="message error">
                <p>Link inválido. Por favor, solicite um novo código de recuperação.</p>
            </div>
            <p style="text-align: center; margin-top: 20px;">
                <a href="recuperar_senha.php">
                    <i class="fas fa-redo"></i> Solicitar novo código
                </a>
            </p>
        <?php endif; ?>

        <p style="text-align: center; margin-top: 20px;">
            <a href="login.php">
                <i class="fas fa-arrow-left"></i> Voltar ao Login
            </a>
        </p>
    </section>

    <?php include '../../root/footer.php'; ?>

    <script>
        // Formatar entrada do código
        document.querySelector('input[name="codigo"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
    </script>
</body>

</html>