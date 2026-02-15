<?php
require_once 'db.php';
require_once 'notifications_helper.php';
session_start();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!isset($_POST['email']) || !isset($_POST['pass'])) {
      throw new \Exception("Campos de email e senha são obrigatórios");
    }

    $email = trim($_POST['email']);
    $pass = trim($_POST['pass']);

    if (empty($email) || empty($pass)) {
      throw new \Exception("Email e senha não podem estar vazios");
    }

    if (!$conn) {
      throw new \Exception("Erro na conexão com o banco de dados");
    }

    $stmt = $conn->prepare("SELECT id, utilizador, palavrapasse_hash, tipo_utilizador FROM utilizadores WHERE email = ?");
    if (!$stmt) {
      throw new \Exception("Erro na preparação da consulta: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
      throw new \Exception("Erro ao executar a consulta: " . $stmt->error);
    }

    $stmt->store_result();

    if ($stmt->num_rows === 1) {
      $stmt->bind_result($id, $user, $hash, $tipo_utilizador);
      $stmt->fetch();

      if (password_verify($pass, $hash)) {
        $_SESSION['user'] = $user;
        $_SESSION['email'] = $email;
        $_SESSION['user_id'] = $id;
        $_SESSION['tipo_utilizador'] = $tipo_utilizador;

        // Notificação de sucesso no login
        notifySuccess('Bem-vindo de volta, ' . htmlspecialchars($user) . '! Login realizado com sucesso.');

        header("Location: ../index.php");
        exit;
      } else {

        throw new \Exception("Senha incorreta");
      }
    } else {
      throw new \Exception("Email não encontrado");
    }
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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AlugaTorres | Login</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="../style/style.css">
  <link rel="website icon" type="png" href="../style/img/Logo_AlugaTorres_branco.png">
</head>

<body>
  <?php include '../header.php'; ?>
  <?php include '../sidebar.php'; ?>

  <section class="login">
    <h2><i class="fas fa-user"></i> Login</h2>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
      <div class="form-group">
        <label><i class="fas fa-envelope"></i> Email:</label>
        <input type="email" name="email" required placeholder="Seu email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
      </div>

      <div class="form-group">
        <label><i class="fas fa-lock"></i> Senha:</label>
        <input type="password" name="pass" required placeholder="Sua senha">
      </div>

      <div class="remember-pass">
        <label>
          <input type="checkbox" name="remember_me" id="remember_me" checked>
          Lembrar-me
        </label>
        <a href="recuperar.php">Esqueceu a Passe?</a>
      </div>

      <button type="submit"><i class="fas fa-sign-in-alt"></i> Entrar</button>
      <p>Ainda não tens conta? <a href="registro_tipo.php">Regista-te</a></p>
    </form>
  </section>

  <?php include '../footer.php'; ?>

  <script src="../js/script.js"></script>

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
  </script>
</body>

</html>