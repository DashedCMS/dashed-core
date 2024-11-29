<section class="@if($data['top_margin'] ?? true) pt-16 sm:pt-24 @endif @if($data['bottom_margin'] ?? true) pb-16 sm:pb-24 @endif">
    <x-container :show="$data['in_container'] ?? false">
        <article
                style="max-width: unset !important;"
                class="prose md:prose-lg mx-auto prose-headings:font-normal prose-li:marker:text-primary prose-blockquote:border-primary prose-blockquote:not-italic prose-blockquote:font-normal prose-blockquote:text-gray-600"
        >
            {!! preg_replace('/\s*style=("|\')(.*?)("|\')/i', '', $data['content']) !!}
        </article>
    </x-container>
</section>
