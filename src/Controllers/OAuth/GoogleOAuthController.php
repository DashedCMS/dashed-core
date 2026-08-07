<?php

namespace Dashed\DashedCore\Controllers\OAuth;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Google\Client as GoogleClient;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Filament\Notifications\Notification;
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

        // CSRF-bescherming: random state meesturen en in de sessie bewaren.
        $state = Str::random(40);
        session()->put('google_oauth_state', $state);
        $client->setState($state);

        return redirect()->away($client->createAuthUrl());
    }

    public function callback(Request $request): RedirectResponse
    {
        // Verifieer de state tegen de sessie om OAuth-CSRF / code-injectie te blokkeren.
        $expectedState = session()->pull('google_oauth_state');
        $state = $request->string('state')->toString();

        if (! $expectedState || ! hash_equals($expectedState, $state)) {
            return redirect('/')->with('error', 'Ongeldige OAuth state.');
        }

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
                ->body(__('OAuth error: :bericht', ['bericht' => $token['error_description'] ?? $token['error']]))
                ->send();

            return redirect(route('filament.dashed.resources.reviews.index'));
        }

        if (! empty($token['refresh_token'])) {
            Customsetting::set('google_oauth_refresh_token', $token['refresh_token']);
        }

        Notification::make()
            ->success()
            ->body(__('Google OAuth gekoppeld ✅'))
            ->send();

        return redirect(route('filament.dashed.resources.reviews.index'));
    }
}
