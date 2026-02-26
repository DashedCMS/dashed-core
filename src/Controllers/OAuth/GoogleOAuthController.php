<?php

namespace Dashed\DashedCore\Controllers\OAuth;

use Illuminate\Http\Request;
use Google\Client as GoogleClient;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Dashed\DashedCore\Models\Customsetting;

class GoogleOAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        $clientId = (string) Customsetting::get('google_oauth_client_id');
        $clientSecret = (string) Customsetting::get('google_oauth_client_secret');

        $client = new GoogleClient();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri(route('google.oauth.callback'));

        $client->setScopes(['https://www.googleapis.com/auth/business.manage']);
        $client->setAccessType('offline');
        $client->setPrompt('consent'); // belangrijk voor refresh_token

        return redirect()->away($client->createAuthUrl());
    }

    public function callback(Request $request): RedirectResponse
    {
        $code = $request->string('code')->toString();

        if (! $code) {
            return redirect('/')->with('error', 'Geen OAuth code ontvangen.');
        }

        $clientId = (string) Customsetting::get('google_oauth_client_id');
        $clientSecret = (string) Customsetting::get('google_oauth_client_secret');
        $redirectUri = (string) Customsetting::get('google_oauth_redirect_uri');

        $client = new GoogleClient();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);

        $client->setScopes(['https://www.googleapis.com/auth/business.manage']);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            return redirect('/')->with('error', 'OAuth error: ' . ($token['error_description'] ?? $token['error']));
        }

        // refresh_token komt vaak alleen de eerste keer (of met prompt=consent)
        if (! empty($token['refresh_token'])) {
            Customsetting::set('google_oauth_refresh_token', $token['refresh_token']);
        }

        return redirect('/')->with('success', 'Google OAuth gekoppeld ✅');
    }
}
