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

    public function test_registering_the_same_key_replaces_the_source(): void
    {
        TaskSources::register(TaskSource::make('a', 'A', fn () => 1, '/a'));
        TaskSources::register(TaskSource::make('a', 'A2', fn () => 4, '/a'));

        $this->assertSame(4, TaskSources::openCount());
        $this->assertSame('A2', TaskSources::open()[0]['label']);
    }
}
