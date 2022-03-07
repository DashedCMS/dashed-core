<?php

namespace Qubiqx\QcommerceCore\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Qubiqx\QcommerceCore\Models\User;
use Illuminate\Queue\SerializesModels;
use Qubiqx\QcommerceCore\Models\Customsetting;
use Qubiqx\QcommerceTranslations\Models\Translation;

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
        return $this->view('qcommerce-core::emails.password-reset')
            ->from(Customsetting::get('site_from_email'), Customsetting::get('company_name'))->subject(Translation::get('password-reset-email-subject', 'login', 'A password reset has been requested for your account at :siteName:', 'text', [
                'siteName' => Customsetting::get('site_name'),
            ]))
            ->with([
                'user' => $this->user,
            ]);
    }
}
