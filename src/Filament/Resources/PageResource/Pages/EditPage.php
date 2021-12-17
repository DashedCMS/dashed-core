<?php

namespace Qubiqx\QcommerceCore\Filament\Resources\PageResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\EditRecord\Concerns\Translatable;
use Illuminate\Support\Str;
use Qubiqx\QcommerceCore\Classes\Sites;
use Qubiqx\QcommerceCore\Filament\Resources\PageResource;
use Qubiqx\QcommerceCore\Models\Page;

class EditPage extends EditRecord
{
    use Translatable;

    protected static string $resource = PageResource::class;

    //Todo: add action button to directly view the page

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['slug'] = Str::slug($data['slug'] ?: $data['title']);

        while (Page::where('id', '!=', $this->record->id)->where('slug->' . $this->activeFormLocale, $data['slug'])->count()) {
            $data['slug'] .= Str::random(1);
        }

        $data['site_id'] = $data['site_id'] ?: Sites::getFirstSite()['id'];

        $content = $data['content'];
        $data['content'] = $this->record->content;
        $data['content'][$this->activeFormLocale] = $content;

        return $data;
    }
}
