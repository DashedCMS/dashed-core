<?php

namespace Dashed\DashedCore\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Dashed\DashedCore\Models\User;
use Illuminate\Queue\SerializesModels;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedTranslations\Models\Translation;

class PasswordResetMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $view = view()->exists(config('dashed-core.site_theme') . '.emails.password-reset') ? config('dashed-core.site_theme') . '.emails.password-reset' : 'dashed-core::emails.password-reset';

        return $this->view($view)
            ->from(Customsetting::get('site_from_email'), Customsetting::get('site_name'))->subject(Translation::get('password-reset-email-subject', 'login', 'A password reset has been requested for your account at :siteName:', 'text', [
                'siteName' => Customsetting::get('site_name'),
            ]))
            ->with([
                'user' => $this->user,
            ]);
    }
}
