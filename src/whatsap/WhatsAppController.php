<?php

declare(strict_types=1);

/**
 * Controlador de WhatsApp.
 *
 * @author Juan Quintero
 */
class WhatsAppController {

    private WhatsAppClient $client;

    public function __construct(
            WhatsAppClient $client
    ) {
        $this->client = $client;
    }

    /**
     * Envía un mensaje de texto.
     */
    public function sendText(
            string $to,
            string $message
    ): array {
        return $this->client->sendText(
                        $to,
                        $message
                );
    }

    /**
     * Envía una plantilla.
     */
    public function sendTemplate(
            string $to,
            string $templateName,
            string $languageCode = 'es'
    ): array {
        return $this->client->sendTemplate(
                        $to,
                        $templateName,
                        $languageCode
                );
    }

    /**
     * Envía una plantilla con parámetros.
     */
    public function sendTemplateWithParameters(
            string $to,
            string $templateName,
            array $parameters,
            string $languageCode = 'es'
    ): array {
        return $this->client->sendTemplateWithParameters(
                        $to,
                        $templateName,
                        $parameters,
                        $languageCode
                );
    }
}
