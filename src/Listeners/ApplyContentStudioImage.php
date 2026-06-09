<?php

namespace Dashed\DashedCore\Listeners;

use Illuminate\Support\Facades\DB;
use Dashed\DashedCore\Models\CustomBlock;
use Dashed\DashedFiles\Events\AiImageOperationCompleted;
use Dashed\DashedCore\Classes\ContentStudio\ContentStudioImagePatcher;

class ApplyContentStudioImage
{
    public function handle(AiImageOperationCompleted $event): void
    {
        $context = $event->operation->context ?? [];

        if (($context['handler'] ?? null) !== 'content_studio_image') {
            return;
        }

        $resultId = $event->operation->result_media_id;
        if (! $resultId) {
            return;
        }

        $customBlockId = $context['custom_block_id'] ?? null;
        $locale = $context['locale'] ?? null;
        $blockKey = $context['block_key'] ?? null;
        $field = $context['field'] ?? null;
        $prompt = $context['prompt'] ?? null;

        if (! $customBlockId || ! $locale || $blockKey === null || ! $field || ! is_string($prompt)) {
            return;
        }

        DB::transaction(function () use ($customBlockId, $locale, $blockKey, $field, $prompt, $resultId) {
            /** @var CustomBlock|null $customBlock */
            $customBlock = CustomBlock::query()->lockForUpdate()->find($customBlockId);
            if (! $customBlock) {
                return;
            }

            $blocks = $customBlock->getTranslation('blocks', $locale);
            if (! is_array($blocks)) {
                return;
            }

            $patched = (new ContentStudioImagePatcher())->patch($blocks, $blockKey, $field, $prompt, (int) $resultId);

            if ($patched !== $blocks) {
                $customBlock->setTranslation('blocks', $locale, $patched)->save();
            }
        });
    }
}
