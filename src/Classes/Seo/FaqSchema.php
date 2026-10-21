<?php

namespace Dashed\DashedCore\Classes\Seo;

use Spatie\SchemaOrg\Schema;
use Spatie\SchemaOrg\BaseType;
use Dashed\DashedCore\Models\GlobalBlock;

/**
 * One FAQPage for a page, built from the FAQ blocks in its content, in the
 * head. Built in PHP from the model and not pushed from inside the block
 * view, because every block renders inside @cache: a schema pushed from
 * the view is lost on the first cache hit. Two FAQ blocks on one page give
 * one FAQPage with all questions; Google wants one per page.
 */
class FaqSchema
{
    public static function for(object $model): ?BaseType
    {
        $content = method_exists($model, 'getAttribute') ? $model->getAttribute('content') : ($model->content ?? null);

        if (! is_array($content)) {
            return null;
        }

        $questions = static::questions($content);

        if ($questions === []) {
            return null;
        }

        return Schema::fAQPage()->mainEntity(array_map(
            fn (array $item) => Schema::question()
                ->name($item['question'])
                ->acceptedAnswer(Schema::answer()->text($item['answer'])),
            $questions,
        ));
    }

    /**
     * The question/answer pairs in a builder content array, global blocks
     * included. Two shapes are in use: the core block (`faqs[]` with
     * `title`/`content`) and the project block (`questions[]` with
     * `question`/`description`); the AI writers in dashed-marketing write
     * both at once. An item without a question or without an answer is
     * skipped: an empty Answer is a validation error in the rich result.
     *
     * @return array<int, array{question: string, answer: string}>
     */
    public static function questions(array $blocks, int $depth = 0): array
    {
        $items = [];

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }

            $type = $block['type'] ?? null;
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];

            if ($type === 'globalBlock' && $depth < 3) {
                $global = GlobalBlock::find($data['globalBlock'] ?? null);

                if ($global && is_array($global->content)) {
                    $items = array_merge($items, static::questions($global->content, $depth + 1));
                }

                continue;
            }

            if (! in_array($type, static::blockTypes(), true)) {
                continue;
            }

            foreach (['questions', 'faqs'] as $key) {
                foreach ((array) ($data[$key] ?? []) as $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $question = static::plainText($item['question'] ?? $item['title'] ?? '');
                    $answer = static::plainText($item['description'] ?? $item['content'] ?? $item['answer'] ?? '');

                    if ($question !== '' && $answer !== '' && ! isset($items[$question])) {
                        $items[$question] = ['question' => $question, 'answer' => $answer];
                    }
                }
            }
        }

        return array_values($items);
    }

    /**
     * Which block types hold FAQs: `faq` unless a project registers more
     * (dashed-core.seo.faq_block_types). The same list dashed-marketing
     * uses to find FAQ blocks for its audit.
     */
    public static function blockTypes(): array
    {
        return (array) config('dashed-core.seo.faq_block_types', ['faq']);
    }

    /**
     * TipTap document, HTML or plain string to one line of text.
     */
    public static function plainText(mixed $value): string
    {
        if (is_array($value)) {
            $value = rescue(fn () => cms()->convertToHtml($value), '', false);
        }

        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5);

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
