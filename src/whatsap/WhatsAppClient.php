<?php

declare(strict_types=1);

/**
 * Cliente para WhatsApp Cloud API de Meta.
 *
 * Se encarga exclusivamente de realizar peticiones
 * hacia la API de WhatsApp.
 *
 * @author Juan Quintero
 */
class WhatsAppClient {

    private const API_VERSION = 'v26.0';
    private const BASE_URL = 'https://graph.facebook.com';

    private string $phoneNumberId;
    private string $accessToken;

    public function __construct(
            string $phoneNumberId,
            string $accessToken
    ) {

        $this->phoneNumberId = $phoneNumberId;
        $this->accessToken = $accessToken;
    }

    /**
     * Envía un mensaje de texto.
     */
    public function sendText(
            string $to,
            string $message
    ): array {
        return $this->request('POST', '/messages', [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'text',
                    'text' => [
                        'body' => $message,
                    ],
        ]);
    }

    /**
     * Envía una plantilla de WhatsApp.
     */
    public function sendTemplate(
            string $to,
            string $templateName,
            string $languageCode = 'es'
    ): array {
        return $this->request('POST', '/messages', [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'template',
                    'template' => [
                        'name' => $templateName,
                        'language' => [
                            'code' => $languageCode,
                        ],
                    ],
        ]);
    }

    /**
     * Envía una plantilla con parámetros.
     *
     * Ejemplo:
     *
     * [
     *     'Juan',
     *     '12345'
     * ]
     */
    public function sendTemplateWithParameters(
            string $to,
            string $templateName,
            array $parameters,
            string $languageCode = 'es'
    ): array {
        $bodyParameters = [];

        foreach ($parameters as $parameter) {
            $bodyParameters[] = [
                'type' => 'text',
                'text' => (string) $parameter,
            ];
        }

        return $this->request('POST', '/messages', [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'template',
                    'template' => [
                        'name' => $templateName,
                        'language' => [
                            'code' => $languageCode,
                        ],
                        'components' => [
                            [
                                'type' => 'body',
                                'parameters' => $bodyParameters,
                            ],
                        ],
                    ],
        ]);
    }

    /**
     * Realiza una petición a WhatsApp Cloud API.
     */
    private function request(string $method, string $endpoint, array $data = []): array {
        $url = self::BASE_URL . '/' . self::API_VERSION . '/' . $this->phoneNumberId . $endpoint;

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode(
                    $data,
                    JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            ),
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);

            curl_close($ch);

            throw new RuntimeException(
                            'Error de conexión con WhatsApp: ' . $error
                    );
        }

        $statusCode = curl_getinfo(
                $ch,
                CURLINFO_HTTP_CODE
        );

        curl_close($ch);

        $decodedResponse = json_decode(
                $response,
                true
        );

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException(
                            'WhatsApp API respondió con HTTP '
                            . $statusCode
                            . ': '
                            . $response
                    );
        }

        return [
            'status' => $statusCode,
            'data' => $decodedResponse,
        ];
    }
}
