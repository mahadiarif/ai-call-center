<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
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
