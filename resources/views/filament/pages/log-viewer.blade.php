<x-filament-panels::page>

    {{-- Toolbar --}}
    <div class="flex flex-wrap items-end gap-4">

        {{-- File selector --}}
        <div class="flex-1 min-w-48">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Log File</label>
            <select wire:model.live="selectedFile"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900
                           text-sm text-gray-900 dark:text-gray-100 px-3 py-2
                           focus:outline-none focus:ring-2 focus:ring-primary-500">
                @foreach($this->logFiles() as $path => $name)
                    <option value="{{ $path }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Level filter --}}
        <div class="w-44">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Level</label>
            <select wire:model.live="selectedLevel"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900
                           text-sm text-gray-900 dark:text-gray-100 px-3 py-2
                           focus:outline-none focus:ring-2 focus:ring-primary-500">
                @foreach($this->levels() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Line limit --}}
        <div class="w-36">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Last N lines</label>
            <select wire:model.live="lineLimit"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900
                           text-sm text-gray-900 dark:text-gray-100 px-3 py-2
                           focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="100">100</option>
                <option value="500">500</option>
                <option value="1000">1000</option>
                <option value="2000">2000</option>
            </select>
        </div>

        {{-- Clear log button --}}
        <button wire:click="clearLog"
                wire:confirm="Clear this log file? This cannot be undone."
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-50 hover:bg-red-100
                       text-red-700 text-sm font-medium border border-red-200 transition-colors">
            <x-heroicon-o-trash class="w-4 h-4"/>
            Clear Log
        </button>
    </div>

    {{-- Log entries --}}
    @php $logs = $this->getLogs(); @endphp

    @if(empty($logs))
        <div class="flex flex-col items-center justify-center py-16 text-gray-400">
            <x-heroicon-o-check-circle class="w-12 h-12 mb-3 text-emerald-400"/>
            <p class="text-sm font-medium">No log entries found</p>
        </div>
    @else
        <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">
            Showing {{ count($logs) }} entries (newest first)
        </div>

        <div class="space-y-2">
            @foreach($logs as $entry)
                @php
                    $level = $entry['level'];
                    $colors = match($level) {
                        'emergency', 'alert', 'critical' => ['bg' => 'bg-red-50 dark:bg-red-950/30',   'border' => 'border-red-400',   'badge' => 'bg-red-600 text-white',        'text' => 'text-red-900 dark:text-red-200'],
                        'error'                          => ['bg' => 'bg-orange-50 dark:bg-orange-950/30', 'border' => 'border-orange-400', 'badge' => 'bg-orange-500 text-white',   'text' => 'text-orange-900 dark:text-orange-200'],
                        'warning'                        => ['bg' => 'bg-yellow-50 dark:bg-yellow-950/30', 'border' => 'border-yellow-400', 'badge' => 'bg-yellow-500 text-white',   'text' => 'text-yellow-900 dark:text-yellow-200'],
                        'notice', 'info'                 => ['bg' => 'bg-blue-50 dark:bg-blue-950/30',  'border' => 'border-blue-400',   'badge' => 'bg-blue-500 text-white',       'text' => 'text-blue-900 dark:text-blue-200'],
                        default                          => ['bg' => 'bg-gray-50 dark:bg-gray-800/50',  'border' => 'border-gray-300',   'badge' => 'bg-gray-500 text-white',       'text' => 'text-gray-800 dark:text-gray-200'],
                    };
                    // Split message from stack trace (stack trace starts after the JSON context)
                    $parts       = preg_split('/(\{".*?\})\s*(?=\n|$)/s', $entry['message'], 2, PREG_SPLIT_DELIM_CAPTURE);
                    $mainMessage = trim($parts[0] ?? $entry['message']);
                    $context     = isset($parts[1]) ? trim($parts[1]) : '';
                    $stackTrace  = isset($parts[2]) ? trim($parts[2]) : '';
                @endphp

                <details class="rounded-xl border-l-4 {{ $colors['bg'] }} {{ $colors['border'] }} overflow-hidden">
                    <summary class="flex items-start gap-3 px-4 py-3 cursor-pointer select-none list-none">
                        {{-- Level badge --}}
                        <span class="shrink-0 inline-block px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wide mt-0.5 {{ $colors['badge'] }}">
                            {{ $level }}
                        </span>

                        {{-- Datetime + env --}}
                        <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400 mt-0.5 whitespace-nowrap">
                            {{ $entry['datetime'] }}
                            @if($entry['env'] !== app()->environment())
                                <span class="ml-1 font-medium text-gray-600">[{{ $entry['env'] }}]</span>
                            @endif
                        </span>

                        {{-- Message --}}
                        <span class="flex-1 text-sm font-medium {{ $colors['text'] }} break-words min-w-0">
                            {{ Str::limit($mainMessage, 200) }}
                        </span>

                        {{-- Expand icon --}}
                        <x-heroicon-o-chevron-down class="w-4 h-4 shrink-0 text-gray-400 mt-0.5 transition-transform details-open:rotate-180"/>
                    </summary>

                    {{-- Expanded: full message + stack trace --}}
                    <div class="px-4 pb-4 pt-1 space-y-3 border-t border-current/10">
                        <pre class="text-xs {{ $colors['text'] }} whitespace-pre-wrap break-words font-mono leading-relaxed">{{ $mainMessage }}</pre>

                        @if($stackTrace)
                            <details class="mt-2">
                                <summary class="text-xs text-gray-500 cursor-pointer select-none">Stack trace</summary>
                                <pre class="mt-2 text-xs text-gray-600 dark:text-gray-300 whitespace-pre-wrap break-words font-mono leading-relaxed bg-gray-100 dark:bg-gray-900 rounded p-3 overflow-x-auto">{{ $stackTrace }}</pre>
                            </details>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>
    @endif

</x-filament-panels::page>
