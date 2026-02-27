<?php

namespace Dashed\DashedCore\Classes;

use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Log;
use Dashed\DashedCore\Models\Customsetting;

class GoogleBusinessClient
{
    public function makeClient(): GoogleClient
    {
        $clientId = (string) Customsetting::get('google_oauth_client_id');
        $clientSecret = (string) Customsetting::get('google_oauth_client_secret');
        $redirectUri = route('google.oauth.callback');
        $refreshToken = (string) Customsetting::get('google_oauth_refresh_token');

        if (! $clientId || ! $clientSecret || ! $redirectUri || ! $refreshToken) {
            dd($clientId,$clientSecret,$redirectUri,$refreshToken);
            throw new \RuntimeException('Google OAuth instellingen ontbreken.');
        }

        $client = new GoogleClient();

        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);

        // Dit is de scope die nodig is voor reviews
        $client->setScopes([
            'https://www.googleapis.com/auth/business.manage',
        ]);

        $client->setAccessType('offline');
        $client->setPrompt('consent');

        // 👇 Dit is de magie: refresh token → nieuwe access token
        $token = $client->fetchAccessTokenWithRefreshToken($refreshToken);

        if (isset($token['error'])) {
            Log::error('Google token refresh failed', $token);

            throw new \RuntimeException(
                'Google token refresh failed: ' .
                ($token['error_description'] ?? $token['error'])
            );
        }

        $client->setAccessToken($token);

        return $client;
    }

    public function getAccessToken(): string
    {
        $client = $this->makeClient();

        $accessToken = $client->getAccessToken()['access_token'] ?? null;

        if (! $accessToken) {
            throw new \RuntimeException('Geen access_token ontvangen van Google.');
        }

        return $accessToken;
    }
}
