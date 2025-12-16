<?php

namespace Dashed\DashedCore\Livewire\Frontend\Search;

use Livewire\Component;

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

        $this->dispatch('searchInitiated');
    }

    public function render()
    {
        return view(config('dashed-core.site_theme', 'dashed') . '.search.search-results');
    }
}
