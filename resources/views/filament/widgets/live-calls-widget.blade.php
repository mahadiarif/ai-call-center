<x-filament-widgets::widget>
    <x-filament::section>
        <div wire:poll.2000ms>
            {{-- Header --}}
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <span class="relative flex h-3 w-3">
                        @if($activeCalls > 0)
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-success-500"></span>
                        @else
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-gray-400 dark:bg-gray-600"></span>
                        @endif
                    </span>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">📞 Live Call Monitor</h3>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400 font-mono"> BD Time: {{ $currentTime }}</span>
            </div>

            {{-- Stats Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                {{-- Active Now --}}
                <div class="p-4 rounded-xl border-2 transition-all {{ $activeCalls > 0 ? 'border-success-500 bg-success-50 dark:bg-success-950/20' : 'border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50' }}">
                    <div class="text-3xl font-black {{ $activeCalls > 0 ? 'text-success-600 dark:text-success-400' : 'text-gray-400 dark:text-gray-600' }}">
                        {{ $activeCalls }}
                    </div>
                    <div class="text-[10px] uppercase tracking-wider font-bold mt-1 {{ $activeCalls > 0 ? 'text-success-700 dark:text-success-500' : 'text-gray-500' }}">
                        🔴 Active Now
                    </div>
                </div>

                {{-- Last 1 Hour --}}
                <div class="p-4 rounded-xl border-2 border-primary-200 dark:border-primary-900 bg-primary-50 dark:bg-primary-950/20">
                    <div class="text-3xl font-black text-primary-600 dark:text-primary-400">{{ $lastHour }}</div>
                    <div class="text-[10px] uppercase tracking-wider font-bold mt-1 text-primary-700 dark:text-primary-500">⏱ Last 1 Hour</div>
                </div>

                {{-- Today Total --}}
                <div class="p-4 rounded-xl border-2 border-info-200 dark:border-info-900 bg-info-50 dark:bg-info-950/20">
                    <div class="text-3xl font-black text-info-600 dark:text-info-400">{{ $todayTotal }}</div>
                    <div class="text-[10px] uppercase tracking-wider font-bold mt-1 text-info-700 dark:text-info-500">📅 Today Total</div>
                </div>

                {{-- AI Latency --}}
                <div class="p-4 rounded-xl border-2 border-warning-200 dark:border-warning-900 bg-warning-50 dark:bg-warning-950/20">
                    <div class="text-3xl font-black text-warning-600 dark:text-warning-400">{{ number_format($avgLatency / 1000, 2) }}s</div>
                    <div class="text-[10px] uppercase tracking-wider font-bold mt-1 text-warning-700 dark:text-warning-500">⚡ AI Latency (Avg)</div>
                </div>
            </div>

            {{-- Recent Calls Table --}}
            @if($recentCalls && $recentCalls->count() > 0)
                <div class="flex items-center gap-2 mb-3">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">🕑 Recent Activity</p>
                </div>
                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/50">
                            <tr>
                                <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">Customer</th>
                                <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">IVR Service</th>
                                <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-400 text-center">Status</th>
                                <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-400 text-right">Duration / Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($recentCalls as $call)
                                @php
                                    $isLive = $call->status === 'Incoming' && $call->created_at->diffInMinutes(now()) < 15;
                                    $durationSec = $isLive ? $call->created_at->diffInSeconds(now()) : $call->created_at->diffInSeconds($call->updated_at);
                                    $durationStr = $durationSec >= 60 ? floor($durationSec/60) . 'm ' . ($durationSec%60) . 's' : $durationSec . 's';
                                @endphp
                                <tr class="{{ $isLive ? 'bg-success-50/50 dark:bg-success-950/10' : 'bg-white dark:bg-gray-900' }}">
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col">
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $call->customer_name ?: 'Unknown' }}</span>
                                            <span class="text-xs text-gray-500">{{ $call->mobile_number }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                        {{ $call->ivrService?->service_name ?: 'General' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @php
                                            $badgeColor = match($call->status) {
                                                'Incoming' => 'success',
                                                'Resolved' => 'info',
                                                'Drop Call' => 'danger',
                                                default => 'gray'
                                            };
                                        @endphp
                                        <x-filament::badge :color="$badgeColor">
                                            {{ $call->status }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex flex-col items-end">
                                            <span class="font-mono font-bold {{ $isLive ? 'text-success-600 animate-pulse' : 'text-gray-600 dark:text-gray-400' }}">
                                                {{ $durationStr }}
                                            </span>
                                            <span class="text-[10px] text-gray-400 italic">{{ $call->created_at->diffForHumans() }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-12 text-gray-400 dark:text-gray-600">
                    <x-heroicon-o-phone-x-mark class="w-12 h-12 mb-2 opacity-20" />
                    <p class="text-sm">No live calls at the moment</p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>