@php
    $abCacheable = ! config('dashed-core.blocks.disable_caching')
        && ! in_array($block['type'], array_merge(config('dashed-core.blocks.caching_disabled', []), cms()->builder('blockDisabledForCache')))
        && $model;
    $abKeyA = $abCacheable ? $model->getContentBlockCacheKey($iteration, $block['type']) : null;
    // B hangt ook aan de test: een gewijzigde variant B krijgt een nieuwe sleutel.
    $abKeyB = $abCacheable ? $abKeyA . '_ab' . $abTest->id . '_' . $abTest->updated_at?->timestamp : null;
    $abDataB = $abTest->variantBData();
@endphp
@once
    @include('dashed-core::ab-tests.script')
@endonce
<div class="dab-test" data-dab-test="{{ $abTest->id }}" data-dab-split="{{ (int) $abTest->split_b }}" data-dab-goal="{{ $abTest->goal }}">
    <div class="dab-variant" data-dab-variant="a">
        @if($abCacheable)
            @cache($abKeyA)
            <x-dynamic-component :component="'blocks.' . $block['type']" :type="$block['type']"
                                 :data="$block['data']" {{ $attributes->merge() }}></x-dynamic-component>
            @endcache
        @else
            <x-dynamic-component :component="'blocks.' . $block['type']" :type="$block['type']"
                                 :data="$block['data']" {{ $attributes->merge() }}/>
        @endif
    </div>
    <div class="dab-variant" data-dab-variant="b" hidden>
        @if($abCacheable)
            @cache($abKeyB)
            <x-dynamic-component :component="'blocks.' . $block['type']" :type="$block['type']"
                                 :data="$abDataB" {{ $attributes->merge() }}></x-dynamic-component>
            @endcache
        @else
            <x-dynamic-component :component="'blocks.' . $block['type']" :type="$block['type']"
                                 :data="$abDataB" {{ $attributes->merge() }}/>
        @endif
    </div>
</div>
