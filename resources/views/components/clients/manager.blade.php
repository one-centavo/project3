<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] p-4 sm:p-6 lg:p-8 transition-colors duration-300">
    <div class="max-w-7xl mx-auto space-y-8">
        
        <!-- Dashboard Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-gray-100 dark:border-[#3E3E3A] pb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]">Sync Manager</h1>
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Offline-first client registration and synchronization platform</p>
            </div>
            
            <div x-data="{ isOnline: navigator.onLine }" 
                 x-init="
                    window.addEventListener('online', () => isOnline = true);
                    window.addEventListener('offline', () => isOnline = false);
                 "
                 class="flex items-center">
                <template x-if="isOnline">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-400 border border-green-200 dark:border-green-900/50">
                        <span class="w-2 h-2 mr-2 bg-green-500 rounded-full animate-pulse"></span>
                        Online
                    </span>
                </template>
                <template x-if="!isOnline">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200 dark:border-amber-900/50">
                        <span class="w-2 h-2 mr-2 bg-amber-500 rounded-full animate-pulse"></span>
                        Offline Mode
                    </span>
                </template>
            </div>
        </div>

        <!-- Dashboard Layout Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Left Column: Registration Form -->
            <div class="lg:col-span-4">
                <livewire:clients.create-client />
            </div>
            
            <!-- Right Column: Unsynced & Synced Tables -->
            <div class="lg:col-span-8 space-y-8">
                <!-- Unsynced Clients Table -->
                <livewire:clients.clients-table type="unsynced" />
                
                <!-- Synced Clients Table -->
                <livewire:clients.clients-table type="synced" />
            </div>
            
        </div>
        
    </div>
</div>