import "./offline-db";
if ("serviceWorker" in navigator) {
    window.addEventListener("load", () => {
        navigator.serviceWorker
            .register("/sw.js")
            .then((reg) =>
                console.log(
                    "Single Service Worker registered at root!",
                    reg,
                ),
            )
            .catch((err) => console.error("Error registering Service Worker:", err));
    });
}
