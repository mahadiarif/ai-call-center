<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    {{-- Top Stats Grid --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <div style="background-color: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <p style="font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase;">Live Calls</p>
            <p style="font-size: 1.875rem; font-weight: 800; color: #ef4444;">{{ $activeCalls }}</p>
        </div>
        <div style="background-color: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <p style="font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase;">Total Today</p>
            <p style="font-size: 1.875rem; font-weight: 800; color: #1f2937;">{{ $todayTotal }}</p>
        </div>
        <div style="background-color: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <p style="font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase;">AI Latency (Avg)</p>
            <p style="font-size: 1.875rem; font-weight: 800; color: #10b981;">{{ number_format($avgLatency / 1000, 2) }}s</p>
        </div>
        <div style="background-color: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <p style="font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase;">Server Time</p>
            <p style="font-size: 1.875rem; font-weight: 800; color: #3b82f6;">{{ $currentTime }}</p>
        </div>
    </div>

    {{-- Live Call List --}}
    <div style="background-color: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
        <div style="padding: 1rem; border-bottom: 1px solid #f3f4f6; background-color: #f9fafb;">
            <h3 style="font-size: 1rem; font-weight: 700; color: #111827;">Live Call Operations</h3>
        </div>

        <div style="padding: 1rem;">
            @if($recentCalls->isEmpty())
                <p style="text-align: center; color: #9ca3af; padding: 2rem;">No calls recorded today.</p>
            @else
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    @foreach($recentCalls as $call)
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem; border: 1px solid {{ $call->status === 'Incoming' ? '#fee2e2' : '#f3f4f6' }}; border-radius: 0.5rem; background-color: {{ $call->status === 'Incoming' ? '#fff5f5' : 'white' }};">
                            {{-- Caller Info --}}
                            <div style="display: flex; align-items: center; gap: 1rem; flex: 1;">
                                <div style="width: 10px; height: 10px; border-radius: 50%; background-color: {{ $call->status === 'Incoming' ? '#ef4444' : '#10b981' }}; {{ $call->status === 'Incoming' ? 'animation: pulse 2s infinite;' : '' }}"></div>
                                <div>
                                    <p style="font-size: 0.875rem; font-weight: 700; color: #111827;">{{ $call->mobile_number }}</p>
                                    <p style="font-size: 0.75rem; color: #6b7280;">{{ $call->ivrService?->service_name ?? 'General' }} • {{ $call->created_at->diffForHumans() }}</p>
                                </div>
                            </div>

                            {{-- Status Badge --}}
                            <div style="flex: 1; text-align: center;">
                                <span style="display: inline-block; padding: 0.25rem 0.75rem; font-size: 0.75rem; font-weight: 700; border-radius: 9999px; background-color: {{ $call->status === 'Incoming' ? '#fee2e2' : '#d1fae5' }}; color: {{ $call->status === 'Incoming' ? '#dc2626' : '#059669' }};">
                                    {{ $call->status }}
                                </span>
                            </div>

                            {{-- Operations Actions --}}
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                @if($call->status === 'Incoming')
                                    <x-filament::button 
                                        size="xs" 
                                        color="warning"
                                        tooltip="Transfer to Human Agent"
                                        wire:click="transferCall('{{ $call->id }}')"
                                    >
                                        Agent
                                    </x-filament::button>
                                    <x-filament::button 
                                        size="xs" 
                                        color="danger"
                                        tooltip="Force Hangup"
                                        wire:click="hangupCall('{{ $call->id }}')"
                                    >
                                        End
                                    </x-filament::button>
                                @else
                                    <x-filament::button 
                                        size="xs" 
                                        color="gray"
                                        tag="a"
                                        href="{{ route('filament.admin.resources.service-requests.edit', $call->id) }}"
                                    >
                                        View
                                    </x-filament::button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <style>
        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
    </style>
</div>