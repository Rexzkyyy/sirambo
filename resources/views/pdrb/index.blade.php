@extends('layouts.main')

@section('title', 'Import PDRB')

@section('content')
    <div class="w-full max-w-none mx-auto px-4 py-6">

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">
                Import Data PDRB
                {{ $pendekatan === 'lapangan_usaha' ? 'Menurut Lapangan Usaha' : 'Menurut Pengeluaran' }}
            </h1>
            @if(in_array(auth()->user()?->role, ['provinsi', 'provinsi_supervisor']))
                <a href="{{ route('pdrb.import_logs') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-bold transition flex items-center gap-2 border border-gray-200 shadow-sm">
                    <i class="fas fa-history text-blue-600"></i>
                    Lihat Log History
                </a>
            @endif
        </div>

        {{-- ================= PESAN ================= --}}
        @if(session('success'))
            <div class="bg-green-100 text-green-700 p-4 mb-6 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if(session('success_full'))
            <div class="bg-green-100 text-green-700 p-4 mb-6 rounded whitespace-pre-line">
                {!! session('success_full') !!}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 text-red-700 p-4 mb-6 rounded whitespace-pre-line">
                {!! session('error') !!}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 text-red-700 p-4 mb-6 rounded">
                <div class="font-semibold mb-2">Validasi gagal:</div>
                @foreach($errors->all() as $err)
                    <div>- {{ $err }}</div>
                @endforeach
            </div>
        @endif

        {{-- ================================================= --}}
        {{-- STATUS KUNCI + TOMBOL DI ATAS --}}
        {{-- ================================================= --}}
        <div id="lock-banner" class="mb-4 px-4 py-2 rounded shadow hidden" style="display:none;">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <span id="lock-banner-icon" class="text-lg mr-2"></span>
                    <div>
                        <span id="lock-banner-text" class="font-bold text-sm"></span>
                        <span id="lock-banner-subtext" class="text-xs text-gray-600 ml-2"></span>
                    </div>
                </div>
                @if(auth()->user()?->role === 'provinsi')
                    <button type="button" id="lock-toggle-btn"
                        class="px-3 py-1.5 rounded font-bold text-white text-xs transition shadow">
                    </button>
                @endif
            </div>
        </div>

        {{-- ================================================= --}}
        {{-- MODAL POP-UP KUNCI --}}
        {{-- ================================================= --}}
        @if(auth()->user()?->role === 'provinsi')
            <div id="lock-modal-overlay" class="fixed inset-0 hidden"
                style="background:rgba(0,0,0,0.4); display:none; align-items:center; justify-content:center; z-index: 9999;">
                <div class="bg-white rounded-lg shadow-2xl overflow-hidden" style="width:340px;">
                    {{-- Header --}}
                    <div id="modal-header" class="px-4 py-3">
                        <h3 id="modal-title" class="text-sm font-bold text-white"></h3>
                    </div>
                    {{-- Body --}}
                    <div class="px-4 py-3">
                        <p id="modal-desc" class="text-gray-600 text-xs mb-3"></p>
                        
                        <div id="wilayah-group" class="mb-3">
                            <label class="block mb-1 font-medium text-xs text-gray-700">
                                <i class="fas fa-map-marker-alt mr-1"></i> Wilayah
                            </label>
                            <select id="modal-wilayah-input" class="w-full border border-gray-300 p-1.5 rounded text-xs focus:ring-2 focus:ring-blue-400 outline-none">
                                <option value="0">Semua Wilayah (Global)</option>
                                @foreach($wilayah as $w)
                                    <option value="{{ $w->id_wilayah }}">{{ $w->nama_wilayah }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="deadline-group" class="mb-3">
                            <label class="block mb-1 font-medium text-xs text-gray-700">
                                <i class="fas fa-clock mr-1"></i> Deadline <span class="text-gray-400">(opsional)</span>
                            </label>
                            <input type="datetime-local" id="modal-deadline-input"
                                class="w-full border border-gray-300 p-1.5 rounded text-xs focus:ring-2 focus:ring-blue-400 outline-none">
                        </div>
                    </div>
                    {{-- Footer --}}
                    <div class="px-4 py-2 bg-gray-50 flex justify-end space-x-2 border-t">
                        <button type="button" id="modal-cancel-btn"
                            class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition text-xs font-medium">
                            Batal
                        </button>
                        <button type="button" id="modal-confirm-btn"
                            class="px-3 py-1.5 rounded text-white font-bold text-xs transition shadow">
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <input type="hidden" id="current-pendekatan" value="{{ $pendekatan }}">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- ================================================= --}}
            {{-- CARD 1 : IMPORT SINGLE TAHUN --}}
            {{-- ================================================= --}}
            <div class="bg-white p-6 rounded shadow">
                <h2 class="text-xl font-bold mb-4 text-blue-600">
                    <i class="fas fa-file-import mr-2"></i>
                    Import PDRB
                    {{ $pendekatan === 'lapangan_usaha' ? 'Lapangan Usaha' : 'Pengeluaran' }}
                    (Single Tahun)
                </h2>

                {{-- DOWNLOAD TEMPLATE SINGLE --}}
                <a href="{{ route('pdrb.template', ['jenis' => $pendekatan]) }}"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 mb-4 transition">
                    <i class="fas fa-download mr-2"></i>
                    Download Template Single
                </a>

                {{-- FORM SINGLE --}}
                <form action="{{ route('pdrb.import', ['jenis' => $pendekatan]) }}" method="POST"
                    enctype="multipart/form-data" id="import-single-form">
                    @csrf
                    <input type="hidden" name="jenis" value="{{ $pendekatan }}">

                    <div class="space-y-4">

                        @php
                            $quarterMap = [
                                'Triwulan I' => 1,
                                'Triwulan II' => 2,
                                'Triwulan III' => 3,
                                'Triwulan IV' => 4
                            ];
                        @endphp

                        <div>
                            <label class="block mb-1 font-medium">Tahun</label>
                            <select name="id_tahun" id="tahun-select" class="w-full border p-2 rounded"
                                data-current-year="{{ $currentYear }}" required>
                                <option value="">-- Pilih Tahun --</option>
                                @foreach($tahun as $t)
                                    <option value="{{ $t->id_tahun }}" data-year="{{ $t->tahun }}">{{ $t->tahun }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block mb-1 font-medium">Periode</label>
                            <select name="id_periode" id="periode-select" class="w-full border p-2 rounded"
                                data-current-quarter="{{ $currentQuarter }}" required>
                                <option value="">-- Pilih Periode --</option>
                                @foreach($periode as $p)
                                    <option value="{{ $p->id_periode }}"
                                        data-quarter="{{ $quarterMap[$p->nama_periode] ?? '' }}">
                                        {{ $p->nama_periode }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="text-sm text-gray-600">
                            @if($pendekatan === 'lapangan_usaha')
                                📌 File Excel berisi sektor Lapangan Usaha (A–U)
                            @else
                                📌 File Excel berisi komponen Pengeluaran (Konsumsi, PMTB, Ekspor, dll)
                            @endif
                        </div>

                        <div>
                            <label class="block mb-1 font-medium">File Excel</label>
                            <input type="file" name="file" class="w-full border p-2 rounded" required>
                        </div>

                        <button type="submit" id="submit-import-btn"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded font-medium disabled:bg-gray-400 disabled:cursor-not-allowed">
                            <i class="fas fa-upload mr-2"></i>
                            Upload & Import Single
                        </button>
                    </div>
                </form>
            </div>

            {{-- ================================================= --}}
            {{-- CARD 2 : IMPORT MULTI TAHUN (PROVINSI) --}}
            {{-- ================================================= --}}
            @if($canImportMulti)
                <div class="bg-white p-6 rounded shadow">
                    <h2 class="text-xl font-bold mb-4 text-purple-600">
                        <i class="fas fa-layer-group mr-2"></i>
                        Import PDRB
                        {{ $pendekatan === 'lapangan_usaha' ? 'Lapangan Usaha' : 'Pengeluaran' }}
                        (Multi Tahun)
                    </h2>

                    {{-- DOWNLOAD TEMPLATE MULTI --}}
                    <a href="{{ route('pdrb.template.full', ['jenis' => $pendekatan]) }}"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 mb-4 transition">
                        <i class="fas fa-download mr-2"></i>
                        Download Template Multi Tahun
                    </a>

                    {{-- INFO FILE --}}
                    <div class="bg-purple-50 border border-purple-200 p-4 rounded text-sm text-purple-800 mb-4">
                        <p class="font-semibold mb-2">📌 Ketentuan File Excel:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li><b>1 File Excel</b></li>
                            <li><b>2 Sheet WAJIB</b>: Berlaku & Konstan</li>
                            <li>
                                @if($pendekatan === 'lapangan_usaha')
                                    Data sektor Lapangan Usaha
                                @else
                                    Data komponen Pengeluaran
                                @endif
                            </li>
                            <li>Mendukung <b>multi tahun & semua triwulan</b></li>
                        </ul>
                    </div>

                    {{-- FORM MULTI --}}
                    <form action="{{ route('pdrb.import.full', ['jenis' => $pendekatan]) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="jenis" value="{{ $pendekatan }}">

                        <div class="space-y-4">
                            <div>
                                <label class="block mb-1 font-medium">Wilayah Tujuan</label>
                                <select name="id_wilayah" class="w-full border p-2 rounded" required>
                                    <option value="">-- Pilih Wilayah --</option>
                                    @foreach($wilayah as $w)
                                        <option value="{{ $w->id_wilayah }}">
                                            {{ $w->nama_wilayah }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block mb-1 font-medium">File Excel</label>
                                <input type="file" name="file" class="w-full border p-2 rounded" required>
                            </div>

                            <button class="w-full bg-purple-600 hover:bg-purple-700 text-white py-3 rounded font-medium">
                                <i class="fas fa-upload mr-2"></i>
                                Upload & Import Multi Tahun
                            </button>
                        </div>
                    </form>
                </div>
            @endif

        </div>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const yearSelect = document.getElementById('tahun-select');
            const periodSelect = document.getElementById('periode-select');
            const pendekatan = document.getElementById('current-pendekatan')?.value || 'lapangan_usaha';
            const submitBtn = document.getElementById('submit-import-btn');
            const isProvinsi = {{ auth()->user()?->role === 'provinsi' ? 'true' : 'false' }};

            // Banner elements
            const lockBanner = document.getElementById('lock-banner');
            const lockBannerIcon = document.getElementById('lock-banner-icon');
            const lockBannerText = document.getElementById('lock-banner-text');
            const lockBannerSubtext = document.getElementById('lock-banner-subtext');
            const lockToggleBtn = document.getElementById('lock-toggle-btn');

            // Modal elements
            const modalOverlay = document.getElementById('lock-modal-overlay');
            const modalHeader = document.getElementById('modal-header');
            const modalTitle = document.getElementById('modal-title');
            const modalDesc = document.getElementById('modal-desc');
            const deadlineGroup = document.getElementById('deadline-group');
            const modalDeadlineInput = document.getElementById('modal-deadline-input');
            const modalCancelBtn = document.getElementById('modal-cancel-btn');
            const modalConfirmBtn = document.getElementById('modal-confirm-btn');

            let currentLockState = false;

            if (!yearSelect || !periodSelect) return;

            const currentYear = parseInt(yearSelect.dataset.currentYear || '', 10);
            const currentQuarter = parseInt(periodSelect.dataset.currentQuarter || '', 10);

            // === Filter periode berdasarkan tahun ===
            const updatePeriodOptions = () => {
                const selectedOption = yearSelect.options[yearSelect.selectedIndex];
                const selectedYear = parseInt(selectedOption?.dataset?.year || '', 10);
                const isCurrentYear = Number.isInteger(selectedYear) && selectedYear === currentYear;

                let hasValidSelection = false;

                Array.from(periodSelect.options).forEach((opt) => {
                    const quarter = parseInt(opt.dataset.quarter || '', 10);
                    if (!quarter) return;

                    const shouldDisable = isCurrentYear && Number.isInteger(currentQuarter) && quarter > currentQuarter;
                    opt.disabled = shouldDisable;
                    opt.hidden = shouldDisable;

                    if (opt.selected && !shouldDisable) {
                        hasValidSelection = true;
                    }
                });

                if (!hasValidSelection) periodSelect.value = '';
            };

            updatePeriodOptions();
            yearSelect.addEventListener('change', updatePeriod            // === Cek status kunci global (AJAX) ===
            const modalWilayahInput = document.getElementById('modal-wilayah-input');

            const checkLockStatus = (idWilayah = 0) => {
                const query = new URLSearchParams({
                    pendekatan: pendekatan,
                    id_wilayah: idWilayah
                });

                fetch(`{{ route('pdrb.lock-status') }}?${query}`)
                    .then(res => res.json())
                    .then(data => {
                        if (!data.success) return;

                        // Jika idWilayah == 0 (global), update banner utama
                        if (idWilayah == 0) {
                            currentLockState = data.is_locked || !!data.lock_deadline;
                            updateBanner(data);
                        }
                        
                        // Jika modal sedang terbuka, update UI modal (desc, button text)
                        if (modalOverlay && !modalOverlay.classList.contains('hidden')) {
                            // Untuk modal, state lock yang ditampilkan adalah spesifik wilayah terpilih
                            const isSpecLocked = data.is_manually_locked || (data.lock_deadline && new Date(data.lock_deadline) < new Date());
                            updateModalUI(isSpecLocked, idWilayah);
                            // Update global state if we are acting on what we see in modal
                            currentLockState = isSpecLocked;
                        }
                    })
                    .catch(err => console.error('Lock check error:', err));
            };

            const updateBanner = (data) => {
                lockBanner.classList.remove('hidden');
                lockBanner.style.display = 'block';

                if (data.is_locked) {
                    lockBanner.className = 'mb-4 px-4 py-2 rounded shadow bg-red-50 border border-red-300';
                    lockBannerIcon.innerHTML = '🔒';
                    lockBannerText.innerHTML = 'TERKUNCI (GLOBAL)';
                    lockBannerText.className = 'font-bold text-sm text-red-700';
                    lockBannerSubtext.innerHTML = isProvinsi ? '<span class="text-red-600 font-medium italic ml-2">(Sebagai Provinsi, Anda tetap dapat melakukan import)</span>' : '';

                    if (lockToggleBtn) {
                        lockToggleBtn.innerHTML = '<i class="fas fa-unlock mr-1"></i> Buka Kunci Global';
                        lockToggleBtn.className = 'px-3 py-1.5 rounded font-bold text-white text-xs transition shadow bg-green-600 hover:bg-green-700';
                    }

                    if (!isProvinsi) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-ban mr-2"></i> Import Dikunci';
                    }
                } else {
                    if (data.lock_deadline) {
                        lockBanner.className = 'mb-4 px-4 py-2 rounded shadow bg-orange-50 border border-orange-300';
                        lockBannerIcon.innerHTML = '⏳';
                        lockBannerText.innerHTML = 'TERBUKA';
                        lockBannerText.className = 'font-bold text-sm text-orange-700';
                        lockBannerSubtext.innerHTML = `<span class="text-orange-600">Akan terkunci: ${new Date(data.lock_deadline).toLocaleString('id-ID')}</span>`;
                        if (isProvinsi) lockBannerSubtext.innerHTML += ' <span class="italic">(Provinsi tetap bisa import)</span>';
                        
                        if (lockToggleBtn) {
                            lockToggleBtn.innerHTML = '<i class="fas fa-unlock mr-1"></i> Buka';
                            lockToggleBtn.className = 'px-3 py-1.5 rounded font-bold text-white text-xs transition shadow bg-green-600 hover:bg-green-700';
                        }
                    } else {
                        lockBanner.className = 'mb-4 px-4 py-2 rounded shadow bg-green-50 border border-green-300';
                        lockBannerIcon.innerHTML = '🔓';
                        lockBannerText.innerHTML = 'TERBUKA';
                        lockBannerText.className = 'font-bold text-sm text-green-700';
                        lockBannerSubtext.innerHTML = '';

                        if (lockToggleBtn) {
                            lockToggleBtn.innerHTML = '<i class="fas fa-lock mr-1"></i> Kunci Import';
                            lockToggleBtn.className = 'px-3 py-1.5 rounded font-bold text-white text-xs transition shadow bg-red-600 hover:bg-red-700';
                        }
                    }

                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-upload mr-2"></i> Upload & Import Single';
                }
            };

            const updateModalUI = (isLocked, idWilayah) => {
                const selectedText = modalWilayahInput.options[modalWilayahInput.selectedIndex].text;
                if (isLocked) {
                    modalHeader.className = 'px-4 py-3 bg-green-600';
                    modalTitle.innerHTML = '<i class="fas fa-unlock mr-1"></i> Buka Kunci';
                    modalDesc.innerHTML = `Status saat ini: <b>TERKUNCI</b> untuk ${selectedText}. Buka kunci agar wilayah ini bisa import kembali.`;
                    deadlineGroup.classList.add('hidden');
                    modalConfirmBtn.innerHTML = '<i class="fas fa-unlock mr-1"></i> Buka';
                    modalConfirmBtn.className = 'px-3 py-1.5 rounded text-white font-bold text-xs transition shadow bg-green-600 hover:bg-green-700';
                } else {
                    modalHeader.className = 'px-4 py-3 bg-red-600';
                    modalTitle.innerHTML = '<i class="fas fa-lock mr-1"></i> Kunci Import';
                    modalDesc.innerHTML = `Status saat ini: <b>TERBUKA</b> untuk ${selectedText}. Kunci agar wilayah ini tidak bisa melakukan import.`;
                    deadlineGroup.classList.remove('hidden');
                    modalConfirmBtn.innerHTML = '<i class="fas fa-lock mr-1"></i> Kunci';
                    modalConfirmBtn.className = 'px-3 py-1.5 rounded text-white font-bold text-xs transition shadow bg-red-600 hover:bg-red-700';
                }
            };

            if (modalWilayahInput) {
                modalWilayahInput.addEventListener('change', function() {
                    checkLockStatus(this.value);
                });
            }

            // Cek status saat halaman dimuat + Polling setiap 30 detik
            checkLockStatus(0);
            setInterval(() => checkLockStatus(0), 30000);

            // === TOMBOL KUNCI/BUKA → buka modal ===
            if (lockToggleBtn && modalOverlay) {
                lockToggleBtn.addEventListener('click', function () {
                    if (modalWilayahInput) modalWilayahInput.value = "0";
                    checkLockStatus(0);
                    modalOverlay.classList.remove('hidden');
                    modalOverlay.style.display = 'flex';
                });

                // Tutup modal
                modalCancelBtn.addEventListener('click', function () {
                    modalOverlay.classList.add('hidden');
                    modalOverlay.style.display = 'none';
                });

                // Klik di luar modal → tutup
                modalOverlay.addEventListener('click', function (e) {
                    if (e.target === modalOverlay) {
                        modalOverlay.classList.add('hidden');
                        modalOverlay.style.display = 'none';
                    }
                });

                // Konfirmasi kunci/buka
                modalConfirmBtn.addEventListener('click', function () {
                    const action = currentLockState ? 'unlock' : 'lock';
                    const deadline = modalDeadlineInput?.value || null;
                    const idWilayah = modalWilayahInput?.value || 0;

                    const body = {
                        pendekatan: pendekatan,
                        action: action,
                        id_wilayah: idWilayah
                    };

                    if (action === 'lock' && deadline) {
                        body.deadline = deadline;
                    }

                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Memproses...';

                    fetch('{{ route("pdrb.toggle-lock") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(body)
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                alert(data.message);
                                modalOverlay.classList.add('hidden');
                                modalOverlay.style.display = 'none';
                                checkLockStatus(idWilayah);
                                // Jika kita baru saja mengubah sesuatu yang bukan global, 
                                // kita juga harus cek ulang global status untuk banner
                                if (idWilayah != 0) checkLockStatus(0);
                            } else {
                                alert('Gagal: ' + data.message);
                            }
                        })
                        .catch(err => alert('Error: ' + err.message))
                        .finally(() => {
                            modalConfirmBtn.disabled = false;
                        });
                });
            }
        });
    </script>
@endsection