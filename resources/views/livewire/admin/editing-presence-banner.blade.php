<div wire:poll.20s="heartbeat" class="dashed-edit-presence">
    @if (! empty($editors))
        @php
            $names = collect($editors)->pluck('name')->filter()->values()->all();
            $latest = collect($editors)->max('last_seen');
            $seconds = max(0, time() - (int) $latest);
            $relative = $seconds < 60
                ? ($seconds <= 5 ? 'zojuist' : $seconds . ' sec geleden')
                : (int) floor($seconds / 60) . ' min geleden';
        @endphp

        <div class="fi-section mt-6 mb-2 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200" role="status" aria-live="polite">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="mt-0.5 h-5 w-5 flex-shrink-0">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 6a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 6Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                </svg>

                <div class="flex-1">
                    @if (count($names) === 1)
                        <strong>{{ $names[0] }}</strong> is dit record ook aan het bewerken
                    @else
                        <strong>{{ implode(', ', array_slice($names, 0, -1)) }}</strong> en
                        <strong>{{ end($names) }}</strong> zijn dit record ook aan het bewerken
                    @endif
                    <span class="opacity-75">- laatst actief {{ $relative }}.</span>
                    <div class="mt-1 text-xs opacity-75">
                        Pas op met gelijktijdig opslaan: de laatste wijziging overschrijft de andere.
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
