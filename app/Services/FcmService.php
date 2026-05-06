<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FcmService
{
    protected string $fcmUrl;

    public function __construct()
    {
        $projectId = config('firebase.project_id');

        $this->fcmUrl = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
    }

    /**
     * Send notification to multiple tokens
     */
    public function sendToTokens(
        array $tokens,
        string $title,
        string $body,
        array $data = []
    ): int {
        $tokens = array_values(array_unique(array_filter($tokens)));

        if (empty($tokens)) {
            return 0;
        }

        $sent = 0;

        foreach ($tokens as $token) {
            try {
                if ($this->sendToToken($token, $title, $body, $data)) {
                    $sent++;
                }
            } catch (Throwable $e) {
                Log::error('FCM send exception.', [
                    'message' => $e->getMessage(),
                    'token' => substr($token, 0, 20) . '...',
                ]);
            }
        }

        Log::info('FCM send summary.', [
            'total_tokens' => count($tokens),
            'success_sent' => $sent,
            'failed' => count($tokens) - $sent,
            'title' => $title,
        ]);

        return $sent;
    }

    /**
     * Send notification to single token
     */
    public function sendToToken(
        string $token,
        string $title,
        string $body,
        array $data = []
    ): bool {
        $projectId = config('firebase.project_id');

        if (!$projectId) {
            Log::warning('Firebase project_id missing.');
            return false;
        }

        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            Log::warning('FCM access token unavailable.');
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
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                        'icon' => asset('/assets/img/icon/logo.png'),
                        'badge' => asset('/assets/img/icon/logo.png'),
                    ],

                    'fcm_options' => [
                        'link' => $data['url'] ?? url('/dashboard'),
                    ],
                ],

                'android' => [
                    'priority' => 'high',
                ],

                'data' => collect($data)
                    ->map(fn ($value) => (string) $value)
                    ->all(),
            ],
        ];

        try {

            $response = Http::withToken($accessToken)
                ->timeout(15)
                ->retry(3, 500)
                ->acceptJson()
                ->post($this->fcmUrl, $payload);

            if ($response->successful()) {

                Log::info('FCM notification sent.', [
                    'title' => $title,
                    'token' => substr($token, 0, 20) . '...',
                ]);

                return true;
            }

            $responseBody = $response->json();

            Log::warning('FCM send failed.', [
                'status' => $response->status(),
                'response' => $responseBody,
            ]);

            $this->handleInvalidToken($token, $responseBody);

            return false;

        } catch (Throwable $e) {

            Log::error('FCM HTTP exception.', [
                'message' => $e->getMessage(),
                'token' => substr($token, 0, 20) . '...',
            ]);

            return false;
        }
    }

    /**
     * Get cached Google access token
     */
    protected function getAccessToken(): ?string
    {
        return Cache::remember('firebase_access_token', 3500, function () {

            $serviceAccount = $this->getServiceAccount();

            if (!$serviceAccount) {
                Log::warning('Firebase service account missing.');
                return null;
            }

            $now = time();

            $header = $this->base64UrlEncode(json_encode([
                'alg' => 'RS256',
                'typ' => 'JWT',
            ]));

            $claim = $this->base64UrlEncode(json_encode([
                'iss' => $serviceAccount['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            $unsignedJwt = $header . '.' . $claim;

            $signature = '';

            $signed = openssl_sign(
                $unsignedJwt,
                $signature,
                $serviceAccount['private_key'],
                OPENSSL_ALGO_SHA256
            );

            if (!$signed) {

                Log::warning('FCM JWT signing failed.', [
                    'openssl_error' => openssl_error_string(),
                ]);

                return null;
            }

            $jwt = $unsignedJwt . '.' . $this->base64UrlEncode($signature);

            $response = Http::asForm()
                ->timeout(15)
                ->retry(3, 500)
                ->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

            if ($response->failed()) {

                Log::warning('Google OAuth token request failed.', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return null;
            }

            return $response->json('access_token');
        });
    }

    /**
     * Remove invalid token from database
     */
    protected function handleInvalidToken(
        string $token,
        array|string|null $response
    ): void {

        $responseString = is_array($response)
            ? json_encode($response)
            : (string) $response;

        $invalidErrors = [
            'UNREGISTERED',
            'INVALID_ARGUMENT',
            'registration-token-not-registered',
        ];

        foreach ($invalidErrors as $error) {

            if (str_contains($responseString, $error)) {

                DB::table('fcm_tokens')
                    ->where('token', $token)
                    ->delete();

                Log::info('Invalid FCM token deleted.', [
                    'token' => substr($token, 0, 20) . '...',
                ]);

                break;
            }
        }
    }

    /**
     * Get Firebase service account
     */
    protected function getServiceAccount(): ?array
    {
        $jsonPath = config('firebase.service_account_json');

        if ($jsonPath && is_file($jsonPath)) {

            $json = json_decode(file_get_contents($jsonPath), true);

            if (
                !empty($json['client_email']) &&
                !empty($json['private_key'])
            ) {
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

    /**
     * Base64 URL encode
     */
    protected function base64UrlEncode(string $value): string
    {
        return rtrim(
            strtr(base64_encode($value), '+/', '-_'),
            '='
        );
    }
}