<?php

namespace Dashed\DashedCore\Livewire\Frontend\Search;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Classes\AccountHelper;
use Dashed\DashedTranslations\Models\Translation;

class SearchResults extends Component
{
    public ?string $search = '';
    public ?array $searchResults = [];
    public array $blockData = [];

    public function mount(array $blockData = [])
    {
        $this->blockData = $blockData;
        $this->search = request()->get('search', '');
        $this->searchForResults();
    }

    public function searchForResults(): void
    {
        $this->searchResults = cms()->getSearchResults($this->search);
    }

    public function render()
    {
        return view(env('SITE_THEME', 'dashed') . '.search.search-results');
    }
}
