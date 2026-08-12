<?php

declare(strict_types=1);

/**
 * Webhook para WhatsApp Cloud API de Meta.
 *
 * @author Juan Quintero
 */
class WhatsAppWebhook {

    private const LOG_FILE = __DIR__ . '/../../storage/whatsapp_webhook.log';

    /**
     * Punto de entrada del webhook.
     */
    public function handle(): void {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
          error_log('Debug: ' . $_SERVER['REQUEST_METHOD']);
error_log('Datos: ' . json_encode($_GET ?? []));
//        $this->saveWebhookRequest();
        switch ($method) {
            case 'GET':
                $this->verifyWebhook();
                break;

            case 'POST':
                $this->handleIncomingMessage();
                break;

            default:
                http_response_code(405);
                echo 'Method Not Allowed';
        }
    }

    private function getVerifyToken(): string {
        return getenv('WHATSAPP_VERIFY_TOKEN') ?: '';
    }

    private function getAppSecret(): string {
        return getenv('WHATSAPP_APP_SECRET') ?: '';
    }

    /**
     * Valida la firma de Meta (X-Hub-Signature-256)
     */
    private function verifySignature(string $body): bool {
        $secret = $this->getAppSecret();
        
        if (empty($secret)) {
            // Si no está configurado, permitir (útil para desarrollo)
            return true;
        }

        $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
        
        if (empty($signature)) {
            return false;
        }

        // Meta envía: sha256=hash
        if (!str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $hash = substr($signature, 7);
        $expectedHash = hash_hmac('sha256', $body, $secret);

        return hash_equals($hash, $expectedHash);
    }
    private function verifyWebhook(): void {
        $mode = $_GET['hub_mode'] ?? '';
        $verifyToken = $_GET['hub_verify_token'] ?? '';
        $challenge = $_GET['hub_challenge'] ?? '';

        if (
                $mode === 'subscribe' && hash_equals($this->getVerifyToken(), $verifyToken)
        ) {
            http_response_code(200);
            echo $challenge;
            return;
        }

        http_response_code(403);
        echo 'Forbidden';
    }

    /**
     * Procesa los eventos enviados por Meta.
     */
    private function handleIncomingMessage(): void {
        $rawData = file_get_contents('php://input');
            error_log('=== WEBHOOK POST RECIBIDO ===');
            error_log('Raw Data: ' . substr($rawData, 0, 500));
            error_log('Headers: ' . json_encode(getallheaders()));

        if ($rawData === false || trim($rawData) === '') {
            error_log('Error: Request body vacío');
            http_response_code(400);
            echo 'Empty request body';
            return;
        }

        // Validar firma de Meta
        if (!$this->verifySignature($rawData)) {
            error_log('Error: Firma inválida');
            http_response_code(403);
            echo 'Forbidden - Invalid signature';
            return;
        }

        error_log('Firma válida - Procesando datos');

        $data = json_decode(
                $rawData,
                true
        );

        if (
                json_last_error() !== JSON_ERROR_NONE || !is_array($data)
        ) {
            error_log('Error: JSON inválido - ' . json_last_error_msg());
            http_response_code(400);
            echo 'Invalid JSON';
            return;
        }

        error_log('Data decodificada: ' . json_encode($data, JSON_PRETTY_PRINT));

//        $this->saveWebhookEvent($data);

        $messages = $this->extractMessages($data);

        error_log('Mensajes extraídos: ' . count($messages));

        foreach ($messages as $message) {
            error_log('Procesando mensaje: ' . json_encode($message));
            $this->processMessage($message);
        }

        http_response_code(200);
        echo 'EVENT_RECEIVED';
    }

    /**
     * Extrae los mensajes recibidos desde el evento de Meta.
     */
    private function extractMessages(array $data): array {
        return $data['entry'][0]['changes'][0]['value']['messages'] ?? [];
    }

    /**
     * Procesa un mensaje individual.
     */
    private function processMessage(array $message): void {
        $from = $message['from'] ?? null;
        $type = $message['type'] ?? null;

        if (!$from || !$type) {
            return;
        }

        $messageData = [
            'from' => $from,
            'type' => $type,
        ];

        if ($type === 'text') {
            $messageData['text'] = $message['text']['body'] ?? '';
        }

//        $this->saveWebhookEvent([
//            'type' => 'message',
//            'data' => $messageData,
//        ]);
    }

    /**
     * Guarda el evento recibido.
     */
    private function saveWebhookEvent(array $data): void {
        $directory = dirname(self::LOG_FILE);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }


        $log = sprintf(
                "[%s] %s%s",
                date('Y-m-d H:i:s'),
                json_encode(
                        $data,
                        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
                ),
                PHP_EOL
        );

        // Crear el archivo si no existe.
        if (!file_exists(self::LOG_FILE)) {
            touch(self::LOG_FILE);
        }


        file_put_contents(
                self::LOG_FILE,
                $log,
                FILE_APPEND | LOCK_EX
        );
    }

    private function saveWebhookRequest(): void {
        $directory = __DIR__ . '/../../storage/webhooks';

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        // Buscar el siguiente número disponible.
        $files = glob($directory . '/webhook_*.json');

        $nextNumber = 1;

        if ($files !== false && count($files) > 0) {
            $numbers = [];

            foreach ($files as $file) {
                if (preg_match('/webhook_(\d+)\.json$/', $file, $matches)) {
                    $numbers[] = (int) $matches[1];
                }
            }

            if (!empty($numbers)) {
                $nextNumber = max($numbers) + 1;
            }
        }

        $rawBody = file_get_contents('php://input');

        $request = [
            'number' => $nextNumber,
            'date' => date('Y-m-d H:i:s'),
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'uri' => $_SERVER['REQUEST_URI'] ?? null,
            'headers' => $this->getRequestHeaders(),
            'body_raw' => $rawBody,
        ];

        $file = sprintf(
                '%s/webhook_%d.json',
                $directory,
                $nextNumber
        );

        file_put_contents(
                $file,
                json_encode(
                        $request,
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                LOCK_EX
        );
    }

    private function getRequestHeaders(): array {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();

            return is_array($headers) ? $headers : [];
        }

        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace(
                        '_',
                        '-',
                        substr($key, 5)
                );

                $headers[$headerName] = $value;
            }
        }

        return $headers;
    }
}

$webhook = new WhatsAppWebhook();
$webhook->handle();
