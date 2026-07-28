<style>
/* Clean Freeze Table Pattern - Pure CSS Sticky Approach */
.sticky-table-container {
    position: relative;
    max-height: 100%;
    height: 100%;
    overflow: auto;
    width: 100%;
    min-height: 0;
}

.sticky-table {
    border-collapse: separate;
    border-spacing: 0;
    width: max-content;
    min-width: 100%;
    --sticky-header-height: 0px;
}

/* Force horizontal scroll for q-to-q */
.sticky-table.force-scroll-x th,
.sticky-table.force-scroll-x td {
    min-width: 110px;
}

.sticky-table.force-scroll-x .sticky-col-1,
.sticky-table.force-scroll-x .sticky-col-2 {
    min-width: 120px;
}

/* Z-Index Layering:
   z-40 = Corner (header + column)
   z-30 = Headers
   z-20 = Left columns
   z-0  = Normal cells
*/

/* Freeze Headers - All header cells sticky */
.sticky-table thead th {
    position: sticky;
    top: 0;
    z-index: 30;
    background-color: #f3f4f6;
    border: 1px solid #d1d5db;
}

/* Second header row positioned below first */
.sticky-table thead tr:nth-child(2) th {
    top: var(--sticky-header-height, 0px);
}

/* Freeze Left Columns */
.sticky-col-1 {
    position: sticky !important;
    left: 0 !important;
    z-index: 20 !important;
    background-color: white;
    border: 1px solid #d1d5db;
}

.sticky-col-2 {
    position: sticky !important;
    left: 120px !important; /* Width of first column */
    z-index: 20 !important;
    background-color: white;
    border: 1px solid #d1d5db;
}

/* Corner Cells (Header + Left Column intersection) */
.sticky-table thead th.sticky-col-1,
.sticky-table thead th.sticky-col-2 {
    z-index: 40 !important; /* Highest priority */
    background-color: #f3f4f6;
}

/* Nowrap for better readability */
.sticky-col-1, .sticky-col-2 {
    white-space: nowrap;
    min-width: 120px;
}

/* Kategori row styling */
.sticky-table tbody tr.kategori-row td {
    background-color: #f9fafb;
    font-weight: 600;
}

.sticky-table tbody tr.kategori-row td.sticky-col-1,
.sticky-table tbody tr.kategori-row td.sticky-col-2 {
    background-color: #f9fafb;
}

/* Row Hover Effects */
.sticky-table tbody tr {
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.sticky-table tbody tr:hover td {
    background-color: #fef3c7 !important;
}

.sticky-table tbody tr:hover td.sticky-col-1,
.sticky-table tbody tr:hover td.sticky-col-2 {
    font-weight: 600;
}

/* Alternating Rows */
.sticky-table tbody tr:nth-child(even):not(:hover) {
    background-color: #fafafa;
}

.sticky-table tbody tr:nth-child(even):not(:hover) td.sticky-col-1,
.sticky-table tbody tr:nth-child(even):not(:hover) td.sticky-col-2 {
    background-color: #f5f5f5;
}

/* Kategori Row Hover */
.sticky-table tbody tr.kategori-row:hover td {
    background-color: #dbeafe !important;
}

/* Responsive */
@media (max-width: 768px) {
    .sticky-table thead tr:nth-child(2) th {
        top: var(--sticky-header-height, 0px);
    }
    
    .sticky-col-1, .sticky-col-2 {
        min-width: 100px;
    }
    
    .sticky-col-2 {
        left: 100px !important;
    }
}
</style></style>

<script>
// Fungsi untuk modal rentang tahun
document.addEventListener('DOMContentLoaded', function() {
    const syncStickyHeaderOffsets = () => {
        document.querySelectorAll('.sticky-table').forEach((table) => {
            const firstHeaderRow = table.querySelector('thead tr:first-child');
            if (!firstHeaderRow) return;
            const height = firstHeaderRow.getBoundingClientRect().height;
            table.style.setProperty('--sticky-header-height', `${height}px`);
        });
    };

    syncStickyHeaderOffsets();
    window.addEventListener('resize', syncStickyHeaderOffsets);

    // Set nilai tipe PDRB dari URL
    const tipeSelect = document.getElementById('tipe-pdrb-select');
    const urlParams = new URLSearchParams(window.location.search);
    const tipeFromUrl = urlParams.get('tipe_pdrb');
    
    if (tipeFromUrl) {
        tipeSelect.value = tipeFromUrl;
    } else {
        tipeSelect.value = 'berlaku';
    }
    
    // Set nilai rentang tahun jika ada di URL
    const rentangTahun = urlParams.get('rentang_tahun');
    if (rentangTahun) {
        const [tahunAwal, tahunAkhir] = rentangTahun.split('-');
        const tahunAwalSelect = document.getElementById('tahun_awal');
        const tahunAkhirSelect = document.getElementById('tahun_akhir');
        
        if (tahunAwalSelect) tahunAwalSelect.value = tahunAwal;
        if (tahunAkhirSelect) tahunAkhirSelect.value = tahunAkhir;
        
        // Kosongkan select tahun tunggal
        document.getElementById('id_tahun_select').value = '';
    }
    
    // Event listeners untuk modal rentang tahun
    const rentangTahunBtn = document.getElementById('rentangTahunBtn');
    const cancelRentangBtn = document.getElementById('cancelRentangBtn');
    const applyRentangBtn = document.getElementById('applyRentangBtn');
    const clearRentangBtn = document.getElementById('clearRentangBtn');
    const modal = document.getElementById('rentangTahunModal');
    
    if (rentangTahunBtn) {
        rentangTahunBtn.addEventListener('click', function() {
            modal.classList.remove('hidden');
        });
    }
    
    if (cancelRentangBtn) {
        cancelRentangBtn.addEventListener('click', function() {
            modal.classList.add('hidden');
        });
    }
    
    if (applyRentangBtn) {
        applyRentangBtn.addEventListener('click', function() {
            const tahunAwal = document.getElementById('tahun_awal').value;
            const tahunAkhir = document.getElementById('tahun_akhir').value;
            
            if (!tahunAwal || !tahunAkhir) {
                alert('Silakan pilih tahun awal dan tahun akhir');
                return;
            }
            
            if (parseInt(tahunAwal) > parseInt(tahunAkhir)) {
                alert('Tahun awal tidak boleh lebih besar dari tahun akhir');
                return;
            }
            
            // Set nilai input hidden untuk rentang tahun
            document.getElementById('rentang_tahun_input').value = `${tahunAwal}-${tahunAkhir}`;
            
            // Kosongkan select tahun tunggal
            document.getElementById('id_tahun_select').value = '';
            
            // Submit form
            document.querySelector('form[method="GET"]').submit();
        });
    }
    
    if (clearRentangBtn) {
        clearRentangBtn.addEventListener('click', function() {
            // Hapus nilai rentang tahun dari URL dan submit form
            const url = new URL(window.location.href);
            url.searchParams.delete('rentang_tahun');
            window.location.href = url.toString();
        });
    }
    
    // Close modal ketika klik di luar modal
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
});
</script>
