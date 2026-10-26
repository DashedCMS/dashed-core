@php
    use Dashed\DashedCore\Models\BackgroundTask;
    use Dashed\DashedCore\Livewire\Admin\BackgroundTasks;
@endphp
{{-- Eigen stijlblok: niet elk klantproject compileert de views uit vendor/dashed mee in Tailwind. --}}
<div class="dsh-tasks" x-data="{ open: false }" x-on:keydown.escape.window="open = false" x-on:click.outside="open = false"
     wire:poll.{{ $active->isNotEmpty() ? '3s' : '30s' }} data-background-tasks>
    <style>
        .dsh-tasks { position: relative; display: flex; align-items: center; }
        .dsh-tasks__button { position: relative; display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 8px; border: 0; background: transparent; color: rgb(107 114 128); cursor: pointer; }
        .dsh-tasks__button:hover { background: rgb(0 0 0 / .05); }
        .dark .dsh-tasks__button:hover { background: rgb(255 255 255 / .06); }
        .dsh-tasks__button svg { width: 22px; height: 22px; }
        .dsh-tasks__button--busy svg { animation: dsh-tasks-spin 2s linear infinite; }
        @keyframes dsh-tasks-spin { to { transform: rotate(360deg); } }
        .dsh-tasks__badge { position: absolute; top: 2px; right: 2px; min-width: 16px; height: 16px; padding: 0 4px; border-radius: 8px; background: #2563eb; color: #fff; font-size: 10px; line-height: 16px; font-weight: 600; }
        .dsh-tasks__panel { position: absolute; top: calc(100% + 6px); right: 0; z-index: 40; width: 360px; max-width: calc(100vw - 32px); max-height: 70vh; overflow-y: auto; border-radius: 12px; background: #fff; box-shadow: 0 10px 30px rgb(0 0 0 / .15); border: 1px solid rgb(0 0 0 / .08); font-size: 13px; color: rgb(17 24 39); }
        .dark .dsh-tasks__panel { background: rgb(24 24 27); color: rgb(244 244 245); border-color: rgb(255 255 255 / .1); }
        .dsh-tasks__heading { padding: 10px 14px 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; opacity: .55; }
        .dsh-tasks__item { padding: 8px 14px; }
        .dsh-tasks__item + .dsh-tasks__item { border-top: 1px solid rgb(127 127 127 / .12); }
        .dsh-tasks__row { display: flex; justify-content: space-between; gap: 8px; }
        .dsh-tasks__label { font-weight: 500; overflow-wrap: anywhere; }
        .dsh-tasks__meta { opacity: .65; font-size: 12px; margin-top: 2px; }
        .dsh-tasks__bar { height: 6px; border-radius: 3px; background: rgb(127 127 127 / .18); margin-top: 6px; overflow: hidden; }
        .dsh-tasks__bar span { display: block; height: 100%; background: #2563eb; border-radius: 3px; }
        .dsh-tasks__bar--unknown span { width: 30%; animation: dsh-tasks-slide 1.4s ease-in-out infinite; }
        @keyframes dsh-tasks-slide { 0% { transform: translateX(-100%); } 100% { transform: translateX(340%); } }
        .dsh-tasks__status--failed { color: #b91c1c; }
        .dark .dsh-tasks__status--failed { color: #f87171; }
        .dsh-tasks__status--completed { color: #15803d; }
        .dark .dsh-tasks__status--completed { color: #4ade80; }
        .dsh-tasks__link { color: #2563eb; text-decoration: underline; }
        .dsh-tasks__empty { padding: 14px; opacity: .65; }
    </style>

    <button type="button" class="dsh-tasks__button {{ $active->isNotEmpty() ? 'dsh-tasks__button--busy' : '' }}"
            x-on:click="open = ! open" aria-label="{{ __('Achtergrondtaken') }}" title="{{ __('Achtergrondtaken') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 4v5h-5"/></svg>
        @if ($active->isNotEmpty())
            <span class="dsh-tasks__badge">{{ $active->count() }}</span>
        @endif
    </button>

    <div class="dsh-tasks__panel" x-show="open" x-cloak x-transition.opacity>
        @if ($active->isEmpty() && $recent->isEmpty())
            <div class="dsh-tasks__empty">{{ __('Er lopen geen taken op de achtergrond.') }}</div>
        @endif

        @if ($active->isNotEmpty())
            <div class="dsh-tasks__heading">{{ __('Bezig') }}</div>
            @foreach ($active as $task)
                @php($percentage = $task->percentage())
                @php($remaining = $task->secondsRemaining())
                <div class="dsh-tasks__item" wire:key="task-{{ $task->id }}" data-task="{{ $task->id }}">
                    <div class="dsh-tasks__row">
                        <span class="dsh-tasks__label">{{ $task->label }}</span>
                        @if ($percentage !== null)
                            <span>{{ $percentage }}%</span>
                        @endif
                    </div>
                    <div class="dsh-tasks__bar {{ $percentage === null ? 'dsh-tasks__bar--unknown' : '' }}">
                        <span @if ($percentage !== null) style="width: {{ $percentage }}%" @endif></span>
                    </div>
                    <div class="dsh-tasks__meta">
                        @if ($task->status === BackgroundTask::STATUS_QUEUED)
                            {{ __('In de wachtrij') }}
                        @elseif ($task->total)
                            {{ __(':klaar van :totaal', ['klaar' => $task->done, 'totaal' => $task->total]) }}
                            @if ($remaining !== null)
                                · {{ __('nog ongeveer :tijd', ['tijd' => BackgroundTasks::formatSeconds($remaining)]) }}
                            @endif
                        @else
                            {{ __('Gestart :tijd', ['tijd' => $task->started_at?->diffForHumans()]) }}
                        @endif
                        @if ($task->user_id && $task->user_id !== auth()->id())
                            · {{ $task->user?->name }}
                        @endif
                    </div>
                </div>
            @endforeach
        @endif

        @if ($recent->isNotEmpty())
            <div class="dsh-tasks__heading">{{ __('Recent') }}</div>
            @foreach ($recent as $task)
                <div class="dsh-tasks__item" wire:key="task-{{ $task->id }}" data-task="{{ $task->id }}">
                    <div class="dsh-tasks__row">
                        <span class="dsh-tasks__label">{{ $task->label }}</span>
                        <span class="dsh-tasks__status--{{ $task->status }}">
                            {{ $task->status === BackgroundTask::STATUS_FAILED ? __('Mislukt') : __('Klaar') }}
                        </span>
                    </div>
                    <div class="dsh-tasks__meta">
                        {{ $task->finished_at?->diffForHumans() ?? $task->updated_at?->diffForHumans() }}
                        @if ($task->user_id && $task->user_id !== auth()->id())
                            · {{ $task->user?->name }}
                        @endif
                        @if ($task->message)
                            · {{ \Illuminate\Support\Str::limit($task->message, 160) }}
                        @endif
                    </div>
                    @if ($task->result_url)
                        <div class="dsh-tasks__meta">
                            <a class="dsh-tasks__link" href="{{ $task->result_url }}">{{ $task->result_label ?: __('Bekijken') }}</a>
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    </div>
</div>
