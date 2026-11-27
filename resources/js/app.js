document.addEventListener("livewire:navigate", (event) => {
    const { url, history, cached } = event.detail;

    // These screens can change while the other one is in the navigation history.
    if (history && cached && ["/hoje", "/habitos"].includes(url.pathname)) {
        event.preventDefault();
        window.location.reload();
    }
});

if ("serviceWorker" in navigator) {
    window.addEventListener("load", () => {
        navigator.serviceWorker.register("/sw.js").catch(() => {
            // The app works without it; only offline support and install are lost.
        });
    });
}
