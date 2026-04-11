<?php

namespace Dashed\DashedCore\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Mail\Concerns\HasEmailTemplate;
use Dashed\DashedCore\Mail\Contracts\RegistersEmailTemplate;

class NotificationMail extends Mailable implements RegistersEmailTemplate
{
    use HasEmailTemplate;
    use Queueable;
    use SerializesModels;

    public string $notification;

    public string $mailSubject;

    public function __construct(string $notification, string $subject)
    {
        $this->notification = $notification;
        $this->mailSubject = $subject;
    }

    public static function emailTemplateName(): string
    {
        return 'Generieke notificatie';
    }

    public static function emailTemplateDescription(): ?string
    {
        return 'Generieke notificatiemail die systeembrede berichten stuurt.';
    }

    public static function availableVariables(): array
    {
        return ['notification', 'siteName', 'primaryColor'];
    }

    public static function defaultSubject(): string
    {
        return ':siteName: notificatie';
    }

    public static function defaultBlocks(): array
    {
        return [
            ['type' => 'heading', 'data' => ['text' => 'Een bericht van :siteName:', 'level' => 'h1']],
            ['type' => 'text', 'data' => ['body' => '<p>:notification:</p>']],
        ];
    }

    public static function sampleData(): array
    {
        return [
            'notification' => 'Dit is een voorbeeld van een systeemnotificatie.',
            'siteName' => Customsetting::get('site_name'),
        ];
    }

    public static function makeForTest(): ?self
    {
        return new self('Dit is een testnotificatie.', 'Test notificatie');
    }

    public function build()
    {
        $context = [
            'notification' => $this->notification,
            'siteName' => Customsetting::get('site_name'),
        ];

        $templateHtml = $this->renderFromTemplate($context);

        if ($templateHtml !== null) {
            [$fromEmail, $fromName] = $this->templateFrom(Customsetting::get('site_from_email'), Customsetting::get('site_name'));

            return $this->html($templateHtml)
                ->from($fromEmail, $fromName)
                ->subject($this->templateSubject($this->mailSubject, $context));
        }

        $view = view()->exists(config('dashed-core.site_theme', 'dashed') . '.emails.notification')
            ? config('dashed-core.site_theme', 'dashed') . '.emails.notification'
            : 'dashed-core::emails.notification';

        return $this->view($view)
            ->from(Customsetting::get('site_from_email'), Customsetting::get('site_name'))
            ->subject($this->mailSubject)
            ->with([
                'notification' => $this->notification,
            ]);
    }
}
