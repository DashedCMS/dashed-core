<div class="drh">
    <style>
        .drh { display: flex; flex-direction: column; gap: 1rem; font-size: .875rem; }
        .drh-tabs { display: flex; gap: .5rem; border-bottom: 1px solid rgba(127,127,127,.25); }
        .drh-tab { padding: .5rem .75rem; border-bottom: 2px solid transparent; cursor: pointer; background: none; color: inherit; }
        .drh-tab-active { border-bottom-color: rgb(var(--primary-600, 37 99 235)); font-weight: 600; }
        .drh-list { display: flex; flex-direction: column; gap: .75rem; }
        .drh-item { border: 1px solid rgba(127,127,127,.25); border-radius: .5rem; padding: .75rem; }
        .drh-head { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; justify-content: space-between; }
        .drh-meta { opacity: .75; font-size: .8125rem; }
        .drh-badge { display: inline-block; padding: .05rem .5rem; border-radius: 9999px; font-size: .75rem; font-weight: 600; background: rgba(127,127,127,.15); }
        .drh-badge-live { background: rgba(22,163,74,.15); color: rgb(21,128,61); }
        .drh-badge-open { background: rgba(234,179,8,.2); color: rgb(161,98,7); }
        .drh-actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .5rem; }
        .drh-btn { font-size: .8125rem; padding: .25rem .625rem; border-radius: .375rem; border: 1px solid rgba(127,127,127,.35); background: none; color: inherit; cursor: pointer; }
        .drh-btn:hover { background: rgba(127,127,127,.1); }
        .drh-btn-primary { border-color: rgb(var(--primary-600, 37 99 235)); color: rgb(var(--primary-600, 37 99 235)); }
        .drh-comment { margin-top: .5rem; padding: .5rem; border-left: 3px solid rgba(234,179,8,.6); background: rgba(234,179,8,.08); }
        .drh-table { width: 100%; border-collapse: collapse; margin-top: .5rem; table-layout: fixed; }
        .drh-table th, .drh-table td { text-align: left; vertical-align: top; padding: .375rem; border-top: 1px solid rgba(127,127,127,.2); word-break: break-word; }
        .drh-old { color: rgb(185,28,28); }
        .drh-new { color: rgb(21,128,61); }
        .drh-empty { opacity: .7; }
    </style>

    @if ($supportsRevisions && $supportsActivity)
        <div class="drh-tabs">
            <button type="button" wire:click="showTab('revisions')" @class(['drh-tab', 'drh-tab-active' => $tab === 'revisions'])>{{ __('Versies') }}</button>
            <button type="button" wire:click="showTab('activity')" @class(['drh-tab', 'drh-tab-active' => $tab === 'activity'])>{{ __('Wijzigingen') }}</button>
        </div>
    @endif

    @if ($tab === 'revisions' && $supportsRevisions)
        @if ($revisions->isEmpty())
            <p class="drh-empty">{{ __('Er zijn nog geen versies. Bij de eerstvolgende keer opslaan wordt de huidige stand als eerste versie bewaard.') }}</p>
        @endif

        <div class="drh-list">
            @foreach ($revisions as $revision)
                <div class="drh-item" wire:key="revision-{{ $revision->id }}">
                    <div class="drh-head">
                        <div>
                            <strong>{{ $revision->created_at?->format('d-m-Y H:i') }}</strong>
                            <span class="drh-meta">· {{ \Dashed\DashedCore\Models\Revision::userLabel($revision->user) }}</span>
                            @if ($revision->reasonLabel())
                                <span class="drh-meta">· {{ $revision->reasonLabel() }}</span>
                            @endif
                        </div>
                        <span @class([
                            'drh-badge',
                            'drh-badge-live' => $revision->id === $liveId,
                            'drh-badge-open' => $revision->isOpen(),
                        ])>
                            {{ $revision->id === $liveId ? __('Live') : $revision->statusLabel() }}
                        </span>
                    </div>

                    @if ($revision->reviewer)
                        <div class="drh-meta">
                            {{ $revision->rejected_at && $revision->isOpen() ? __('Afgekeurd door :naam', ['naam' => \Dashed\DashedCore\Models\Revision::userLabel($revision->reviewer)]) : __('Beoordeeld door :naam', ['naam' => \Dashed\DashedCore\Models\Revision::userLabel($revision->reviewer)]) }}
                            · {{ $revision->reviewed_at?->format('d-m-Y H:i') }}
                        </div>
                    @endif

                    @if (filled($revision->review_comment))
                        <div class="drh-comment">{{ $revision->review_comment }}</div>
                    @endif

                    <div class="drh-actions">
                        <button type="button" class="drh-btn" wire:click="compare({{ $revision->id }}, 'previous')">{{ __('Vergelijk met vorige versie') }}</button>
                        @if ($revision->id !== $liveId)
                            <button type="button" class="drh-btn" wire:click="compare({{ $revision->id }}, 'live')">{{ __('Vergelijk met live') }}</button>
                            <button type="button" class="drh-btn drh-btn-primary"
                                    wire:click="restore({{ $revision->id }})"
                                    wire:confirm="{{ __('Deze versie terugzetten? De huidige stand blijft als versie bewaard.') }}">
                                {{ __('Terugzetten naar deze versie') }}
                            </button>
                        @endif
                    </div>

                    @if ($compareId === $revision->id && is_array($diff))
                        @if ($diff === [])
                            <p class="drh-empty" style="margin-top:.5rem">{{ __('Geen verschillen.') }}</p>
                        @else
                            <table class="drh-table">
                                <thead>
                                    <tr>
                                        <th style="width:28%">{{ __('Veld') }}</th>
                                        <th style="width:10%">{{ __('Taal') }}</th>
                                        <th>{{ $compareWith === 'live' ? __('Live') : __('Vorige') }}</th>
                                        <th>{{ __('Deze versie') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($diff as $row)
                                        <tr>
                                            <td>{{ $row['field'] }}</td>
                                            <td>{{ $row['locale'] ? strtoupper($row['locale']) : '' }}</td>
                                            <td class="drh-old">{{ $row['old'] !== '' ? $row['old'] : '-' }}</td>
                                            <td class="drh-new">{{ $row['new'] !== '' ? $row['new'] : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if ($tab === 'activity')
        @if (empty($activities))
            <p class="drh-empty">{{ __('Er zijn nog geen wijzigingen gelogd.') }}</p>
        @endif

        <div class="drh-list">
            @foreach ($activities as $index => $activity)
                <div class="drh-item" wire:key="activity-{{ $index }}">
                    <div class="drh-head">
                        <div>
                            <strong>{{ $activity['at'] }}</strong>
                            <span class="drh-meta">· {{ $activity['user'] }}</span>
                        </div>
                        <span class="drh-badge">{{ $activity['event'] }}{{ $activity['subject'] ? ' · ' . $activity['subject'] : '' }}</span>
                    </div>

                    @if ($activity['changes'])
                        <table class="drh-table">
                            <tbody>
                                @foreach ($activity['changes'] as $change)
                                    <tr>
                                        <td style="width:28%">{{ $change['field'] }}</td>
                                        <td class="drh-old">{{ $change['old'] !== '' ? $change['old'] : '-' }}</td>
                                        <td class="drh-new">{{ $change['new'] !== '' ? $change['new'] : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
