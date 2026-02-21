<?php

/**
 * Simple SMTP Class - Envio de emails via SMTP com suporte SSL/TLS
 */

class SMTP
{
    private $socket;
    private $host;
    private $port;
    private $username;
    private $password;
    private $from;
    private $errors = [];
    private $connected = false;

    /**
     * Construtor
     */
    public function __construct($host, $port, $username, $password, $from = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->from = $from ?: $username;
    }

    /**
     * Conectar ao servidor SMTP
     */
    public function connect()
    {
        // Usar ssl:// para porta 465, tcp:// para outras portas
        $protocol = ($this->port == 465) ? 'ssl' : 'tcp';

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $this->socket = @stream_socket_client(
            $protocol . '://' . $this->host . ':' . $this->port,
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$this->socket) {
            $this->errors[] = "Falha ao conectar: $errstr ($errno)";
            return false;
        }

        // Configurar timeout
        stream_set_timeout($this->socket, 30);

        $response = fgets($this->socket, 515);
        if (substr($response, 0, 3) != '220') {
            $this->errors[] = "Resposta inválida do servidor: " . trim($response);
            return false;
        }

        $this->connected = true;
        return true;
    }

    /**
     * Ler resposta multilinha
     */
    private function getResponse()
    {
        $response = '';
        $line = fgets($this->socket, 515);
        while ($line) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
            $line = fgets($this->socket, 515);
        }
        return $response;
    }

    /**
     * Enviar comando e obter resposta
     */
    private function sendCommand($command)
    {
        fwrite($this->socket, $command . "\r\n");
        return $this->getResponse();
    }

    /**
     * Enviar email
     */
    public function sendMail($to, $subject, $body, $headers)
    {
        if (!$this->connected) {
            if (!$this->connect()) {
                return false;
            }
        }

        // EHLO
        $response = $this->sendCommand("EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

        // Se EHLO falhar, tentar HELO
        if (substr($response, 0, 3) != '250') {
            $response = $this->sendCommand("HELO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
            if (substr($response, 0, 3) != '250') {
                $this->errors[] = "HELO falhou: " . trim($response);
                return false;
            }
        }

        // AUTH LOGIN
        $response = $this->sendCommand("AUTH LOGIN");
        if (substr($response, 0, 3) != '334') {
            $this->errors[] = "AUTH LOGIN falhou: " . trim($response);
            return false;
        }

        // Username (base64)
        $response = $this->sendCommand(base64_encode($this->username));
        if (substr($response, 0, 3) != '334') {
            $this->errors[] = "Username inválido: " . trim($response);
            return false;
        }

        // Password (base64)
        $response = $this->sendCommand(base64_encode($this->password));
        if (substr($response, 0, 3) != '235') {
            $this->errors[] = "Password inválida: " . trim($response);
            return false;
        }

        // MAIL FROM
        $response = $this->sendCommand("MAIL FROM:<" . $this->from . ">");
        if (substr($response, 0, 3) != '250') {
            $this->errors[] = "MAIL FROM falhou: " . trim($response);
            return false;
        }

        // RCPT TO
        $response = $this->sendCommand("RCPT TO:<" . $to . ">");
        if (substr($response, 0, 3) != '250') {
            $this->errors[] = "RCPT TO falhou: " . trim($response);
            return false;
        }

        // DATA
        $response = $this->sendCommand("DATA");
        if (substr($response, 0, 3) != '354') {
            $this->errors[] = "DATA falhou: " . trim($response);
            return false;
        }

        // Construir mensagem
        $message = $headers . "\r\n";
        $message .= "Subject: " . $subject . "\r\n";
        $message .= "\r\n";
        $message .= $body . "\r\n";
        $message .= ".";

        // Enviar mensagem
        $response = $this->sendCommand($message);
        if (substr($response, 0, 3) != '250') {
            $this->errors[] = "Envio falhou: " . trim($response);
            return false;
        }

        // QUIT
        $this->sendCommand("QUIT");

        return true;
    }

    /**
     * Fechar conexão
     */
    public function close()
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
            $this->connected = false;
        }
    }

    /**
     * Obter erros
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
