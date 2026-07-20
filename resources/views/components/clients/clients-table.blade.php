<?php

use App\Models\Client;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $type = 'synced';
    public $search = '';

    protected $listeners = [
        'client-saved' => '$refresh',
        'client-synced' => '$refresh',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $clients = [];

        if ($this->type === 'synced') {
            $clients = Client::query()
                ->when($this->search, function ($query) {
                    $searchLower = '%' . $this->search . '%';
                    $query->where(function ($q) use ($searchLower) {
                        $q->where('dni', 'like', $searchLower)
                          ->orWhere('first_name', 'like', $searchLower)
                          ->orWhere('second_name', 'like', $searchLower)
                          ->orWhere('first_last_name', 'like', $searchLower)
                          ->orWhere('second_last_name', 'like', $searchLower)
                          ->orWhere('email', 'like', $searchLower)
                          ->orWhere('phone_number', 'like', $searchLower)
                          ->orWhere('address', 'like', $searchLower);
                    });
                })
                ->latest()
                ->paginate(5);
        }

        return view('components.clients.clients-table', [
            'clients' => $clients,
        ]);
    }
};
?>

<div class="w-full">
    @if ($type === 'unsynced')
        <div x-data="{
            localClients: [],
            searchQuery: '',
            currentPage: 1,
            perPage: 5,
            async loadOfflineClients() {
                if (typeof window.getClientsForSync === 'function') {
                    this.localClients = await window.getClientsForSync();
                }
            },
            get filteredClients() {
                const query = this.searchQuery.trim().toLowerCase();
                if (!query) return this.localClients;
                return this.localClients.filter(c => {
                    const fullName = `${c.first_name} ${c.second_name || ''} ${c.first_last_name} ${c.second_last_name || ''}`.toLowerCase();
                    return (c.dni && c.dni.toLowerCase().includes(query)) ||
                           fullName.includes(query) ||
                           (c.email && c.email.toLowerCase().includes(query)) ||
                           (c.phone_number && c.phone_number.toLowerCase().includes(query)) ||
                           (c.address && c.address.toLowerCase().includes(query));
                });
            },
            get paginatedClients() {
                const start = (this.currentPage - 1) * this.perPage;
                return this.filteredClients.slice(start, start + this.perPage);
            },
            get totalPages() {
                return Math.ceil(this.filteredClients.length / this.perPage) || 1;
            }
        }"
        x-init="
            loadOfflineClients();
            window.addEventListener('client-saved', () => loadOfflineClients());
            window.addEventListener('client-synced', () => loadOfflineClients());
            window.addEventListener('offline-db-ready', () => loadOfflineClients());
        "
        class="bg-white dark:bg-[#161615] rounded-xl shadow-[0px_4px_20px_rgba(0,0,0,0.05),inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[0px_4px_20px_rgba(0,0,0,0.15),inset_0px_0px_0px_1px_#fffaed2d] p-6 lg:p-8 transition-all duration-300">
            
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 mb-6 border-b border-gray-100 dark:border-[#3E3E3A] gap-4">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-amber-50 dark:bg-amber-950/30 rounded-lg text-amber-600 dark:text-amber-400">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Pendientes de Sincronizar</h2>
                        <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Almacenamiento fuera de línea (IndexedDB)</p>
                    </div>
                </div>
                
                <div class="relative w-full sm:w-64">
                    <input type="text" x-model="searchQuery" @input="currentPage = 1" placeholder="Buscar fuera de línea..." 
                           class="w-full pl-9 pr-4 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:border-[#f53003] dark:focus:border-[#FF4433] focus:ring-1 focus:ring-[#f53003] focus:outline-none transition-all duration-200">
                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.603 10.603Z" />
                        </svg>
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 dark:text-gray-300 uppercase bg-gray-50 dark:bg-[#232321] rounded-t-lg">
                        <tr>
                            <th scope="col" class="px-4 py-3">DNI</th>
                            <th scope="col" class="px-4 py-3">Nombre Completo</th>
                            <th scope="col" class="px-4 py-3">Correo Electrónico</th>
                            <th scope="col" class="px-4 py-3">Teléfono</th>
                            <th scope="col" class="px-4 py-3">Dirección</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="paginatedClients.length === 0">
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">
                                    No se encontraron clientes pendientes.
                                </td>
                            </tr>
                        </template>
                        <template x-for="client in paginatedClients" :key="client.uuid">
                            <tr class="border-b border-gray-100 dark:border-[#3E3E3A] hover:bg-gray-50/50 dark:hover:bg-[#1d1d1b] transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white" x-text="client.dni"></td>
                                <td class="px-4 py-3 text-gray-900 dark:text-white" x-text="`${client.first_name} ${client.second_name || ''} ${client.first_last_name} ${client.second_last_name || ''}`"></td>
                                <td class="px-4 py-3" x-text="client.email"></td>
                                <td class="px-4 py-3" x-text="client.phone_number"></td>
                                <td class="px-4 py-3" x-text="client.address"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <template x-if="filteredClients.length > perPage">
                <div class="flex items-center justify-between pt-6 border-t border-gray-100 dark:border-[#3E3E3A] mt-6">
                    <span class="text-xs text-[#706f6c] dark:text-[#A1A09A]" x-text="`Mostrando ${((currentPage-1)*perPage)+1} a ${Math.min(currentPage*perPage, filteredClients.length)} de ${filteredClients.length} entradas`"></span>
                    <div class="inline-flex space-x-1">
                        <button @click="currentPage > 1 ? currentPage-- : null" :disabled="currentPage === 1" 
                                class="px-3 py-1 text-xs border border-gray-200 dark:border-[#3E3E3A] rounded hover:bg-gray-50 dark:hover:bg-[#232321] text-gray-700 dark:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                            Anterior
                        </button>
                        <button @click="currentPage < totalPages ? currentPage++ : null" :disabled="currentPage === totalPages"
                                class="px-3 py-1 text-xs border border-gray-200 dark:border-[#3E3E3A] rounded hover:bg-gray-50 dark:hover:bg-[#232321] text-gray-700 dark:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                            Siguiente
                        </button>
                    </div>
                </div>
            </template>
        </div>
    @else
        <div class="bg-white dark:bg-[#161615] rounded-xl shadow-[0px_4px_20px_rgba(0,0,0,0.05),inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[0px_4px_20px_rgba(0,0,0,0.15),inset_0px_0px_0px_1px_#fffaed2d] p-6 lg:p-8 transition-all duration-300">
            
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 mb-6 border-b border-gray-100 dark:border-[#3E3E3A] gap-4">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-green-50 dark:bg-green-950/30 rounded-lg text-green-600 dark:text-green-400">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Clientes Sincronizados</h2>
                        <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Almacenamiento en base de datos en la nube</p>
                    </div>
                </div>
                
                <div class="relative w-full sm:w-64">
                    <input type="text" wire:model.live="search" placeholder="Buscar sincronizados..." 
                           class="w-full pl-9 pr-4 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:border-[#f53003] dark:focus:border-[#FF4433] focus:ring-1 focus:ring-[#f53003] focus:outline-none transition-all duration-200">
                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.603 10.603Z" />
                        </svg>
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 dark:text-gray-300 uppercase bg-gray-50 dark:bg-[#232321] rounded-t-lg">
                        <tr>
                            <th scope="col" class="px-4 py-3">DNI</th>
                            <th scope="col" class="px-4 py-3">Nombre Completo</th>
                            <th scope="col" class="px-4 py-3">Correo Electrónico</th>
                            <th scope="col" class="px-4 py-3">Teléfono</th>
                            <th scope="col" class="px-4 py-3">Dirección</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clients as $client)
                            <tr class="border-b border-gray-100 dark:border-[#3E3E3A] hover:bg-gray-50/50 dark:hover:bg-[#1d1d1b] transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $client->dni }}</td>
                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                    {{ $client->first_name }}
                                    {{ $client->second_name ? $client->second_name . ' ' : '' }}
                                    {{ $client->first_last_name }}
                                    {{ $client->second_last_name ? ' ' . $client->second_last_name : '' }}
                                </td>
                                <td class="px-4 py-3">{{ $client->email }}</td>
                                <td class="px-4 py-3">{{ $client->phone_number }}</td>
                                <td class="px-4 py-3">{{ $client->address }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">
                                    No se encontraron clientes sincronizados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($clients instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $clients->hasPages())
                <div class="pt-6 border-t border-gray-100 dark:border-[#3E3E3A] mt-6">
                    {{ $clients->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
