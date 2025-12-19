<?php

namespace Dashed\DashedCore\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Dashed\DashedCore\Models\User;
use Illuminate\Queue\SerializesModels;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedTranslations\Models\Translation;

class NewAdminAccountMail extends Mailable
{
    use Queueable;
    use SerializesModels;
    public User $user;
    public string $password;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, string $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $view = view()->exists(config('dashed-core.site_theme', 'dashed') . '.emails.new-admin-account') ? config('dashed-core.site_theme', 'dashed') . '.emails.new-admin-account' : 'dashed-core::emails.new-admin-account';

        return $this->view($view)
            ->from(Customsetting::get('site_from_email'), Customsetting::get('site_name'))->subject(Translation::get('new-admin-account-email-subject', 'login', 'Je admin account bij :siteName: is aangemaakt.', 'text', [
                'siteName' => Customsetting::get('site_name'),
            ]))
            ->with([
                'user' => $this->user,
                'password' => $this->password,
            ]);
    }
}
