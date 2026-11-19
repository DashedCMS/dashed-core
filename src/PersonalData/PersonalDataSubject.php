<?php

declare(strict_types=1);

namespace Dashed\DashedCore\PersonalData;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Over wie een verzoek gaat: een e-mailadres, plus de accounts die op het
 * moment van starten bij dat adres horen. Die lijst wordt één keer
 * vastgelegd, zodat een provider die na het account aan de beurt is nog
 * steeds weet om welke user_id's het ging.
 */
final class PersonalDataSubject
{
    /** @var array<int, int> */
    private array $userIds;

    /**
     * @param  array<int, int>|null  $userIds
     */
    public function __construct(public readonly string $email, ?array $userIds = null)
    {
        $this->userIds = $userIds ?? self::findUserIds($this->email);
    }

    public static function forEmail(string $email): self
    {
        return new self(self::normalize($email));
    }

    public static function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /**
     * Hash van het adres voor het logboek. HMAC met de app-sleutel, zodat de
     * hash niet met een lijst bekende adressen terug te rekenen is. Na het
     * wisselen van APP_KEY komt een opnieuw berekende hash niet meer overeen.
     */
    public static function hash(string $email): string
    {
        return hash_hmac('sha256', self::normalize($email), (string) config('app.key'));
    }

    /** @return array<int, int> */
    public function userIds(): array
    {
        return $this->userIds;
    }

    public function hasUsers(): bool
    {
        return $this->userIds !== [];
    }

    /**
     * Zoekt hoofdletterongevoelig op een e-mailkolom. MySQL vergelijkt met
     * de standaardcollatie al zonder hoofdletters en houdt zo de index; op
     * andere drivers (SQLite in de tests) gaat het via LOWER().
     */
    public function whereEmail(EloquentBuilder|QueryBuilder $query, string $column, string $boolean = 'and'): EloquentBuilder|QueryBuilder
    {
        $driver = $query->getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            return $query->where($column, '=', $this->email, $boolean);
        }

        $wrapped = $query->getGrammar()->wrap($column);

        return $query->whereRaw("LOWER({$wrapped}) = ?", [$this->email], $boolean);
    }

    /**
     * Rijen die bij dit adres horen via een e-mailkolom en/of een
     * user_id-kolom. Een kolom die niet bestaat wordt overgeslagen.
     */
    public function whereEmailOrUser(EloquentBuilder|QueryBuilder $query, ?string $emailColumn = 'email', ?string $userColumn = 'user_id'): EloquentBuilder|QueryBuilder
    {
        $table = $query instanceof EloquentBuilder ? $query->getModel()->getTable() : $query->from;
        $hasEmail = $emailColumn && Schema::hasColumn($table, $emailColumn);
        $hasUser = $userColumn && $this->userIds !== [] && Schema::hasColumn($table, $userColumn);

        if (! $hasEmail && ! $hasUser) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($query) use ($hasEmail, $hasUser, $emailColumn, $userColumn) {
            if ($hasEmail) {
                $this->whereEmail($query, $emailColumn);
            }
            if ($hasUser) {
                $query->orWhereIn($userColumn, $this->userIds);
            }
        });
    }

    /** @return array<int, int> */
    private static function findUserIds(string $email): array
    {
        if ($email === '' || ! Schema::hasTable('users')) {
            return [];
        }

        $subject = new self($email, []);

        return $subject->whereEmail(DB::table('users'), 'email')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
