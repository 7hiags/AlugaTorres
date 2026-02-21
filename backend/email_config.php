<?php
// Configuração de Email com SMTP
// Editável através do painel de admin em admin/configuracoes.php
// Nota: Para Gmail use um "App Password" de 16 caracteres (sem espaços)
return [
  // Configurações SMTP (preenchidas pelo admin)
  // Para Gmail: smtp.gmail.com, porta 587 (TLS) ou 465 (SSL)
  // Para Mailtrap: smtp.mailtrap.io
  'smtp_host' => 'smtp.gmail.com',
  'smtp_port' => 465,
  'smtp_user' => 'suportealugatorres@gmail.com',
  'smtp_pass' => 'dmiyrpwbieoldqbd',

  // Configurações do remetente
  'from_email' => 'suportealugatorres@gmail.com',
  'from_name' => 'AlugaTorres',

  // Email do administrador (recebe notificações)
  'admin_email' => 'suportealugatorres@gmail.com',

  // Emails de suporte
  'support_email' => 'suportealugatorres@gmail.com',
  'newsletter_email' => 'alugatorrespt@gmail.com',

  // Tipo de envio: 'smtp' ou 'mail' (função nativa)
  'mailer' => 'smtp',

  // Observação: Configure o SMTP no painel de admin para ativar o envio de emails.
  // Para Gmail: smtp.gmail.com, porta 587, use app password (não password normal)
  // Para Mailtrap: smtp.mailtrap.io, porta 2525 ou 587
];
