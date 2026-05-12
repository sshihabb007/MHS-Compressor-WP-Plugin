/* coi-serviceworker.js — Cross-Origin Isolation Service Worker
 * MHS Image Compressor | Author: Mehedi Shihab (sshihabb007)
 * Enables SharedArrayBuffer by injecting COOP/COEP headers via SW
 * Source: https://github.com/gzuidhof/coi-serviceworker (MIT)
 */
self.addEventListener("install", () => self.skipWaiting());
self.addEventListener("activate", (e) => e.waitUntil(self.clients.claim()));

self.addEventListener("fetch", function (e) {
  if (e.request.cache === "only-if-cached" && e.request.mode !== "same-origin") return;
  e.respondWith(
    fetch(e.request)
      .then(function (res) {
        if (res.status === 0) return res;
        const newHeaders = new Headers(res.headers);
        newHeaders.set("Cross-Origin-Opener-Policy", "same-origin");
        newHeaders.set("Cross-Origin-Embedder-Policy", "require-corp");
        newHeaders.set("Cross-Origin-Resource-Policy", "cross-origin");
        return new Response(res.body, {
          status: res.status,
          statusText: res.statusText,
          headers: newHeaders,
        });
      })
      .catch((e) => console.error("[shihab-coi-sw] fetch error:", e))
  );
});
