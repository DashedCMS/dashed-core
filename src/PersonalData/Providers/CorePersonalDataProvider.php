<?php

declare(strict_types=1);

namespace Dashed\DashedCore\PersonalData\Providers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Database\Query\Builder;
use Dashed\DashedCore\PersonalData\Anonymized;
use Dashed\DashedCore\PersonalData\PersonalDataExport;
use Dashed\DashedCore\PersonalData\PersonalDataFinding;
use Dashed\DashedCore\PersonalData\PersonalDataSubject;
use Dashed\DashedCore\PersonalData\PersonalDataProvider;

/**
 * Het account en wat dashed-core eromheen bewaart: inlogpogingen, verzonden
 * mails, meldingen, sessies, resettokens, API-sleutels en het
 * activiteitenlogboek over het account. Draait als laatste, zodat de andere
 * pakketten hun rijen eerst van het account hebben losgekoppeld.
 *
 * Een beheerdersaccount wordt nooit verwijderd: dat zou iemand uit het CMS
 * sluiten en zijn spoor wissen. Haal eerst de rol weg en doe het verzoek
 * opnieuw.
 */
class CorePersonalDataProvider extends PersonalDataProvider
{
    public const ADMIN_ROLES = ['admin', 'superadmin'];

    public function key(): string
    {
        return 'dashed-core';
    }

    public function label(): string
    {
        return __('Account en systeem');
    }

    public function priority(): int
    {
        return 1000;
    }

    public function find(PersonalDataSubject $subject): array
    {
        [$customers, $admins] = $this->splitUsers($subject);

        $findings = [];

        if ($customers !== []) {
            $findings[] = PersonalDataFinding::delete('users', __('Account'), count($customers), __('Het account wordt verwijderd. Lukt dat niet door een koppeling, dan wordt het geanonimiseerd.'));
        }
        if ($admins !== []) {
            $findings[] = PersonalDataFinding::keep('users_admin', __('Beheerdersaccount'), count($admins), __('Een beheerdersaccount wordt niet automatisch verwijderd. Haal eerst de beheerdersrol weg.'));
        }

        foreach ($this->sources($subject, $customers) as $key => $source) {
            $count = $source['query'] ? (clone $source['query'])->count() : 0;
            if ($count > 0) {
                $findings[] = PersonalDataFinding::delete($key, $source['label'], $count);
            }
        }

        return $findings;
    }

    public function export(PersonalDataSubject $subject, PersonalDataExport $export): void
    {
        $userModel = config('auth.providers.users.model') ?: \Dashed\DashedCore\Models\User::class;

        if ($subject->hasUsers()) {
            // Via het model, zodat $hidden (wachtwoord, MFA-geheimen) wegblijft.
            $export->add('account', $userModel::query()->whereIn('id', $subject->userIds())->get()
                ->map(fn ($user) => array_diff_key($user->toArray(), array_flip(['password', 'remember_token', 'app_authentication_secret', 'app_authentication_recovery_codes', 'roles']))));
        }

        foreach ($this->sources($subject, $subject->userIds()) as $key => $source) {
            if ($source['query'] && ! ($source['exportable'] ?? true)) {
                continue;
            }
            if ($source['query']) {
                $export->add($key, $this->rows(clone $source['query'], $source['without'] ?? []));
            }
        }
    }

    public function erase(PersonalDataSubject $subject): array
    {
        [$customers, $admins] = $this->splitUsers($subject);
        $findings = [];

        // Eerst de rijen rond het account, dan het account zelf.
        foreach ($this->sources($subject, $customers) as $key => $source) {
            if (! $source['query']) {
                continue;
            }
            $count = (clone $source['query'])->delete();
            if ($count > 0) {
                $findings[] = PersonalDataFinding::delete($key, $source['label'], $count);
            }
        }

        $deleted = 0;
        $anonymized = 0;
        foreach ($customers as $userId) {
            foreach ($this->pivotTables() as $table => $column) {
                DB::table($table)->where($column, $userId)->delete();
            }

            try {
                // Een savepoint, zodat een geweigerde delete de transactie
                // van de runner niet meesleept.
                DB::transaction(fn () => DB::table('users')->where('id', $userId)->delete());
                $deleted++;
            } catch (QueryException) {
                // Een tabel van een pakket dat hier geen provider voor heeft
                // houdt het account met een foreign key vast. Dan blijft de
                // rij staan, maar zonder iets dat naar de persoon wijst.
                DB::table('users')->where('id', $userId)->update($this->anonymousUser($userId));
                $anonymized++;
            }
        }

        if ($deleted > 0) {
            $findings[] = PersonalDataFinding::delete('users', __('Account'), $deleted);
        }
        if ($anonymized > 0) {
            $findings[] = PersonalDataFinding::anonymize('users', __('Account'), $anonymized, __('Verwijderen kon niet door een koppeling in een andere tabel.'));
        }
        if ($admins !== []) {
            $findings[] = PersonalDataFinding::keep('users_admin', __('Beheerdersaccount'), count($admins), __('Een beheerdersaccount wordt niet automatisch verwijderd. Haal eerst de beheerdersrol weg.'));
        }

        return $findings;
    }

    /**
     * @param  array<int, int>  $userIds
     * @return array<string, array{label: string, query: ?Builder, without?: array<int, string>, exportable?: bool}>
     */
    protected function sources(PersonalDataSubject $subject, array $userIds): array
    {
        $userModel = config('auth.providers.users.model') ?: \Dashed\DashedCore\Models\User::class;
        $morphTypes = array_values(array_unique([$userModel, (new $userModel())->getMorphClass(), \Dashed\DashedCore\Models\User::class]));

        return [
            'login_attempts' => [
                'label' => __('Inlogpogingen'),
                'query' => $this->hasTable('dashed__login_attempts')
                    ? $subject->whereEmailOrUser(DB::table('dashed__login_attempts'), 'email', 'user_id')
                    : null,
            ],
            'sent_emails' => [
                'label' => __('Verzonden e-mails'),
                'query' => $this->hasTable('dashed__sent_emails')
                    ? $subject->whereEmail(DB::table('dashed__sent_emails'), 'to_email')
                    : null,
                'without' => ['html_body', 'message_id', 'tracking_id', 'site_id'],
            ],
            'notifications' => [
                'label' => __('Meldingen'),
                'query' => $this->hasTable('notifications') && $userIds !== []
                    ? DB::table('notifications')->whereIn('notifiable_type', $morphTypes)->whereIn('notifiable_id', $userIds)
                    : null,
            ],
            'activity_log' => [
                'label' => __('Activiteitenlogboek over het account'),
                'query' => $this->hasTable('activity_log') && $userIds !== []
                    ? DB::table('activity_log')->whereIn('subject_type', $morphTypes)->whereIn('subject_id', $userIds)
                    : null,
            ],
            'sessions' => [
                'label' => __('Sessies'),
                'query' => $this->hasTable('sessions') && $userIds !== []
                    ? DB::table('sessions')->whereIn('user_id', $userIds)
                    : null,
                'exportable' => false,
            ],
            'password_reset_tokens' => [
                'label' => __('Wachtwoordherstel'),
                'query' => $this->hasTable('password_reset_tokens')
                    ? $subject->whereEmail(DB::table('password_reset_tokens'), 'email')
                    : null,
                'exportable' => false,
            ],
            'personal_access_tokens' => [
                'label' => __('API-sleutels'),
                'query' => $this->hasTable('personal_access_tokens') && $userIds !== []
                    ? DB::table('personal_access_tokens')->whereIn('tokenable_type', $morphTypes)->whereIn('tokenable_id', $userIds)
                    : null,
                'without' => ['token'],
            ],
        ];
    }

    /**
     * @return array{0: array<int, int>, 1: array<int, int>}
     */
    protected function splitUsers(PersonalDataSubject $subject): array
    {
        if (! $subject->hasUsers()) {
            return [[], []];
        }

        $roles = DB::table('users')->whereIn('id', $subject->userIds())->pluck('role', 'id');
        $admins = $roles->filter(fn ($role) => in_array($role, self::ADMIN_ROLES, true))->keys()->map(fn ($id) => (int) $id)->all();
        $customers = array_values(array_diff($subject->userIds(), $admins));

        return [$customers, $admins];
    }

    /**
     * Koppeltabellen naar rollen; die gaan met het account mee.
     *
     * @return array<string, string>
     */
    protected function pivotTables(): array
    {
        return array_filter([
            'dashed__model_has_roles' => $this->hasTable('dashed__model_has_roles') ? 'user_id' : null,
        ]);
    }

    /** @return array<string, mixed> */
    protected function anonymousUser(int $userId): array
    {
        $columns = array_flip(\Illuminate\Support\Facades\Schema::getColumnListing('users'));
        $values = [
            'email' => Anonymized::email($userId),
            'name' => Anonymized::NAME,
            'first_name' => null,
            'last_name' => null,
            'password' => bcrypt(Str::random(40)),
            'remember_token' => null,
            'role' => 'customer',
            'phone_number' => null,
            'date_of_birth' => null,
            'gender' => null,
            'marketing' => 0,
            'street' => null,
            'house_nr' => null,
            'zip_code' => null,
            'city' => null,
            'country' => null,
            'company_name' => null,
            'company' => null,
            'btw_id' => null,
            'password_reset_token' => null,
            'tax_id' => null,
            'invoice_street' => null,
            'invoice_house_nr' => null,
            'invoice_zip_code' => null,
            'invoice_city' => null,
            'invoice_country' => null,
            'app_authentication_secret' => null,
            'app_authentication_recovery_codes' => null,
        ];

        return array_intersect_key($values, $columns);
    }
}
