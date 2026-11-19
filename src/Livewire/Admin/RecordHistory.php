<?php

namespace Dashed\DashedCore\Livewire\Admin;

use Livewire\Component;
use Livewire\Attributes\Locked;
use Dashed\DashedCore\Models\User;
use Dashed\DashedCore\Models\Revision;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Classes\Revisions\Revisions;
use Dashed\DashedCore\Classes\Revisions\RevisionDiff;
use Dashed\DashedCore\Classes\Revisions\ContentApproval;
use Dashed\DashedCore\Classes\Revisions\ActivityTimeline;

/**
 * De slide-over "Versies" op een bewerkpagina: de revisies van het record
 * (met vergelijken en terugzetten) en de wijzigingen uit de activitylog.
 * Eén component voor beide, zodat elke bewerkpagina met één actie
 * (RecordHistoryAction) meedoet.
 */
class RecordHistory extends Component
{
    #[Locked]
    public string $modelClass = '';

    #[Locked]
    public string $recordKey = '';

    #[Locked]
    public string $returnUrl = '';

    public string $tab = 'revisions';

    public ?int $compareId = null;

    public string $compareWith = 'previous';

    protected ?Model $resolved = null;

    public function mount(): void
    {
        $record = $this->record();
        abort_unless($record && auth()->user()?->can('update', $record), 403);

        if (! Revisions::supports($record)) {
            $this->tab = 'activity';
        }
    }

    public function record(): ?Model
    {
        if ($this->resolved) {
            return $this->resolved;
        }

        if (! is_a($this->modelClass, Model::class, true)) {
            return null;
        }

        return $this->resolved = $this->modelClass::query()->withoutGlobalScopes()->whereKey($this->recordKey)->first();
    }

    public function showTab(string $tab): void
    {
        $this->tab = in_array($tab, ['revisions', 'activity'], true) ? $tab : 'revisions';
    }

    public function compare(int $revisionId, string $with = 'previous'): void
    {
        $this->compareId = $this->compareId === $revisionId && $this->compareWith === $with ? null : $revisionId;
        $this->compareWith = $with === 'live' ? 'live' : 'previous';
    }

    public function restore(int $revisionId)
    {
        $record = $this->record();
        $user = auth()->user();
        abort_unless($record && $user instanceof User && $user->can('update', $record), 403);

        $revision = Revision::query()->forRecord($record)->findOrFail($revisionId);
        $asDraft = ContentApproval::savesAsDraft($record, $user);

        Revisions::restore($revision, $user);

        Notification::make()
            ->title($asDraft
                ? __('De versie staat klaar als concept. Bied het aan ter beoordeling om het te publiceren.')
                : __('De versie is teruggezet'))
            ->success()
            ->send();

        return redirect()->to($this->returnUrl ?: url()->previous());
    }

    public function render()
    {
        $record = $this->record();
        $revisions = collect();
        $diff = null;

        if ($record && Revisions::supports($record)) {
            $revisions = Revision::query()
                ->forRecord($record)
                ->with(['user', 'reviewer'])
                ->latest('id')
                ->limit(100)
                ->get();

            if ($this->compareId && ($selected = $revisions->firstWhere('id', $this->compareId))) {
                $other = $this->compareWith === 'live'
                    ? Revisions::latestLive($record)
                    : Revision::query()->forRecord($record)->live()->where('id', '<', $selected->id)->latest('id')->first();

                // Met 'live' is dit wat terugzetten zou veranderen: van de
                // huidige live versie naar de gekozen versie.
                $diff = RevisionDiff::between($other?->snapshot, $selected->snapshot, $record);
            }
        }

        return view('dashed-core::livewire.admin.record-history', [
            'record' => $record,
            'supportsRevisions' => $record && Revisions::supports($record),
            'supportsActivity' => ActivityTimeline::supports($record),
            'revisions' => $revisions,
            'liveId' => $revisions->first(fn (Revision $r) => $r->isLive())?->id,
            'diff' => $diff,
            'activities' => $this->tab === 'activity' && $record ? ActivityTimeline::for($record) : [],
        ]);
    }
}
