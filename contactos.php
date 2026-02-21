<?php

/**
 * ========================================
 * Página de Contactos - AlugaTorres
 * ========================================
 * Este arquivo contém a página de contactos do site AlugaTorres.
 * Permite aos utilizadores enviar mensagens que são armazenadas
 * no banco de dados e enviadas por email.
 * 
 * @author AlugaTorres
 * @version 1.0
 */

// ============================================
// Inicialização da Sessão
// ============================================

session_start();

// ============================================
// Processamento do Formulário (POST)
// ============================================

// Se foi enviado um POST, processa o formulário e responde em JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Define o tipo de resposta como JSON
  header('Content-Type: application/json; charset=utf-8');

  // Inclui arquivos necessários para conexão e envio de emails
  require_once __DIR__ . '/backend/db.php';
  require_once __DIR__ . '/backend/email_utils.php';

  try {

    // ============================================
    // Conexão com Banco de Dados usando PDO
    // ============================================

    // Conectar via PDO usando as variáveis definidas em db.php
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $pdo = new \PDO($dsn, $user, $password, [
      \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
      \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]);

    // ============================================
    // Processamento dos Dados de Entrada
    // ============================================

    // Aceitar tanto form-data (popula $_POST) quanto JSON no body
    $rawBody = file_get_contents('php://input');
    $input = $_POST;

    // Se $_POST estiver vazio, tenta decodificar o body JSON
    if (empty($input) && !empty($rawBody)) {
      $decoded = json_decode($rawBody, true);
      if (is_array($decoded)) {
        $input = $decoded;
      }
    }

    // ============================================
    // Validação de Campos Obrigatórios
    // ============================================

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

    // ============================================
    // Captura e Sanitização dos Dados
    // ============================================

    $nome = trim((string)$input['nome']);
    $email = trim((string)$input['email']);
    $assunto = trim((string)$input['assunto']);
    $mensagem = trim((string)$input['mensagem']);

    // Captura o ID do utilizador se estiver logado
    $utilizador_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    // Validação do formato de email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      throw new \Exception('Formato de email inválido');
    }

    // ============================================
    // Inserção no Banco de Dados
    // ============================================

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

    // ============================================
    // Envio de Emails de Confirmação
    // ============================================

    if ($success) {
      // ------------------------------------------
      // Email de confirmação ao remetente
      // ------------------------------------------
      $subjectUser = 'Recebemos a sua mensagem - AlugaTorres';
      $bodyUser = "<p>Olá " . htmlspecialchars($nome) . ",</p>" .
        "<p>Recebemos a sua mensagem com o assunto <strong>" . htmlspecialchars($assunto) . "</strong>. Vamos responder o mais rápido possível.</p>" .
        "<p>Resumo da mensagem:<br>" . nl2br(htmlspecialchars($mensagem)) . "</p>" .
        "<p>Obrigado,<br>Equipe AlugaTorres</p>";

      $userEmailResult = sendEmail($email, $subjectUser, $bodyUser);

      // ------------------------------------------
      // Email de aviso para suporte
      // ------------------------------------------
      $supportEmail = getSupportEmail();
      $subjectSupport = 'Novo contacto recebido';
      $bodySupport = "<p>Foi recebida uma nova mensagem de contacto:</p>" .
        "<p><strong>Nome:</strong> " . htmlspecialchars($nome) . "<br>" .
        "<strong>Email:</strong> " . htmlspecialchars($email) . "<br>" .
        "<strong>Assunto:</strong> " . htmlspecialchars($assunto) . "</p>" .
        "<p><strong>Mensagem:</strong><br>" . nl2br(htmlspecialchars($mensagem)) . "</p>";

      $supportEmailResult = sendEmail($supportEmail, $subjectSupport, $bodySupport);

      // Retorna resposta de sucesso
      echo json_encode([
        'status' => 'success',
        'message' => 'Mensagem enviada com sucesso!',
        'emails' => ['user' => $userEmailResult, 'support' => $supportEmailResult]
      ]);
    } else {
      throw new \Exception('Falha ao inserir no banco de dados');
    }

    // ============================================
    // Tratamento de Exceções
    // ============================================  
  } catch (\Exception $e) {
    // Em caso de erro, retorna código 500 e mensagem de erro
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
  }

  // Encerra a execução após processar o POST
  exit;
}
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
  <!-- ========================================
       Meta Tags e Configurações
       ======================================== -->
  <meta charset="UTF-8">
  <title>AlugaTorres | Contactos</title>

  <!-- ========================================
       Folhas de Estilo (CSS)
       ======================================== -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="style/style.css">
  <link rel="website icon" type="png" href="style/img/Logo_AlugaTorres_branco.png">
</head>

<body>
  <!-- ========================================
       Inclusão de Componentes
       ======================================== -->
  <?php include 'header.php'; ?>
  <?php include 'sidebar.php'; ?>

  <!-- ========================================
       Seção Principal de Contactos
       ======================================== -->
  <section class="contact-section">

    <!-- ========================================
         Lado Esquerdo: Informações de Contacto
         ======================================== -->
    <div class="contact-info-side">
      <div class="contact-header">
        <h2>Entre em Contacto</h2>
        <p>Estamos aqui para tornar sua viagem inesquecível</p>
      </div>

      <div class="contact-info-container">

        <!-- Item: Localização -->
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

        <!-- Item: Telefone -->
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

        <!-- Item: Email -->
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

        <!-- Item: Redes Sociais -->
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

    <!-- ========================================
         Lado Direito: Formulário de Contacto
         ======================================== -->
    <div class="contact-form-side">
      <div class="form-container-contacto">
        <form id="form-contacto" class="contact-form">
          <h3><i class="fas fa-paper-plane"></i> Envie-nos uma mensagem</h3>

          <!-- Campo: Nome -->
          <div class="form-group">
            <label><i class="fas fa-user"></i> Nome</label>
            <input type="text" name="nome" required placeholder="Seu nome completo">
          </div>

          <!-- Campo: Email -->
          <div class="form-group">
            <label><i class="fas fa-envelope"></i> Email</label>
            <input type="email" name="email" required placeholder="seu.email@exemplo.com">
          </div>

          <!-- Campo: Assunto -->
          <div class="form-group">
            <label><i class="fas fa-tag"></i> Assunto</label>
            <input type="text" name="assunto" required placeholder="Assunto da mensagem">
          </div>

          <!-- Campo: Mensagem -->
          <div class="form-group">
            <label><i class="fas fa-comment-alt"></i> Mensagem</label>
            <textarea name="mensagem" required placeholder="Digite sua mensagem aqui..." rows="5"></textarea>
          </div>

          <!-- Botão de Envio -->
          <button type="submit" class="submit-button">
            <i class="fas fa-paper-plane"></i> Enviar Mensagem
          </button>
        </form>
      </div>
    </div>
  </section>

  <!-- ========================================
       Mensagem de Resposta ( feedback do envio)
       ======================================== -->
  <div id="resposta" class="response-message"></div>

  <!-- ========================================
       Rodapé da Página
       ======================================== -->
  <?php include 'footer.php'; ?>

  <!-- ========================================
       Scripts JavaScript
       ======================================== -->
  <script src="js/script.js"></script>

</body>

</html>