<?php

namespace Dashed\DashedCore\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Blade;
use Dashed\DashedCore\Services\VisualEditor\VisualEditor;

class InjectVisualEditorToolbar
{
    public function __construct(private VisualEditor $editor)
    {
    }

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $contentType = $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $adminPrefix = config('filament-old.path', env('FILAMENT_PATH', 'dashed'));
        if (
            $request->routeIs('filament.*')
            || $request->is($adminPrefix)
            || $request->is($adminPrefix . '/*')
            || $request->is('api/*')
        ) {
            return $response;
        }

        if (! $this->editor->isAdmin()) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content) || ! str_contains($content, '</body>')) {
            return $response;
        }

        $toolbar = Blade::render('dashed-core::visual-editor.toolbar', [
            'active' => $this->editor->isActive(),
        ]);

        $response->setContent(str_replace('</body>', $toolbar . '</body>', $content));

        return $response;
    }
}
