<?php

namespace Dashed\DashedCore\Database\Migrations;

use Illuminate\Support\Facades\DB;

class WrapEmailTemplateTranslations
{
    public static function wrapExisting(string $defaultLocale): void
    {
        DB::table('dashed__email_templates')->orderBy('id')->lazy()->each(function ($row) use ($defaultLocale) {
            $updates = [];

            if (! blank($row->subject) && ! self::isJsonLocaleMap($row->subject)) {
                $updates['subject'] = json_encode([$defaultLocale => $row->subject]);
            }

            if (! blank($row->from_name) && ! self::isJsonLocaleMap($row->from_name)) {
                $updates['from_name'] = json_encode([$defaultLocale => $row->from_name]);
            }

            if (! blank($row->blocks)) {
                $decoded = json_decode($row->blocks, true);
                if (is_array($decoded) && array_is_list($decoded)) {
                    $updates['blocks'] = json_encode([$defaultLocale => $decoded]);
                }
            }

            if (! empty($updates)) {
                DB::table('dashed__email_templates')->where('id', $row->id)->update($updates);
            }
        });
    }

    private static function isJsonLocaleMap(string $value): bool
    {
        $decoded = json_decode($value, true);
        if (! is_array($decoded)) {
            return false;
        }
        foreach (array_keys($decoded) as $key) {
            if (! is_string($key) || ! preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $key)) {
                return false;
            }
        }

        return true;
    }
}
