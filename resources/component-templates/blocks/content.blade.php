<section class="py-24">
    <x-container>
        <article
                class="prose md:prose-lg mx-auto prose-headings:font-normal prose-li:marker:text-primary prose-blockquote:border-primary prose-blockquote:not-italic prose-blockquote:font-normal prose-blockquote:text-gray-600 overflow-hidden"
        >
            {!! preg_replace('/\s*style=("|\')(.*?)("|\')/i', '', tiptap_converter()->asHTML($data['content'])) !!}
        </article>
    </x-container>
</section>