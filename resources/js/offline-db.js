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
    const clientsForSync = await db.getAllFromIndex("clients", "for_sync", false);

    return clientsForSync;
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
