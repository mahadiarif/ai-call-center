<div style="width: 100%; display: flex; flex-direction: column; gap: 1.5rem;">
    {{-- Top Stats Grid --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; width: 100%;">
        {{-- Live Calls Card --}}
        <div style="padding: 1.5rem; border-radius: 1rem; border: 1px solid rgba(156, 163, 175, 0.2); background-color: rgba(239, 68, 68, 0.05); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); width: 100%;">
            <p style="font-size: 0.875rem; font-weight: 700; color: #ef4444; text-transform: uppercase; letter-spacing: 0.05em;">Live Calls</p>
            <p style="font-size: 2.25rem; font-weight: 900; color: #ef4444; margin-top: 0.5rem;">{{ $activeCalls }}</p>
        </div>

        {{-- Today Total Card --}}
        <div style="padding: 1.5rem; border-radius: 1rem; border: 1px solid rgba(156, 163, 175, 0.2); background-color: rgba(156, 163, 175, 0.05); width: 100%;">
            <p style="font-size: 0.875rem; font-weight: 700; color: gray; text-transform: uppercase; letter-spacing: 0.05em;">Total Today</p>
            <p style="font-size: 2.25rem; font-weight: 900; margin-top: 0.5rem;">{{ $todayTotal }}</p>
        </div>

        {{-- Latency Card --}}
        <div style="padding: 1.5rem; border-radius: 1rem; border: 1px solid rgba(156, 163, 175, 0.2); background-color: rgba(16, 185, 129, 0.05); width: 100%;">
            <p style="font-size: 0.875rem; font-weight: 700; color: #10b981; text-transform: uppercase; letter-spacing: 0.05em;">AI Latency (Avg)</p>
            <p style="font-size: 2.25rem; font-weight: 900; color: #10b981; margin-top: 0.5rem;">{{ number_format($avgLatency / 1000, 2) }}s</p>
        </div>

        {{-- Server Time --}}
        <div style="padding: 1.5rem; border-radius: 1rem; border: 1px solid rgba(156, 163, 175, 0.2); background-color: rgba(59, 130, 246, 0.05); width: 100%;">
            <p style="font-size: 0.875rem; font-weight: 700; color: #3b82f6; text-transform: uppercase; letter-spacing: 0.05em;">Server Time</p>
            <p style="font-size: 1.75rem; font-weight: 800; color: #3b82f6; margin-top: 0.75rem;">{{ $currentTime }}</p>
        </div>
    </div>

    {{-- Live Call List Container --}}
    <div style="width: 100%; border-radius: 1rem; border: 1px solid rgba(156, 163, 175, 0.2); background-color: rgba(156, 163, 175, 0.02); overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
        <div style="padding: 1.25rem; border-bottom: 1px solid rgba(156, 163, 175, 0.2); background-color: rgba(156, 163, 175, 0.05); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 1.25rem; font-weight: 800;">🛰️ Live Operations Monitor</h3>
            <div style="font-size: 0.75rem; font-weight: 700; padding: 0.35rem 1rem; border-radius: 9999px; background-color: #ef4444; color: white; animation: blink 1s infinite; box-shadow: 0 0 15px rgba(239, 68, 68, 0.4);">LIVE</div>
        </div>

        <div style="padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem;">
            @if($recentCalls->isEmpty())
                <div style="text-align: center; padding: 4rem; color: gray;">
                    <p style="font-size: 1.125rem; font-weight: 600;">No active calls at the moment.</p>
                </div>
            @else
                @foreach($recentCalls as $call)
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 1.25rem; border-radius: 0.75rem; border: 1px solid rgba(156, 163, 175, 0.15); background-color: {{ $call->status === 'Incoming' ? 'rgba(239, 68, 68, 0.04)' : 'rgba(156, 163, 175, 0.04)' }}; width: 100%;">
                        {{-- Caller Info --}}
                        <div style="display: flex; align-items: center; gap: 1.5rem; flex: 1;">
                            <div style="width: 14px; height: 14px; border-radius: 50%; background-color: {{ $call->status === 'Incoming' ? '#ef4444' : '#10b981' }}; {{ $call->status === 'Incoming' ? 'box-shadow: 0 0 12px #ef4444;' : '' }}"></div>
                            <div>
                                <p style="font-size: 1.125rem; font-weight: 800;">{{ $call->mobile_number }}</p>
                                <p style="font-size: 0.875rem; color: gray; font-weight: 600;">
                                    {{ $call->ivrService?->service_name ?? 'General' }} • {{ $call->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>

                        {{-- Status & Name --}}
                        <div style="flex: 1; text-align: center;">
                            <p style="font-size: 1rem; font-weight: 800; color: {{ $call->status === 'Incoming' ? '#ef4444' : '#10b981' }};">
                                {{ $call->status }}
                            </p>
                            <p style="font-size: 0.875rem; color: gray; font-weight: 600;">{{ $call->customer_name ?? 'Unknown Customer' }}</p>
                        </div>

                        {{-- Operations Actions --}}
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            @if($call->status === 'Incoming')
                                <button wire:click="transferCall('{{ $call->id }}')" style="padding: 0.5rem 1.25rem; border-radius: 0.5rem; background-color: #f59e0b; color: white; font-size: 0.875rem; font-weight: 800; border: none; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">Agent</button>
                                <button wire:click="hangupCall('{{ $call->id }}')" style="padding: 0.5rem 1.25rem; border-radius: 0.5rem; background-color: #ef4444; color: white; font-size: 0.875rem; font-weight: 800; border: none; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">End</button>
                            @else
                                <a href="{{ route('filament.admin.resources.service-requests.edit', $call->id) }}" style="padding: 0.5rem 1.25rem; border-radius: 0.5rem; background-color: rgba(156, 163, 175, 0.2); color: inherit; font-size: 0.875rem; font-weight: 800; text-decoration: none;">View Details</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <style>
        @keyframes blink { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }
    </style>
</div>