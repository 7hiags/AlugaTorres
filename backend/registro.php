<?php
require_once 'db.php';
require_once __DIR__ . '/email_utils.php';
require_once __DIR__ . '/notifications_helper.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


// Se já estiver logado, redireciona
if (isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

// Verifica se veio do formulário de escolha de perfil
$tipo_utilizador = isset($_GET['tipo_utilizador']) ? $_GET['tipo_utilizador'] : 'arrendatario';
$tipos_permitidos = ['proprietario', 'arrendatario'];

if (!in_array($tipo_utilizador, $tipos_permitidos)) {
    $tipo_utilizador = 'arrendatario';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['user']);
    $email = trim($_POST['email']);
    $confirm_email = trim($_POST['confirm_email']);
    $pass = $_POST['pass'];
    $confirm_pass = $_POST['confirm_pass'];
    $tipo = $_POST['tipo_utilizador'];
    $telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
    $nif = isset($_POST['nif']) ? trim($_POST['nif']) : '';

    // Validações básicas
    if (empty($user) || empty($email) || empty($pass)) {
        notifyError("Todos os campos são obrigatórios!");
        header("Location: registro.php?tipo_utilizador=$tipo");
        exit;
    } elseif ($email !== $confirm_email) {
        notifyError("Os emails não coincidem!");
        header("Location: registro.php?tipo_utilizador=$tipo");
        exit;
    } elseif ($pass !== $confirm_pass) {
        notifyError("As palavras-passe não coincidem!");
        header("Location: registro.php?tipo_utilizador=$tipo");
        exit;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        notifyError("Email inválido! Verifique o formato do email.");
        header("Location: registro.php?tipo_utilizador=$tipo");
        exit;
    } elseif (strlen($pass) < 8) {
        notifyError("A palavra-passe deve ter pelo menos 8 caracteres!");
        header("Location: registro.php?tipo_utilizador=$tipo");
        exit;
    } else {
        // Verifica se o email já existe
        $check = $conn->prepare("SELECT id FROM utilizadores WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            notifyError("Este email já está registado! Use outro email ou faça login.");
            header("Location: registro.php?tipo_utilizador=$tipo");
            exit;
        } else {

            // Hash da palavra-passe
            $hash = password_hash($pass, PASSWORD_DEFAULT);

            // Insere novo utilizador com tipo
            $stmt = $conn->prepare("INSERT INTO utilizadores (utilizador, email, palavrapasse_hash, tipo_utilizador, telefone, nif) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $user, $email, $hash, $tipo, $telefone, $nif);

            if ($stmt->execute()) {
                // Login automático após registro
                $user_id = $stmt->insert_id;
                $_SESSION['user'] = $user;
                $_SESSION['email'] = $email;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['tipo_utilizador'] = $tipo;

                // Notificação de sucesso
                notifySuccess('Bem-vindo ao AlugaTorres, ' . htmlspecialchars($user) . '! Registo realizado com sucesso.');

                // Enviar email de boas-vindas ao novo utilizador
                $subjectUser = 'Bem-vindo ao AlugaTorres';
                $bodyUser = "<p>Olá " . htmlspecialchars($user) . ",</p>" .
                    "<p>Obrigado por se registar no AlugaTorres. Agora pode pesquisar e reservar casas em Torres Novas.</p>" .
                    "<p>Se tiver dúvidas, responda a este email.</p>" .
                    "<p>Boas reservas!<br>Equipe AlugaTorres</p>";

                try {
                    $userEmailResult = sendEmail($email, $subjectUser, $bodyUser);
                } catch (\Exception $e) {
                    // Não bloquear o fluxo de registo se o email falhar
                }

                // Notificar admin sobre novo registo
                $adminEmail = getAdminEmail() ?: 'suportealugatorres@gmail.com';
                $subjectAdmin = 'Novo utilizador registado';
                $bodyAdmin = "<p>Um novo utilizador acabou de registar-se:</p>" .
                    "<p><strong>Nome:</strong> " . htmlspecialchars($user) . "<br>" .
                    "<strong>Email:</strong> " . htmlspecialchars($email) . "<br>" .
                    "<strong>Tipo:</strong> " . htmlspecialchars($tipo) . "</p>";

                try {
                    $adminEmailResult = sendEmail($adminEmail, $subjectAdmin, $bodyAdmin);
                } catch (\Exception $e) {
                    // Ignorar falhas de email para não bloquear o registo
                }

                // Redireciona conforme o tipo de usuário
                if ($tipo === 'proprietario') {
                    header("Location: ../dashboard.php");
                } else {
                    header("Location: ../index.php");
                }
                exit;
            } else {
                notifyError("Erro ao registar utilizador: " . $conn->error);
                header("Location: registro.php?tipo_utilizador=$tipo");
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlugaTorres | Registo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="website icon" type="png" href="../style/img/Logo_AlugaTorres_branco.png">
</head>

<body>
    <?php include '../header.php'; ?>
    <?php include '../sidebar.php'; ?>

    <section class="login">
        <h2><i class="fas fa-user-plus"></i> Criar Conta</h2>

        <div class="register-info">
            <h3>
                <?php if ($tipo_utilizador === 'proprietario'): ?>
                    <i class="fas fa-home"></i> Registo de Proprietário
                <?php else: ?>
                    <i class="fas fa-user-tag"></i> Registo de Arrendatário
                <?php endif; ?>
            </h3>
            <p>
                <?php if ($tipo_utilizador === 'proprietário'): ?>
                    Está a criar uma conta de proprietário. Poderá anunciar e gerir as suas propriedades em Torres Novas.
                <?php else: ?>
                    Está a criar uma conta de arrendatário. Poderá pesquisar e reservar alojamentos em Torres Novas.
                <?php endif; ?>
            </p>
        </div>



        <form class="registro" method="POST">
            <input type="hidden" name="tipo_utilizador" value="<?php echo htmlspecialchars($tipo_utilizador); ?>">

            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-user"></i>Nome Completo <span class="required">*</span></label>
                    <input type="text" name="user" required placeholder="Seu nome completo"
                        value="<?php echo isset($_POST['user']) ? htmlspecialchars($_POST['user']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-envelope"></i>Email <span class="required">*</span></label>
                    <input type="email" name="email" required placeholder="seu.email@exemplo.com"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i>Confirmar Email <span class="required">*</span></label>
                    <input type="email" name="confirm_email" required placeholder="Confirme o email">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-phone"></i>Telefone</label>
                    <input type="tel" name="telefone" placeholder="+351 912 345 678"
                        value="<?php echo isset($_POST['telefone']) ? htmlspecialchars($_POST['telefone']) : ''; ?>">
                </div>
            </div>

            <?php if ($tipo_utilizador === 'proprietario'): ?>
                <div class="form-group">
                    <label><i class="fas fa-id-card"></i>NIF (Opcional)</label>
                    <input type="text" name="nif" placeholder="Seu NIF para faturação"
                        value="<?php echo isset($_POST['nif']) ? htmlspecialchars($_POST['nif']) : ''; ?>">
                </div>
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-lock"></i>Palavra-passe <span class="required">*</span></label>
                    <input type="password" name="pass" required placeholder="Mínimo 8 caracteres">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i>Confirmar Palavra-passe <span class="required">*</span></label>
                    <input type="password" name="confirm_pass" required placeholder="Repita a palavra-passe">
                </div>
            </div>

            <label termos-condicoes>
                <input type="checkbox" name="termos" required>Aceito os <a href="../termos.php" target="_blank">Termos e Condições</a> e a <a href="../privacidade.php" target="_blank">Política de Privacidade</a>
            </label>

            <button type="submit"><i class="fas fa-user-plus"></i> Criar Conta</button>

            <p>
                Já tem conta? <a href="login.php">Faça login aqui</a>
                <br>
                <small>
                    <a href="registro_tipo.php">Escolher outro tipo de perfil</a>
                </small>
            </p>
        </form>
    </section>

    <?php include '../footer.php'; ?>

    <script src="../js/script.js"></script>

</body>

</html>