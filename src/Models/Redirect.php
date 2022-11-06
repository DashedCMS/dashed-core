<?php

namespace Qubiqx\QcommerceCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Redirect extends Model
{
    use SoftDeletes;

    protected $table = 'qcommerce__redirects';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'delete_redirect_after' => 'date',
    ];

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

        $redirect = new Redirect();
        $redirect->from = $oldSlug;
        $redirect->to = $newSlug;
        $redirect->sort = '301';
        $redirect->delete_redirect_after = now()->addMonths(3);
        $redirect->save();

        Redirect::whereColumn('from', 'to')->delete();
        foreach (Redirect::get() as $redirect) {
            if (url($redirect->from) == url($redirect->to)) {
                $redirect->delete();
            }
        }

        $routeModels = cms()->builder('routeModels');

        foreach ($routeModels as $routeModel) {
            if (method_exists($routeModel['routeHandler'], 'replaceInContent')) {
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
