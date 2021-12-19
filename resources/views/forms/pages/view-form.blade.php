<div class="flex-1 w-full px-4 mx-auto md:px-6 lg:px-8 max-w-7xl">
    <div>
        <div class="space-y-6">
            <header class="space-y-2 items-start justify-between sm:flex sm:space-y-0 sm:space-x-4 sm:py-4">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    {{ $this->getTitle() }}
                </h1>
            </header>
            {{ $this->table }}
        </div>
    </div>
</div>
