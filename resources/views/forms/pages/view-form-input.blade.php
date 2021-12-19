<div class="flex-1 w-full px-4 mx-auto md:px-6 lg:px-8 max-w-7xl">
    <div>
        <div class="space-y-6">
            <header class="space-y-2 items-start justify-between sm:flex sm:space-y-0 sm:space-x-4 sm:py-4">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    {{ $this->getTitle() }}
                </h1>
            </header>

            <div>
                <div class="grid grid-cols-6 gap-8">
                    <div class="col-span-4">
                        <div class="text-sm bg-white rounded-md p-4">
                            <h2 class="text-2xl font-bold">Formulier invoer</h2>
                            <div v-for="(value, name) in formInput.content" class="mt-4">
                                <p class="font-bold">{{$test ?? 'Niet ingevuld'}}:</p>
                                <div v-html="value"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-span-2 bg-white rounded-md p-4">
                        <div>
                            <h2 class="text-2xl font-bold mb-4">Overige informatie</h2>
                            <ul class="space-y-4">
                                <hr>
                                <li>IP: {{$test ?? 'Niet ingevuld'}}</li>
                                <hr>
                                <li>User agent: {{$test ?? 'Niet ingevuld'}}</li>
                                <hr>
                                <li>Ingevoerd vanaf: {{$test ?? 'Niet ingevuld'}}</li>
                                <hr>
                                <li>Ingevoerd op: {{$test ?? 'Niet ingevuld'}}</li>
                                <hr>
                                <li>Site ID: {{$test ?? 'Niet ingevuld'}}</li>
                                <hr>
                                <li>Locale: {{$test ?? 'Niet ingevuld'}}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
