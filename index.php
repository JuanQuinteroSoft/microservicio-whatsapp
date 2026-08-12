<?php

declare(strict_types=1);

/**
 * Punto de entrada principal de la aplicación
 * 
 * @author Juan Quintero
 */

// Cargar variables de entorno
if (file_exists(__DIR__ . '/.env')) {
    $env = file_get_contents(__DIR__ . '/.env');
    foreach (explode("\n", $env) as $line) {
        $line = trim($line);
        if (!empty($line) && !str_starts_with($line, '#')) {
            list($key, $value) = explode('=', $line, 2);
            putenv($key . '=' . trim($value, '\'"'));
        }
    }
}

// Definir la ruta solicitada
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$baseUrl = str_replace('index.php', '', $_SERVER['SCRIPT_NAME']);
$route = str_replace($baseUrl, '', $requestUri);
$route = '/' . ltrim($route, '/');

// Router simple
switch ($route) {
    case '/webhook':
        handleWebhook();
        break;

    case '/send':
        handleSendMessage();
        break;

    case '/':
    case '/health':
        healthCheck();
        break;

    default:
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'Ruta no encontrada: ' . $route,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

/**
 * Maneja el webhook de WhatsApp
 */
function handleWebhook(): void {
    try {
        require_once __DIR__ . '/src/whatsap/WhatsAppWebhook.php';
    } catch (Throwable $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Maneja el envío de mensajes
 */
function handleSendMessage(): void {
    try {
        // Solo POST permitido
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Método no permitido',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            return;
        }

        $payload = json_decode(file_get_contents('php://input'), true);

        if (!isset($payload['to']) || !isset($payload['message'])) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Parámetros requeridos: to, message',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            return;
        }

        require_once __DIR__ . '/src/whatsap/WhatsAppClient.php';
        require_once __DIR__ . '/src/whatsap/WhatsAppController.php';

        $phoneNumberId = getenv('WHATSAPP_PHONE_NUMBER_ID');
        $accessToken = getenv('WHATSAPP_ACCESS_TOKEN');

        if (!$phoneNumberId || !$accessToken) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Variables de entorno no configuradas',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            return;
        }

        $client = new WhatsAppClient($phoneNumberId, $accessToken);
        $controller = new WhatsAppController($client);

        $response = $controller->sendText($payload['to'], $payload['message']);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'data' => $response,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Verifica el estado de la aplicación
 */
function healthCheck(): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'message' => 'PruebaMicroservicio está en línea',
        'timestamp' => date('c'),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
