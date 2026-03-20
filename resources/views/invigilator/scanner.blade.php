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
                        <div id="scanner-overlay" class="absolute inset-0 bg-gray-900/80 items-center justify-center hidden">
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

                <!-- Offline Mode Panel -->
                <div class="mt-6 pt-6 border-t border-gray-100" x-data="offlineManager()">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-900">Offline Mode</h3>
                        <span :class="isOnline ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'" class="px-2 py-0.5 rounded text-xs font-medium">
                            <span x-text="isOnline ? 'Online' : 'Offline'"></span>
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <button @click="downloadSchedule()" :disabled="!isOnline" class="btn btn-outline text-xs py-2 w-full">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Download Schedule
                        </button>
                        <button @click="sync() " :disabled="!isOnline || !hasPending" class="btn btn-outline text-xs py-2 w-full relative">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Sync Attendance
                            <span x-show="hasPending" x-text="pendingCount" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center"></span>
                        </button>
                    </div>

                    <div x-show="lastDownload" class="mt-2 text-[10px] text-gray-400">
                        Last download: <span x-text="lastDownload"></span>
                    </div>
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
        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }

        function offlineManager() {
            return {
                isOnline: navigator.onLine,
                hasPending: false,
                pendingCount: 0,
                lastDownload: localStorage.getItem('mx_last_download') || null,

                init() {
                    window.addEventListener('online', () => this.isOnline = true);
                    window.addEventListener('offline', () => this.isOnline = false);
                    this.updatePending();
                },

                updatePending() {
                    const logs = JSON.parse(localStorage.getItem('mx_offline_logs') || '[]');
                    this.pendingCount = logs.length;
                    this.hasPending = logs.length > 0;
                },

                async downloadSchedule() {
                    // In a real app, we'd prompt for the exam ID or get active exams
                    const examId = prompt("Enter Exam ID to download (e.g., 1 or 2):");
                    if (!examId) return;

                    try {
                        const resp = await fetch(`/api/v1/offline/schedule/${examId}`);
                        if (!resp.ok) throw new Error("Failed to fetch schedule");

                        const data = await resp.json();
                        localStorage.setItem('mx_offline_schedule', JSON.stringify(data.allocations));
                        this.lastDownload = new Date().toLocaleString();
                        localStorage.setItem('mx_last_download', this.lastDownload);
                        alert("Schedule downloaded successfully!");
                    } catch (err) {
                        alert("Error downloading schedule: " + err.message);
                    }
                },

                async sync() {
                    const logs = JSON.parse(localStorage.getItem('mx_offline_logs') || '[]');
                    if (logs.length === 0) return;

                    try {
                        const resp = await fetch('/api/v1/offline/sync', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ logs })
                        });

                        const data = await resp.json();
                        if (data.success) {
                            localStorage.setItem('mx_offline_logs', '[]');
                            this.updatePending();
                            alert(`Synced ${data.synced_count} records successfully!`);
                        }
                    } catch (err) {
                        alert("Sync failed: " + err.message);
                    }
                }
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const resultEl = document.getElementById('scan-result');
            const resultContent = document.getElementById('result-content');
            const historyEl = document.getElementById('scan-history');
            const manualInput = document.getElementById('manual-qr-input');
            const manualBtn = document.getElementById('manual-validate-btn');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            let html5QrCode;

            // Camera Scanner logic
            const startBtn = document.getElementById('start-scanner');
            startBtn.addEventListener('click', async () => {
                if (html5QrCode && html5QrCode.isScanning) {
                    await html5QrCode.stop();
                    startBtn.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg> Start Camera';
                    return;
                }

                if (!html5QrCode) {
                    html5QrCode = new Html5Qrcode("scanner-video");
                }

                try {
                    startBtn.disabled = true;
                    startBtn.textContent = "Requesting Camera...";
                    
                    await html5QrCode.start(
                        { facingMode: "environment" },
                        {
                            fps: 10,
                            qrbox: { width: 250, height: 250 }
                        },
                        (decodedText) => {
                            validateQr(decodedText);
                            // Optional: vibrate or play sound on success
                            if (navigator.vibrate) navigator.vibrate(100);
                        },
                        (errorMessage) => {
                            // parse error, ignore
                        }
                    );
                    
                    startBtn.disabled = false;
                    startBtn.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg> Stop Camera';
                } catch (err) {
                    startBtn.disabled = false;
                    startBtn.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg> Start Camera';
                    alert("Camera Error: " + err);
                }
            });

            // Manual validation
            manualBtn.addEventListener('click', () => {
                const payload = manualInput.value.trim();
                if (payload) validateQr(payload);
            });

            async function validateQr(payload) {
                resultEl.classList.remove('hidden');
                resultContent.innerHTML = '<div class="text-center py-4"><p class="text-gray-500">Validating…</p></div>';

                if (!navigator.onLine) {
                    validateOffline(payload);
                    return;
                }

                try {
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
                    // Fallback to offline if network fails during request
                    validateOffline(payload);
                }
            }

            function validateOffline(payload) {
                // For the prototype, we assume the QR payload is the JSON string
                // In production, we'd verify the HMAC here if we have the key
                let qrData;
                try {
                    qrData = JSON.parse(payload);
                } catch (e) {
                    showResult({ valid: false, result_label: 'Invalid QR', message: 'Malformed data' });
                    return;
                }

                const schedule = JSON.parse(localStorage.getItem('mx_offline_schedule') || '[]');
                const allocation = schedule.find(a => a.aid == qrData.aid);

                if (!allocation) {
                    const data = { valid: false, result_label: 'Not in Schedule', message: 'Student not found in offline data' };
                    showResult(data);
                    addToHistory(data);
                    return;
                }

                // Check session match
                const now = Math.floor(Date.now() / 1000);
                const window = 15 * 60; // 15 minutes grace

                if (now < allocation.start - window) {
                    const data = { valid: false, result_label: 'Too Early', message: 'Session has not started' };
                    showResult(data);
                    addToHistory(data);
                    return;
                }

                // If valid, store in offline logs
                const data = {
                    valid: true,
                    result_label: 'Valid (Offline)',
                    message: 'Entry Permitted',
                    student: {
                        name: allocation.student,
                        matric_number: allocation.matric,
                        hall: allocation.hall,
                        system: allocation.system
                    }
                };

                const logs = JSON.parse(localStorage.getItem('mx_offline_logs') || '[]');
                logs.push({
                    aid: allocation.aid,
                    scanned_at: now,
                    result: 'valid'
                });
                localStorage.setItem('mx_offline_logs', JSON.stringify(logs));

                // Update UI
                showResult(data);
                addToHistory(data);

                // Refresh Alpine data if present
                if (window.Alpine) {
                    // This is a bit hacky but works for manual Alpine init
                }
            }

            function showResult(data) {
                if (data.valid) {
                    resultContent.innerHTML = `
                        <div class="scan-result valid">
                            <svg class="w-12 h-12 mx-auto mb-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            <p class="text-xl font-bold">${data.result_label || 'Valid'}</p>
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
                            <p class="text-xl font-bold">${data.result_label || 'Invalid'}</p>
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
                        <span class="text-sm font-medium ${data.valid ? 'text-emerald-700' : 'text-red-700'} ml-2">${data.result_label || 'Valid'}</span>
                        ${data.student ? `<span class="text-xs text-gray-500 ml-2">${data.student.name}</span>` : ''}
                    </div>`;

                if (historyEl.querySelector('p')) historyEl.innerHTML = '';
                historyEl.prepend(entry);
            }
        });
    </script>
</x-layouts.app>
