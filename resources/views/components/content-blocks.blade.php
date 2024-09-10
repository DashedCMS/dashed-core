@if($content)
    @foreach($content as $block)
        @if(in_array($block['type'], config('dashed-core.blocks.caching_disabled', [])))
            <x-dynamic-component :component="'blocks.' . $block['type']" :type="$block['type']"
                                 :data="$block['data']" {{ $attributes->merge() }}/>
        @else
            @cache($model->getContentBlockCacheKey($loop->iteration, $block['type']))
            <x-dynamic-component :component="'blocks.' . $block['type']" :type="$block['type']"
                                 :data="$block['data']" {{ $attributes->merge() }}></x-dynamic-component>
            @endcache
        @endif
    @endforeach
@endif
