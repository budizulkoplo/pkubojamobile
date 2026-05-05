<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): int
    {
        $tokens = array_values(array_unique(array_filter($tokens)));
        if (empty($tokens)) {
            return 0;
        }

        $sent = 0;
        foreach ($tokens as $token) {
            if ($this->sendToToken($token, $title, $body, $data)) {
                $sent++;
            }
        }

        Log::info('FCM send summary.', [
            'total_tokens' => count($tokens),
            'sent' => $sent,
            'title' => $title,
        ]);

        return $sent;
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        $projectId = config('firebase.project_id');
        $accessToken = $this->getAccessToken();

        if (!$projectId || !$accessToken) {
            Log::warning('FCM is not configured.');
            return false;
        }

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'webpush' => [
                    'fcm_options' => [
                        'link' => $data['url'] ?? url('/dashboard'),
                    ],
                ],
                'data' => collect($data)
                    ->map(fn ($value) => (string) $value)
                    ->all(),
            ],
        ];

        $response = Http::withToken($accessToken)
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

        if ($response->failed()) {
            Log::warning('FCM send failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    protected function getAccessToken(): ?string
    {
        $serviceAccount = $this->getServiceAccount();
        if (!$serviceAccount) {
            return null;
        }

        $now = time();
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claim = $this->base64UrlEncode(json_encode([
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $unsignedJwt = $header . '.' . $claim;
        $signature = '';
        if (! openssl_sign($unsignedJwt, $signature, $serviceAccount['private_key'], OPENSSL_ALGO_SHA256)) {
            Log::warning('FCM JWT signing failed.', [
                'openssl_error' => openssl_error_string(),
            ]);

            return null;
        }

        $jwt = $unsignedJwt . '.' . $this->base64UrlEncode($signature);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if ($response->failed()) {
            Log::warning('FCM access token failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json('access_token');
    }

    protected function getServiceAccount(): ?array
    {
        $jsonPath = config('firebase.service_account_json');
        if ($jsonPath && is_file($jsonPath)) {
            $json = json_decode(file_get_contents($jsonPath), true);
            if (!empty($json['client_email']) && !empty($json['private_key'])) {
                return $json;
            }
        }

        $clientEmail = config('firebase.client_email');
        $privateKey = config('firebase.private_key');

        if (!$clientEmail || !$privateKey) {
            return null;
        }

        return [
            'client_email' => $clientEmail,
            'private_key' => $privateKey,
        ];
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
