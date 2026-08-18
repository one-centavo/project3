<?php

use App\Models\Client;
use Livewire\Component;

new class extends Component {
    public bool $isOpen = false;
    public string $uuid = '';
    public string $dni = '';
    public string $first_name = '';
    public ?string $second_name = '';
    public string $first_last_name = '';
    public ?string $second_last_name = '';
    public string $email = '';
    public string $phone_number = '';
    public string $address = '';

    protected array $rules = [];

    protected $listeners = [
        'edit-client' => 'loadClient',
    ];

    public function loadClient(string $uuid): void
    {
        $client = Client::find($uuid);
        if ($client) {
            $this->uuid = $client->uuid;
            $this->dni = $client->dni;
            $this->first_name = $client->first_name;
            $this->second_name = $client->second_name;
            $this->first_last_name = $client->first_last_name;
            $this->second_last_name = $client->second_last_name;
            $this->email = $client->email;
            $this->phone_number = $client->phone_number;
            $this->address = $client->address;
            $this->isOpen = true;

            $this->resetErrorBag();
        }
    }

    public function update(): void
    {
        $validated = $this->validate([
            'dni' => 'required|string|max:10|unique:clients,dni,' . $this->uuid . ',uuid',
            'first_name' => 'required|string|max:150',
            'second_name' => 'nullable|string|max:150',
            'first_last_name' => 'required|string|max:150',
            'second_last_name' => 'nullable|string|max:150',
            'email' => 'required|email|max:255|unique:clients,email,' . $this->uuid . ',uuid',
            'phone_number' => 'required|string|max:10|unique:clients,phone_number,' . $this->uuid . ',uuid',
            'address' => 'required|string|max:255',
        ]);

        $client = Client::find($this->uuid);
        if ($client) {
            $client->update($validated);
            $this->dispatch('client-saved');
            $this->close();
        } else {
            throw new \Exception("Client not found on server.");
        }
    }

    public function close(): void
    {
        $this->isOpen = false;
    }
};
?>

<div x-data="{
    isOpen: @entangle('isOpen'),
    uuid: @entangle('uuid'),
    dni: @entangle('dni'),
    first_name: @entangle('first_name'),
    second_name: @entangle('second_name'),
    first_last_name: @entangle('first_last_name'),
    second_last_name: @entangle('second_last_name'),
    email: @entangle('email'),
    phone_number: @entangle('phone_number'),
    address: @entangle('address'),

    originalDni: '',
    originalEmail: '',
    originalPhoneNumber: '',

    successMessage: '',
    errorMessage: '',
    isOnline: navigator.onLine,
    isSyncing: false,

    init() {
        window.addEventListener('online', () => {
            this.isOnline = true;
        });
        window.addEventListener('offline', () => {
            this.isOnline = false;
        });
        window.addEventListener('open-edit-client', (e) => {
            const client = e.detail.client;
            this.uuid = client.uuid;
            this.dni = client.dni;
            this.first_name = client.first_name;
            this.second_name = client.second_name || '';
            this.first_last_name = client.first_last_name;
            this.second_last_name = client.second_last_name || '';
            this.email = client.email;
            this.phone_number = client.phone_number;
            this.address = client.address;

            this.originalDni = client.dni;
            this.originalEmail = client.email;
            this.originalPhoneNumber = client.phone_number;

            this.successMessage = '';
            this.errorMessage = '';
            this.isOpen = true;
        });
    },

    async saveOffline() {
        const client = {
            uuid: this.uuid,
            dni: this.dni,
            first_name: this.first_name,
            second_name: this.second_name || null,
            first_last_name: this.first_last_name,
            second_last_name: this.second_last_name || null,
            email: this.email,
            phone_number: this.phone_number,
            address: this.address,
            updated_at: new Date().toISOString(),
        };

        if (typeof window.keepClientInLocalDB === 'function') {
            try {
                await window.keepClientInLocalDB(client);
                this.successMessage = 'Cliente guardado correctamente';
                this.isOpen = false;
                window.dispatchEvent(new CustomEvent('client-saved'));
                if (window.Livewire) {
                    window.Livewire.dispatch('client-saved');
                }

                if (navigator.onLine && typeof window.syncOfflineClients === 'function') {
                    await window.syncOfflineClients();
                }
            } catch (err) {
                console.error(err);
                this.errorMessage = 'No se pudo guardar el cliente. Por favor intente de nuevo.';
            }
        } else {
            this.errorMessage = 'El asistente de base de datos fuera de línea no está cargado.';
        }
    },

    async submitForm() {
        this.successMessage = '';
        this.errorMessage = '';

        if (!this.dni || this.dni.trim() === '') {
            this.errorMessage = 'El DNI es requerido.';
            return;
        }
        if (!this.first_name || this.first_name.trim() === '') {
            this.errorMessage = 'El primer nombre es requerido.';
            return;
        }
        if (!this.first_last_name || this.first_last_name.trim() === '') {
            this.errorMessage = 'El primer apellido es requerido.';
            return;
        }
        if (!this.email || this.email.trim() === '') {
            this.errorMessage = 'El correo electrónico es requerido.';
            return;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email)) {
            this.errorMessage = 'Por favor ingrese un correo electrónico válido.';
            return;
        }
        if (!this.phone_number || this.phone_number.trim() === '') {
            this.errorMessage = 'El número de teléfono es requerido.';
            return;
        }
        if (!this.address || this.address.trim() === '') {
            this.errorMessage = 'La dirección es requerida.';
            return;
        }

        if (typeof window.isDuplicate === 'function') {
            if (this.dni !== this.originalDni && await window.isDuplicate('dni', this.dni)) {
                this.errorMessage = 'El DNI ya existe.';
                return;
            }
            if (this.email !== this.originalEmail && await window.isDuplicate('email', this.email)) {
                this.errorMessage = 'El correo electrónico ya existe.';
                return;
            }
            if (this.phone_number !== this.originalPhoneNumber && await window.isDuplicate('phone_number', this.phone_number)) {
                this.errorMessage = 'El número de teléfono ya existe.';
                return;
            }
        }

        if (navigator.onLine) {
            try {
                await this.$wire.update();
            } catch (err) {
                console.warn('Online update failed, falling back to offline saving:', err);
                await this.saveOffline();
            }
        } else {
            await this.saveOffline();
        }
    }
}"
     x-show="isOpen" 
     class="fixed inset-0 z-50 overflow-hidden" 
     style="display: none;"
     @keydown.escape.window="isOpen = false">
    
    <div x-show="isOpen" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-500/75 dark:bg-black/80 backdrop-blur-sm transition-opacity" 
         @click="isOpen = false">
    </div>

    <div class="fixed inset-0 flex items-center justify-center p-4 md:p-0 md:justify-end">
        <div x-show="isOpen"
             x-transition:enter="transform transition ease-in-out duration-300 sm:duration-500"
             x-transition:enter-start="translate-y-full md:translate-y-0 md:translate-x-full"
             x-transition:enter-end="translate-y-0 md:translate-x-0"
             x-transition:leave="transform transition ease-in-out duration-300 sm:duration-500"
             x-transition:leave-start="translate-y-0 md:translate-x-0"
             x-transition:leave-end="translate-y-full md:translate-y-0 md:translate-x-full"
             class="w-full max-w-lg bg-white dark:bg-[#161615] shadow-2xl rounded-xl md:rounded-none md:h-full flex flex-col pointer-events-auto border-l border-gray-200 dark:border-[#3E3E3A]">
             
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-[#3E3E3A]">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-[#fff2f2] dark:bg-[#1D0002] rounded-lg text-[#f53003] dark:text-[#FF4433]">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Editar Cliente</h2>
                        <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Modificar información de cliente</p>
                    </div>
                </div>
                <button type="button" @click="isOpen = false" class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400 focus:outline-none">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Body (Scrollable Content) -->
            <div class="flex-1 overflow-y-auto p-6 space-y-4">
                <!-- Alpine Error Alert -->
                <template x-if="errorMessage">
                    <div class="flex items-center p-4 text-sm text-red-800 dark:text-red-300 bg-red-50 dark:bg-red-950/30 rounded-lg border border-red-100 dark:border-red-900 transition-all duration-300" role="alert">
                        <svg class="shrink-0 inline w-4 h-4 mr-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9 4h2v8H9V4Zm1 10a1.1 1.1 0 1 1 0-2.2 1.1 1.1 0 0 1 0 2.2Z"/>
                        </svg>
                        <div>
                            <span class="font-medium">¡Advertencia!</span> <span x-text="errorMessage"></span>
                        </div>
                    </div>
                </template>

                <form @submit.prevent="submitForm" id="edit-client-form" class="space-y-4">
                    <!-- DNI Field -->
                    <div>
                        <label for="edit_dni" class="block mb-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300">
                            DNI <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="edit_dni" x-model="dni" placeholder="ej. 1234567890"
                            class="w-full px-3.5 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:border-[#f53003] dark:focus:border-[#FF4433] focus:ring-1 focus:ring-[#f53003] focus:outline-none transition-all duration-200">
                        @error('dni') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Names Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="edit_first_name" class="block mb-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                Primer Nombre <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="edit_first_name" x-model="first_name" placeholder="ej. Juan"
                                class="w-full px-3.5 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:border-[#f53003] dark:focus:border-[#FF4433] focus:ring-1 focus:ring-[#f53003] focus:outline-none transition-all duration-200">
                            @error('first_name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="edit_second_name" class="block mb-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                Segundo Nombre
                            </label>
                            <input type="text" id="edit_second_name" x-model="second_name" placeholder="ej. Eduardo"
                                class="w-full px-3.5 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:border-[#f53003] dark:focus:border-[#FF4433] focus:ring-1 focus:ring-[#f53003] focus:outline-none transition-all duration-200">
                            @error('second_name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Last Names Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="edit_first_last_name" class="block mb-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                Primer Apellido <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="edit_first_last_name" x-model="first_last_name" placeholder="ej. Pérez"
                                class="w-full px-3.5 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:border-[#f53003] dark:focus:border-[#FF4433] focus:ring-1 focus:ring-[#f53003] focus:outline-none transition-all duration-200">
                            @error('first_last_name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="edit_second_last_name" class="block mb-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                Segundo Apellido
                            </label>
                            <input type="text" id="edit_second_last_name" x-model="second_last_name" placeholder="ej. Gómez"
                                class="w-full px-3.5 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:border-[#f53003] dark:focus:border-[#FF4433] focus:ring-1 focus:ring-[#f53003] focus:outline-none transition-all duration-200">
                            @error('second_last_name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Contact Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="edit_email" class="block mb-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                Correo Electrónico <span class="text-red-500">*</span>
                            </label>
                            <input type="email" id="edit_email" x-model="email" placeholder="ej. juan.perez@example.com"
                                class="w-full px-3.5 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:border-[#f53003] dark:focus:border-[#FF4433] focus:ring-1 focus:ring-[#f53003] focus:outline-none transition-all duration-200">
                            @error('email') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="edit_phone_number" class="block mb-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                Número de Teléfono <span class="text-red-500">*</span>
                            </label>
                            <input type="tel" id="edit_phone_number" x-model="phone_number" placeholder="ej. 1234567890"
                                class="w-full px-3.5 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:border-[#f53003] dark:focus:border-[#FF4433] focus:ring-1 focus:ring-[#f53003] focus:outline-none transition-all duration-200">
                            @error('phone_number') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Address Field -->
                    <div>
                        <label for="edit_address" class="block mb-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300">
                            Dirección <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="edit_address" x-model="address" placeholder="ej. Av. Siempreviva 742"
                            class="w-full px-3.5 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:border-[#f53003] dark:focus:border-[#FF4433] focus:ring-1 focus:ring-[#f53003] focus:outline-none transition-all duration-200">
                        @error('address') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-100 dark:border-[#3E3E3A] flex items-center justify-end space-x-3 bg-gray-50 dark:bg-[#161615]">
                <button type="button" @click="isOpen = false" class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#232321] rounded-md transition-all duration-200">
                    Cancelar
                </button>
                <button type="submit" form="edit-client-form" class="px-4 py-2 bg-[#1b1b18] hover:bg-black text-white dark:bg-[#eeeeec] dark:hover:bg-white dark:text-[#1C1C1A] font-semibold text-sm rounded-md transition-all duration-200 shadow-sm hover:shadow active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-[#f53003] dark:focus:ring-white">
                    Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>
