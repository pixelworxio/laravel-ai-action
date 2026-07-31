<x-pulse::card :cols="$cols" :rows="$rows" :class="$class" wire:poll.5s="">
    <x-pulse::card-header name="AI Actions">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
            </svg>
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand">
        @if ($rows->isEmpty())
            <x-pulse::no-results />
        @else
            <table class="w-full">
                <thead>
                    <tr class="text-left text-xs text-gray-500 dark:text-gray-400">
                        <th class="font-medium pb-2">Action</th>
                        <th class="font-medium pb-2 text-right">Calls</th>
                        <th class="font-medium pb-2 text-right">Cost</th>
                        <th class="font-medium pb-2 text-right">Avg</th>
                        <th class="font-medium pb-2 text-right">Max</th>
                        <th class="font-medium pb-2 text-right">Tokens</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr class="text-sm">
                            <td class="py-1 font-mono">{{ $row->agent }}</td>
                            <td class="py-1 text-right">{{ number_format($row->calls) }}</td>
                            <td class="py-1 text-right">${{ number_format($row->cost, 4) }}</td>
                            <td class="py-1 text-right">{{ $row->avgDurationMs !== null ? number_format($row->avgDurationMs).'ms' : '—' }}</td>
                            <td class="py-1 text-right">{{ $row->maxDurationMs !== null ? number_format($row->maxDurationMs).'ms' : '—' }}</td>
                            <td class="py-1 text-right">{{ $row->tokens !== null ? number_format($row->tokens) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-pulse::scroll>
</x-pulse::card>
