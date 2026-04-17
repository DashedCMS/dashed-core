<x-filament-widgets::widget class="fi-wi-horizon-failed-jobs">
    <x-filament::section :heading="$this->getHeading()">
        <x-slot name="headerEnd">
            <div class="flex gap-2">
                @if($failedJobs->isNotEmpty())
                    <x-filament::button
                        wire:click="retryAllJobs"
                        wire:loading.attr="disabled"
                        color="warning"
                        size="sm"
                        icon="heroicon-o-arrow-path"
                    >
                        Alles opnieuw proberen
                    </x-filament::button>
                @endif

                <x-filament::button
                    wire:click="flushFailed"
                    wire:loading.attr="disabled"
                    wire:confirm="Weet je zeker dat je alle failed jobs wilt verwijderen?"
                    color="danger"
                    size="sm"
                    icon="heroicon-o-trash"
                >
                    Flush failed
                </x-filament::button>
            </div>
        </x-slot>

        @if($failedJobs->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <x-filament::icon
                    icon="heroicon-o-check-circle"
                    class="h-12 w-12 text-success-500 mb-4"
                />
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                    Geen failed jobs
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Er zijn geen gefaalde jobs.
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm divide-y divide-gray-200 dark:divide-white/10">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Job
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Queue
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Foutmelding
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Tijdstip
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Acties
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach($failedJobs as $job)
                            @php
                                $jobName = $job->name ?? '';
                                $shortName = strlen($jobName) > 50 ? '...' . substr($jobName, -47) : $jobName;
                                $parts = explode('\\', $jobName);
                                $displayName = end($parts);
                                $displayName = strlen($displayName) > 50 ? substr($displayName, 0, 50) . '...' : $displayName;

                                $exception = $job->exception ?? '';
                                $firstLine = strtok($exception, "\n");
                                $shortException = strlen($firstLine) > 80 ? substr($firstLine, 0, 80) . '...' : $firstLine;

                                $failedAt = isset($job->failed_at) ? \Carbon\Carbon::createFromTimestamp($job->failed_at) : null;
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-mono text-xs text-gray-900 dark:text-white" title="{{ $jobName }}">
                                        {{ $displayName }}
                                    </div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 font-mono mt-0.5 truncate max-w-xs" title="{{ $jobName }}">
                                        {{ $jobName }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <x-filament::badge color="info" size="sm">
                                        {{ $job->queue ?? 'default' }}
                                    </x-filament::badge>
                                </td>
                                <td class="px-4 py-3 max-w-sm">
                                    <span
                                        class="text-xs text-red-600 dark:text-red-400 cursor-help"
                                        title="{{ $exception }}"
                                    >{{ $shortException ?: '-' }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $failedAt ? $failedAt->format('d-m-Y H:i:s') : '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-filament::button
                                            wire:click="retryJob('{{ $job->id }}')"
                                            wire:loading.attr="disabled"
                                            color="warning"
                                            size="xs"
                                            icon="heroicon-o-arrow-path"
                                        >
                                            Retry
                                        </x-filament::button>

                                        <x-filament::button
                                            wire:click="deleteJob('{{ $job->id }}')"
                                            wire:loading.attr="disabled"
                                            wire:confirm="Weet je zeker dat je deze job wilt verwijderen?"
                                            color="danger"
                                            size="xs"
                                            icon="heroicon-o-trash"
                                        >
                                            Verwijder
                                        </x-filament::button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400 text-right">
                {{ $failedJobs->count() }} failed {{ $failedJobs->count() === 1 ? 'job' : 'jobs' }}
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
