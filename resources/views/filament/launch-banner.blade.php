{{-- Balk op het dashboard tot de livegang is afgerond (zie LaunchChecklist). --}}
<style>
    .dsh-launch-banner { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; margin-bottom: 1rem; padding: .75rem 1rem; border-radius: .75rem; background: #ecfdf5; color: #065f46; font-size: .875rem; }
    .dark .dsh-launch-banner { background: rgb(6 95 70 / .3); color: #a7f3d0; }
    .dsh-launch-banner a { color: inherit; font-weight: 600; text-decoration: underline; }
</style>
<div class="dsh-launch-banner" data-launch-banner>
    <span>{{ __('Je winkel is :klaar van :totaal klaar voor de livegang.', ['klaar' => $progress['done'], 'totaal' => $progress['total']]) }}</span>
    <a href="{{ $url }}">{{ __('Bekijk de checklist') }}</a>
</div>
