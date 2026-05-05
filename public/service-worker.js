// =============================
// SERVICE WORKER — SAFE VERSION
// =============================

// Trigger saat pertama kali di-install
self.addEventListener('install', (event) => {
    // Skip waiting supaya versi baru langsung aktif
    self.skipWaiting();
});

// Trigger saat service worker aktif
self.addEventListener('activate', (event) => {
    // Membersihkan SW lama jika ada
    event.waitUntil(clients.claim());
});

// FETCH HANDLER PALING AMAN
// Tidak mengganggu request network
// Tidak memaksa fetch()
// Tidak membuat PWA blank meski internet mati
self.addEventListener('fetch', (event) => {
    // Biarkan browser melakukan fetch default-nya
});
// =============================
// SERVICE WORKER — SAFE VERSION
// =============================

// Trigger saat pertama kali di-install
self.addEventListener('install', (event) => {
    // Skip waiting supaya versi baru langsung aktif
    self.skipWaiting();
});

// Trigger saat service worker aktif
self.addEventListener('activate', (event) => {
    // Membersihkan SW lama jika ada
    event.waitUntil(clients.claim());
});

// FETCH HANDLER PALING AMAN
// Tidak mengganggu request network
// Tidak memaksa fetch()
// Tidak membuat PWA blank meski internet mati
self.addEventListener('fetch', (event) => {
    // Biarkan browser melakukan fetch default-nya
});
