@if($content)
    @php
        // Preload all referenced global blocks in one query to avoid N+1.
        $globalBlockIds = collect($content)
            ->where('type', 'globalBlock')
            ->pluck('data.globalBlock')
            ->filter()
            ->unique()
            ->values()
            ->all();
        $preloadedGlobalBlocks = $globalBlockIds
            ? \Dashed\DashedCore\Models\GlobalBlock::whereIn('id', $globalBlockIds)->get()->keyBy('id')
            : collect();
    @endphp
    @foreach($content as $block)
        @if($block['type'] == 'globalBlock')
            @php($globalBlockContent = $preloadedGlobalBlocks->get($block['data']['globalBlock'] ?? null))
            @if($globalBlockContent)
                <x-dashed-core::content-blocks :content="$globalBlockContent->content" {{ $attributes->merge() }}/>
            @endif
        @else
            @if(config('dashed-core.blocks.disable_caching') || in_array($block['type'], array_merge(config('dashed-core.blocks.caching_disabled', []), cms()->builder('blockDisabledForCache'))) || !isset($model))
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
