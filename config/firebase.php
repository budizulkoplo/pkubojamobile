<?php

$firebaseValue = static function (string $key, mixed $default = null): ?string {
    $value = env($key, $default);

    if ($value === null) {
        return null;
    }

    $value = trim((string) $value);

    if ($value === '' || $value === '...') {
        return null;
    }

    return $value;
};

return [
    'project_id' => $firebaseValue('FIREBASE_PROJECT_ID'),
    'client_email' => $firebaseValue('FIREBASE_CLIENT_EMAIL'),
    'private_key' => str_replace('\\n', "\n", (string) $firebaseValue('FIREBASE_PRIVATE_KEY')),
    'service_account_json' => $firebaseValue('FIREBASE_SERVICE_ACCOUNT_JSON'),

    'web' => [
        'apiKey' => $firebaseValue('FIREBASE_WEB_API_KEY'),
        'authDomain' => $firebaseValue('FIREBASE_WEB_AUTH_DOMAIN'),
        'projectId' => $firebaseValue('FIREBASE_WEB_PROJECT_ID', $firebaseValue('FIREBASE_PROJECT_ID')),
        'storageBucket' => $firebaseValue('FIREBASE_WEB_STORAGE_BUCKET'),
        'messagingSenderId' => $firebaseValue('FIREBASE_WEB_MESSAGING_SENDER_ID'),
        'appId' => $firebaseValue('FIREBASE_WEB_APP_ID'),
        'vapidKey' => $firebaseValue('FIREBASE_WEB_VAPID_KEY'),
    ],
];
