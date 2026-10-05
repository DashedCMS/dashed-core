<?php

declare(strict_types=1);

use Dashed\DashedCore\Middleware\RejectTamperedLivewireUpdates;

/**
 * De vorm van de payload die sinds december 2025 op /livewire/update wordt afgevuurd
 * (CVE-2025-54068): een publieke property krijgt een lijst met daarin tuples die zich
 * voordoen als Livewire-synthesizers.
 */
it('herkent de gadget-payload van de Livewire-exploit', function () {
    $updates = [
        'filters' => [
            1,
            [
                ['a' => [['__toString' => 'phpversion'], ['s' => 'clctn', 'class' => 'GuzzleHttp\\Psr7\\FnStream']]],
                ['class' => 'League\\Flysystem\\UrlGeneration\\ShardedPrefixPublicUrlGenerator', 's' => 'clctn'],
            ],
        ],
    ];

    expect(RejectTamperedLivewireUpdates::containsSyntheticTuple($updates))->toBeTrue();
});

it('herkent een tuple direct op een property', function () {
    expect(RejectTamperedLivewireUpdates::containsSyntheticTuple([
        'search' => [null, ['s' => 'mdl', 'class' => 'Laravel\\Prompts\\Terminal']],
    ]))->toBeTrue();
});

it('laat gewone updates van formulieren en filters door', function () {
    expect(RejectTamperedLivewireUpdates::containsSyntheticTuple([
        'search' => 'bank',
        'quantity' => 2,
        'filters.0.active' => 14,
        'categoryIds' => [3, 7],
        'extras' => [['id' => 730, 'value' => 'Lounge links'], ['id' => 731, 'value' => null]],
        'data' => ['name' => 'Robin', 'tags' => ['a', 'b'], 's' => 'zoekterm'],
        'range' => [10, ['min' => 0, 'max' => 50]],
    ]))->toBeFalse();
});

it('weigert absurd diep geneste waarden in plaats van eindeloos af te dalen', function () {
    $value = 'x';
    for ($i = 0; $i < 200; $i++) {
        $value = [$value];
    }

    expect(RejectTamperedLivewireUpdates::containsSyntheticTuple(['data' => $value]))->toBeTrue();
});

it('negeert alles wat geen array is', function () {
    expect(RejectTamperedLivewireUpdates::containsSyntheticTuple(null))->toBeFalse()
        ->and(RejectTamperedLivewireUpdates::containsSyntheticTuple('tekst'))->toBeFalse();
});
