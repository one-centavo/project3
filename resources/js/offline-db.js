import { openDB } from "idb";

const dbPromise = openDB("clients-offline-db", 1, {
    upgrade(db) {
        if (!db.objectStoreNames.contains("clients")) {
            const store = db.createObjectStore("clients", { keyPath: "uuid" });
            store.createIndex("for_sync", "is_sync");
        }
    },
});

async function keepClientInLocalDB(client) {
    const db = await dbPromise;

    client.is_sync = false;
    client.updated_at = new Date().toISOString();

    const tx = db.transaction("clients", "readwrite");
    await tx.store.put(client);
    await tx.done;
}

async function getClientsForSync() {
    const db = await dbPromise;
    const allClients = await db.getAll("clients");
    return allClients.filter((client) => !client.is_sync);
}

async function markClientAsSynced(UuidList) {
    const db = await dbPromise;
    const tx = db.transaction("clients", "readwrite");

    for (const uuid of UuidList) {
        const client = await tx.store.get(uuid);
        if (client) {
            client.is_sync = true;
            await tx.store.put(client);
        }
    }

    await tx.done;
}

window.keepClientInLocalDB = keepClientInLocalDB;
window.getClientsForSync = getClientsForSync;
window.markClientAsSynced = markClientAsSynced;

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
                this.syncOfflineClients();
            });
            window.addEventListener("offline", () => {
                this.isOnline = false;
            });
            window.addEventListener("offline-db-ready", () => {
                if (this.isOnline) {
                    this.syncOfflineClients();
                }
            });
            if (this.isOnline) {
                this.syncOfflineClients();
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
                } else {
                    console.error("Synchronization failed:", result);
                }
            } catch (err) {
                console.error("Error during synchronization:", err);
            } finally {
                this.isSyncing = false;
            }
        },

        submitForm() {
            this.successMessage = "";
            this.errorMessage = "";

            if (!this.dni || this.dni.trim() === "") {
                this.errorMessage = "DNI (ID Number) is required.";
                return;
            }
            if (!this.first_name || this.first_name.trim() === "") {
                this.errorMessage = "First Name is required.";
                return;
            }
            if (!this.first_last_name || this.first_last_name.trim() === "") {
                this.errorMessage = "First Last Name is required.";
                return;
            }
            if (!this.email || this.email.trim() === "") {
                this.errorMessage = "Email address is required.";
                return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email)) {
                this.errorMessage = "Please enter a valid email address.";
                return;
            }
            if (!this.phone_number || this.phone_number.trim() === "") {
                this.errorMessage = "Phone Number is required.";
                return;
            }
            if (!this.address || this.address.trim() === "") {
                this.errorMessage = "Address is required.";
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
                updated_at: new Date().toISOString(),
            };

            if (typeof window.keepClientInLocalDB === "function") {
                window
                    .keepClientInLocalDB(client)
                    .then(() => {
                        this.successMessage =
                            "Client registered successfully (stored offline)!";
                        this.resetForm();

                        if (this.isOnline) {
                            this.syncOfflineClients();
                        }
                    })
                    .catch((err) => {
                        console.error(err);
                        this.errorMessage =
                            "Failed to save client offline. Please try again.";
                    });
            } else {
                this.errorMessage = "Offline database helper is not loaded.";
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
