{{--
    Foutpagina van de site (403, 419, 429, 500, ...), in de huisstijl. Beschikbaar:
    $statusCode, $title, $message, $homeUrl, $branding (siteName, logoUrl, primaryColor).
    Een aparte pagina per code kan ook: errors/<code>.blade.php naast dit bestand.
    De 404 blijft via not-found/show.blade.php lopen.
--}}
<x-master>
    <main class="relative isolate flex min-h-[60vh] items-center justify-center bg-linear-to-br from-primary-500 to-primary-200">
        <div class="mx-auto px-6 py-24 text-center">
            <p class="text-base font-semibold leading-8 text-white">{{ $statusCode }}</p>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-white sm:text-5xl">{{ Translation::get('title-' . $statusCode, 'errors', $title) }}</h1>
            <p class="mt-4 text-base text-white/70 sm:mt-6">{{ Translation::get('message-' . $statusCode, 'errors', $message) }}</p>
            <div class="mt-10 flex justify-center gap-4">
                @if($statusCode === 419)
                    <button type="button" class="button button--primary-dark" onclick="history.back()">{{ Translation::get('go-back', 'errors', 'Ga terug') }}</button>
                @endif
                <a href="{{ $homeUrl }}" class="button button--primary-dark"><span aria-hidden="true">&larr;</span>
                    {{ Translation::get('back-to-home', 'errors', 'Terug naar de homepagina') }}</a>
            </div>
        </div>
    </main>
</x-master>
