<?php
session_start();

// Se foi enviado um POST, processa o formulário e responde em JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json; charset=utf-8');
  require_once __DIR__ . '/backend/db.php';
  require_once __DIR__ . '/backend/email_utils.php';

  try {

    // Conectar via pdo usando as variáveis definidas em db.php
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $pdo = new \PDO($dsn, $user, $password, [
      \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
      \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]);


    // Aceitar tanto form-data (popula $_POST) quanto JSON no body
    $rawBody = file_get_contents('php://input');
    $input = $_POST;
    if (empty($input) && !empty($rawBody)) {
      $decoded = json_decode($rawBody, true);
      if (is_array($decoded)) {
        $input = $decoded;
      }
    }

    // Verificação explícita de campos vazios usando if
    if (!isset($input['nome']) || trim((string)$input['nome']) === '') {
      throw new \Exception('O campo nome é obrigatório');
    }
    if (!isset($input['email']) || trim((string)$input['email']) === '') {
      throw new \Exception('O campo email é obrigatório');
    }
    if (!isset($input['assunto']) || trim((string)$input['assunto']) === '') {
      throw new \Exception('O campo assunto é obrigatório');
    }
    if (!isset($input['mensagem']) || trim((string)$input['mensagem']) === '') {
      throw new \Exception('O campo mensagem é obrigatório');
    }

    // Captura e sanitiza
    $nome = trim((string)$input['nome']);
    $email = trim((string)$input['email']);
    $assunto = trim((string)$input['assunto']);
    $mensagem = trim((string)$input['mensagem']);
    $utilizador_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      throw new \Exception('Formato de email inválido');
    }

    // Inserir no banco (nome da tabela: mensagens_contactos)
    $stmt = $pdo->prepare("INSERT INTO mensagens_contactos 
      (utilizador_id, nome, email, assunto, mensagem, data_envio, lida, ip_address, user_agent) 
      VALUES (:utilizador_id, :nome, :email, :assunto, :mensagem, NOW(), 0, :ip, :user_agent)");

    $success = $stmt->execute([
      ':utilizador_id' => $utilizador_id,
      ':nome' => $nome,
      ':email' => $email,
      ':assunto' => $assunto,
      ':mensagem' => $mensagem,
      ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
      ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
    ]);

    if ($success) {
      // Enviar email de confirmação ao remetente
      $subjectUser = 'Recebemos a sua mensagem - AlugaTorres';
      $bodyUser = "<p>Olá " . htmlspecialchars($nome) . ",</p>" .
        "<p>Recebemos a sua mensagem com o assunto <strong>" . htmlspecialchars($assunto) . "</strong>. Vamos responder o mais rápido possível.</p>" .
        "<p>Resumo da mensagem:<br>" . nl2br(htmlspecialchars($mensagem)) . "</p>" .
        "<p>Obrigado,<br>Equipe AlugaTorres</p>";

      $userEmailResult = sendEmail($email, $subjectUser, $bodyUser);

      // Enviar email de aviso para admin
      $adminEmail = getAdminEmail() ?: 'suportealugatorres@gmail.com';
      $subjectAdmin = 'Novo contacto recebido';
      $bodyAdmin = "<p>Foi recebida uma nova mensagem de contacto:</p>" .
        "<p><strong>Nome:</strong> " . htmlspecialchars($nome) . "<br>" .
        "<strong>Email:</strong> " . htmlspecialchars($email) . "<br>" .
        "<strong>Assunto:</strong> " . htmlspecialchars($assunto) . "</p>" .
        "<p><strong>Mensagem:</strong><br>" . nl2br(htmlspecialchars($mensagem)) . "</p>";

      $adminEmailResult = sendEmail($adminEmail, $subjectAdmin, $bodyAdmin);

      echo json_encode([
        'status' => 'success',
        'message' => 'Mensagem enviada com sucesso!',
        'emails' => ['user' => $userEmailResult, 'admin' => $adminEmailResult]
      ]);
    } else {
      throw new \Exception('Falha ao inserir no banco de dados');
    }
  } catch (\Exception $e) {

    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
  }

  exit;
}
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
  <meta charset="UTF-8">
  <title>AlugaTorres | Contactos</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="style/style.css">
  <link rel="website icon" type="png" href="style/img/Logo_AlugaTorres_branco.png">
</head>

<body>
  <?php include 'header.php'; ?>
  <?php include 'sidebar.php'; ?>

  <section class="contact-section">
    <div class="contact-info-side">
      <div class="contact-header">
        <h2>Entre em Contacto</h2>
        <p>Estamos aqui para tornar sua viagem inesquecível</p>
      </div>

      <div class="contact-info-container">
        <div class="contact-info-item">
          <div class="icon-circle">
            <i class="fas fa-map-marker-alt"></i>
          </div>
          <div class="info-content">
            <h3>Localização</h3>
            <p>Rua Principal, 123</p>
            <p>Torres Novas, Portugal</p>
          </div>
        </div>

        <div class="contact-info-item">
          <div class="icon-circle">
            <i class="fas fa-phone-alt"></i>
          </div>
          <div class="info-content">
            <h3>Telefone</h3>
            <p>+351 929 326 577</p>
            <p>Segunda a Sexta: 9h - 18h</p>
          </div>
        </div>

        <div class="contact-info-item">
          <div class="icon-circle">
            <i class="fas fa-envelope"></i>
          </div>
          <div class="info-content">
            <h3>Email</h3>
            <p>alugatorrespt@gmail.com</p>
            <p>suportealugatorres@gmail.com</p>
          </div>
        </div>

        <div class="contact-social">
          <h3>Siga-nos</h3>
          <div class="social-links">
            <a href="#" class="social-circle"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="social-circle"><i class="fab fa-instagram"></i></a>
            <a href="#" class="social-circle"><i class="fab fa-twitter"></i></a>
            <a href="#" class="social-circle"><i class="fab fa-linkedin-in"></i></a>
          </div>
        </div>
      </div>
    </div>

    <div class="contact-form-side">
      <div class="form-container">
        <form id="form-contacto" class="contact-form">
          <h3><i class="fas fa-paper-plane"></i> Envie-nos uma mensagem</h3>

          <div class="form-group">
            <label><i class="fas fa-user"></i> Nome</label>
            <input type="text" name="nome" required placeholder="Seu nome completo">
          </div>

          <div class="form-group">
            <label><i class="fas fa-envelope"></i> Email</label>
            <input type="email" name="email" required placeholder="seu.email@exemplo.com">
          </div>

          <div class="form-group">
            <label><i class="fas fa-tag"></i> Assunto</label>
            <input type="text" name="assunto" required placeholder="Assunto da mensagem">
          </div>

          <div class="form-group">
            <label><i class="fas fa-comment-alt"></i> Mensagem</label>
            <textarea name="mensagem" required placeholder="Digite sua mensagem aqui..." rows="5"></textarea>
          </div>

          <button type="submit" class="submit-button">
            <i class="fas fa-paper-plane"></i> Enviar Mensagem
          </button>
        </form>
      </div>
    </div>
  </section>

  <div id="resposta" class="response-message"></div>

  <?php include 'footer.php'; ?>

  <script src="backend/script.js"></script>
</body>

</html>