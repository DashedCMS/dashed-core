<div class="relative isolate overflow-hidden bg-white px-6 @if($data['top_margin'] ?? true) pt-16 sm:pt-24 @endif @if($data['bottom_margin'] ?? true) pb-16 sm:pb-24 @endif lg:overflow-visible lg:px-0">
    <div class="mx-auto grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 lg:mx-0 lg:max-w-none lg:grid-cols-2 lg:items-start lg:gap-y-10">
        <div class="lg:col-span-2 lg:col-start-1 lg:row-start-1 lg:mx-auto lg:grid lg:w-full lg:max-w-7xl lg:grid-cols-2 lg:gap-x-8 lg:px-8">
            <div class="lg:pr-4">
                <div class="lg:max-w-lg">
                    @if($data['subtitle'] ?? false)
                        <p class="text-base font-semibold leading-7 text-primary-600">{{ $data['subtitle'] }}</p>
                    @endif
                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ $data['title'] }}</h1>
                </div>
                <div class="max-w-xl text-base leading-7 text-gray-700 lg:max-w-lg mt-4">
                    {!! nl2br($data['content'] ?? '') !!}
                </div>

                @if(count($data['buttons'] ?? []))
                    <div class="grid items-center gap-4 md:flex mt-6">
                        @foreach ($data['buttons'] ?? [] as $button)
                            <x-button
                                    type="button button--{{ $button['type'] }}"
                                    href="{{ linkHelper()->getUrl($button) }}"
                            >{{ $button['title'] }}</x-button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        <div class="lg:col-span-2 lg:col-start-1 lg:row-start-2 lg:mx-auto lg:grid lg:w-full lg:max-w-7xl lg:grid-cols-2 lg:gap-x-8 lg:px-8">
        </div>
        <div class="-ml-12 -mt-12 p-12 lg:sticky lg:top-4 lg:col-start-2 lg:row-span-2 lg:row-start-1 lg:overflow-hidden">
            <x-dashed-files::image
                    class="w-[48rem] max-w-none rounded-xl bg-gray-900 shadow-xl ring-1 ring-gray-400/10 sm:w-[57rem]"
                    config="dashed"
                    :mediaId="$data['image']"
                    :alt="$data['title']"
                    :manipulations="[
                            'fit' => [1000, 500],
                        ]"
            />
        </div>
    </div>
</div>
