<section class="py-24">
    <x-container>
{{--        @dd($data['content'], tiptap_converter()->asHTML($data['content']))--}}
        <article
                style="max-width: unset !important;"
                class="prose md:prose-lg mx-auto prose-headings:font-normal prose-li:marker:text-primary prose-blockquote:border-primary prose-blockquote:not-italic prose-blockquote:font-normal prose-blockquote:text-gray-600"
        >
{{--            {!! tiptap_converter()->asHTML($data['content']) !!}--}}
            {!! preg_replace('/\s*style=("|\')(.*?)("|\')/i', '', $data['content']) !!}
{{--            {!! preg_replace('/\s*style=("|\')(.*?)("|\')/i', '', tiptap_converter()->asHTML($data['content'])) !!}--}}
        </article>
    </x-container>
</section>
