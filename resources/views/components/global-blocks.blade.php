@props(['name'])

@php($globalBlock = \Dashed\DashedCore\Models\GlobalBlock::where('name', $name)->first())
@if($globalBlock)
    <x-dashed-core::content-blocks :content="$globalBlock->content" {{ $attributes->merge() }}/>
@else
    @php(\Dashed\DashedCore\Models\GlobalBlock::create(['name' => $name]))
@endif
