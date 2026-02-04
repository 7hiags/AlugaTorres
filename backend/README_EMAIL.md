📬 Guia rápido para configurar envio de emails (modo iniciante - PHP mail())

Resumo: Esta versão usa a função nativa `mail()` do PHP para enviar notificações (jeito mais simples e compatível sem instalar dependências).

Teste rápido:
- Faça uma reserva via UI e verifique a mensagem de confirmação no modal.
- Abra `backend/email_log.txt` para ver o conteúdo do email e o estado do envio (sent/failed).

Nota:
- Em ambientes locais (XAMPP) a função `mail()` muitas vezes não envia emails sem configurar sendmail/SMTP no servidor. Isto é normal; os logs ajudam a validar os conteúdos durante o desenvolvimento.

Se quiser que eu configure envio via SMTP (Mailtrap para testes) mais tarde, diga e eu ajudo passo a passo.