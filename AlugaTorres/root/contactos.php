<?php

require_once __DIR__ . '/init.php';

// Se foi enviado um POST, processa o formulário e responde em JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Define o tipo de resposta como JSON
  header('Content-Type: application/json; charset=utf-8');

  // Inclui arquivos necessários para conexão e envio de emails
  require_once __DIR__ . '/../backend/db.php';
  require_once __DIR__ . '/../backend/email_defin/email_utils.php';

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

    // Função para validar campos obrigatórios
    function validarCamposObrigatorios($input, $campos)
    {
      foreach ($campos as $campo) {
        if (!isset($input[$campo]) || trim((string)$input[$campo]) === '') {
          throw new \Exception("O campo $campo é obrigatório");
        }
      }
    }

    // Função para enviar e-mails
    function enviarEmails($nome, $email, $assunto, $mensagem)
    {
      $subjectUser = 'Recebemos a sua mensagem - AlugaTorres';
      $bodyUser = "<p>Olá " . htmlspecialchars($nome) . ",</p>" .
        "<p>Recebemos a sua mensagem com o assunto <strong>" . htmlspecialchars($assunto) . "</strong>. Vamos responder o mais rápido possível.</p>" .
        "<p>Resumo da mensagem:<br>" . nl2br(htmlspecialchars($mensagem)) . "</p>" .
        "<p>Obrigado,<br>Equipe AlugaTorres</p>";

      $userEmailResult = sendEmail($email, $subjectUser, $bodyUser);

      $supportEmail = getSupportEmail();
      $subjectSupport = 'Novo contacto recebido';
      $bodySupport = "<p>Foi recebida uma nova mensagem de contacto:</p>" .
        "<p><strong>Nome:</strong> " . htmlspecialchars($nome) . "<br>" .
        "<strong>Email:</strong> " . htmlspecialchars($email) . "<br>" .
        "<strong>Assunto:</strong> " . htmlspecialchars($assunto) . "</p>" .
        "<p><strong>Mensagem:</strong><br>" . nl2br(htmlspecialchars($mensagem)) . "</p>";

      $supportEmailResult = sendEmail($supportEmail, $subjectSupport, $bodySupport);

      return ['user' => $userEmailResult, 'support' => $supportEmailResult];
    }

    // Função para inserir mensagem no banco de dados
    function inserirMensagem($pdo, $dados)
    {
      $stmt = $pdo->prepare("INSERT INTO mensagens_contactos 
            (utilizador_id, nome, email, assunto, mensagem, data_envio, lida, ip_address, user_agent) 
            VALUES (:utilizador_id, :nome, :email, :assunto, :mensagem, NOW(), 0, :ip, :user_agent)");

      return $stmt->execute([
        ':utilizador_id' => $dados['utilizador_id'],
        ':nome' => $dados['nome'],
        ':email' => $dados['email'],
        ':assunto' => $dados['assunto'],
        ':mensagem' => $dados['mensagem'],
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
      ]);
    }

    // Validação de Campos Obrigatórios
    // Verificação explícita de campos vazios usando if
    validarCamposObrigatorios($input, ['nome', 'email', 'assunto', 'mensagem']);

    $dados = [
      'utilizador_id' => $_SESSION['user_id'] ?? null,
      'nome' => trim((string)$input['nome']),
      'email' => trim((string)$input['email']),
      'assunto' => trim((string)$input['assunto']),
      'mensagem' => trim((string)$input['mensagem']),
    ];

    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
      throw new \Exception('Formato de email inválido');
    }

    if (inserirMensagem($pdo, $dados)) {
      $emails = enviarEmails($dados['nome'], $dados['email'], $dados['assunto'], $dados['mensagem']);
      echo json_encode(['status' => 'success', 'message' => 'Mensagem enviada com sucesso!', 'emails' => $emails]);
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

$pageTitle = 'AlugaTorres | Contactos';
$metaDescription = 'Fale connosco para obter informações e assistência';

require_once __DIR__ . '/head.php';
include 'header.php';
include 'sidebar.php'; ?>

<body>
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

  <div id="resposta" class="response-message"></div>

  <?php include 'footer.php'; ?>

</body>

</html>