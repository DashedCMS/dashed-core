@php
    $issues = rescue(fn () => \Dashed\DashedCore\Classes\SystemHealth::issues(request()), [], false);
    $statusUrl = rescue(fn () => \Dashed\DashedCore\Filament\Pages\Settings\SystemStatusPage::getUrl(), null, false);
    $danger = collect($issues)->contains(fn ($issue) => $issue['status'] === 'danger');
@endphp
@if (count($issues))
    {{-- Eigen stijlblok: niet elk klantproject compileert de views uit vendor/dashed mee in Tailwind. --}}
    <style>
        .dsh-banner { padding: .5rem 1rem; font-size: .875rem; line-height: 1.35; color: #fff; }
        .dsh-banner--danger { background: #b91c1c; }
        .dsh-banner--warning { background: #b45309; }
        .dsh-banner ul { margin: 0; padding: 0; list-style: none; }
        .dsh-banner li + li { margin-top: .25rem; }
        .dsh-banner strong { font-weight: 600; }
        .dsh-banner a { color: #fff; text-decoration: underline; white-space: nowrap; }
    </style>
    <div class="dsh-banner {{ $danger ? 'dsh-banner--danger' : 'dsh-banner--warning' }}" role="alert" data-system-health-banner>
        <ul>
            @foreach ($issues as $issue)
                <li data-issue="{{ $issue['key'] }}">
                    <strong>{{ $issue['label'] }}:</strong> {{ $issue['detail'] }}
                    @if ($statusUrl)
                        <a href="{{ $statusUrl }}">{{ __('Systeemstatus') }}</a>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif
