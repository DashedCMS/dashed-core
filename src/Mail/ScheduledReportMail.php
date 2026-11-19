<?php

namespace Dashed\DashedCore\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Dashed\DashedCore\Models\Report;
use Dashed\DashedCore\Reports\ReportResult;
use Dashed\DashedCore\Reports\ReportExporter;

/**
 * Een ingepland rapport: de eerste rijen in de mail, het hele rapport als
 * bijlage. Bewust niet via de wachtrij: de uitkomst is al berekend en hoeft
 * niet geserialiseerd te worden.
 */
class ScheduledReportMail extends Mailable
{
    public const PREVIEW_ROWS = 25;

    public function __construct(public Report $report, public ReportResult $result)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Rapport: :naam', ['naam' => $this->report->name]));
    }

    public function content(): Content
    {
        $block = view('dashed-core::reports.mail-block', [
            'report' => $this->report,
            'result' => $this->result,
            'rows' => array_slice($this->result->rows, 0, self::PREVIEW_ROWS),
            'more' => max(0, count($this->result->rows) - self::PREVIEW_ROWS),
        ])->render();

        return new Content(
            view: 'dashed-core::emails.layout',
            with: MailBranding::for() + ['blocks' => [$block]],
        );
    }

    public function attachments(): array
    {
        $format = $this->report->schedule_format ?: 'xlsx';
        $contents = ReportExporter::contents($this->result, $format);
        $filename = ReportExporter::filename($this->report->name, $format);

        return [
            Attachment::fromData(fn () => $contents, $filename)
                ->withMime(str_ends_with($filename, '.xlsx')
                    ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    : 'text/csv'),
        ];
    }
}
