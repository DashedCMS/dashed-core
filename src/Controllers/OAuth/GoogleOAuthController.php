<?php

namespace Dashed\DashedCore\Controllers\OAuth;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Google\Client as GoogleClient;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Classes\GoogleOAuthFlows;

class GoogleOAuthController extends Controller
{
    public const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public function redirect(Request $request): RedirectResponse
    {
        $flowKey = $request->string('flow')->toString() ?: 'business';
        $flow = $flowKey === 'business' ? null : GoogleOAuthFlows::get($flowKey);

        abort_if($flowKey !== 'business' && ! $flow, 404);

        $clientId = (string) Customsetting::get('google_oauth_client_id');
        $clientSecret = (string) Customsetting::get('google_oauth_client_secret');

        $client = new GoogleClient();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri(route('google.oauth.callback'));

        $client->setScopes($flow['scopes'] ?? ['https://www.googleapis.com/auth/business.manage']);
        $client->setAccessType('offline');
        $client->setPrompt('consent'); // belangrijk voor refresh_token

        // CSRF-bescherming: random state meesturen en in de sessie bewaren.
        $state = Str::random(40);
        session()->put('google_oauth_state', $state);
        session()->put('google_oauth_flow', $flowKey);
        session()->put('google_oauth_site', $request->string('site')->toString() ?: null);
        $client->setState($state);

        return redirect()->away($client->createAuthUrl());
    }

    public function callback(Request $request): RedirectResponse
    {
        // Verifieer de state tegen de sessie om OAuth-CSRF / code-injectie te blokkeren.
        $expectedState = session()->pull('google_oauth_state');
        $flowKey = session()->pull('google_oauth_flow') ?: 'business';
        $siteId = session()->pull('google_oauth_site');
        $state = $request->string('state')->toString();

        if (! $expectedState || ! hash_equals($expectedState, $state)) {
            return redirect('/')->with('error', 'Ongeldige OAuth state.');
        }

        $code = $request->string('code')->toString();

        if (! $code) {
            return redirect('/')->with('error', 'Geen OAuth code ontvangen.');
        }

        if ($flowKey !== 'business') {
            return $this->callbackForFlow($flowKey, $code, $siteId);
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

    /**
     * Een aangemelde flow (zie GoogleOAuthFlows). De code wordt hier via de
     * HTTP-client ingewisseld in plaats van via de Google-bibliotheek, zodat
     * een test hem met Http::fake() kan nabootsen.
     */
    protected function callbackForFlow(string $flowKey, string $code, ?string $siteId): RedirectResponse
    {
        $flow = GoogleOAuthFlows::get($flowKey);

        abort_if(! $flow, 404);

        $response = Http::asForm()->timeout(30)->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => (string) Customsetting::get('google_oauth_client_id'),
            'client_secret' => (string) Customsetting::get('google_oauth_client_secret'),
            'redirect_uri' => route('google.oauth.callback'),
            'grant_type' => 'authorization_code',
        ]);

        $token = (array) $response->json();

        if ($response->failed() || isset($token['error'])) {
            Notification::make()
                ->danger()
                ->body(__('OAuth error: :bericht', ['bericht' => $token['error_description'] ?? $token['error'] ?? $response->status()]))
                ->send();

            return redirect(($flow['return'])($siteId));
        }

        ($flow['store'])($token, $siteId);

        Notification::make()
            ->success()
            ->body(__('Google-koppeling opgeslagen'))
            ->send();

        return redirect(($flow['return'])($siteId));
    }
}
