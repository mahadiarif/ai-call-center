<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        {{-- Stats Cards --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
            {{-- Status --}}
            <div style="padding: 1rem; border-radius: 0.75rem; border: 1px solid rgba(156, 163, 175, 0.2); background-color: {{ $isAsteriskRunning ? 'rgba(34, 197, 94, 0.1)' : 'rgba(239, 68, 68, 0.1)' }}; display: flex; align-items: center; gap: 1rem;">
                <div style="width: 10px; height: 10px; border-radius: 50%; background-color: {{ $isAsteriskRunning ? 'rgb(34, 197, 94)' : 'rgb(239, 68, 68)' }};"></div>
                <div>
                    <div style="font-size: 0.75rem; color: rgb(107, 114, 128);">Asterisk Status</div>
                    <div style="font-size: 1rem; font-weight: 700; color: {{ $isAsteriskRunning ? 'rgb(22, 163, 74)' : 'rgb(220, 38, 38)' }};">
                        {{ $isAsteriskRunning ? 'Online / Running' : 'Offline / Stopped' }}
                    </div>
                </div>
            </div>

            {{-- Active Channels --}}
            <div style="padding: 1rem; border-radius: 0.75rem; border: 1px solid rgba(156, 163, 175, 0.2); background-color: rgba(59, 130, 246, 0.05); display: flex; flex-direction: column;">
                <div style="font-size: 0.75rem; color: rgb(107, 114, 128);">Active Channels</div>
                <div style="font-size: 1.5rem; font-weight: 900; color: rgb(37, 99, 235);">{{ $activeChannels }}</div>
            </div>

            {{-- Uptime --}}
            <div style="padding: 1rem; border-radius: 0.75rem; border: 1px solid rgba(156, 163, 175, 0.2); background-color: rgba(139, 92, 246, 0.05); display: flex; flex-direction: column;">
                <div style="font-size: 0.75rem; color: rgb(107, 114, 128);">System Uptime</div>
                <div style="font-size: 1.125rem; font-weight: 700; color: rgb(124, 58, 237);">{{ $asteriskUptime }}</div>
            </div>

            {{-- Endpoints --}}
            <div style="padding: 1rem; border-radius: 0.75rem; border: 1px solid rgba(156, 163, 175, 0.2); background-color: rgba(245, 158, 11, 0.05); display: flex; flex-direction: column;">
                <div style="font-size: 0.75rem; color: rgb(107, 114, 128);">Registered Peers</div>
                <div style="font-size: 1.125rem; font-weight: 700; color: rgb(217, 119, 6);">
                    SIP: {{ $sipPeers }} | PJSIP: {{ $pjsipEndpoints }}
                </div>
            </div>
        </div>

        {{-- Actions Bar --}}
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem; background-color: rgba(156, 163, 175, 0.05); border-radius: 0.75rem; border: 1px solid rgba(156, 163, 175, 0.2);">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <x-filament::button wire:click="saveConfigs" color="success" icon="heroicon-m-check-badge">
                    Save All Configs
                </x-filament::button>
                <x-filament::button wire:click="loadConfigs" color="gray" icon="heroicon-m-arrow-path" size="sm">
                    Refresh
                </x-filament::button>
            </div>
            
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <x-filament::button wire:click="reloadAsterisk('sip')" color="warning" size="xs" icon="heroicon-m-phone">
                    Reload SIP
                </x-filament::button>
                <x-filament::button wire:click="reloadAsterisk('pjsip')" color="warning" size="xs" icon="heroicon-m-signal">
                    Reload PJSIP
                </x-filament::button>
                <x-filament::button wire:click="reloadAsterisk('extensions')" color="warning" size="xs" icon="heroicon-m-command-line">
                    Reload Dialplan
                </x-filament::button>
                <x-filament::button wire:click="reloadAsterisk('all')" color="danger" size="xs" icon="heroicon-m-bolt">
                    Full Reload
                </x-filament::button>
            </div>
        </div>

        {{-- Editors --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem;">
            {{-- sip.conf --}}
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label style="font-size: 0.875rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                    📄 sip.conf
                </label>
                <textarea 
                    wire:model="sipConfig" 
                    style="width: 100%; height: 500px; font-family: monospace; font-size: 0.875rem; padding: 1rem; border-radius: 0.75rem; border: 1px solid rgba(156, 163, 175, 0.3); background-color: rgba(0, 0, 0, 0.02); color: inherit;"
                    spellcheck="false"
                ></textarea>
            </div>

            {{-- pjsip.conf --}}
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label style="font-size: 0.875rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                    📡 pjsip.conf
                </label>
                <textarea 
                    wire:model="pjsipConfig" 
                    style="width: 100%; height: 500px; font-family: monospace; font-size: 0.875rem; padding: 1rem; border-radius: 0.75rem; border: 1px solid rgba(156, 163, 175, 0.3); background-color: rgba(0, 0, 0, 0.02); color: inherit;"
                    spellcheck="false"
                ></textarea>
            </div>

            {{-- extensions.conf --}}
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label style="font-size: 0.875rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                    📜 extensions.conf
                </label>
                <textarea 
                    wire:model="extensionsConfig" 
                    style="width: 100%; height: 500px; font-family: monospace; font-size: 0.875rem; padding: 1rem; border-radius: 0.75rem; border: 1px solid rgba(156, 163, 175, 0.3); background-color: rgba(0, 0, 0, 0.02); color: inherit;"
                    spellcheck="false"
                ></textarea>
            </div>
        </div>
    </div>
</x-filament-panels::page>
