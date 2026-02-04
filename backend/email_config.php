<?php
// Configuração mínima de email (apenas PHP mail() — jeito "que o PHP deixa")
// Edite apenas estes valores para definir o remetente padrão
return [
    'from_email' => 'no-reply@alugatorres.local',
    'from_name' => 'AlugaTorres',
    'admin_email' => 'suportealugatorres@gmail.com',
    // Observação: esta implementação usa a função nativa mail() do PHP.
    // Em ambiente local (XAMPP) mail() pode não enviar emails sem configuração adicional.
    // Verifique `backend/email_log.txt` para ver o conteúdo e o estado do envio.
];
