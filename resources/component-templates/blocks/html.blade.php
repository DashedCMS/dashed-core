<section class="@if($data['top_margin']) pt-16 sm:pt-24 @endif @if($data['bottom_margin']) pb-16 sm:pb-24 @endif">
    <x-container :show="$data['in_container'] ?? false">
            <div>
                {!! $data['html'] !!}
            </div>
    </x-container>
</section>
