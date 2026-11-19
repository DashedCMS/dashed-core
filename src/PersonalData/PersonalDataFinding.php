<?php

declare(strict_types=1);

namespace Dashed\DashedCore\PersonalData;

/**
 * Eén regel in het overzicht of het logboek: een bron, hoeveel records, en
 * wat ermee gebeurt (of gebeurd is). Bevat bewust geen persoonsgegevens, want
 * het logboek blijft na verwijdering staan als bewijs.
 */
final class PersonalDataFinding
{
    public const ACTION_DELETE = 'delete';

    public const ACTION_ANONYMIZE = 'anonymize';

    public const ACTION_KEEP = 'keep';

    public const ACTION_UNLINK = 'unlink';

    public function __construct(
        public readonly string $source,
        public readonly string $label,
        public readonly int $count,
        public readonly string $action,
        public readonly ?string $reason = null,
    ) {
    }

    public static function make(string $source, string $label, int $count, string $action, ?string $reason = null): self
    {
        return new self($source, $label, $count, $action, $reason);
    }

    public static function delete(string $source, string $label, int $count, ?string $reason = null): self
    {
        return new self($source, $label, $count, self::ACTION_DELETE, $reason);
    }

    public static function anonymize(string $source, string $label, int $count, ?string $reason = null): self
    {
        return new self($source, $label, $count, self::ACTION_ANONYMIZE, $reason);
    }

    public static function keep(string $source, string $label, int $count, string $reason): self
    {
        return new self($source, $label, $count, self::ACTION_KEEP, $reason);
    }

    public static function unlink(string $source, string $label, int $count, ?string $reason = null): self
    {
        return new self($source, $label, $count, self::ACTION_UNLINK, $reason);
    }

    /** @return array<string, string> */
    public static function actionLabels(): array
    {
        return [
            self::ACTION_DELETE => __('Verwijderen'),
            self::ACTION_ANONYMIZE => __('Anonimiseren'),
            self::ACTION_KEEP => __('Bewaren'),
            self::ACTION_UNLINK => __('Loskoppelen'),
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'label' => $this->label,
            'count' => $this->count,
            'action' => $this->action,
            'reason' => $this->reason,
        ];
    }
}
