    @php
        $schema = \Spatie\SchemaOrg\Schema::breadcrumbList();
        foreach($breadcrumbs as $breadcrumb){
        $schema->itemListElement([
            \Spatie\SchemaOrg\Schema::listItem()
                ->position(1)
                ->item(\Spatie\SchemaOrg\Schema::webPage()->url($breadcrumb['url'])->name($breadcrumb['name'])),
        ]);
        }
    @endphp
    {!! $schema !!}
