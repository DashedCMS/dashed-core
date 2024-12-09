<section class="@if($data['top_margin']) pt-16 sm:pt-24 @endif @if($data['bottom_margin']) pb-16 sm:pb-24 @endif bg-primary-100/50">
    <x-container :show="$data['in_container'] ?? false">
        <div class="mt-8 grid md:grid-cols-2 gap-8 md:gap-16 items-start">
            <h2 class="text-3xl md:text-4xl font-bold text-balance text-primary-800">{{ $data['title'] }}</h2>

            @if($data['subtitle'] ?? false)
                <div class="text-balance text-primary-500">
                    {!! tiptap_converter()->asHTML($data['subtitle']) !!}
                </div>
            @endif
        </div>

        <div class="mt-16 grid grid-cols-2 md:grid-cols-4 gap-x-8 gap-y-16">
            @foreach($data['team'] as $team)
                <div class="card w-full"
                data-aos="fade-left" data-aos-delay="{{ 100 * $loop->iteration }}">
                    <div class="card__content text-center relative p-20 aspect-[2/3] transition-transform duration-1000 text-white font-bold">
                        <div class="card__front absolute inset-0 flex items-center justify-center">
                            <x-dashed-files::image
                                    class="aspect-[2/3] rounded-lg w-full object-cover"
                                    :mediaId="$team['image']"
                                    :alt="$team['name']"
                                    :manipulations="[
                                            'widen' => 800,
                                        ]"
                            />
                        </div>
                        <div class="card__back absolute top-0 bottom-0 right-0 left-0 flex items-center justify-center">
                            <x-dashed-files::image
                                    class="aspect-[2/3] rounded-lg w-full object-cover"
                                    :mediaId="$team['image-2']"
                                    :alt="$team['name']"
                                    :manipulations="[
                                            'widen' => 800,
                                        ]"
                            />
                        </div>
                    </div>

                    <h3 class="md:text-xl mt-4 font-bold text-primary-300">{{ $team['name'] }}</h3>

                    <p class="opacity-75 text-primary-800">{{ $team['function'] }}</p>
                </div>
                {{--                <div class="w-full relative group card">--}}
                {{--                    <div class="card__content">--}}
                {{--                        <div class="card__front">--}}
                {{--                            <x-dashed-files::image--}}
                {{--                                    class="aspect-[2/3] object-cover w-full rounded-md"--}}
                {{--                                    :mediaId="$team['image-2']"--}}
                {{--                                    :alt="$team['name']"--}}
                {{--                                    :manipulations="[--}}
                {{--                                'widen' => 800,--}}
                {{--                            ]"--}}
                {{--                            />--}}
                {{--                        </div>--}}
                {{--                        <div class="card__back">--}}
                {{--                            <x-dashed-files::image--}}
                {{--                                    class="aspect-[2/3] object-cover w-full rounded-md"--}}
                {{--                                    :mediaId="$team['image']"--}}
                {{--                                    :alt="$team['name']"--}}
                {{--                                    :manipulations="[--}}
                {{--                                'widen' => 800,--}}
                {{--                            ]"--}}
                {{--                            />--}}
                {{--                        </div>--}}
                {{--                    </div>--}}

                {{--                    <h3 class="md:text-xl mt-4 font-bold text-primary-300">{{ $team['name'] }}</h3>--}}

                {{--                    <p class="opacity-75 text-primary-800">{{ $team['function'] }}</p>--}}
                {{--                </div>--}}
            @endforeach
        </div>
    </x-container>
</section>
