    @php
        $schema = \Spatie\SchemaOrg\Schema::breadcrumbList();
            $items = [];
            $loop = 1;
        foreach($breadcrumbs as $breadcrumb){
            $items[] = \Spatie\SchemaOrg\Schema::listItem()
                ->position($loop)
                ->url($breadcrumb['url'])
                ->name($breadcrumb['name'])
                ->identifier($breadcrumb['name'])
                ->item(\Spatie\SchemaOrg\Schema::webPage()->url($breadcrumb['url'])->name($breadcrumb['name']));
            $loop++;
        }
        $schema->itemListElement($items);
    @endphp
    {!! $schema !!}
