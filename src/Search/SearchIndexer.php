<?php

namespace Dashed\DashedCore\Search;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

class SearchIndexer
{
    public const TABLE = 'dashed__search_index';

    public function index(Model $model): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        $type = $model->getMorphClass();
        $id = $model->getKey();

        if ($id === null) {
            return;
        }

        $now = Carbon::now();
        $rows = [];

        foreach ($model->searchIndexLocales() as $locale) {
            $payload = $model->toSearchIndexArray($locale);
            $text = trim((string) ($payload['text'] ?? ''));
            $keywords = trim((string) ($payload['keywords'] ?? ''));

            if ($text === '' && $keywords === '') {
                continue;
            }

            $rows[] = [
                'searchable_type' => $type,
                'searchable_id' => $id,
                'locale' => $locale,
                'search_text' => $text,
                'keywords' => $keywords !== '' ? $keywords : null,
                'updated_at' => $now,
            ];
        }

        // Verwijder eerst de oude rijen voor dit model, schrijf daarna de nieuwe.
        DB::table(self::TABLE)
            ->where('searchable_type', $type)
            ->where('searchable_id', $id)
            ->delete();

        if ($rows !== []) {
            DB::table(self::TABLE)->insert($rows);
        }
    }

    public function remove(Model $model): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        DB::table(self::TABLE)
            ->where('searchable_type', $model->getMorphClass())
            ->where('searchable_id', $model->getKey())
            ->delete();
    }
}
