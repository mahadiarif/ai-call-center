<x-filament-widgets::widget>
    <x-filament::section>
        <div wire:poll.2000ms>
            {{-- Header --}}
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem;">
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <div style="position:relative; display:flex; height:0.75rem; width:0.75rem;">
                        @if($activeCalls > 0)
                            <span style="position:absolute; height:100%; width:100%; border-radius:9999px; background-color:rgb(34, 197, 94); opacity:0.75; animation: ping 1s cubic-bezier(0, 0, 0.2, 1) infinite;"></span>
                            <span style="position:relative; height:0.75rem; width:0.75rem; border-radius:9999px; background-color:rgb(34, 197, 94);"></span>
                        @else
                            <span style="position:relative; height:0.75rem; width:0.75rem; border-radius:9999px; background-color:rgb(156, 163, 175);"></span>
                        @endif
                    </div>
                    <h3 style="font-size:1.125rem; font-weight:700;">📞 Live Call Monitor</h3>
                </div>
                <span style="font-size:0.75rem; color:rgb(156, 163, 175); font-family:monospace;">BD Time: {{ $currentTime }}</span>
            </div>

            {{-- Stats Cards --}}
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
                {{-- Active Now --}}
                <div style="padding:1rem; border-radius:0.75rem; border:2px solid {{ $activeCalls > 0 ? 'rgb(34, 197, 94)' : 'rgba(156, 163, 175, 0.2)' }}; background-color: {{ $activeCalls > 0 ? 'rgba(34, 197, 94, 0.1)' : 'rgba(156, 163, 175, 0.05)' }};">
                    <div style="font-size:1.875rem; font-weight:900; color: {{ $activeCalls > 0 ? 'rgb(34, 197, 94)' : 'rgb(156, 163, 175)' }};">
                        {{ $activeCalls }}
                    </div>
                    <div style="font-size:0.625rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin-top:0.25rem;">
                        🔴 Active Now
                    </div>
                </div>

                {{-- Last 1 Hour --}}
                <div style="padding:1rem; border-radius:0.75rem; border:2px solid rgb(59, 130, 246); background-color: rgba(59, 130, 246, 0.1);">
                    <div style="font-size:1.875rem; font-weight:900; color: rgb(59, 130, 246);">{{ $lastHour }}</div>
                    <div style="font-size:0.625rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin-top:0.25rem; color: rgb(59, 130, 246);">⏱ Last 1 Hour</div>
                </div>

                {{-- Today Total --}}
                <div style="padding:1rem; border-radius:0.75rem; border:2px solid rgb(139, 92, 246); background-color: rgba(139, 92, 246, 0.1);">
                    <div style="font-size:1.875rem; font-weight:900; color: rgb(139, 92, 246);">{{ $todayTotal }}</div>
                    <div style="font-size:0.625rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin-top:0.25rem; color: rgb(139, 92, 246);">📅 Today Total</div>
                </div>

                {{-- AI Latency --}}
                <div style="padding:1rem; border-radius:0.75rem; border:2px solid rgb(245, 158, 11); background-color: rgba(245, 158, 11, 0.1);">
                    <div style="font-size:1.875rem; font-weight:900; color: rgb(245, 158, 11);">{{ number_format($avgLatency / 1000, 2) }}s</div>
                    <div style="font-size:0.625rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin-top:0.25rem; color: rgb(245, 158, 11);">⚡ AI Latency (Avg)</div>
                </div>
            </div>

            {{-- Recent Calls Table --}}
            @if($recentCalls && $recentCalls->count() > 0)
                <p style="font-size:0.875rem; font-weight:600; margin-bottom:0.75rem;">🕑 Recent Activity</p>
                <div style="overflow:hidden; border-radius:0.5rem; border:1px solid rgba(156, 163, 175, 0.2);">
                    <table style="width:100%; text-align:left; font-size:0.875rem; border-collapse: collapse;">
                        <thead style="background-color: rgba(156, 163, 175, 0.05);">
                            <tr>
                                <th style="padding:0.75rem 1rem; font-weight:600;">Customer</th>
                                <th style="padding:0.75rem 1rem; font-weight:600;">IVR Service</th>
                                <th style="padding:0.75rem 1rem; font-weight:600; text-align:center;">Status</th>
                                <th style="padding:0.75rem 1rem; font-weight:600; text-align:right;">Duration</th>
                            </tr>
                        </thead>
                        <tbody style="background-color: transparent;">
                            @foreach($recentCalls as $call)
                                @php
                                    $isLive = $call->status === 'Incoming' && $call->created_at->diffInMinutes(now()) < 15;
                                    $durationSec = $isLive ? $call->created_at->diffInSeconds(now()) : $call->created_at->diffInSeconds($call->updated_at);
                                    $durationStr = $durationSec >= 60 ? floor($durationSec/60) . 'm ' . ($durationSec%60) . 's' : $durationSec . 's';
                                    $rowBg = $isLive ? 'rgba(34, 197, 94, 0.05)' : 'transparent';
                                @endphp
                                <tr style="background-color: {{ $rowBg }}; border-top: 1px solid rgba(156, 163, 175, 0.1);">
                                    <td style="padding:0.75rem 1rem;">
                                        <div style="display:flex; flex-direction:column;">
                                            <span style="font-weight:600;">{{ $call->customer_name ?: 'Unknown' }}</span>
                                            <span style="font-size:0.75rem; color:rgb(156, 163, 175);">{{ $call->mobile_number }}</span>
                                        </div>
                                    </td>
                                    <td style="padding:0.75rem 1rem; color:rgb(107, 114, 128);">
                                        {{ $call->ivrService?->service_name ?: 'General' }}
                                    </td>
                                    <td style="padding:0.75rem 1rem; text-align:center;">
                                        @php
                                            $badgeColor = match($call->status) {
                                                'Incoming' => 'rgb(34, 197, 94)',
                                                'Resolved' => 'rgb(59, 130, 246)',
                                                'Drop Call' => 'rgb(239, 68, 68)',
                                                default => 'rgb(156, 163, 175)'
                                            };
                                        @endphp
                                        <span style="padding:0.125rem 0.5rem; border-radius:9999px; font-size:0.75rem; font-weight:600; background-color:{{ $badgeColor }}; color:white;">
                                            {{ $call->status }}
                                        </span>
                                    </td>
                                    <td style="padding:0.75rem 1rem; text-align:right;">
                                        <div style="display:flex; flex-direction:column; align-items:flex-end;">
                                            <span style="font-weight:700; color: {{ $isLive ? 'rgb(34, 197, 94)' : 'inherit' }}">
                                                {{ $durationStr }}
                                            </span>
                                            <span style="font-size:0.625rem; color:rgb(156, 163, 175);">{{ $call->created_at->diffForHumans() }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:3rem 0; color:rgb(156, 163, 175);">
                    <p style="font-size:0.875rem;">No live calls at the moment</p>
                </div>
            @endif
        </div>
    </x-filament::section>
    
    <style>
        @keyframes ping {
            75%, 100% {
                transform: scale(2);
                opacity: 0;
            }
        }
    </style>
</x-filament-widgets::widget>