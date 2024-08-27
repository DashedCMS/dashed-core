<?php

namespace Dashed\DashedCore\Classes;

use Dashed\DashedCore\Mail\NotificationMail;
use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Support\Facades\Mail;

class Mails
{
    public static function sendNotificationToAdmins($content, $subject = null): void
    {
        if (! $subject) {
            $subject = $content;
        }

        try {
            foreach (self::getAdminNotificationEmails() as $notificationInvoiceEmail) {
                Mail::to($notificationInvoiceEmail)->send(new NotificationMail($content, $subject));
            }
        } catch (\Exception $e) {
        }
    }

    public static function getAdminNotificationEmails(): array
    {
        $emails = Customsetting::get('notification_invoice_emails', Sites::getActive(), '[]');

        if ($emails) {
            return json_decode($emails);
        }

        return [];
    }

    //Todo: move to ecommerce package
    public static function getAdminLowStockNotificationEmails(): array
    {
        $emails = Customsetting::get('notification_low_stock_emails', Sites::getActive(), '[]');

        if ($emails) {
            return json_decode($emails);
        }

        return [];
    }

    public static function getAdminFormInputEmails(): array
    {
        $emails = Customsetting::get('notification_form_inputs_emails', Sites::getActive(), '[]');

        if ($emails) {
            return json_decode($emails);
        }

        return [];
    }
}
