<div x-data="{ src: @entangle('src').defer }" class="w-full">
    <template x-if="src">
        <div class="aspect-video w-full max-w-full border rounded overflow-hidden mt-2">
            <iframe
                x-bind:src="src.includes('youtube') ? src.replace('watch?v=', 'embed/') : src"
                class="w-full h-full"
                frameborder="0"
                allowfullscreen
                loading="lazy"
            ></iframe>
        </div>
    </template>
</div>
