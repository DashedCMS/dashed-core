<div class="@if($data['top_margin'] ?? true) pt-16 sm:pt-24 @endif @if($data['bottom_margin'] ?? true) pb-16 sm:pb-24 @endif bg-linear-to-tr from-primary to-secondary">
    <x-container :show="$data['in_container'] ?? false">
        <div class="px-4 sm:flex sm:items-center sm:justify-between sm:px-6 lg:px-8 xl:px-0">
            <div></div>
            <h2>
                {{ $data['title'] }}
            </h2>
        </div>

        <div class="mt-4 flow-root">
            <div class="-my-2">
                <div class="relative overflow-x-auto py-2 swiper"
                     data-slides-per-view-default="2"
                     data-slides-per-view="4"
                     data-slides-per-view-md="6"
                     data-space-between="16"
                     data-loop="true">
                    <div class="swiper-wrapper">
                        @foreach($data['logos'] as $logo)
                            <a href="{{ linkHelper()->getUrl($logo) }}"
                               class="relative flex flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 swiper-slide bg-white">
                              <span aria-hidden="true" class="absolute inset-0">
                                  <x-dashed-files::image
                                      class="h-full w-full object-contain object-center"
                                      :mediaId="$logo['image']"
                                      :manipulations="[
                                                'widen' => 300,
                                            ]"
                                  />
                              </span>
                            </a>
                        @endforeach
                        @foreach($data['logos'] as $logo)
                            <a href="{{ linkHelper()->getUrl($logo) }}"
                               class="relative flex flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 swiper-slide bg-white">
                              <span aria-hidden="true" class="absolute inset-0">
                                  <x-dashed-files::image
                                      class="h-full w-full object-contain object-center"
                                      :mediaId="$logo['image']"
                                      :manipulations="[
                                                'widen' => 300,
                                            ]"
                                  />
                              </span>
                            </a>
                        @endforeach
                        @foreach($data['logos'] as $logo)
                            <a href="{{ linkHelper()->getUrl($logo) }}"
                               class="relative flex flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 swiper-slide bg-white">
                              <span aria-hidden="true" class="absolute inset-0">
                                  <x-dashed-files::image
                                      class="h-full w-full object-contain object-center"
                                      :mediaId="$logo['image']"
                                      :manipulations="[
                                                'widen' => 300,
                                            ]"
                                  />
                              </span>
                            </a>
                        @endforeach
                        @foreach($data['logos'] as $logo)
                            <a href="{{ linkHelper()->getUrl($logo) }}"
                               class="relative flex flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 swiper-slide bg-white">
                              <span aria-hidden="true" class="absolute inset-0">
                                  <x-dashed-files::image
                                      class="h-full w-full object-contain object-center"
                                      :mediaId="$logo['image']"
                                      :manipulations="[
                                                'widen' => 300,
                                            ]"
                                  />
                              </span>
                            </a>
                        @endforeach
                        @foreach($data['logos'] as $logo)
                            <a href="{{ linkHelper()->getUrl($logo) }}"
                               class="relative flex flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 swiper-slide bg-white">
                              <span aria-hidden="true" class="absolute inset-0">
                                  <x-dashed-files::image
                                      class="h-full w-full object-contain object-center"
                                      :mediaId="$logo['image']"
                                      :manipulations="[
                                                'widen' => 300,
                                            ]"
                                  />
                              </span>
                            </a>
                        @endforeach
                        @foreach($data['logos'] as $logo)
                            <a href="{{ linkHelper()->getUrl($logo) }}"
                               class="relative flex flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 swiper-slide bg-white">
                              <span aria-hidden="true" class="absolute inset-0">
                                  <x-dashed-files::image
                                      class="h-full w-full object-contain object-center"
                                      :mediaId="$logo['image']"
                                      :manipulations="[
                                                'widen' => 300,
                                            ]"
                                  />
                              </span>
                            </a>
                        @endforeach
                        @foreach($data['logos'] as $logo)
                            <a href="{{ linkHelper()->getUrl($logo) }}"
                               class="relative flex flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 swiper-slide bg-white">
                              <span aria-hidden="true" class="absolute inset-0">
                                  <x-dashed-files::image
                                      class="h-full w-full object-contain object-center"
                                      :mediaId="$logo['image']"
                                      :manipulations="[
                                                'widen' => 300,
                                            ]"
                                  />
                              </span>
                            </a>
                        @endforeach
                        @foreach($data['logos'] as $logo)
                            <a href="{{ linkHelper()->getUrl($logo) }}"
                               class="relative flex flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 swiper-slide bg-white">
                              <span aria-hidden="true" class="absolute inset-0">
                                  <x-dashed-files::image
                                      class="h-full w-full object-contain object-center"
                                      :mediaId="$logo['image']"
                                      :manipulations="[
                                                'widen' => 300,
                                            ]"
                                  />
                              </span>
                            </a>
                        @endforeach
                        @foreach($data['logos'] as $logo)
                            <a href="{{ linkHelper()->getUrl($logo) }}"
                               class="relative flex flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 swiper-slide bg-white">
                              <span aria-hidden="true" class="absolute inset-0">
                                  <x-dashed-files::image
                                      class="h-full w-full object-contain object-center"
                                      :mediaId="$logo['image']"
                                      :manipulations="[
                                                'widen' => 300,
                                            ]"
                                  />
                              </span>
                            </a>
                        @endforeach
                        @foreach($data['logos'] as $logo)
                            <a href="{{ linkHelper()->getUrl($logo) }}"
                               class="relative flex flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 swiper-slide bg-white">
                              <span aria-hidden="true" class="absolute inset-0">
                                  <x-dashed-files::image
                                      class="h-full w-full object-contain object-center"
                                      :mediaId="$logo['image']"
                                      :manipulations="[
                                                'widen' => 300,
                                            ]"
                                  />
                              </span>
                            </a>
                        @endforeach
                        @foreach($data['logos'] as $logo)
                            <a href="{{ linkHelper()->getUrl($logo) }}"
                               class="relative flex flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 swiper-slide bg-white">
                              <span aria-hidden="true" class="absolute inset-0">
                                  <x-dashed-files::image
                                      class="h-full w-full object-contain object-center"
                                      :mediaId="$logo['image']"
                                      :manipulations="[
                                                'widen' => 300,
                                            ]"
                                  />
                              </span>
                            </a>
                        @endforeach
                        @foreach($data['logos'] as $logo)
                            <a href="{{ linkHelper()->getUrl($logo) }}"
                               class="relative flex flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 swiper-slide bg-white">
                              <span aria-hidden="true" class="absolute inset-0">
                                  <x-dashed-files::image
                                      class="h-full w-full object-contain object-center"
                                      :mediaId="$logo['image']"
                                      :manipulations="[
                                                'widen' => 300,
                                            ]"
                                  />
                              </span>
                            </a>
                        @endforeach
                        @foreach($data['logos'] as $logo)
                            <a href="{{ linkHelper()->getUrl($logo) }}"
                               class="relative flex flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 swiper-slide bg-white">
                              <span aria-hidden="true" class="absolute inset-0">
                                  <x-dashed-files::image
                                      class="h-full w-full object-contain object-center"
                                      :mediaId="$logo['image']"
                                      :manipulations="[
                                                'widen' => 300,
                                            ]"
                                  />
                              </span>
                            </a>
                        @endforeach
                        @foreach($data['logos'] as $logo)
                            <a href="{{ linkHelper()->getUrl($logo) }}"
                               class="relative flex flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 swiper-slide bg-white">
                              <span aria-hidden="true" class="absolute inset-0">
                                  <x-dashed-files::image
                                      class="h-full w-full object-contain object-center"
                                      :mediaId="$logo['image']"
                                      :manipulations="[
                                                'widen' => 300,
                                            ]"
                                  />
                              </span>
                            </a>
                        @endforeach
                        @foreach($data['logos'] as $logo)
                            <a href="{{ linkHelper()->getUrl($logo) }}"
                               class="relative flex flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 swiper-slide bg-white">
                              <span aria-hidden="true" class="absolute inset-0">
                                  <x-dashed-files::image
                                      class="h-full w-full object-contain object-center"
                                      :mediaId="$logo['image']"
                                      :manipulations="[
                                                'widen' => 300,
                                            ]"
                                  />
                              </span>
                            </a>
                        @endforeach
                        @foreach($data['logos'] as $logo)
                            <a href="{{ linkHelper()->getUrl($logo) }}"
                               class="relative flex flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 swiper-slide bg-white">
                              <span aria-hidden="true" class="absolute inset-0">
                                  <x-dashed-files::image
                                      class="h-full w-full object-contain object-center"
                                      :mediaId="$logo['image']"
                                      :manipulations="[
                                                'widen' => 300,
                                            ]"
                                  />
                              </span>
                            </a>
                        @endforeach
                        @foreach($data['logos'] as $logo)
                            <a href="{{ linkHelper()->getUrl($logo) }}"
                               class="relative flex flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 swiper-slide bg-white">
                              <span aria-hidden="true" class="absolute inset-0">
                                  <x-dashed-files::image
                                      class="h-full w-full object-contain object-center"
                                      :mediaId="$logo['image']"
                                      :manipulations="[
                                                'widen' => 300,
                                            ]"
                                  />
                              </span>
                            </a>
                        @endforeach
                        @foreach($data['logos'] as $logo)
                            <a href="{{ linkHelper()->getUrl($logo) }}"
                               class="relative flex flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 swiper-slide bg-white">
                              <span aria-hidden="true" class="absolute inset-0">
                                  <x-dashed-files::image
                                      class="h-full w-full object-contain object-center"
                                      :mediaId="$logo['image']"
                                      :manipulations="[
                                                'widen' => 300,
                                            ]"
                                  />
                              </span>
                            </a>
                        @endforeach
                        @foreach($data['logos'] as $logo)
                            <a href="{{ linkHelper()->getUrl($logo) }}"
                               class="relative flex flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 swiper-slide bg-white">
                              <span aria-hidden="true" class="absolute inset-0">
                                  <x-dashed-files::image
                                      class="h-full w-full object-contain object-center"
                                      :mediaId="$logo['image']"
                                      :manipulations="[
                                                'widen' => 300,
                                            ]"
                                  />
                              </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </x-container>
</div>
