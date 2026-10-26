<div>
    @if ($usages->isNotEmpty())
        <div x-data="{ open: false }" data-usage-notice style="margin-bottom: 1rem; border-radius: .5rem; border: 1px solid rgba(37, 99, 235, .25); background: rgba(37, 99, 235, .06); padding: .625rem .875rem; font-size: .875rem;">
            <button type="button" x-on:click="open = ! open" style="font-weight: 600; text-align: left;">
                {{ trans_choice('Wordt gebruikt op :aantal plek|Wordt gebruikt op :aantal plekken', $usages->count(), ['aantal' => $usages->count()]) }}
                <span x-text="open ? '▴' : '▾'"></span>
            </button>
            <ul x-show="open" x-cloak style="margin: .5rem 0 0; padding-left: 1.25rem; list-style: disc;">
                @foreach ($usages as $usage)
                    <li>
                        <span style="opacity: .7;">{{ $usage['source'] }}:</span>
                        @if ($usage['url'])
                            <a href="{{ $usage['url'] }}" style="text-decoration: underline;">{{ $usage['title'] }}</a>
                        @else
                            {{ $usage['title'] }}
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
