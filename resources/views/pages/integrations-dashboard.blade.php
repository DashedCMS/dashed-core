<x-filament-panels::page>
    @php
        $byCategory = $this->integrations;
    @endphp

    @if(empty($byCategory))
        <div class="fi-section rounded-xl bg-white p-6 ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Er zijn nog geen integraties geregistreerd. Provider-pakketten registreren zichzelf via
                <code>cms()->registerIntegration(...)</code> in hun service-provider.
            </p>
        </div>
    @endif

    @foreach($byCategory as $category => $rows)
        <section class="space-y-3">
            <h2 class="text-base font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-300">
                {{ ucfirst($category) }}
            </h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($rows as $row)
                    <x-dashed-core::integration-card
                        :definition="$row['definition']"
                        :health="$row['health']"
                    />
                @endforeach
            </div>
        </section>
    @endforeach
</x-filament-panels::page>
