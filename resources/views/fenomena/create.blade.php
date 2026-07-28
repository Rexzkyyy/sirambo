@extends('layouts.main')

@section('title', 'Upload Excel Fenomena PDRB')

@section('content')
<div class="container-fluid px-6 py-8 bg-gray-50 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Upload Excel Fenomena PDRB</h1>
            <p class="text-gray-500 mt-1">Upload file Excel untuk mengimport data fenomena ekonomi</p>
        </div>
        <div class="flex gap-3">
            <!-- Tombol Kunci Upload (untuk semua wilayah) - HANYA untuk provinsi -->
            @if(isset($isProvinsi) && $isProvinsi)
            <button id="lock-upload-btn" 
                    data-tahun="{{ $tahun }}"
                    data-pendekatan="{{ $pendekatan }}"
                    data-mode="{{ $mode }}"
                    data-locked="{{ isset($isUploadLocked) && $isUploadLocked ? 'true' : 'false' }}"
                    class="px-4 py-2.5 rounded-lg flex items-center gap-2 transition font-medium {{ (isset($isUploadLocked) && $isUploadLocked) ? 'bg-red-600 hover:bg-red-700' : 'bg-yellow-600 hover:bg-yellow-700' }} text-white">
                <i data-lucide="{{ (isset($isUploadLocked) && $isUploadLocked) ? 'lock' : 'unlock' }}" class="w-5 h-5"></i>
                <span>{{ (isset($isUploadLocked) && $isUploadLocked) ? 'Buka Kunci Upload' : 'Kunci Upload Wilayah' }}</span>
            </button>
            @endif
            
            <a href="{{ route('fenomena.index', ['mode' => $mode, 'tahun' => $tahun, 'pendekatan' => $pendekatan, 'id_wilayah' => $id_wilayah]) }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2.5 rounded-lg flex items-center gap-2 transition font-medium">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    <!-- Peringatan jika upload dikunci (untuk semua wilayah) -->
    @if(isset($isUploadLocked) && $isUploadLocked)
    <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-4 mb-6">
        <div class="flex items-center">
            <i data-lucide="lock" class="w-5 h-5 text-red-600 mr-3"></i>
            <div>
                <p class="text-sm font-medium text-red-800">Upload Data Sedang Dikunci!</p>
                <p class="text-sm text-red-700 mt-1">
                    Administrator provinsi sedang mengunci upload data untuk semua wilayah. 
                    Anda tidak dapat mengupload atau mengubah data saat ini. Silakan coba lagi nanti.
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Peringatan jika jawaban terkunci (per wilayah) -->
    @if(isset($isAnswerLocked) && $isAnswerLocked)
    <div class="bg-orange-50 border-l-4 border-orange-500 rounded-lg p-4 mb-6">
        <div class="flex items-center">
            <i data-lucide="lock" class="w-5 h-5 text-orange-600 mr-3"></i>
            <div>
                <p class="text-sm font-medium text-orange-800">Jawaban Wilayah Telah Dikunci!</p>
                <p class="text-sm text-orange-700 mt-1">
                    Jawaban untuk wilayah ini sudah dikunci. Anda tidak dapat mengupload atau mengubah data.
                </p>
            </div>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8">
        @if((isset($isUploadLocked) && $isUploadLocked) || (isset($isAnswerLocked) && $isAnswerLocked))
        <!-- Tampilkan form disabled jika terkunci -->
        <div class="opacity-50 pointer-events-none">
            <div class="space-y-6">
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i data-lucide="info" class="h-5 w-5 text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                <strong>Informasi Upload:</strong><br>
                                Mode: {{ ucfirst($mode) }}<br>
                                Tahun: {{ $tahun }}<br>
                                Pendekatan: {{ str_replace('_', ' ', $pendekatan) }}<br>
                                @if($id_wilayah)
                                    Wilayah ID: {{ $id_wilayah }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">File Excel</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg bg-gray-100">
                        <div class="space-y-1 text-center">
                            <i data-lucide="upload" class="mx-auto h-12 w-12 text-gray-400"></i>
                            <p class="text-sm text-gray-500">
                                @if($isUploadLocked) Upload dinonaktifkan karena provinsi sedang mengunci upload data
                                @else Upload dinonaktifkan karena jawaban wilayah sudah dikunci @endif
                            </p>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Template Excel</h3>
                            <p class="text-sm text-gray-500">Download template Excel</p>
                        </div>
                        <a href="{{ route('fenomena.download-template', ['mode' => $mode, 'pendekatan' => $pendekatan, 'tahun' => $tahun]) }}" 
                           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2.5 rounded-lg flex items-center gap-2 transition font-medium">
                            <i data-lucide="download" class="w-5 h-5"></i>
                            <span>Download Template</span>
                        </a>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6 flex justify-end gap-3">
                    <a href="{{ route('fenomena.index', ['mode' => $mode, 'tahun' => $tahun, 'pendekatan' => $pendekatan, 'id_wilayah' => $id_wilayah]) }}" 
                       class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2.5 rounded-lg font-medium transition">
                        Kembali
                    </a>
                    <button type="button" disabled class="bg-gray-400 cursor-not-allowed text-white px-6 py-2.5 rounded-lg font-medium">
                        <i data-lucide="lock" class="w-5 h-5 inline mr-2"></i>
                        Upload Terkunci
                    </button>
                </div>
            </div>
        </div>
        @else
        <!-- Form upload normal jika tidak terkunci -->
        <form action="{{ route('fenomena.store') }}" method="POST" enctype="multipart/form-data" id="upload-form">
            @csrf
            <input type="hidden" name="action" value="excel">
            <input type="hidden" name="mode" value="{{ $mode }}">
            <input type="hidden" name="tahun" value="{{ $tahun }}">
            <input type="hidden" name="pendekatan" value="{{ $pendekatan }}">
            <input type="hidden" name="id_wilayah" value="{{ $id_wilayah }}">
            
            <div class="space-y-6">
                <!-- Informasi -->
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i data-lucide="info" class="h-5 w-5 text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                <strong>Informasi Upload:</strong><br>
                                Mode: {{ ucfirst($mode) }}<br>
                                Tahun: {{ $tahun }}<br>
                                Pendekatan: {{ str_replace('_', ' ', $pendekatan) }}<br>
                                @if($id_wilayah)
                                    Wilayah ID: {{ $id_wilayah }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Pilih File -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">File Excel</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-400 transition-colors">
                        <div class="space-y-1 text-center">
                            <i data-lucide="upload" class="mx-auto h-12 w-12 text-gray-400"></i>
                            <div class="flex text-sm text-gray-600">
                                <label for="excel_file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                    <span>Upload file Excel</span>
                                    <input id="excel_file" name="excel_file" type="file" class="sr-only" accept=".xlsx,.xls" required>
                                </label>
                                <p class="pl-1">atau drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">XLSX atau XLS (max 5MB)</p>
                            <p id="file-name" class="text-sm text-gray-600 hidden"></p>
                        </div>
                    </div>
                    @error('excel_file')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tombol Download Template -->
                <div class="border-t border-gray-200 pt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Belum punya template?</h3>
                            <p class="text-sm text-gray-500">Download template Excel terlebih dahulu, isi data, lalu upload kembali.</p>
                        </div>
                        <a href="{{ route('fenomena.download-template', ['mode' => $mode, 'pendekatan' => $pendekatan, 'tahun' => $tahun]) }}" 
                           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2.5 rounded-lg flex items-center gap-2 transition font-medium">
                            <i data-lucide="download" class="w-5 h-5"></i>
                            <span>Download Template</span>
                        </a>
                    </div>
                </div>

                <!-- Tombol Submit -->
                <div class="border-t border-gray-200 pt-6 flex justify-end gap-3">
                    <a href="{{ route('fenomena.index', ['mode' => $mode, 'tahun' => $tahun, 'pendekatan' => $pendekatan, 'id_wilayah' => $id_wilayah]) }}" 
                       class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2.5 rounded-lg font-medium transition">
                        Batal
                    </a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg font-medium flex items-center gap-2 shadow-md hover:shadow-lg transition-all" id="submit-btn">
                        <i data-lucide="upload" class="w-5 h-5"></i>
                        <span>Upload dan Import</span>
                    </button>
                </div>
            </div>
        </form>
        @endif
    </div>

    <!-- Petunjuk -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i data-lucide="help-circle" class="w-5 h-5 text-blue-500"></i>
            Petunjuk Pengisian Template
        </h3>
        <div class="space-y-3 text-gray-600">
            @if($mode == 'tahunan')
                <div class="flex gap-3">
                    <span class="font-bold text-blue-600">1.</span>
                    <span>Download template Excel sesuai dengan mode dan pendekatan yang dipilih</span>
                </div>
                <div class="flex gap-3">
                    <span class="font-bold text-blue-600">2.</span>
                    <span>Isi kolom NILAI (%) dengan angka (gunakan koma untuk desimal, contoh: 11,08)</span>
                </div>
                <div class="flex gap-3">
                    <span class="font-bold text-blue-600">3.</span>
                    <span>Isi kolom FENOMENA dengan deskripsi singkat</span>
                </div>
                <div class="flex gap-3">
                    <span class="font-bold text-blue-600">4.</span>
                    <span>Isi kolom RATING dengan angka 1-5 (1=Sangat Rendah, 5=Sangat Tinggi)</span>
                </div>
                <div class="flex gap-3">
                    <span class="font-bold text-blue-600">5.</span>
                    <span>Jangan mengubah struktur tabel atau menghapus baris yang ada</span>
                </div>
            @else
                <div class="flex gap-3">
                    <span class="font-bold text-blue-600">1.</span>
                    <span>Download template Excel sesuai dengan mode dan pendekatan yang dipilih</span>
                </div>
                <div class="flex gap-3">
                    <span class="font-bold text-blue-600">2.</span>
                    <span>Setiap kategori/subkategori memiliki 2 baris: Q-to-Q dan Y-on-Y</span>
                </div>
                <div class="flex gap-3">
                    <span class="font-bold text-blue-600">3.</span>
                    <span>Isi kolom NILAI (%) untuk setiap triwulan dengan angka (contoh: 11,08)</span>
                </div>
                <div class="flex gap-3">
                    <span class="font-bold text-blue-600">4.</span>
                    <span>Isi kolom FENOMENA dan RATING sesuai kebutuhan</span>
                </div>
            @endif
            <div class="mt-4 p-3 bg-yellow-50 rounded-lg">
                <p class="text-sm text-yellow-800">
                    <i data-lucide="alert-triangle" class="w-4 h-4 inline mr-1"></i>
                    <strong>Perhatian:</strong> Pastikan file yang diupload sesuai dengan mode dan tahun yang dipilih. Data yang sudah ada akan diperbarui.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Kunci Upload -->
<div id="lock-upload-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4">
        <div class="p-6">
            <div class="flex items-center justify-center mb-4">
                <div id="modal-icon" class="w-16 h-16 rounded-full flex items-center justify-center">
                    <i data-lucide="lock" class="w-8 h-8 text-yellow-600"></i>
                </div>
            </div>
            <h3 id="modal-title" class="text-xl font-semibold text-gray-900 text-center mb-2">Konfirmasi Kunci Upload</h3>
            <p id="modal-message" class="text-gray-600 text-center mb-6">
                Apakah Anda yakin ingin mengunci upload untuk SEMUA wilayah? Setelah dikunci, semua kabupaten/kota tidak dapat melakukan upload data.
            </p>
            <div class="flex gap-3">
                <button id="modal-cancel" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-medium transition">
                    Batal
                </button>
                <button id="modal-confirm" class="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg font-medium transition">
                    Ya, Kunci
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
    
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    
    .border-dashed {
        border-style: dashed;
    }
    
    .hover\:border-blue-400:hover {
        border-color: #60a5fa;
    }
    
    .spinner {
        border: 2px solid #f3f3f3;
        border-top: 2px solid #ffffff;
        border-radius: 50%;
        width: 16px;
        height: 16px;
        animation: spin 1s linear infinite;
        display: inline-block;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
        
        const fileInput = document.getElementById('excel_file');
        const fileName = document.getElementById('file-name');
        const submitBtn = document.getElementById('submit-btn');
        const form = document.getElementById('upload-form');
        
        // Handle file selection
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    fileName.textContent = `File terpilih: ${file.name}`;
                    fileName.classList.remove('hidden');
                    
                    if (file.size > 5 * 1024 * 1024) {
                        alert('Ukuran file terlalu besar. Maksimal 5MB.');
                        this.value = '';
                        fileName.classList.add('hidden');
                    }
                    
                    const ext = file.name.split('.').pop().toLowerCase();
                    if (ext !== 'xlsx' && ext !== 'xls') {
                        alert('Format file harus .xlsx atau .xls');
                        this.value = '';
                        fileName.classList.add('hidden');
                    }
                } else {
                    fileName.classList.add('hidden');
                }
            });
        }
        
        // Handle form submit
        if (form) {
            form.addEventListener('submit', function(e) {
                const file = fileInput ? fileInput.files[0] : null;
                if (!file) {
                    e.preventDefault();
                    alert('Silakan pilih file Excel terlebih dahulu');
                    return;
                }
                
                if (submitBtn) {
                    submitBtn.disabled = true;
                    const originalHtml = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<span class="spinner mr-2"></span> Mengupload...';
                    
                    setTimeout(() => {
                        if (submitBtn.disabled) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalHtml;
                        }
                    }, 30000);
                }
            });
        }
        
        // Drag and drop functionality
        const dropZone = document.querySelector('.border-dashed');
        if (dropZone) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight(e) {
                dropZone.classList.add('border-blue-500', 'bg-blue-50');
            }
            
            function unhighlight(e) {
                dropZone.classList.remove('border-blue-500', 'bg-blue-50');
            }
            
            dropZone.addEventListener('drop', function(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                
                if (files.length > 0 && fileInput) {
                    fileInput.files = files;
                    const event = new Event('change');
                    fileInput.dispatchEvent(event);
                }
            });
        }
        
        // Lock upload button handler (untuk semua wilayah)
        const lockBtn = document.getElementById('lock-upload-btn');
        const modal = document.getElementById('lock-upload-modal');
        const modalTitle = document.getElementById('modal-title');
        const modalMessage = document.getElementById('modal-message');
        const modalIcon = document.getElementById('modal-icon');
        const modalConfirm = document.getElementById('modal-confirm');
        const modalCancel = document.getElementById('modal-cancel');
        
        let currentAction = null;
        let currentData = null;
        
        if (lockBtn) {
            lockBtn.addEventListener('click', function() {
                const isLocked = this.dataset.locked === 'true';
                const tahun = this.dataset.tahun;
                const pendekatan = this.dataset.pendekatan;
                const mode = this.dataset.mode;
                
                currentData = {
                    tahun: tahun,
                    pendekatan: pendekatan,
                    mode: mode,
                    _token: '{{ csrf_token() }}'
                };
                
                if (isLocked) {
                    currentAction = 'unlock';
                    modalTitle.textContent = 'Konfirmasi Buka Kunci Upload';
                    modalMessage.textContent = 'Apakah Anda yakin ingin membuka kunci upload untuk SEMUA wilayah? Kabupaten/kota akan dapat kembali mengupload data.';
                    modalConfirm.textContent = 'Ya, Buka Kunci';
                    modalConfirm.className = 'flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition';
                    const iconElement = modalIcon.querySelector('i');
                    if (iconElement) {
                        iconElement.setAttribute('data-lucide', 'unlock');
                    }
                } else {
                    currentAction = 'lock';
                    modalTitle.textContent = 'Konfirmasi Kunci Upload';
                    modalMessage.textContent = 'Apakah Anda yakin ingin mengunci upload untuk SEMUA wilayah? Setelah dikunci, semua kabupaten/kota tidak dapat melakukan upload data.';
                    modalConfirm.textContent = 'Ya, Kunci Upload';
                    modalConfirm.className = 'flex-1 bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg font-medium transition';
                    const iconElement = modalIcon.querySelector('i');
                    if (iconElement) {
                        iconElement.setAttribute('data-lucide', 'lock');
                    }
                }
                
                lucide.createIcons();
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });
        }
        
        if (modalCancel) {
            modalCancel.addEventListener('click', function() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });
        }
        
        if (modalConfirm) {
            modalConfirm.addEventListener('click', function() {
                if (!currentData) return;
                
                modalConfirm.disabled = true;
                modalConfirm.innerHTML = '<span class="spinner mr-2"></span> Memproses...';
                
                fetch('{{ route("fenomena.toggle-upload-lock") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(currentData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Gagal: ' + data.message);
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan. Silakan coba lagi.');
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                })
                .finally(() => {
                    modalConfirm.disabled = false;
                    modalConfirm.textContent = currentAction === 'lock' ? 'Ya, Kunci Upload' : 'Ya, Buka Kunci';
                });
            });
        }
        
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            });
        }
    });
</script>
@endsection