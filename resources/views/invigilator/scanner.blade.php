<x-layouts.app :title="'QR Scanner'">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">QR Scanner</h1>
        <p class="text-sm text-gray-500 mt-1">Scan student exam passes to validate entry</p>
    </x-slot>

    <div class="max-w-lg mx-auto">
        <div class="card">
            <div class="card-body">
                <!-- Scanner Area -->
                <div id="scanner-container" class="relative">
                    <div id="camera-view" class="w-full aspect-square rounded-lg bg-gray-900 overflow-hidden relative">
                        <video id="scanner-video" class="w-full h-full object-cover" playsinline></video>
                        <!-- Scanner overlay -->
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="w-56 h-56 border-2 border-white/50 rounded-2xl relative">
                                <div class="absolute -top-0.5 -left-0.5 w-8 h-8 border-t-4 border-l-4 border-indigo-400 rounded-tl-lg"></div>
                                <div class="absolute -top-0.5 -right-0.5 w-8 h-8 border-t-4 border-r-4 border-indigo-400 rounded-tr-lg"></div>
                                <div class="absolute -bottom-0.5 -left-0.5 w-8 h-8 border-b-4 border-l-4 border-indigo-400 rounded-bl-lg"></div>
                                <div class="absolute -bottom-0.5 -right-0.5 w-8 h-8 border-b-4 border-r-4 border-indigo-400 rounded-br-lg"></div>
                            </div>
                        </div>
                        <div id="scanner-overlay" class="absolute inset-0 bg-gray-900/80 flex items-center justify-center hidden">
                            <p class="text-white text-sm">Processing…</p>
                        </div>
                    </div>
                    <button id="start-scanner" class="btn btn-primary w-full mt-4">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Start Camera
                    </button>
                </div>

                <!-- Manual Input -->
                <div class="mt-6 pt-6 border-t border-gray-100">
                    <p class="text-sm text-gray-500 mb-3">Or enter QR data manually:</p>
                    <div class="flex gap-2">
                        <input type="text" id="manual-qr-input" class="form-input-styled flex-1" placeholder="Paste QR payload…">
                        <button id="manual-validate-btn" class="btn btn-primary">Validate</button>
                    </div>
                </div>

                <!-- Result Display -->
                <div id="scan-result" class="mt-6 hidden">
                    <div id="result-content"></div>
                </div>

                <!-- Recent Scans -->
                <div class="mt-6 pt-6 border-t border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Recent Scans</h3>
                    <div id="scan-history" class="space-y-2">
                        <p class="text-sm text-gray-400 text-center py-4">No scans yet</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const resultEl = document.getElementById('scan-result');
            const resultContent = document.getElementById('result-content');
            const historyEl = document.getElementById('scan-history');
            const manualInput = document.getElementById('manual-qr-input');
            const manualBtn = document.getElementById('manual-validate-btn');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const scanHistory = [];

            // Manual validation
            manualBtn.addEventListener('click', () => {
                const payload = manualInput.value.trim();
                if (payload) validateQr(payload);
            });

            async function validateQr(payload) {
                try {
                    resultEl.classList.remove('hidden');
                    resultContent.innerHTML = '<div class="text-center py-4"><p class="text-gray-500">Validating…</p></div>';

                    const resp = await fetch('{{ route("api.validate-qr") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ qr_payload: payload }),
                    });

                    const data = await resp.json();
                    showResult(data);
                    addToHistory(data);
                } catch (err) {
                    resultContent.innerHTML = `<div class="scan-result invalid">Network error: ${err.message}</div>`;
                }
            }

            function showResult(data) {
                if (data.valid) {
                    resultContent.innerHTML = `
                        <div class="scan-result valid">
                            <svg class="w-12 h-12 mx-auto mb-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            <p class="text-xl font-bold">${data.message}</p>
                            ${data.student ? `
                                <div class="mt-3 text-left bg-white rounded-lg p-3 text-sm">
                                    <p><strong>${data.student.name}</strong></p>
                                    <p class="text-gray-600">${data.student.matric_number}</p>
                                    <p class="text-gray-600">Hall: ${data.student.hall} · System: ${data.student.system}</p>
                                </div>
                            ` : ''}
                        </div>`;
                } else {
                    resultContent.innerHTML = `
                        <div class="scan-result invalid">
                            <svg class="w-12 h-12 mx-auto mb-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                            <p class="text-xl font-bold">${data.result_label}</p>
                            <p class="text-sm mt-1">${data.message}</p>
                        </div>`;
                }
            }

            function addToHistory(data) {
                const time = new Date().toLocaleTimeString();
                const entry = document.createElement('div');
                entry.className = `flex justify-between items-center px-3 py-2 rounded-lg ${data.valid ? 'bg-emerald-50' : 'bg-red-50'}`;
                entry.innerHTML = `
                    <div>
                        <span class="text-xs font-mono text-gray-400">${time}</span>
                        <span class="text-sm font-medium ${data.valid ? 'text-emerald-700' : 'text-red-700'} ml-2">${data.result_label}</span>
                        ${data.student ? `<span class="text-xs text-gray-500 ml-2">${data.student.name}</span>` : ''}
                    </div>`;

                if (historyEl.querySelector('p')) historyEl.innerHTML = '';
                historyEl.prepend(entry);
            }
        });
    </script>
</x-layouts.app>
