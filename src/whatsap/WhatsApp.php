<?php

/**
 * Description of WhatsApp
 *
 * @author juan.quintero
 */
declare(strict_types=1);

require_once __DIR__ . '/WhatsAppClient.php';
require_once __DIR__ . '/WhatsAppController.php';

$phoneNumberId = getenv('WHATSAPP_PHONE_NUMBER_ID');
$accessToken = getenv('WHATSAPP_ACCESS_TOKEN');

$client = new WhatsAppClient(
        $phoneNumberId,
        $accessToken
);

$controller = new WhatsAppController($client);

try {
    $response = $controller->sendTemplate(
            '573188888888',
            'hello_world',
            'en_US'
    );

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
            $response,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );
} catch (Throwable $e) {
    http_response_code(500);

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
