@if($content)
    @php($dashedEditorActive = isset($model) && app(\Dashed\DashedCore\Services\VisualEditor\VisualEditor::class)->isActive())
    @foreach($content as $block)
        @if($block['type'] == 'globalBlock')
            @php($globalBlockContent = \Dashed\DashedCore\Models\GlobalBlock::find($block['data']['globalBlock']) ?? [])
            @if($globalBlockContent)
                <x-dashed-core::content-blocks :content="$globalBlockContent->content" {{ $attributes->merge() }}/>
            @endif
        @else
            @if ($dashedEditorActive)
                <div data-dashed-block
                     data-block="{{ $loop->index }}"
                     data-block-type="{{ $block['type'] }}"
                     data-model-type="{{ $model::class }}"
                     data-model-id="{{ $model->id }}"
                     data-locale="{{ app()->getLocale() }}">
                    <x-dynamic-component :component="'blocks.' . $block['type']" :type="$block['type']"
                                         :data="$block['data']" {{ $attributes->merge() }}/>
                </div>
            @elseif(config('dashed-core.blocks.disable_caching') || in_array($block['type'], array_merge(config('dashed-core.blocks.caching_disabled', []), cms()->builder('blockDisabledForCache'))) || !isset($model))
                <x-dynamic-component :component="'blocks.' . $block['type']" :type="$block['type']"
                                     :data="$block['data']" {{ $attributes->merge() }}/>
            @else
                @cache($model->getContentBlockCacheKey($loop->iteration, $block['type']))
                <x-dynamic-component :component="'blocks.' . $block['type']" :type="$block['type']"
                                     :data="$block['data']" {{ $attributes->merge() }}></x-dynamic-component>
                @endcache
            @endif
        @endif
    @endforeach
@endif
