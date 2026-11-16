<x-filament::page>
    <div class="space-y-6">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ __('Adressen waar Postmark niet meer naartoe stuurt, na een hard bounce, een spamklacht of een handmatige blokkade. Heractiveer een adres alleen als de oorzaak weg is, bijvoorbeeld een volle mailbox die weer leeg is of een typefout die is verbeterd. Een spamklacht kan Postmark niet opheffen. Bij heractiveren gaat het adres ook van de blokkadelijst van de nieuwsbrief, behalve als het daar om een marktplaats of een handmatige blokkade staat.') }}
        </p>

        {{ $this->table }}
    </div>
</x-filament::page>
