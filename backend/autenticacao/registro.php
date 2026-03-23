<?php
// inicialização comum para scripts backend
require_once __DIR__ . '/../../root/init.php';
// já temos acesso a $conn, email_utils e notificações (via init)


// Se já estiver logado, redireciona
if (isset($_SESSION['user'])) {
    header("Location: ../../root/index.php");
    exit;
}

// Verifica se veio do formulário de escolha de perfil
$tipo_utilizador = isset($_GET['tipo_utilizador']) ? $_GET['tipo_utilizador'] : 'arrendatario';
$tipos_permitidos = ['proprietario', 'arrendatario'];

if (!in_array($tipo_utilizador, $tipos_permitidos)) {
    $tipo_utilizador = 'arrendatario';
}

// Processa o formulário de registo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_raw = trim($_POST['user']);
    $email = trim($_POST['email']);

    // Extrair apenas primeiro e último nome
    $user_clean = preg_replace('/\\s+/', ' ', $user_raw);
    $parts = explode(' ', $user_clean);
    if (count($parts) < 2) {
        notifyError("O nome deve incluir pelo menos primeiro e último nome!");
        header("Location: registro.php?tipo_utilizador=" . urlencode($_POST['tipo_utilizador']));
        exit;
    }
    $first_name = $parts[0];
    $last_name = end($parts);
    $display_name = trim($first_name . ' ' . $last_name);

    if (strlen($first_name) < 2 || strlen($last_name) < 2) {
        notifyError("Primeiro e último nome devem ter pelo menos 2 caracteres!");
        header("Location: registro.php?tipo_utilizador=" . urlencode($_POST['tipo_utilizador']));
        exit;
    }
    $confirm_email = trim($_POST['confirm_email']);
    $pass = $_POST['pass'];
    $confirm_pass = $_POST['confirm_pass'];
    $tipo = $_POST['tipo_utilizador'];
    $telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
    $nif = isset($_POST['nif']) ? trim($_POST['nif']) : '';

    // Validações básicas
    if (empty($user_raw) || empty($email) || empty($pass)) {
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
    } elseif (!empty($nif) && (!preg_match('/^[0-9]+$/', $nif) || strlen($nif) !== 9)) {
        notifyError("O NIF deve conter exatamente 9 dígitos numéricos!");
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
            $stmt = $conn->prepare("INSERT INTO utilizadores 
            (utilizador, 
            email, 
            palavrapasse_hash, 
            tipo_utilizador, 
            telefone, 
            nif) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "ssssss",
                $display_name,
                $email,
                $hash,
                $tipo,
                $telefone,
                $nif
            );

            if ($stmt->execute()) {
                // Login automático após registro
                $user_id = $stmt->insert_id;
                $_SESSION['user'] = $display_name;
                $_SESSION['email'] = $email;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['tipo_utilizador'] = $tipo;

                // Definir idioma padrão do utilizador
                $_SESSION['lingua'] = 'portuguese';

                // Criar definições do utilizador com idioma padrão
                $stmtDef = $conn->prepare("INSERT INTO definicoes_utilizador (utilizador_id, lingua) VALUES (?, 'portuguese')");
                $stmtDef->bind_param("i", $user_id);
                $stmtDef->execute();
                $stmtDef->close();

                // Notificação de sucesso
                notifySuccess('Bem-vindo ao AlugaTorres, ' . htmlspecialchars($display_name) . '! Registo realizado com sucesso.');

                // Enviar email de boas-vindas ao novo utilizador
                $subjectUser = 'Bem-vindo ao AlugaTorres';
                $messageUserHtml = '
                    <p>Obrigado por se registar na nossa plataforma!</p>
                    <p>Agora pode:</p>
                    ' . ($tipo === 'proprietario' ? '
                    <ul style="color: #555;">
                        <li>📤 Anunciar as suas casas</li>
                        <li>💰 Receber reservas diretamente</li>
                        <li>📊 Gerir calendário e preços</li>
                    </ul>' : '
                    <ul style="color: #555;">
                        <li>🔍 Pesquisar casas em Torres Novas</li>
                        <li>📅 Reservar datas disponíveis</li>
                        <li>⭐ Deixar avaliações</li>
                    </ul>') . '
                    <p><a href="https://alugatorres.pt/AlugaTorres/" style="background: #038e01; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Começar Agora</a></p>
                    <p>Precisa de ajuda? Responda a este email.</p>';
                $bodyUser = EmailEstilizado('Bem-vindo ao AlugaTorres', $messageUserHtml, null, $display_name);

                try {
                    $userEmailResult = sendEmail($email, $subjectUser, $bodyUser);
                } catch (\Exception $e) {
                    // Não bloquear o fluxo de registo se o email falhar
                }

                // Notificar admin sobre novo registo
                $adminEmail = getAdminEmail() ?: 'suportealugatorres@gmail.com';
                $subjectAdmin = 'Novo utilizador registado';
                $messageAdminHtml = '
                    <p>Novo registo completado com sucesso:</p>
                    <ul style="color: #555;">
                        <li><strong>Nome:</strong> ' . htmlspecialchars($display_name) . '</li>
                        <li><strong>Email:</strong> ' . htmlspecialchars($email) . '</li>
                        <li><strong>Tipo:</strong> ' . htmlspecialchars($tipo) . '</li>
                    </ul>
                    <p><a href="https://alugatorres.pt/AlugaTorres/admin/utilizadores.php" style="background: #038e01; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Ver Painel Utilizadores</a></p>';
                $bodyAdmin = EmailEstilizado('Novo Utilizador Registado', $messageAdminHtml);

                try {
                    $adminEmailResult = sendEmail($adminEmail, $subjectAdmin, $bodyAdmin);
                } catch (\Exception $e) {
                    // Ignorar falhas de email para não bloquear o registo
                }

                // Redireciona conforme o tipo de usuário
                if ($tipo === 'proprietario') {
                    header("Location: ../../root/dashboard.php");
                } else {
                    header("Location: ../../root/index.php");
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
<?php
$pageTitle = 'AlugaTorres | Registo';
require_once __DIR__ . '/../../root/head.php';
include '../../root/header.php';
include '../../root/sidebar.php';
?>

<body>
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
                <?php if ($tipo_utilizador === 'proprietario'): ?>
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
                    <label><i class="fas fa-user"></i>Primeiro e Último Nome <span class="required">*</span></label>
                    <input type="text" name="user" required placeholder="Ex: João Silva (apenas primeiro e último)"
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
                    <input type="text" name="nif" placeholder="9 dígitos numéricos" maxlength="9" pattern="[0-9]{9}"
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

            <label class="termos-condicoes">
                <input type="checkbox" name="termos" required>Aceito os <a href="../../termos.php" target="_blank">Termos e Condições</a> e a <a href="../../privacidade.php" target="_blank">Política de Privacidade</a>
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

    <?php include '../../root/footer.php'; ?>


</body>

</html>