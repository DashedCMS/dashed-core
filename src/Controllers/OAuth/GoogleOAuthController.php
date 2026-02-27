<?php

namespace Dashed\DashedCore\Controllers\OAuth;

use Filament\Notifications\Notification;
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
        $redirectUri = route('google.oauth.callback');

        $client = new GoogleClient();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);

        $client->setScopes(['https://www.googleapis.com/auth/business.manage']);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            Notification::make()
                ->danger()
                ->body('OAuth error: ' . ($token['error_description'] ?? $token['error']))
                ->send();
            return redirect(route('filament.dashed.resources.reviews.index'));
        }

        if (! empty($token['refresh_token'])) {
            Customsetting::set('google_oauth_refresh_token', $token['refresh_token']);
        }

        Notification::make()
            ->success()
            ->body('Google OAuth gekoppeld ✅')
            ->send();
        return redirect(route('filament.dashed.resources.reviews.index'));
    }
}
