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

class PasswordResetMail extends Mailable implements RegistersEmailTemplate
{
    use HasEmailTemplate;
    use Queueable;
    use SerializesModels;

    public User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public static function emailTemplateName(): string
    {
        return 'Wachtwoord resetten';
    }

    public static function emailTemplateDescription(): ?string
    {
        return 'Verzonden naar een gebruiker als er een wachtwoord-reset is aangevraagd.';
    }

    public static function availableVariables(): array
    {
        return ['userName', 'userEmail', 'resetUrl', 'siteName', 'primaryColor'];
    }

    public static function defaultSubject(): string
    {
        return 'Wachtwoord resetten voor :siteName:';
    }

    public static function defaultBlocks(): array
    {
        return [
            ['type' => 'heading', 'data' => ['text' => 'Wachtwoord resetten', 'level' => 'h1']],
            ['type' => 'text', 'data' => ['body' => '<p>Beste :userName:,</p><p>Er is een verzoek ontvangen om je wachtwoord te resetten voor je account bij :siteName:. Klik op onderstaande knop om een nieuw wachtwoord in te stellen.</p>']],
            ['type' => 'button', 'data' => ['label' => 'Wachtwoord resetten', 'url' => ':resetUrl:', 'background' => ':primaryColor:', 'color' => '#ffffff']],
            ['type' => 'divider', 'data' => []],
            ['type' => 'text', 'data' => ['body' => '<p>Heb jij geen wachtwoord-reset aangevraagd? Dan kun je deze e-mail negeren.</p>']],
        ];
    }

    public static function sampleData(): array
    {
        $user = User::query()->latest()->first();

        return [
            'user' => $user,
            'userName' => $user?->name ?? 'Voorbeeldgebruiker',
            'userEmail' => $user?->email ?? 'voorbeeld@example.com',
            'resetUrl' => url('/password/reset'),
            'siteName' => Customsetting::get('site_name'),
        ];
    }

    public static function makeForTest(): ?self
    {
        $user = User::query()->latest()->first();

        return $user ? new self($user) : null;
    }

    public function build()
    {
        $context = [
            'user' => $this->user,
            'userName' => $this->user->name,
            'userEmail' => $this->user->email,
            'resetUrl' => url('/password/reset'),
            'siteName' => Customsetting::get('site_name'),
        ];

        $templateHtml = $this->renderFromTemplate($context);
        $fallbackSubject = Translation::get('password-reset-email-subject', 'login', 'Wachtwoord resetten voor :siteName:', 'text', [
            'siteName' => Customsetting::get('site_name'),
        ]);

        if ($templateHtml !== null) {
            [$fromEmail, $fromName] = $this->templateFrom(Customsetting::get('site_from_email'), Customsetting::get('site_name'));

            return $this->html($templateHtml)
                ->from($fromEmail, $fromName)
                ->subject($this->templateSubject($fallbackSubject, $context));
        }

        $view = view()->exists(config('dashed-core.site_theme', 'dashed') . '.emails.password-reset')
            ? config('dashed-core.site_theme', 'dashed') . '.emails.password-reset'
            : 'dashed-core::emails.password-reset';

        return $this->view($view)
            ->from(Customsetting::get('site_from_email'), Customsetting::get('site_name'))
            ->subject($fallbackSubject)
            ->with(['user' => $this->user]);
    }
}
