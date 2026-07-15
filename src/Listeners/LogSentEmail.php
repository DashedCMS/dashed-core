<?php

namespace Dashed\DashedCore\Listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Database\Eloquent\Model;
use Dashed\DashedCore\Models\SentEmail;
use Symfony\Component\Mime\Address;

/**
 * Logt elke door de CMS verzonden mail centraal in dashed__sent_emails.
 * Vangt op MessageSent, dus elke Mailable wordt automatisch meegenomen
 * zonder aanpassing. Loggen mag verzenden nooit laten falen.
 */
class LogSentEmail
{
    public function handle(MessageSent $event): void
    {
        try {
            if (! config('dashed-core.sent_emails.enabled', true)) {
                return;
            }

            $message = $event->message;

            $to = $this->addresses($message->getTo());
            $cc = $this->addresses($message->getCc());
            $bcc = $this->addresses($message->getBcc());
            $from = $this->addresses($message->getFrom());

            [$subjectType, $subjectId] = $this->resolveSubject($event->data);

            SentEmail::create([
                'site_id' => $this->currentSiteId(),
                'message_id' => $event->sent?->getMessageId(),
                'mailable_class' => is_object($event->data['__laravel_mailable'] ?? null)
                    ? get_class($event->data['__laravel_mailable'])
                    : ($event->data['__laravel_mailable'] ?? null),
                'from_email' => $from[0]['email'] ?? null,
                'from_name' => $from[0]['name'] ?? null,
                'to_email' => $to[0]['email'] ?? null,
                'recipients' => [
                    'to' => array_column($to, 'email'),
                    'cc' => array_column($cc, 'email'),
                    'bcc' => array_column($bcc, 'email'),
                ],
                'subject' => (string) ($message->getSubject() ?? ''),
                'html_body' => $message->getHtmlBody(),
                'text_body' => $message->getTextBody(),
                'attachments' => $this->attachments($message),
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'status' => SentEmail::STATUS_SENT,
            ]);
        } catch (\Throwable $e) {
            Log::error('LogSentEmail kon de verzonden mail niet loggen: ' . $e->getMessage());
        }
    }

    /** @return array<int, array{email: string, name: ?string}> */
    protected function addresses(array $addresses): array
    {
        return array_map(fn (Address $a) => [
            'email' => $a->getAddress(),
            'name' => $a->getName() ?: null,
        ], $addresses);
    }

    /** @return array<int, array{filename: ?string, mime: string, size: int}> */
    protected function attachments(\Symfony\Component\Mime\Email $message): array
    {
        $result = [];

        foreach ($message->getAttachments() as $part) {
            $result[] = [
                'filename' => $part->getFilename(),
                'mime' => $part->getMediaType() . '/' . $part->getMediaSubtype(),
                'size' => strlen($part->getBody()),
            ];
        }

        return $result;
    }

    /**
     * Zoek generiek het eerste Eloquent-model in de mail-viewdata voor de
     * morph-koppeling. Houdt dashed-core losgekoppeld van Order e.d.
     *
     * @return array{0: ?string, 1: ?int}
     */
    protected function resolveSubject(array $data): array
    {
        foreach ($data as $value) {
            if ($value instanceof Model && $value->getKey()) {
                return [$value->getMorphClass(), $value->getKey()];
            }
        }

        return [null, null];
    }

    protected function currentSiteId(): ?int
    {
        try {
            $id = config('dashed-core.site_id');

            return $id !== null ? (int) $id : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
