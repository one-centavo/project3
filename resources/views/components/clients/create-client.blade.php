<?php

use Livewire\Component;

new class extends Component {
    public string $uuid = '';

    public string $dni = '';

    public string $first_name = '';

    public ?string $second_name = null;

    public string $first_last_name = '';

    public ?string $second_last_name = null;

    public string $email = '';

    public string $phone_number = '';

    public string $address = '';

    public bool $is_sync = false;
};
?>


<div x-data="{
    dni: @entangle('dni'),
    first_name: @entangle('first_name'),
    second_name: @entangle('second_name'),
    first_last_name: @entangle('first_last_name'),
    second_last_name: @entangle('second_last_name'),
    email: @entangle('email'),
    phone_number: @entangle('phone_number'),
    address: @entangle('address'),
    successMessage: '',
    errorMessage: '',
    isOnline: navigator.onLine,
    isSyncing: false,
    init() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.syncOfflineClients();
        });
        window.addEventListener('offline', () => {
            this.isOnline = false;
        });
        window.addEventListener('offline-db-ready', () => {
            if (this.isOnline) {
                this.syncOfflineClients();
            }
        });
        if (this.isOnline) {
            this.syncOfflineClients();
        }
    },
    async syncOfflineClients() {
        if (this.isSyncing) return;
        if (!this.isOnline) return;
        if (typeof window.getClientsForSync !== 'function' || typeof window.markClientAsSynced !== 'function') {
            return;
        }
        try {
            const clients = await window.getClientsForSync();
            if (clients.length === 0) return;
            this.isSyncing = true;
            const response = await fetch('/api/v1/clients/sync', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ clients })
            });
            const result = await response.json();
            if (response.ok && result.status === 'success') {
                const uuids = clients.map(c => c.uuid);
                await window.markClientAsSynced(uuids);
            } else {
                console.error('Synchronization failed:', result);
            }
        } catch (err) {
            console.error('Error during synchronization:', err);
        } finally {
            this.isSyncing = false;
        }
    },
    submitForm() {
        this.successMessage = '';
        this.errorMessage = '';

        // Validation
        if (!this.dni || this.dni.trim() === '') {
            this.errorMessage = 'DNI (ID Number) is required.';
            return;
        }
        if (!this.first_name || this.first_name.trim() === '') {
            this.errorMessage = 'First Name is required.';
            return;
        }
        if (!this.first_last_name || this.first_last_name.trim() === '') {
            this.errorMessage = 'First Last Name is required.';
            return;
        }
        if (!this.email || this.email.trim() === '') {
            this.errorMessage = 'Email address is required.';
            return;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email)) {
            this.errorMessage = 'Please enter a valid email address.';
            return;
        }
        if (!this.phone_number || this.phone_number.trim() === '') {
            this.errorMessage = 'Phone Number is required.';
            return;
        }
        if (!this.address || this.address.trim() === '') {
            this.errorMessage = 'Address is required.';
            return;
        }

        const client = {
            uuid: crypto.randomUUID(),
            dni: this.dni,
            first_name: this.first_name,
            second_name: this.second_name || null,
            first_last_name: this.first_last_name,
            second_last_name: this.second_last_name || null,
            email: this.email,
            phone_number: this.phone_number,
            address: this.address,
            is_sync: false,
            updated_at: new Date().toISOString()
        };

        if (typeof window.keepClientInLocalDB === 'function') {
            window.keepClientInLocalDB(client)
                .then(() => {
                    this.successMessage = 'Client registered successfully (stored offline)!';
                    // Reset fields
                    this.dni = '';
                    this.first_name = '';
                    this.second_name = '';
                    this.first_last_name = '';
                    this.second_last_name = '';
                    this.email = '';
                    this.phone_number = '';
                    this.address = '';

                    if (this.isOnline) {
                        this.syncOfflineClients();
                    }
                })
                .catch(err => {
                    console.error(err);
                    this.errorMessage = 'Failed to save client offline. Please try again.';
                });
        } else {
            this.errorMessage = 'Offline database helper keepClientInLocalDB is not loaded.';
        }
    }
}" class="w-full max-w-xl mx-auto">
    <div
        class="bg-white dark:bg-[#161615] rounded-xl shadow-[0px_4px_20px_rgba(0,0,0,0.05),inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[0px_4px_20px_rgba(0,0,0,0.15),inset_0px_0px_0px_1px_#fffaed2d] p-6 lg:p-8 transition-all duration-300">

        <!-- Header -->
        <div class="flex items-center space-x-3 mb-6 border-b border-gray-100 dark:border-[#3E3E3A] pb-4">
            <div
                class="p-2 bg-[#fff2f2] dark:bg-[#1D0002] rounded-lg text-[#f53003] dark:text-[#FF4433] transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Register Client</h2>
                <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Offline database binding enabled</p>
            </div>
        </div>

        <!-- Success Alert -->
        <template x-if="successMessage">
            <div class="mb-5 flex items-center p-4 text-sm text-green-800 dark:text-green-300 bg-green-50 dark:bg-green-950/30 rounded-lg border border-green-100 dark:border-green-900 transition-all duration-300"
                role="alert">
                <svg class="shrink-0 inline w-4 h-4 mr-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                    fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z" />
                </svg>
                <div>
                    <span class="font-medium">Success!</span> <span x-text="successMessage"></span>
                </div>
            </div>
        </template>

        <!-- Error Alert -->
        <template x-if="errorMessage">
            <div class="mb-5 flex items-center p-4 text-sm text-red-800 dark:text-red-300 bg-red-50 dark:bg-red-950/30 rounded-lg border border-red-100 dark:border-red-900 transition-all duration-300"
                role="alert">
                <svg class="shrink-0 inline w-4 h-4 mr-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                    fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9 4h2v8H9V4Zm1 10a1.1 1.1 0 1 1 0-2.2 1.1 1.1 0 0 1 0 2.2Z" />
                </svg>
                <div>
                    <span class="font-medium">Warning!</span> <span x-text="errorMessage"></span>
                </div>
            </div>
        </template>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="space-y-4">

            <!-- DNI Field -->
            <div>
                <label for="dni" class="block mb-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300">
                    DNI / ID Number <span class="text-red-500">*</span>
                </label>
                <input type="text" id="dni" x-model="dni" placeholder="e.g. 1234567890"
                    class="w-full px-3.5 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:border-[#f53003] dark:focus:border-[#FF4433] focus:ring-1 focus:ring-[#f53003] focus:outline-none transition-all duration-200"
                    required>
            </div>

            <!-- Names Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="first_name" class="block mb-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300">
                        First Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="first_name" x-model="first_name" placeholder="John"
                        class="w-full px-3.5 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:border-[#f53003] dark:focus:border-[#FF4433] focus:ring-1 focus:ring-[#f53003] focus:outline-none transition-all duration-200"
                        required>
                </div>
                <div>
                    <label for="second_name"
                        class="block mb-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300">
                        Second Name <span class="text-gray-400 dark:text-gray-500 font-normal">(Optional)</span>
                    </label>
                    <input type="text" id="second_name" x-model="second_name" placeholder="Edward"
                        class="w-full px-3.5 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:border-[#f53003] dark:focus:border-[#FF4433] focus:ring-1 focus:ring-[#f53003] focus:outline-none transition-all duration-200">
                </div>
            </div>

            <!-- Last Names Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="first_last_name"
                        class="block mb-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300">
                        First Last Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="first_last_name" x-model="first_last_name" placeholder="Doe"
                        class="w-full px-3.5 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:border-[#f53003] dark:focus:border-[#FF4433] focus:ring-1 focus:ring-[#f53003] focus:outline-none transition-all duration-200"
                        required>
                </div>
                <div>
                    <label for="second_last_name"
                        class="block mb-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300">
                        Second Last Name <span class="text-gray-400 dark:text-gray-500 font-normal">(Optional)</span>
                    </label>
                    <input type="text" id="second_last_name" x-model="second_last_name" placeholder="Smith"
                        class="w-full px-3.5 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:border-[#f53003] dark:focus:border-[#FF4433] focus:ring-1 focus:ring-[#f53003] focus:outline-none transition-all duration-200">
                </div>
            </div>

            <!-- Contact Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="email" class="block mb-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300">
                        Email Address <span class="text-red-500">*</span>
                    </label>
                    <input type="email" id="email" x-model="email" placeholder="john.doe@example.com"
                        class="w-full px-3.5 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:border-[#f53003] dark:focus:border-[#FF4433] focus:ring-1 focus:ring-[#f53003] focus:outline-none transition-all duration-200"
                        required>
                </div>
                <div>
                    <label for="phone_number"
                        class="block mb-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300">
                        Phone Number <span class="text-red-500">*</span>
                    </label>
                    <input type="tel" id="phone_number" x-model="phone_number" placeholder="1234567890"
                        class="w-full px-3.5 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:border-[#f53003] dark:focus:border-[#FF4433] focus:ring-1 focus:ring-[#f53003] focus:outline-none transition-all duration-200"
                        required>
                </div>
            </div>

            <!-- Address Field -->
            <div>
                <label for="address" class="block mb-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300">
                    Address <span class="text-red-500">*</span>
                </label>
                <input type="text" id="address" x-model="address" placeholder="123 Main St, Springfield"
                    class="w-full px-3.5 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:border-[#f53003] dark:focus:border-[#FF4433] focus:ring-1 focus:ring-[#f53003] focus:outline-none transition-all duration-200"
                    required>
            </div>

            <!-- Action Button -->
            <div class="pt-2">
                <button type="submit"
                    class="w-full py-2.5 px-5 bg-[#1b1b18] hover:bg-black text-white dark:bg-[#eeeeec] dark:hover:bg-white dark:text-[#1C1C1A] font-semibold text-sm rounded-md transition-all duration-200 shadow-sm hover:shadow active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-[#f53003] dark:focus:ring-white">
                    Register Client
                </button>
            </div>

        </form>
    </div>
</div>
