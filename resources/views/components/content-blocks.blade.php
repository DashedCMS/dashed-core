@php($attributes = $attributes->except('content'))
@if($content)
    @foreach($content as $block)
        @cache("block_{$loop->iteration}_{$model->id}_{$model->updated_at}")
        <x-dynamic-component :component="'blocks.' . $block['type']" :type="$block['type']" :data="$block['data']"
            {{ $attributes->merge() }} />
        @endcache
    @endforeach
@endif
