<?php

namespace Dashed\DashedCore\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Dashed\DashedCore\Services\VisualEditor\VisualEditor;
use Dashed\DashedCore\Services\VisualEditor\VisitableBlockContentPatcher;

class VisualEditorController extends Controller
{
    public function save(Request $request, VisualEditor $editor, VisitableBlockContentPatcher $patcher): JsonResponse
    {
        if (! $editor->isAdmin()) {
            abort(403);
        }

        $data = $request->validate([
            'model_type' => ['required', 'string'],
            'model_id' => ['required'],
            'locale' => ['required', 'string'],
            'changes' => ['required', 'array'],
            'changes.*.block' => ['required', 'integer'],
            'changes.*.field' => ['required', 'string'],
            'changes.*.value' => ['present', 'string'],
            'changes.*.fieldtype' => ['required', 'in:text,rich'],
        ]);

        $class = $data['model_type'];
        $allowed = collect(cms()->builder('routeModels'))
            ->pluck('class')
            ->filter()
            ->contains($class);

        if (! $allowed || ! class_exists($class) || ! method_exists($class, 'customBlocks')) {
            abort(422, 'Niet-bewerkbaar modeltype.');
        }

        $model = $class::query()->find($data['model_id']);
        if (! $model || ! $model->customBlocks) {
            abort(404);
        }

        $locale = $data['locale'];
        $blocks = $model->customBlocks->getTranslation('blocks', $locale);
        if (! is_array($blocks)) {
            $blocks = [];
        }

        $sanitizer = new HtmlSanitizer((new HtmlSanitizerConfig())->allowSafeElements());

        foreach ($data['changes'] as $change) {
            $value = $change['fieldtype'] === 'rich'
                ? $sanitizer->sanitize($change['value'])
                : strip_tags($change['value']);

            $blocks = $patcher->patch($blocks, (int) $change['block'], $change['field'], $value);
        }

        $model->customBlocks->setTranslation('blocks', $locale, $blocks)->save();

        if (method_exists($model, 'clearContentBlockCache')) {
            try {
                $model->clearContentBlockCache(collect($blocks)->pluck('type')->filter()->unique()->values()->all());
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
