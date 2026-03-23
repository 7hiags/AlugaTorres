<?php

// inicialização comum do projecto (sessão, db, helpers)
require_once __DIR__ . '/../../root/init.php';

// Mostrar mensagem de sucesso se a palavra-passe foi alterada
$password_changed = $_SESSION['password_changed'] ?? false;
unset($_SESSION['password_changed']);

if ($password_changed) {
    notifySuccess('Palavra-passe alterada com sucesso! Agora pode fazer login com a sua nova palavra-passe.');
}

// Processamento do Formulário de Login (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        // Validação de Campos Obrigatórios
        if (!isset($_POST['email']) || !isset($_POST['pass'])) {
            throw new \Exception("Campos de email e palavra-passe são obrigatórios");
        }

        // Captura e Limpeza dos Dados
        $email = trim($_POST['email']);
        $pass = trim($_POST['pass']);

        // Validação de Dados Vazios
        if (empty($email) || empty($pass)) {
            throw new \Exception("Email e palavra-passe não podem estar vazios");
        }

        // Verificação de Conexão com BD
        if (!$conn) {
            throw new \Exception("Erro na conexão com o banco de dados");
        }

        // Consulta Preparada - Procura o utilizador e previne SQL Injection usando prepared statements
        $stmt = $conn->prepare("SELECT id, utilizador, palavrapasse_hash, tipo_utilizador, ativo 
    FROM utilizadores WHERE email = ?");

        if (!$stmt) {
            throw new \Exception("Erro na preparação da consulta: " . $conn->error);
        }

        // Bind do parâmetro email
        $stmt->bind_param("s", $email);

        // Executa a consulta
        if (!$stmt->execute()) {
            throw new \Exception("Erro ao executar a consulta: " . $stmt->error);
        }

        // Armazena o resultado
        $stmt->store_result();

        // Inicializar variáveis para evitar avisos do editor
        $id = 0;
        $user = '';
        $hash = '';
        $tipo_utilizador = '';
        $ativo = 1;

        if ($stmt->num_rows === 1) {
            // Bind dos resultados
            $stmt->bind_result($id, $user, $hash, $tipo_utilizador, $ativo);
            $stmt->fetch();

            // Verificação de palavra-passe
            if (password_verify($pass, $hash)) {

                // Verificação de Conta Ativa
                // Verificar se o utilizador não está banido/suspenso
                if (isset($ativo) && $ativo == 0) {
                    throw new \Exception("Conta suspensa. Contacte o administrador.");
                }

                // Criação da Sessão do Utilizador
                $_SESSION['user'] = $user;
                $_SESSION['email'] = $email;
                $_SESSION['user_id'] = $id;
                $_SESSION['tipo_utilizador'] = $tipo_utilizador;

                // Carregar idioma preferido do utilizador da base de dados
                $stmtlingua = $conn->prepare("SELECT lingua FROM definicoes_utilizador WHERE utilizador_id = ?");
                if ($stmtlingua) {
                    $stmtlingua->bind_param("i", $id);
                    $stmtlingua->execute();
                    $resultlingua = $stmtlingua->get_result();
                    if ($resultlingua->num_rows > 0) {
                        $linguautilizador = $resultlingua->fetch_assoc();
                        $_SESSION['lingua'] = $linguautilizador['lingua'];
                    }
                    $stmtlingua->close();
                }

                // Log para debug
                error_log("Login successful: user_id=$id, user=$user, tipo=$tipo_utilizador");

                // Notificação de sucesso no login
                notifySuccess('Bem-vindo de volta, ' . htmlspecialchars($user) . '! Login realizado com sucesso.');

                // Redirecionamento Conforme Tipo de Utilizador
                if ($tipo_utilizador === 'admin') {
                    // Administradores vão para o painel admin
                    header("Location: ../../admin/index.php");
                } else {
                    // Outros utilizadores vão para a página inicial
                    header("Location: ../../root/index.php");
                }
                exit;
            } else {
                // palavra-passe incorreta
                throw new \Exception("palavra-passe incorreta");
            }
        } else {
            // Email não encontrado
            throw new \Exception("Email não encontrado");
        }

        // Tratamento de Exceções
    } catch (\Exception $e) {
        // Usar sistema de notificações toast - mostrar motivo específico do erro
        notifyError('Erro no login: ' . $e->getMessage());
        header("Location: login.php");
        exit;
    }
}
?>

<?php
$pageTitle = 'AlugaTorres | Login';
require_once __DIR__ . '/../../root/head.php';
include '../../root/header.php';
include '../../root/sidebar.php';
?>

<body>
    <section class="login">
        <h2><i class="fas fa-user"></i> Login</h2>

        <form action="" method="POST">

            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email:</label>
                <input type="email" name="email" required placeholder="Seu email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label><i class="fas fa-lock"></i> palavra-passe:</label>
                <input type="password" name="pass" required placeholder="Sua palavra-passe">
            </div>

            <div class="remember-pass">
                <label>
                    <input type="checkbox" name="remember_me" id="remember_me" checked>
                    Lembrar-me
                </label>
                <a href="recuperar_senha.php">Esqueceu a Passe?</a>
            </div>

            <button type="submit"><i class="fas fa-sign-in-alt"></i> Entrar</button>
            <p>Ainda não tens conta? <a href="registro_tipo.php">Regista-te</a></p>
        </form>
    </section>

    <?php include '../../root/footer.php'; ?>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Elementos do DOM
            const profileToggle = document.getElementById("profile-toggle");
            const sidebar = document.getElementById("sidebar");
            const sidebarOverlay = document.getElementById("sidebar-overlay");
            const closeSidebar = document.getElementById("close-sidebar");

            // Evento: Toggle do perfil
            if (profileToggle) {
                profileToggle.addEventListener("click", function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    sidebar.classList.toggle("active");
                    sidebarOverlay.classList.toggle("active");
                });
            }

            // Evento: Fechar sidebar
            if (closeSidebar) {
                closeSidebar.addEventListener("click", function() {
                    sidebar.classList.remove("active");
                    sidebarOverlay.classList.remove("active");
                });
            }

            // Evento: Fechar sidebar ao clicar fora
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