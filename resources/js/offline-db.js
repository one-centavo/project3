import { openDB } from "idb";

const dbPromise = openDB("clients-offline-db", 2, {
    upgrade(db, oldVersion) {
        if (oldVersion < 1) {
            if (!db.objectStoreNames.contains("clients")) {
                db.createObjectStore("clients", { keyPath: "uuid" });
            }
        }
        if (oldVersion < 2) {
            if (!db.objectStoreNames.contains("server_indices")) {
                db.createObjectStore("server_indices");
            }
        }
    },
});

async function keepClientInLocalDB(client) {
    const db = await dbPromise;
    const tx = db.transaction("clients", "readwrite");
    await tx.store.put(client);
    await tx.done;
}

async function getClientsForSync() {
    const db = await dbPromise;
    return await db.getAll("clients");
}

async function markClientAsSynced(UuidList) {
    const db = await dbPromise;
    const tx = db.transaction("clients", "readwrite");

    for (const uuid of UuidList) {
        await tx.store.delete(uuid);
    }

    await tx.done;
}

async function syncServerIndices() {
    if (!navigator.onLine) return;
    try {
        const response = await fetch("/api/v1/clients/indices", {
            method: "GET",
            headers: {
                Accept: "application/json",
            },
        });
        const result = await response.json();
        if (response.ok && result.status === "success") {
            const db = await dbPromise;
            const tx = db.transaction("server_indices", "readwrite");
            await tx.store.put(result.data.dnis || [], "dni");
            await tx.store.put(result.data.emails || [], "email");
            await tx.store.put(result.data.phone_numbers || [], "phone_number");
            await tx.done;
        } else {
            console.error("Failed to sync server indices:", result);
        }
    } catch (err) {
        console.error("Error syncing server indices:", err);
    }
}

async function isDuplicate(field, value) {
    if (!value) return false;
    const db = await dbPromise;

    // 1. Check in pending local clients
    const localClients = await db.getAll("clients");
    const isLocalDuplicate = localClients.some(
        (client) => client[field] && client[field].toString().trim().toLowerCase() === value.toString().trim().toLowerCase()
    );
    if (isLocalDuplicate) return true;

    // 2. Check in synced server indices
    const serverValues = await db.get("server_indices", field);
    if (serverValues) {
        const isServerDuplicate = serverValues.some(
            (val) => val && val.toString().trim().toLowerCase() === value.toString().trim().toLowerCase()
        );
        if (isServerDuplicate) return true;
    }

    return false;
}

window.keepClientInLocalDB = keepClientInLocalDB;
window.getClientsForSync = getClientsForSync;
window.markClientAsSynced = markClientAsSynced;
window.syncServerIndices = syncServerIndices;
window.isDuplicate = isDuplicate;

window.dispatchEvent(new CustomEvent("offline-db-ready"));

document.addEventListener("alpine:init", () => {
    Alpine.data("clientForm", () => ({
        dni: "",
        first_name: "",
        second_name: "",
        first_last_name: "",
        second_last_name: "",
        email: "",
        phone_number: "",
        address: "",
        successMessage: "",
        errorMessage: "",
        isOnline: navigator.onLine,
        isSyncing: false,

        init() {
            window.addEventListener("online", () => {
                this.isOnline = true;
                this.syncData();
            });
            window.addEventListener("offline", () => {
                this.isOnline = false;
            });
            window.addEventListener("offline-db-ready", () => {
                this.syncData();
            });
            this.syncData();
        },

        async syncData() {
            if (!this.isOnline) return;
            await this.syncOfflineClients();
            if (typeof window.syncServerIndices === "function") {
                await window.syncServerIndices();
            }
        },

        async syncOfflineClients() {
            if (this.isSyncing || !this.isOnline) return;
            if (
                typeof window.getClientsForSync !== "function" ||
                typeof window.markClientAsSynced !== "function"
            ) {
                return;
            }
            try {
                const clients = await window.getClientsForSync();
                if (clients.length === 0) return;
                this.isSyncing = true;
                const response = await fetch("/api/v1/clients/sync", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                    },
                    body: JSON.stringify({ clients }),
                });
                const result = await response.json();
                if (response.ok && result.status === "success") {
                    const uuids = clients.map((c) => c.uuid);
                    await window.markClientAsSynced(uuids);
                    window.dispatchEvent(new CustomEvent("client-synced"));
                    if (window.Livewire) {
                        window.Livewire.dispatch("client-synced");
                    }
                } else {
                    console.error("Synchronization failed:", result);
                }
            } catch (err) {
                console.error("Error during synchronization:", err);
            } finally {
                this.isSyncing = false;
            }
        },

        async submitForm() {
            this.successMessage = "";
            this.errorMessage = "";

            if (!this.dni || this.dni.trim() === "") {
                this.errorMessage = "El DNI es requerido.";
                return;
            }
            if (!this.first_name || this.first_name.trim() === "") {
                this.errorMessage = "El primer nombre es requerido.";
                return;
            }
            if (!this.first_last_name || this.first_last_name.trim() === "") {
                this.errorMessage = "El primer apellido es requerido.";
                return;
            }
            if (!this.email || this.email.trim() === "") {
                this.errorMessage = "El correo electrónico es requerido.";
                return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email)) {
                this.errorMessage = "Por favor ingrese un correo electrónico válido.";
                return;
            }
            if (!this.phone_number || this.phone_number.trim() === "") {
                this.errorMessage = "El número de teléfono es requerido.";
                return;
            }
            if (!this.address || this.address.trim() === "") {
                this.errorMessage = "La dirección es requerida.";
                return;
            }

            if (typeof window.isDuplicate === "function") {
                if (await window.isDuplicate("dni", this.dni)) {
                    this.errorMessage = "El DNI ya existe.";
                    return;
                }
                if (await window.isDuplicate("email", this.email)) {
                    this.errorMessage = "El correo electrónico ya existe.";
                    return;
                }
                if (await window.isDuplicate("phone_number", this.phone_number)) {
                    this.errorMessage = "El número de teléfono ya existe.";
                    return;
                }
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
                updated_at: new Date().toISOString(),
            };

            if (typeof window.keepClientInLocalDB === "function") {
                window
                    .keepClientInLocalDB(client)
                    .then(() => {
                        this.successMessage =
                            "Cliente guardado correctamente";
                        this.resetForm();
                        window.dispatchEvent(new CustomEvent("client-saved"));
                        if (window.Livewire) {
                            window.Livewire.dispatch("client-saved");
                        }

                        if (this.isOnline) {
                            this.syncOfflineClients();
                        }
                    })
                    .catch((err) => {
                        console.error(err);
                        this.errorMessage =
                            "No se pudo guardar el cliente fuera de línea. Por favor intente de nuevo.";
                    });
            } else {
                this.errorMessage = "El asistente de base de datos fuera de línea no está cargado.";
            }
        },

        resetForm() {
            this.dni = "";
            this.first_name = "";
            this.second_name = "";
            this.first_last_name = "";
            this.second_last_name = "";
            this.email = "";
            this.phone_number = "";
            this.address = "";
        },
    }));
});
