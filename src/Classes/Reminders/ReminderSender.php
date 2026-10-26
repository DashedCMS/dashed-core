<?php

namespace Dashed\DashedCore\Classes\Reminders;

use Dashed\DashedCore\Models\Reminder;
use Illuminate\Support\Facades\Mail;
use Dashed\DashedCore\Mail\ReminderMail;
use Dashed\DashedCore\Events\ReminderDue;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Filament\Pages\MyTasksPage;

/**
 * Laat herinneringen afgaan die aan de beurt zijn, elke minuut vanuit de
 * planner. Een herinnering wordt eerst geclaimd (sent_at gezet met een
 * voorwaardelijke update) en pas daarna verstuurd, zodat twee planners die
 * elkaar overlappen hem niet allebei sturen.
 */
class ReminderSender
{
    public static function sendDue(): int
    {
        $sent = 0;

        Reminder::query()->due()->orderBy('remind_at')->limit(500)->get()->each(function (Reminder $reminder) use (&$sent) {
            $claimed = Reminder::query()->whereKey($reminder->id)->whereNull('sent_at')->update(['sent_at' => now()]);
            if ($claimed === 0) {
                return;
            }

            $reminder->refresh();
            self::send($reminder);
            $sent++;
        });

        return $sent;
    }

    public static function send(Reminder $reminder): void
    {
        $user = $reminder->user;
        if (! $user) {
            return;
        }

        $creator = $reminder->created_by && $reminder->created_by !== $reminder->user_id ? $reminder->creator : null;

        rescue(function () use ($reminder, $user, $creator) {
            Notification::make()
                ->title($creator
                    ? __('Taak van :naam: :label', ['naam' => $creator->name, 'label' => $reminder->label])
                    : __('Herinnering: :label', ['label' => $reminder->label]))
                ->body($reminder->note)
                ->icon('heroicon-o-bell-alert')
                ->warning()
                ->actions(array_filter([
                    $reminder->url ? \Filament\Actions\Action::make('open')->label(__('Openen'))->url($reminder->url)->markAsRead() : null,
                    \Filament\Actions\Action::make('tasks')->label(__('Mijn taken'))->url(rescue(fn () => MyTasksPage::getUrl(), null, report: false))->color('gray'),
                ]))
                ->sendToDatabase($user);
        }, report: true);

        rescue(fn () => event(new ReminderDue($reminder)), report: true);

        if ($user->reminders_by_mail && $user->email) {
            rescue(fn () => Mail::to($user->email)->send(new ReminderMail($reminder)), report: true);
        }
    }
}
