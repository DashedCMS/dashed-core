<?php

namespace Dashed\DashedCore\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Dashed\DashedCore\Models\User;
use Illuminate\Queue\SerializesModels;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedTranslations\Models\Translation;
use Dashed\DashedCore\Mail\Concerns\HasEmailTemplate;
use Dashed\DashedCore\Mail\Contracts\RegistersEmailTemplate;

class NewAdminAccountMail extends Mailable implements RegistersEmailTemplate
{
    use HasEmailTemplate;
    use Queueable;
    use SerializesModels;

    public User $user;

    public string $password;

    public function __construct(User $user, string $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    public static function emailTemplateName(): string
    {
        return 'Nieuw admin account';
    }

    public static function emailTemplateDescription(): ?string
    {
        return 'Verzonden naar een nieuwe beheerder met de inloggegevens.';
    }

    public static function availableVariables(): array
    {
        return ['userName', 'userEmail', 'password', 'loginUrl', 'siteName', 'primaryColor'];
    }

    public static function defaultSubject(): string
    {
        return 'Je admin account bij :siteName: is aangemaakt';
    }

    public static function defaultBlocks(): array
    {
        return [
            ['type' => 'heading', 'data' => ['text' => 'Welkom bij :siteName:', 'level' => 'h1']],
            ['type' => 'text', 'data' => ['body' => '<p>Beste :userName:,</p><p>Je admin account is aangemaakt. Hieronder vind je je inloggegevens:</p><p><strong>E-mail:</strong> :userEmail:<br><strong>Wachtwoord:</strong> :password:</p><p>We raden aan om je wachtwoord direct na de eerste keer inloggen te wijzigen.</p>']],
            ['type' => 'button', 'data' => ['label' => 'Inloggen', 'url' => ':loginUrl:', 'background' => ':primaryColor:', 'color' => '#ffffff']],
        ];
    }

    public static function sampleData(): array
    {
        $user = User::query()->latest()->first();

        return [
            'user' => $user,
            'userName' => $user?->name ?? 'Voorbeeldgebruiker',
            'userEmail' => $user?->email ?? 'voorbeeld@example.com',
            'password' => '••••••••',
            'loginUrl' => url('/dashed'),
            'siteName' => Customsetting::get('site_name'),
        ];
    }

    public static function makeForTest(): ?self
    {
        $user = User::query()->latest()->first();

        return $user ? new self($user, 'demo-wachtwoord') : null;
    }

    public function build()
    {
        $context = [
            'user' => $this->user,
            'userName' => $this->user->name,
            'userEmail' => $this->user->email,
            'password' => $this->password,
            'loginUrl' => url('/dashed'),
            'siteName' => Customsetting::get('site_name'),
        ];

        $fallbackSubject = Translation::get('new-admin-account-email-subject', 'login', 'Je admin account bij :siteName: is aangemaakt.', 'text', [
            'siteName' => Customsetting::get('site_name'),
        ]);

        $templateHtml = $this->renderFromTemplate($context);

        if ($templateHtml !== null) {
            [$fromEmail, $fromName] = $this->templateFrom(Customsetting::get('site_from_email'), Customsetting::get('site_name'));

            return $this->html($templateHtml)
                ->from($fromEmail, $fromName)
                ->subject($this->templateSubject($fallbackSubject, $context));
        }

        $view = view()->exists(config('dashed-core.site_theme', 'dashed') . '.emails.new-admin-account')
            ? config('dashed-core.site_theme', 'dashed') . '.emails.new-admin-account'
            : 'dashed-core::emails.new-admin-account';

        return $this->view($view)
            ->from(Customsetting::get('site_from_email'), Customsetting::get('site_name'))
            ->subject($fallbackSubject)
            ->with(['user' => $this->user, 'password' => $this->password]);
    }
}
