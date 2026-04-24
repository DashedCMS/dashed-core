<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Repair migration: an earlier migration wrapped existing email-template
 * content under config('app.fallback_locale') (Laravel's default is 'en')
 * even on installs where the actual content language is something else
 * (e.g. 'nl'). This moves those mislabeled translations from the wrong
 * locale to the install's primary locale, only when it's safe.
 */
return new class () extends Migration {
    public function up(): void
    {
        $primary = config('app.locale', 'nl');
        $mislabeled = config('app.fallback_locale', 'en');

        // No-op if primary == mislabeled (fresh installs running the fixed
        // original migration will always have both equal).
        if ($primary === $mislabeled) {
            return;
        }

        DB::table('dashed__email_templates')->orderBy('id')->lazy()->each(function ($row) use ($primary, $mislabeled) {
            $updates = [];

            foreach (['subject', 'from_name', 'blocks'] as $column) {
                $decoded = json_decode($row->{$column} ?? '', true);
                if (! is_array($decoded)) {
                    continue;
                }

                $hasMislabeled = array_key_exists($mislabeled, $decoded) && ! self::isEmpty($decoded[$mislabeled]);
                $hasPrimary = array_key_exists($primary, $decoded) && ! self::isEmpty($decoded[$primary]);

                if ($hasMislabeled && ! $hasPrimary) {
                    $decoded[$primary] = $decoded[$mislabeled];
                    unset($decoded[$mislabeled]);
                    $updates[$column] = json_encode($decoded);
                }
            }

            if (! empty($updates)) {
                DB::table('dashed__email_templates')->where('id', $row->id)->update($updates);
            }
        });
    }

    public function down(): void
    {
        throw new \RuntimeException('Deze migratie is niet omkeerbaar.');
    }

    private static function isEmpty(mixed $value): bool
    {
        if (is_array($value)) {
            return empty($value);
        }

        return blank($value);
    }
};
