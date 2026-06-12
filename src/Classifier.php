<?php

require_once __DIR__ . '/../config.php';

class Classifier
{
    private string $endpoint = 'https://api.anthropic.com/v1/messages';
    private string $model    = 'claude-haiku-4-5';

    public function clasificar(string $origen, string $destino): string
    {
        $prompt = "You classify ride-share trips. Determine if the following trip involves an airport (origin or destination is an airport, terminal, or aerodrome). Reply with ONLY one word.\n\nOrigin: {$origen}\nDestination: {$destino}\n\nReply with: aeropuerto (if airport involved) or estandar (otherwise).";

        $payload = json_encode([
            'model'      => $this->model,
            'max_tokens' => 10,
            'messages'   => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . CLAUDE_API_KEY,
                'anthropic-version: 2023-06-01',
            ],
        ]);

        $response = curl_exec($ch);

        if (!$response) return 'estandar';

        $data = json_decode($response, true);
        $text = strtolower(trim($data['content'][0]['text'] ?? 'estandar'));

        return str_contains($text, 'aeropuerto') ? 'aeropuerto' : 'estandar';
    }
}
