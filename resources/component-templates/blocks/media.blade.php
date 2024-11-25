<section class="@if($data['top_margin']) pt-16 sm:pt-24 @endif @if($data['bottom_margin']) pb-16 sm:pb-24 @endif">
    <x-container :show="$data['in_container'] ?? false">
            <div class="w-full" style="max-width: '{{ $data['max_width_number'] . $data['max_width_type'] }}';">
                <x-dashed-files::image
                        class="block w-full mx-auto rounded-lg"
                        :mediaId="$data['media']"
                        :manipulations="[
                        'widen' => {{ $data['max_width_type'] == 'px' ? $data['max_width_number'] : 1000 }},
                    ]"
                />
            </div>
    </x-container>
</section>
