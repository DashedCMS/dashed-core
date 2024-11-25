<div class="relative isolate bg-white @if($data['top_margin']) pt-16 sm:pt-24 @endif @if($data['bottom_margin']) pb-16 sm:pb-24 @endif">
    <x-container :show="$data['in_container'] ?? false">
        <div class="mx-auto grid max-w-7xl grid-cols-1 lg:grid-cols-2">
            <div class="relative lg:static">
                <div class="mx-auto max-w-xl lg:mx-0 lg:max-w-lg">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900">{{ $data['title'] }}</h2>
                    <div class="mt-6 text-lg leading-8 text-gray-600">
                        {!! $data['content'] !!}
                    </div>
                    <dl class="mt-10 space-y-4 text-base leading-7 text-gray-600">
                        <div class="text-link flex gap-2 items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                 stroke-width="1.5"
                                 stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                            </svg>

                            <a href="https://maps.google.com?q={{ Customsetting::get('company_street') }} {{ Customsetting::get('company_street_number') }} {{ Customsetting::get('company_postal_code') }} {{ Customsetting::get('company_city') }}"
                               target="_blank" rel="nofollow noopener">
                                <p>{{ Customsetting::get('company_street') }} {{ Customsetting::get('company_street_number') }}</p>
                                <p>{{ Customsetting::get('company_postal_code') }} {{ Customsetting::get('company_city') }}</p>
                            </a>
                        </div>

                        <p class="text-link flex gap-2 items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                 stroke-width="1.5"
                                 stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/>
                            </svg>

                            <a href="tel:{{ Customsetting::get('company_phone_number') }}">{{ Customsetting::get('company_phone_number') }}</a>
                        </p>

                        <p class="text-link flex gap-2 items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                 stroke-width="1.5"
                                 stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                            </svg>

                            <a href="mailto:{{ Customsetting::get('site_to_email') }}">{{ Customsetting::get('site_to_email') }}</a>
                        </p>
                    </dl>
                </div>
            </div>
            <div class="max-w-2xl">
                <livewire:dashed-forms.form :blockData="$data" :formId="$data['form'] ?? 0"/>
            </div>
        </div>
    </x-container>
</div>
