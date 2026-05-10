<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6">
        {{-- Actions Bar --}}
        <div class="flex items-center justify-between gap-4 p-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="flex items-center gap-3">
                <x-filament::button wire:click="saveConfigs" color="success" icon="heroicon-m-check-badge">
                    Save Changes
                </x-filament::button>
                <x-filament::button wire:click="loadConfigs" color="gray" icon="heroicon-m-arrow-path">
                    Discard & Refresh
                </x-filament::button>
            </div>
            
            <div class="flex items-center gap-2">
                <x-filament::button wire:click="reloadAsterisk('sip')" color="warning" size="sm" icon="heroicon-m-phone">
                    Reload SIP
                </x-filament::button>
                <x-filament::button wire:click="reloadAsterisk('extensions')" color="warning" size="sm" icon="heroicon-m-command-line">
                    Reload Dialplan
                </x-filament::button>
                <x-filament::button wire:click="reloadAsterisk('all')" color="danger" size="sm" icon="heroicon-m-bolt">
                    Full Reload
                </x-filament::button>
            </div>
        </div>

        {{-- Editors --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- sip.conf --}}
            <div class="flex flex-col gap-2">
                <label class="text-sm font-bold flex items-center gap-2">
                    <x-heroicon-m-document-text class="w-4 h-4 text-primary-500" />
                    sip.conf
                </label>
                <textarea 
                    wire:model="sipConfig" 
                    class="w-full h-[600px] font-mono text-sm p-4 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 focus:border-primary-500 focus:ring-primary-500"
                    spellcheck="false"
                ></textarea>
            </div>

            {{-- extensions.conf --}}
            <div class="flex flex-col gap-2">
                <label class="text-sm font-bold flex items-center gap-2">
                    <x-heroicon-m-document-text class="w-4 h-4 text-primary-500" />
                    extensions.conf
                </label>
                <textarea 
                    wire:model="extensionsConfig" 
                    class="w-full h-[600px] font-mono text-sm p-4 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 focus:border-primary-500 focus:ring-primary-500"
                    spellcheck="false"
                ></textarea>
            </div>
        </div>
    </div>
</x-filament-panels::page>
