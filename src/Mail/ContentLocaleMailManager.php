<?php

namespace Dashed\DashedCore\Mail;

use Illuminate\Mail\MailManager;

/**
 * Bouwt een ContentLocaleMailer in plaats van een gewone Mailer. build() is
 * een kopie van MailManager::build() in Laravel 12; wijzigt die in een
 * nieuwe Laravel-versie, dan hoort deze kopie mee te gaan.
 */
class ContentLocaleMailManager extends MailManager
{
    public function build($config)
    {
        $mailer = new ContentLocaleMailer(
            $config['name'] ?? 'ondemand',
            $this->app['view'],
            $this->createSymfonyTransport($config),
            $this->app['events']
        );

        if ($this->app->bound('queue')) {
            $mailer->setQueue($this->app['queue']);
        }

        return $mailer;
    }
}
