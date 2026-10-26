<?php

namespace Dashed\DashedCore\Mail;

use Illuminate\Mail\Mailable;
use Dashed\DashedCore\Models\Reminder;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Filament\Pages\MyTasksPage;

/**
 * Herinnering per mail, alleen voor wie dat bij Mijn taken heeft aangezet.
 * Synchroon vanuit ReminderSender, dat zelf al in de planner draait.
 */
class ReminderMail extends Mailable
{
    public function __construct(public Reminder $reminder)
    {
    }

    public function build(): self
    {
        $creator = $this->reminder->created_by && $this->reminder->created_by !== $this->reminder->user_id ? $this->reminder->creator : null;

        return $this
            ->view('dashed-core::emails.reminder')
            ->subject(__('Herinnering: :label', ['label' => $this->reminder->label]))
            ->from(Customsetting::get('site_from_email'), Customsetting::get('site_name'))
            ->with([
                'label' => $this->reminder->label,
                'note' => $this->reminder->note,
                'recordUrl' => $this->reminder->url,
                'creatorName' => $creator?->name,
                'tasksUrl' => rescue(fn () => MyTasksPage::getUrl(), null, report: false),
                'siteName' => Customsetting::get('site_name'),
            ]);
    }
}
