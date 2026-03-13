<?php

namespace Dashed\DashedCore\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;

class SyncChildUrlHistoriesJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300;

    public function __construct(
        public string $modelClass,
        public int|string $modelId,
    ) {
    }

    public function uniqueId(): string
    {
        return 'sync-child-url-histories:' . $this->modelClass . ':' . $this->modelId;
    }

    public function handle(): void
    {
        if (! class_exists($this->modelClass)) {
            return;
        }

        /** @var \Illuminate\Database\Eloquent\Model|null $root */
        $root = $this->modelClass::query()->find($this->modelId);

        if (! $root || ! method_exists($root, 'children')) {
            return;
        }

        $parentIds = [$root->getKey()];

        while (! empty($parentIds)) {
            /** @var Collection $children */
            $children = $this->modelClass::query()
                ->whereIn('parent_id', $parentIds)
                ->get();

            if ($children->isEmpty()) {
                break;
            }

            foreach ($children as $child) {
                SyncModelUrlHistoryJob::dispatch(
                    $child::class,
                    $child->getKey()
                )->afterCommit();
            }

            $parentIds = $children->pluck($root->getKeyName())->all();
        }
    }
}
