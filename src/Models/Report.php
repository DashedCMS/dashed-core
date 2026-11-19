<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Dashed\DashedCore\Reports\ReportRunner;
use Dashed\DashedCore\Reports\ReportResult;

/**
 * Een door een beheerder samengesteld rapport: bron, meetwaarden,
 * groepering, filters en periode, eventueel ingepland als mail.
 */
class Report extends Model
{
    protected $table = 'dashed__reports';

    protected $guarded = [];

    protected $casts = [
        'measures' => 'array',
        'filters' => 'array',
        'recipients' => 'array',
        'date_from' => 'date',
        'date_to' => 'date',
        'last_sent_at' => 'datetime',
    ];

    public const SCHEDULES = ['none', 'daily', 'weekly', 'monthly'];

    public function definition(): array
    {
        return [
            'source' => $this->source,
            'measures' => (array) $this->measures,
            'grouping' => $this->grouping,
            'filters' => (array) $this->filters,
            'period' => $this->period,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ];
    }

    public function run(): ReportResult
    {
        return ReportRunner::run($this->definition());
    }

    /**
     * Of het rapport vandaag verstuurd moet worden: dagelijks elke dag,
     * wekelijks op maandag, maandelijks op de eerste, en nooit twee keer op
     * dezelfde dag.
     */
    public function isDue(?\Illuminate\Support\Carbon $now = null): bool
    {
        $now ??= now();

        if (! in_array($this->schedule, ['daily', 'weekly', 'monthly'], true) || $this->validRecipients() === []) {
            return false;
        }

        if ($this->last_sent_at && $this->last_sent_at->isSameDay($now)) {
            return false;
        }

        return match ($this->schedule) {
            'daily' => true,
            'weekly' => $now->isMonday(),
            'monthly' => $now->day === 1,
        };
    }

    /** @return array<int, string> */
    public function validRecipients(): array
    {
        return array_values(array_unique(array_filter(
            array_map(fn ($email) => trim((string) $email), (array) $this->recipients),
            fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
        )));
    }
}
