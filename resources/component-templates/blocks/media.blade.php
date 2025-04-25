<section
        class="bg-white @if($data['top_margin'] ?? false) pt-16 sm:pt-24 @endif @if($data['bottom_margin'] ?? false) pb-16 sm:pb-24 @endif">
    <x-container :show="$data['in_container'] ?? true">
        <div class="@if($data['align'] == 'right') ml-auto @elseif($data['align'] == 'left') mr-auto @else mx-auto @endif" style="max-width: {{ $data['max_width_number'] . $data['max_width_type'] }};">
            <x-drift::image
                    class="h-full w-full object-cover rounded-lg"
                    :path="$data['media']"
                    :manipulations="[
                    'widen' => 1600,
                ]"
            />
        </div>
    </x-container>
</section>
