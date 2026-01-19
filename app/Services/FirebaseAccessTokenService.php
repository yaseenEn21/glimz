<?php

namespace App\Services;

use Google\Client;

class FirebaseAccessTokenService
{
    public static function make(): string
    {
        $path = config('services.fcm.service_account');

        if (! is_string($path) || ! is_file($path)) {
            throw new \RuntimeException("FCM service account file not found: " . (string) $path);
        }

        $client = new Client();
        $client->setAuthConfig($path);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $token = $client->fetchAccessTokenWithAssertion();

        if (! isset($token['access_token'])) {
            throw new \RuntimeException('Failed to fetch Firebase access token: ' . json_encode($token));
        }

        return $token['access_token'];
    }
}
