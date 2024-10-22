<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Redirect extends Model
{
    use SoftDeletes;

    protected $table = 'dashed__redirects';

    protected $casts = [
        'delete_redirect_after' => 'date',
    ];

    public static function handleSlugChangeForFilamentModel($record, string $locale, string $newSlug)
    {
        app()->setLocale($locale);
        $oldUrl = str($record->getUrl())->replace(url('/'), '')->toString();
        $newUrl = str($oldUrl)->replace($record->getTranslation('slug', $locale), $newSlug)->toString();
        self::handleSlugChange($oldUrl, $newUrl);
    }

    public static function handleSlugChange(?string $oldSlug, ?string $newSlug)
    {
        if ($oldSlug === $newSlug) {
            return;
        }

        if (! str($newSlug)->startsWith('/')) {
            $newSlug = '/' . $newSlug;
        }

        Redirect::where('to', $oldSlug)->update([
            'to' => $newSlug,
        ]);

        if (! Redirect::where('from', $oldSlug)->where('to', $newSlug)->exists()) {
            $redirect = new Redirect();
            $redirect->from = $oldSlug;
            $redirect->to = $newSlug;
            $redirect->sort = '301';
            $redirect->delete_redirect_after = now()->addMonths(3);
            $redirect->save();
        }

        Redirect::whereColumn('from', 'to')->delete();
        foreach (Redirect::get() as $redirect) {
            if (url($redirect->from) == url($redirect->to)) {
                $redirect->delete();
            }
        }

        $routeModels = cms()->builder('routeModels');

        foreach ($routeModels as $routeModel) {
            if (method_exists($routeModel['class'], 'replaceInContent')) {
                $routeModel['class']::replaceInContent([
                    url($oldSlug) => url($newSlug),
                    'href="' . url($oldSlug) . '"' => 'href="' . url($newSlug) . '"',
                    'href="' . $oldSlug . '"' => 'href="' . $newSlug . '"',
                    'href="/' . $oldSlug . '"' => 'href="' . $newSlug . '"',
                ]);
            } elseif (isset($routeModel['routeHandler']) && method_exists($routeModel['routeHandler'], 'replaceInContent')) {
                $routeModel['routeHandler']::replaceInContent([
                    url($oldSlug) => url($newSlug),
                    'href="' . url($oldSlug) . '"' => 'href="' . url($newSlug) . '"',
                    'href="' . $oldSlug . '"' => 'href="' . $newSlug . '"',
                    'href="/' . $oldSlug . '"' => 'href="' . $newSlug . '"',
                ]);
            }
        }
    }
}
