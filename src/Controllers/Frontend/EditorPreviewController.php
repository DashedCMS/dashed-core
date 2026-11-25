<?php

namespace Dashed\DashedCore\Controllers\Frontend;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;
use Dashed\DashedCore\Classes\Editor\EditorPreview;

/**
 * Rendert de onopgeslagen staat uit EditorPreview in het thema. Ondertekend
 * en alleen voor de beheerder die de voorvertoning opende, nooit gecachet en
 * altijd noindex. De blokcache staat voor dit verzoek uit, anders toont een
 * blok zijn opgeslagen versie (de sleutel hangt aan updated_at).
 */
class EditorPreviewController extends Controller
{
    public function __invoke(Request $request, string $token): Response
    {
        $user = $request->user();
        $payload = EditorPreview::load($token);

        abort_unless($user && method_exists($user, 'hasPanelAccess') && $user->hasPanelAccess(), 403);
        abort_unless($payload && (string) $payload['user_id'] === (string) $user->getKey(), 404);

        $model = EditorPreview::hydrate($payload);
        abort_unless($model && method_exists($model, 'renderEditorPreview'), 404);

        app()->setLocale($payload['locale']);
        config(['dashed-core.blocks.disable_caching' => true]);
        $request->attributes->set('dashed.editor_preview', true);

        $result = $model->renderEditorPreview();
        abort_if($result === null || is_string($result), 404);

        $response = $result instanceof Response ? $result : response($result);

        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
