<?php

namespace Dashed\DashedCore\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Dashed\DashedCore\Services\EditPresenceTracker;

class EditingPresenceBanner extends Component
{
    public string $resourceKey = '';

    public string $recordKey = '';

    /**
     * @var array<int, array{name: string, last_seen: int}>
     */
    public array $editors = [];

    public function mount(string $resourceKey, string|int $recordKey): void
    {
        $this->resourceKey = $resourceKey;
        $this->recordKey = (string) $recordKey;

        $this->heartbeat();
    }

    public function heartbeat(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $tracker = app(EditPresenceTracker::class);

        $tracker->ping(
            $this->resourceKey,
            $this->recordKey,
            (int) $user->getKey(),
            $this->resolveUserName($user),
        );

        $this->editors = $tracker->currentEditors(
            $this->resourceKey,
            $this->recordKey,
            (int) $user->getKey(),
        );
    }

    public function render()
    {
        return view('dashed-core::livewire.admin.editing-presence-banner');
    }

    protected function resolveUserName(object $user): string
    {
        foreach (['name', 'full_name', 'first_name', 'email'] as $attr) {
            $value = $user->{$attr} ?? null;
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return 'Iemand';
    }
}
