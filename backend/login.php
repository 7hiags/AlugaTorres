<?php

// Inclusão de Arquivos Necessários

// Carrega o arquivo de conexão com o banco de dados
require_once 'db.php';

// Carrega o helper de notificações
require_once 'notifications_helper.php';

// Inicialização da Sessão
session_start();

// Processamento do Formulário de Login (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  try {
    // Validação de Campos Obrigatórios
    if (!isset($_POST['email']) || !isset($_POST['pass'])) {
      throw new \Exception("Campos de email e senha são obrigatórios");
    }

    // Captura e Limpeza dos Dados
    $email = trim($_POST['email']);
    $pass = trim($_POST['pass']);

    // Validação de Dados Vazios
    if (empty($email) || empty($pass)) {
      throw new \Exception("Email e senha não podem estar vazios");
    }

    // Verificação de Conexão com BD
    if (!$conn) {
      throw new \Exception("Erro na conexão com o banco de dados");
    }

    // Consulta Preparada - Procura o utilizador e previne SQL Injection usando prepared statements
    $stmt = $conn->prepare("SELECT id, utilizador, palavrapasse_hash, tipo_utilizador, ativo FROM utilizadores WHERE email = ?");

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

      // Verificação de Senha
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

        // Log para debug
        error_log("Login successful: user_id=$id, user=$user, tipo=$tipo_utilizador");

        // Notificação de sucesso no login
        notifySuccess('Bem-vindo de volta, ' . htmlspecialchars($user) . '! Login realizado com sucesso.');

        // Redirecionamento Conforme Tipo de Utilizador
        if ($tipo_utilizador === 'admin') {
          // Administradores vão para o painel admin
          header("Location: ../admin/index.php");
        } else {
          // Outros utilizadores vão para a página inicial
          header("Location: ../index.php");
        }
        exit;
      } else {
        // Senha incorreta
        throw new \Exception("Senha incorreta");
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

<!DOCTYPE html>
<html lang="pt">

<head>
  <!-- Meta Tags e Configurações -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AlugaTorres | Login</title>

  <!-- Folhas de Estilo (CSS) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="../style/style.css">
  <link rel="website icon" type="png" href="../style/img/Logo_AlugaTorres_branco.png">
</head>

<body>
  <!-- Inclusão de Componentes -->
  <?php include '../header.php'; ?>
  <?php include '../sidebar.php'; ?>

  <!-- Seção de Formulário de Login -->
  <section class="login">
    <h2><i class="fas fa-user"></i> Login</h2>

    <!-- Formulário de Login -->
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">

      <!-- Campo: Email -->
      <div class="form-group">
        <label><i class="fas fa-envelope"></i> Email:</label>
        <input type="email" name="email" required placeholder="Seu email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
      </div>

      <!-- Campo: Senha -->
      <div class="form-group">
        <label><i class="fas fa-lock"></i> Senha:</label>
        <input type="password" name="pass" required placeholder="Sua senha">
      </div>

      <!-- Opções: Lembrar-me e Esqueceu a senha -->
      <div class="remember-pass">
        <label>
          <input type="checkbox" name="remember_me" id="remember_me" checked>
          Lembrar-me
        </label>
        <a href="recuperar.php">Esqueceu a Passe?</a>
      </div>

      <!-- Botão de Submit -->
      <button type="submit"><i class="fas fa-sign-in-alt"></i> Entrar</button>

      <!-- Link para Registration -->
      <p>Ainda não tens conta? <a href="registro_tipo.php">Regista-te</a></p>
    </form>
  </section>

  <!-- Rodapé da Página -->
  <?php include '../footer.php'; ?>

  <!-- Scripts JavaScript -->
  <script src="../js/script.js"></script>

  <!-- Script para Controle da Sidebar -->
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