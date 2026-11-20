<?php

namespace Dashed\DashedCore\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Dashed\DashedCore\Classes\Tasks\TaskSource;
use Dashed\DashedCore\Classes\Tasks\TaskSources;

class TaskSourcesTest extends TestCase
{
    protected function tearDown(): void
    {
        TaskSources::flush();
        parent::tearDown();
    }

    public function test_only_sources_with_open_work_that_the_user_may_see_are_returned_in_group_order(): void
    {
        TaskSources::register(
            TaskSource::make('retouren', 'Retouren', fn () => 2, '/retouren', group: 'Shop', sort: 2),
            TaskSource::make('aanvragen', 'Aanvragen', fn () => 3, '/aanvragen', group: 'Maatwerk'),
            TaskSource::make('reviews', 'Reviews', fn () => 1, '/reviews', group: 'Shop', sort: 1),
            TaskSource::make('leeg', 'Leeg', fn () => 0, '/leeg', group: 'Shop'),
            TaskSource::make('verborgen', 'Verborgen', fn () => 5, '/x', group: 'Shop', visible: fn () => false),
            TaskSource::make('kapot', 'Kapot', fn () => throw new \RuntimeException('db weg'), '/x', group: 'Shop'),
        );

        $open = TaskSources::open();

        $this->assertSame(['reviews', 'retouren', 'aanvragen'], array_column($open, 'key'));
        $this->assertSame(6, TaskSources::openCount());
    }

    public function test_items_lists_the_records_of_every_visible_source_or_of_one(): void
    {
        TaskSources::register(
            TaskSource::make('aanvragen', 'Aanvragen', fn () => 2, '/aanvragen', group: 'Maatwerk', items: fn () => [
                ['id' => 7, 'title' => 'Urn Bello', 'date' => '2026-11-18 10:00:00', 'url' => '/aanvragen/7'],
                ['id' => 8, 'title' => 'Vaas', 'description' => 'Spoed'],
            ]),
            TaskSource::make('retouren', 'Retouren', fn () => 1, '/retouren', items: fn () => [['id' => 3, 'title' => 'Retour 3']]),
            TaskSource::make('alleen-teller', 'Teller', fn () => 4, '/x'),
            TaskSource::make('verborgen', 'Verborgen', fn () => 1, '/x', visible: fn () => false, items: fn () => [['id' => 1, 'title' => 'x']]),
            TaskSource::make('kapot', 'Kapot', fn () => 1, '/x', items: fn () => throw new \RuntimeException('db weg')),
        );

        $items = TaskSources::items();

        $this->assertSame(['aanvragen|7', 'aanvragen|8', 'retouren|3'], array_column($items, 'key'));
        $this->assertSame('Aanvragen', $items[0]['label']);
        $this->assertSame('Maatwerk', $items[0]['group']);
        $this->assertSame('/aanvragen/7', $items[0]['url']);
        $this->assertSame('2026-11-18', $items[0]['date']->toDateString());
        $this->assertNull($items[1]['date']);
        $this->assertSame('Spoed', $items[1]['description']);

        $this->assertSame(['retouren|3'], array_column(TaskSources::items('retouren'), 'key'));

        $tiles = collect(TaskSources::open())->keyBy('key');
        $this->assertTrue($tiles['aanvragen']['has_items']);
        $this->assertFalse($tiles['alleen-teller']['has_items']);
    }

    public function test_registering_the_same_key_replaces_the_source(): void
    {
        TaskSources::register(TaskSource::make('a', 'A', fn () => 1, '/a'));
        TaskSources::register(TaskSource::make('a', 'A2', fn () => 4, '/a'));

        $this->assertSame(4, TaskSources::openCount());
        $this->assertSame('A2', TaskSources::open()[0]['label']);
    }
}
