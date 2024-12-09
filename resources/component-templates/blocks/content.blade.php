<section
    class="@if($data['top_margin'] ?? true) pt-16 sm:pt-24 @endif @if($data['bottom_margin'] ?? true) pb-16 sm:pb-24 @endif">
    <x-container :show="$data['in_container'] ?? false">
        <article
            class="@if($data['full-width'] ?? false) max-w-full @endif prose md:prose-lg mx-auto prose-headings:font-normal prose-li:marker:text-primary prose-blockquote:border-primary prose-blockquote:not-italic prose-blockquote:font-normal prose-blockquote:text-gray-600 overflow-hidden"
        >
            {!! preg_replace('/\s*style=("|\')(.*?)("|\')/i', '', tiptap_converter()->asHTML($data['content'])) !!}
        </article>
    </x-container>
</section>
