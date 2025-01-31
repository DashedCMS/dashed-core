<x-master>
    @livewireStyles
    @livewireScripts
    <main class="relative isolate h-[100vh] bg-gradient-to-br from-primary-500 to-primary-200 flex items-center justify-center">
        <x-dashed-files::image
            class="absolute inset-0 -z-10 h-full w-full object-cover object-top mix-blend-multiply"
            config="dashed"
            :mediaId="Translation::get('image', 'not-found', '', 'image')"
            :manipulations="[
                            'widen' => 300,
                        ]"
        />
        <div class="mx-auto text-center">
            <p class="text-base font-semibold leading-8 text-white">404</p>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-white sm:text-5xl">{{ Translation::get('page-not-found', 'not-found', 'Pagina niet gevonden') }}</h1>
            <p class="mt-4 text-base text-white/70 sm:mt-6">{{ Translation::get('page-not-found-description', 'not-found', 'De pagina waar je naar opzoek bent bestaat (niet) meer') }}</p>
            <div class="mt-10 flex justify-center">
                <a href="/" class="button button--primary-dark"><span aria-hidden="true">&larr;</span>
                    {{ Translation::get('back-to-home', 'not-found', 'Terug naar de homepagina') }}</a>
            </div>
        </div>
    </main>

    <x-dashed-core::global-blocks name="not-found-page"/>
</x-master>
