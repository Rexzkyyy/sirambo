{{-- resources/views/rekon_p1/partials/detail/scripts.blade.php --}}
@php
    // Definisikan semua variabel Blade di bagian atas
    $csrfToken = csrf_token();
    $updateAdjUrl = route('rekon_p1.update_adj');
    $recalculateUrl = route('rekon_p1.recalculate');
    $historyUrl = route('rekon_p1.history', ['id' => '__ID__']);
    $pollUrl = route('rekon_p1.poll');
    $lockUrl = route('rekon_p1.lock');
    $pollInterval = 1000;


@endphp

<script>
    // Variable global dari Blade
    window.CSRF_TOKEN = "{{ $csrfToken }}";
    window.REKON_UPDATE_ADJ_URL = "{{ $updateAdjUrl }}";
    window.REKON_RECALCULATE_URL = "{{ $recalculateUrl }}";
    window.REKON_HISTORY_URL = "{{ $historyUrl }}";
    window.REKON_POLL_URL = "{{ $pollUrl }}";
    window.REKON_LOCK_URL = "{{ $lockUrl }}";
    window.REKON_POLL_INTERVAL = {{ $pollInterval }};
    window.REKON_TAHUN_ORDER = @json($tahunIdsDisplay ?? []);
    window.REKON_PERIODE_ORDER = @json(($periodeTriwulan ?? collect())->pluck('id_periode')->values()->all());
    window.REKON_PENDEKATAN = "{{ $pendekatan ?? 'lapangan_usaha' }}";
    window.REKON_CURRENT_YEAR_ID = {{ isset($currentYearId) && $currentYearId ? (int) $currentYearId : 'null' }};
    window.REKON_RUNNING_QUARTER = {{ isset($quarterNow) ? (int) $quarterNow : 0 }};
    window.REKON_YOY_TOTAL_KAB_ALERT_ENABLED = {{ !empty($isYoyTotalKabHighlightEnabled) ? 'true' : 'false' }};
    window.REKON_KAB_GROWTH_ALERT_ENABLED = {{ !empty($isKabGrowthAlertEnabled) ? 'true' : 'false' }};
    window.REKON_USER_ID = {{ auth()->id() ?? 'null' }};
    window.ROLE_IS_KAB_KOTA = {{ in_array(auth()->user()->role, ['kabupaten', 'kota']) ? 'true' : 'false' }};
    window.REKON_IS_RECORD_LOCKED = {{ !empty($isLocked) ? 'true' : 'false' }};
    window.REKON_DETAIL = {
        type: "{{ $isKategori ? 'kategori' : 'subkategori' }}",
        id: {{ $isKategori ? ($subKategori->kategori_id ?? 'null') : ($subKategori->id_sub_kategori ?? 'null') }},
        pendekatan: "{{ $pendekatan ?? 'lapangan_usaha' }}"
    };

    // Performance Optimization: Persistent Formatters
    const numberFormatter = new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    const percentFormatter = new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    // Fungsi untuk menentukan kategori perbedaan
    function getDiffCategory(baseValue, adjValue) {
        const base = parseFloat(baseValue || 0);
        const adj = parseFloat(adjValue || 0);

        if (base === 0 || adj === 0) return '';

        const diff = Math.abs(adj - base);
        const bedaArah = ((base > 0 && adj < 0) || (base < 0 && adj > 0));
        const threshold = (window.REKON_PENDEKATAN === 'pengeluaran') ? 4 : 5;

        if (diff >= threshold && bedaArah) return 'extreme_beda_arah';
        if (diff >= threshold) return 'extreme';
        if (bedaArah) return 'beda_arah';

        return '';
    }

    // Fungsi untuk mendapatkan background berdasarkan kategori
    function getBgByCategory(category) {
        if (!category) return '';

        if (category.includes('extreme')) {
            return 'bg-red-strong';
        }
        if (category.includes('beda_arah')) {
            return 'bg-yellow-strong';
        }

        return '';
    }

    // Fungsi untuk mendapatkan label status
    function getStatusLabel(category) {
        if (category === 'extreme_beda_arah') return 'Extreme Beda Arah';
        if (category === 'extreme') return 'Extreme';
        if (category === 'beda_arah') return 'Beda Arah';
        return '';
    }

    // Fungsi untuk merender popup status
    function renderStatusPopup(status) {
        return '';
    }

    function shouldApplyTotalKabGrowthAlert() {
        if (!window.REKON_YOY_TOTAL_KAB_ALERT_ENABLED) return false;
        if (window.REKON_PENDEKATAN !== 'pengeluaran') return false;
        return true;
    }

    function shouldApplyKabGrowthAlert() {
        if (!window.REKON_KAB_GROWTH_ALERT_ENABLED) return false;
        if (window.REKON_PENDEKATAN !== 'pengeluaran') return false;
        return true;
    }

    function isOppositeDirection(a, b) {
        return (a > 0 && b < 0) || (a < 0 && b > 0);
    }

    function parseGrowthNumber(val) {
        if (val === null || val === undefined) return NaN;
        let str = String(val).trim();
        if (str === '') return NaN;

        if (str.includes('.') && str.includes(',')) {
            str = str.replace(/\./g, '').replace(',', '.');
        } else if (str.includes(',')) {
            str = str.replace(',', '.');
        }

        str = str.replace(/[^0-9.-]/g, '');
        if (str === '' || str === '-' || str === '.') return NaN;

        const num = parseFloat(str);
        return Number.isFinite(num) ? num : NaN;
    }

    function getCellGrowthValue(cell) {
        if (!cell) return NaN;
        const raw = cell.dataset ? cell.dataset.value : null;
        let num = parseGrowthNumber(raw);
        if (Number.isFinite(num)) return num;
        const text = cell.textContent || '';
        num = parseGrowthNumber(text);
        return num;
    }

    function applyKabGrowthAlertCell(cell, provCell, origCell = null) {
        if (!cell) return;

        const clearAlert = () => {
            if (cell.classList.contains('kab-growth-alert')) {
                cell.classList.remove('kab-growth-alert', 'bg-red-strong', 'bg-orange-strong', 'bg-yellow-strong');
                if (cell.dataset.kabAlertApplied === '1') {
                    const baseTitle = cell.dataset.kabAlertBaseTitle ?? '';
                    if (baseTitle) {
                        cell.title = baseTitle;
                    } else {
                        cell.removeAttribute('title');
                    }
                    delete cell.dataset.kabAlertApplied;
                }
            }
        };

        if (!shouldApplyKabGrowthAlert()) {
            clearAlert();
            return;
        }

        const value = getCellGrowthValue(cell);
        const prov = getCellGrowthValue(provCell);

        if (!Number.isFinite(value) || !Number.isFinite(prov)) {
            clearAlert();
            return;
        }

        const diffProv = Math.abs(value - prov);
        const isBedaArahProv = isOppositeDirection(value, prov);

        // Internal Shift Checks (Original vs Adjusted)
        let isInternalBedaArah = false;
        let isInternalExtremeGrowth = false;
        let growthDiffInternal = 0;

        if (origCell) {
            const origVal = getCellGrowthValue(origCell);
            if (Number.isFinite(origVal)) {
                isInternalBedaArah = isOppositeDirection(value, origVal);
                growthDiffInternal = Math.abs(value - origVal);
                if (growthDiffInternal > 4) isInternalExtremeGrowth = true;
            }
        }

        // Check for "Adjustment Ratio" extreme (Red Priority)
        let isExtremeAdj = false;
        let adjPercent = 0;
        const cellId = cell.id || '';
        const matchReg = cellId.match(/-kabkota-(\d+)-(\d+)-(\d+)$/);
        if (matchReg) {
            const adjInputId = `adj-${matchReg[1]}-${matchReg[2]}-${matchReg[3]}`;
            const adjInput = document.getElementById(adjInputId);
            if (adjInput) {
                const pdrb = parseFloat(adjInput.dataset.pdrb) || 0;
                const adj = parseInputNumber(adjInput.value);
                adjPercent = pdrb !== 0 ? Math.abs(adj / pdrb) * 100 : 0;
                if (adjPercent > 4) isExtremeAdj = true;
            }
        }

        const isExtremeBedaArahProv = (diffProv > 4 && isBedaArahProv);

        // RED PRIORITY 1: 
        // 1. Extreme Adjustment Ratio (>4%)
        // 2. Internal Direction Flip
        // 3. Internal Growth Shift (>4% from Original) - THIS IS THE "EXTREME" REQUESTED
        // 4. Extreme Provincial Difference (>4% AND Opposite Direction)
        const isRed = isExtremeAdj || isInternalBedaArah || isInternalExtremeGrowth || isExtremeBedaArahProv;

        if (isRed || diffProv > 4 || isBedaArahProv) {
            cell.classList.remove(
                'sign-positive', 'sign-negative',
                'bg-white', 'bg-gray-50', 'bg-yellow-50',
                'bg-green-soft', 'bg-orange-soft',
                'hover:bg-gray-50', 'hover:bg-yellow-100'
            );

            // ALWAYS remove external status badges/tooltips (black popups) 
            // to prevent overlapping with our custom growth toolkit
            const existingPopup = cell.querySelector('.status-tooltip');
            if (existingPopup) existingPopup.remove();

            if (cell.dataset.kabAlertBaseTitle === undefined) {
                cell.dataset.kabAlertBaseTitle = cell.getAttribute('title') ?? '';
            }

            // Clean the base title of any existing "Status:" labels to avoid merged garbage tooltips
            let baseTitle = (cell.dataset.kabAlertBaseTitle ?? '');
            baseTitle = baseTitle.replace(/\s*\|\s*Status:.*$/, '').replace(/^Status:.*$/, '').trim();

            let reason = '';

            if (isRed) {
                // PRIORITAS 1: MERAH (Extreme)
                // Toolkit: Extreme | Beda Arah PDRB | Extreme Adjustment (X%) | Extreme Beda Arah Provinsi
                cell.classList.remove('bg-orange-strong', 'bg-yellow-strong');
                cell.classList.add('kab-growth-alert', 'bg-red-strong');

                const reasons = [];
                // We always lead with "Extreme" if any extreme condition is met
                reasons.push('Extreme');
                if (isInternalBedaArah) reasons.push('Beda Arah PDRB');
                if (isInternalExtremeGrowth) reasons.push(`Extreme Pertumbuhan (${growthDiffInternal.toFixed(2)}% vs Orig)`);
                if (isExtremeAdj) reasons.push(`Extreme Adjustment (${adjPercent.toFixed(2)}%)`);
                if (isExtremeBedaArahProv) reasons.push('Extreme Beda Arah Provinsi');

                // Unique reasons only
                reason = [...new Set(reasons)].join(' | ');
            } else if (diffProv > 4) {
                // PRIORITAS 2: ORANGE (+4% Provinsi)
                cell.classList.remove('bg-red-strong', 'bg-yellow-strong');
                cell.classList.add('kab-growth-alert', 'bg-orange-strong');
                reason = '+4% Provinsi';
            } else if (isBedaArahProv) {
                // PRIORITAS 3: KUNING (Beda Arah dari Provinsi)
                cell.classList.remove('bg-red-strong', 'bg-orange-strong');
                cell.classList.add('kab-growth-alert', 'bg-yellow-strong');
                reason = 'Beda Arah dari Provinsi';
            }

            cell.removeAttribute('title');
            cell.dataset.kabAlertApplied = '1';
            return;
        }

        clearAlert();
    }

    function applyKabkotaGrowthAlerts() {
        if (!shouldApplyKabGrowthAlert()) {
            document.querySelectorAll('.kab-growth-alert').forEach(cell => {
                cell.classList.remove('kab-growth-alert', 'bg-red-strong', 'bg-orange-strong');
            });
            return;
        }

        const defs = [
            {
                selector: '[id^="qoq-konstan-kabkota-"]',
                regex: /^qoq-konstan-kabkota-(\d+)-(\d+)-(\d+)$/,
                prov: m => `qoq-konstan-provinsi-${m[2]}-${m[3]}`
            },
            {
                selector: '[id^="qoq-konstan-plus-adj-kabkota-"]',
                regex: /^qoq-konstan-plus-adj-kabkota-(\d+)-(\d+)-(\d+)$/,
                prov: m => `qoq-konstan-plus-adj-provinsi-${m[2]}-${m[3]}`,
                orig: m => `qoq-konstan-kabkota-${m[1]}-${m[2]}-${m[3]}`
            },
            {
                selector: '[id^="yoy-konstan-kabkota-"]',
                regex: /^yoy-konstan-kabkota-(\d+)-(\d+)-(\d+)$/,
                prov: m => `yoy-konstan-provinsi-${m[2]}-${m[3]}`
            },
            {
                selector: '[id^="yoy-konstan-plus-adj-kabkota-"]',
                regex: /^yoy-konstan-plus-adj-kabkota-(\d+)-(\d+)-(\d+)$/,
                prov: m => `yoy-konstan-plus-adj-provinsi-${m[2]}-${m[3]}`,
                orig: m => `yoy-konstan-kabkota-${m[1]}-${m[2]}-${m[3]}`
            },
            {
                selector: '[id^="ctoc-konstan-kabkota-"]',
                regex: /^ctoc-konstan-kabkota-(\d+)-(\d+)-(\d+)$/,
                prov: m => `ctoc-konstan-provinsi-${m[2]}-${m[3]}`
            },
            {
                selector: '[id^="ctoc-konstan-plus-adj-kabkota-"]',
                regex: /^ctoc-konstan-plus-adj-kabkota-(\d+)-(\d+)-(\d+)$/,
                prov: m => `ctoc-konstan-plus-adj-provinsi-${m[2]}-${m[3]}`,
                orig: m => `ctoc-konstan-kabkota-${m[1]}-${m[2]}-${m[3]}`
            },
            {
                selector: '[id^="laju-berlaku-kabkota-"]',
                regex: /^laju-berlaku-kabkota-(\d+)-(\d+)-(\d+)$/,
                prov: m => `laju-berlaku-provinsi-${m[2]}-${m[3]}`
            },
            {
                selector: '[id^="laju-berlaku-plus-adj-kabkota-"]',
                regex: /^laju-berlaku-plus-adj-kabkota-(\d+)-(\d+)-(\d+)$/,
                prov: m => `laju-berlaku-plus-adj-provinsi-${m[2]}-${m[3]}`,
                orig: m => `laju-berlaku-kabkota-${m[1]}-${m[2]}-${m[3]}`
            },
            {
                selector: '[id^="qoq-konstan-kabkota-total-"]',
                regex: /^qoq-konstan-kabkota-total-(\d+)-(\d+)$/,
                prov: m => `provinsi-total-qoq-konstan-${m[2]}`
            },
            {
                selector: '[id^="qoq-konstan-plus-adj-kabkota-total-"]',
                regex: /^qoq-konstan-plus-adj-kabkota-total-(\d+)-(\d+)$/,
                prov: m => `provinsi-total-qoq-konstan-plus-adj-${m[2]}`,
                orig: m => `qoq-konstan-kabkota-total-${m[1]}-${m[2]}`
            },
            {
                selector: '[id^="yoy-konstan-kabkota-total-"]',
                regex: /^yoy-konstan-kabkota-total-(\d+)-(\d+)$/,
                prov: m => `provinsi-total-yoy-konstan-${m[2]}`
            },
            {
                selector: '[id^="yoy-konstan-plus-adj-kabkota-total-"]',
                regex: /^yoy-konstan-plus-adj-kabkota-total-(\d+)-(\d+)$/,
                prov: m => `provinsi-total-yoy-konstan-plus-adj-${m[2]}`,
                orig: m => `yoy-konstan-kabkota-total-${m[1]}-${m[2]}`
            },
            {
                selector: '[id^="ctoc-konstan-kabkota-total-"]',
                regex: /^ctoc-konstan-kabkota-total-(\d+)-(\d+)$/,
                prov: m => `provinsi-total-ctoc-konstan-${m[2]}`
            },
            {
                selector: '[id^="ctoc-konstan-plus-adj-kabkota-total-"]',
                regex: /^ctoc-konstan-plus-adj-kabkota-total-(\d+)-(\d+)$/,
                prov: m => `provinsi-total-ctoc-konstan-plus-adj-${m[2]}`,
                orig: m => `ctoc-konstan-kabkota-total-${m[1]}-${m[2]}`
            },
            {
                selector: '[id^="laju-berlaku-kabkota-total-"]',
                regex: /^laju-berlaku-kabkota-total-(\d+)-(\d+)$/,
                prov: m => `provinsi-total-laju-berlaku-${m[2]}`
            },
            {
                selector: '[id^="laju-berlaku-plus-adj-kabkota-total-"]',
                regex: /^laju-berlaku-plus-adj-kabkota-total-(\d+)-(\d+)$/,
                prov: m => `provinsi-total-laju-berlaku-plus-adj-${m[2]}`,
                orig: m => `laju-berlaku-kabkota-total-${m[1]}-${m[2]}`
            }
        ];

        defs.forEach(def => {
            document.querySelectorAll(def.selector).forEach(cell => {
                const match = cell.id.match(def.regex);
                if (!match) return;
                const provId = def.prov(match);
                const provCell = document.getElementById(provId);
                const originalCell = def.orig ? document.getElementById(def.orig(match)) : null;
                applyKabGrowthAlertCell(cell, provCell, originalCell);
            });
        });
    }

    function getPeriodeRank(periodeId) {
        const order = Array.isArray(window.REKON_PERIODE_ORDER)
            ? window.REKON_PERIODE_ORDER.map(v => parseInt(v, 10)).filter(v => !isNaN(v))
            : [];
        const idx = order.indexOf(parseInt(periodeId, 10));
        return idx >= 0 ? idx + 1 : 0;
    }

    function getRunningPeriodeId() {
        const order = Array.isArray(window.REKON_PERIODE_ORDER)
            ? window.REKON_PERIODE_ORDER.map(v => parseInt(v, 10)).filter(v => !isNaN(v))
            : [];
        const running = parseInt(window.REKON_RUNNING_QUARTER, 10);
        if (!running || running < 1) return null;
        return order[running - 1] ?? null;
    }

    function getTotalKabGrowthThreshold(metric, rank, tahunId) {
        const runningQuarter = parseInt(window.REKON_RUNNING_QUARTER, 10);
        const runningYearId = parseInt(window.REKON_CURRENT_YEAR_ID, 10);
        const yearId = parseInt(tahunId, 10);

        if (metric === 'yoy') {
            return 1.0;
        }
        if (metric === 'qoq' || metric === 'ctoc' || metric === 'laju') {
            return 1.0;
        }
        return null;
    }

    function applyTotalKabGrowthAlertCell(cell, tahunId, periodeId, metric, provCell) {
        if (!cell) return;

        const runningQuarter = parseInt(window.REKON_RUNNING_QUARTER, 10);
        const rank = getPeriodeRank(periodeId);

        const clearAlert = () => {
            const hadAlert = cell.classList.contains('bg-red-strong') || cell.classList.contains('bg-orange-strong');
            cell.classList.remove('bg-red-strong', 'bg-orange-strong');
            if (hadAlert) {
                applySignRowColors(cell.closest('tr') ?? document);
            }
        };

        if (!shouldApplyTotalKabGrowthAlert()) {
            clearAlert();
            return;
        }

        const value = parseGrowthNumber(cell.dataset.value);
        const prov = provCell ? getCellGrowthValue(provCell) : NaN;

        if (!Number.isFinite(value) || !Number.isFinite(prov)) {
            clearAlert();
            return;
        }

        const absVal = Math.abs(value - prov);

        if (absVal <= 0.001) {
            clearAlert();
            return;
        }

        const threshold = getTotalKabGrowthThreshold(metric, rank, tahunId);
        if (threshold === null) {
            clearAlert();
            return;
        }

        if (absVal > threshold) {
            cell.classList.remove(
                'sign-positive', 'sign-negative',
                'bg-white', 'bg-gray-50', 'bg-yellow-50',
                'bg-green-soft', 'bg-orange-soft',
                'hover:bg-gray-50', 'hover:bg-yellow-100'
            );
            cell.classList.add('bg-red-strong');
            const reason = `Extreme | ${metric.toUpperCase()} melebihi batas (${threshold}%)`;
            if (cell.dataset.signBaseTitle === undefined) {
                cell.dataset.signBaseTitle = cell.getAttribute('title') ?? '';
            }
            const baseTitle = cell.dataset.signBaseTitle ?? '';
            cell.removeAttribute('title');

            return;
        }

        clearAlert();
    }

    let kabGrowthAlertTimeout = null;
    function debounceApplyAllTotalKabGrowthAlerts() {
        if (kabGrowthAlertTimeout) clearTimeout(kabGrowthAlertTimeout);
        kabGrowthAlertTimeout = setTimeout(applyAllTotalKabGrowthAlerts, 150);
    }

    function applyAllTotalKabGrowthAlerts() {
        const defs = [
            { metric: 'qoq', regex: /^qoq-konstan-total-(\d+)-(\d+)$/, selector: '[id^="qoq-konstan-total-"]', prov: m => `qoq-konstan-provinsi-${m[1]}-${m[2]}` },
            { metric: 'qoq', regex: /^qoq-konstan-plus-adj-total-(\d+)-(\d+)$/, selector: '[id^="qoq-konstan-plus-adj-total-"]', prov: m => `qoq-konstan-plus-adj-provinsi-${m[1]}-${m[2]}` },
            { metric: 'yoy', regex: /^yoy-konstan-total-(\d+)-(\d+)$/, selector: '[id^="yoy-konstan-total-"]', prov: m => `yoy-konstan-provinsi-${m[1]}-${m[2]}` },
            { metric: 'yoy', regex: /^yoy-konstan-plus-adj-total-(\d+)-(\d+)$/, selector: '[id^="yoy-konstan-plus-adj-total-"]', prov: m => `yoy-konstan-plus-adj-provinsi-${m[1]}-${m[2]}` },
            { metric: 'ctoc', regex: /^ctoc-konstan-total-(\d+)-(\d+)$/, selector: '[id^="ctoc-konstan-total-"]', prov: m => `ctoc-konstan-provinsi-${m[1]}-${m[2]}` },
            { metric: 'ctoc', regex: /^ctoc-konstan-plus-adj-total-(\d+)-(\d+)$/, selector: '[id^="ctoc-konstan-plus-adj-total-"]', prov: m => `ctoc-konstan-plus-adj-provinsi-${m[1]}-${m[2]}` },
            { metric: 'laju', regex: /^laju-berlaku-total-(\d+)-(\d+)$/, selector: '[id^="laju-berlaku-total-"]', prov: m => `laju-berlaku-provinsi-${m[1]}-${m[2]}` },
            { metric: 'laju', regex: /^laju-berlaku-plus-adj-total-(\d+)-(\d+)$/, selector: '[id^="laju-berlaku-plus-adj-total-"]', prov: m => `laju-berlaku-plus-adj-provinsi-${m[1]}-${m[2]}` },
            { metric: 'qoq', regex: /^qoq-konstan-plus-adj-total-kab-total-(\d+)$/, selector: '[id^="qoq-konstan-plus-adj-total-kab-total-"]', totalKab: true, prov: m => `provinsi-total-qoq-konstan-plus-adj-${m[1]}` },
            { metric: 'yoy', regex: /^yoy-konstan-plus-adj-total-kab-total-(\d+)$/, selector: '[id^="yoy-konstan-plus-adj-total-kab-total-"]', totalKab: true, prov: m => `provinsi-total-yoy-konstan-plus-adj-${m[1]}` },
            { metric: 'ctoc', regex: /^ctoc-konstan-plus-adj-total-kab-total-(\d+)$/, selector: '[id^="ctoc-konstan-plus-adj-total-kab-total-"]', totalKab: true, prov: m => `provinsi-total-ctoc-konstan-plus-adj-${m[1]}` },
            { metric: 'laju', regex: /^laju-berlaku-plus-adj-total-kab-total-(\d+)$/, selector: '[id^="laju-berlaku-plus-adj-total-kab-total-"]', totalKab: true, prov: m => `provinsi-total-laju-berlaku-plus-adj-${m[1]}` }
        ];

        defs.forEach(def => {
            document.querySelectorAll(def.selector).forEach(cell => {
                const match = cell.id.match(def.regex);
                if (!match || !match[1]) return;
                const provCell = def.prov ? document.getElementById(def.prov(match)) : null;
                if (def.totalKab) {
                    const runningPeriodeId = getRunningPeriodeId();
                    if (!runningPeriodeId) return;
                    applyTotalKabGrowthAlertCell(cell, parseInt(match[1], 10), runningPeriodeId, def.metric, provCell);
                    return;
                }
                if (!match[2]) return;
                applyTotalKabGrowthAlertCell(cell, parseInt(match[1], 10), parseInt(match[2], 10), def.metric, provCell);
            });
        });

        applyKabkotaGrowthAlerts();

        // Hapus paksa semua title/tooltip bawaan di status-cell
        document.querySelectorAll('.status-cell').forEach(cell => {
            cell.removeAttribute('title');
        });
    }

    function resetTotalKabStatusVisuals() {
        const selectors = [
            '[id^="qoq-konstan-total-"]',
            '[id^="qoq-konstan-plus-adj-total-"]',
            '[id^="yoy-konstan-total-"]',
            '[id^="yoy-konstan-plus-adj-total-"]',
            '[id^="ctoc-konstan-total-"]',
            '[id^="ctoc-konstan-plus-adj-total-"]',
            '[id^="laju-berlaku-total-"]',
            '[id^="laju-berlaku-plus-adj-total-"]',
            '[id^="indeks-berlaku-plus-adj-total-"]'
        ];

        selectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(cell => {
                const oldTooltip = cell.querySelector('.status-tooltip');
                if (oldTooltip) oldTooltip.remove();
                cell.classList.remove('bg-red-strong', 'bg-yellow-strong', 'bg-orange-strong');
                cell.title = '';
            });
        });

        applySignRowColors();
    }

    // Fungsi untuk memformat angka (Optimized)
    function formatNumber(num, isPercent = false) {
        if (num === null || num === undefined || isNaN(num)) return '-';

        const formatted = numberFormatter.format(num);
        return isPercent ? formatted + '%' : formatted;
    }

    // Fungsi untuk menampilkan notifikasi
    function showNotification(message, type = 'info') {
        const notificationArea = document.getElementById('notification-area');
        if (!notificationArea) return;

        // Hapus notifikasi realtime sebelumnya
        notificationArea.querySelectorAll('.realtime-notification')
            .forEach(el => el.remove());

        const alertClass = type === 'success'
            ? 'bg-green-50 border-green-200 text-green-800'
            : type === 'error'
                ? 'bg-red-50 border-red-200 text-red-800'
                : type === 'warning'
                    ? 'bg-yellow-50 border-yellow-200 text-yellow-800'
                    : 'bg-blue-50 border-blue-200 text-blue-800';

        const icon = type === 'success'
            ? 'OK'
            : type === 'error'
                ? 'X'
                : type === 'warning'
                    ? '!'
                    : 'i';

        const notification = document.createElement('div');

        notification.className =
            `realtime-notification mb-3 p-3 border rounded-lg ${alertClass} animate-fade-in`;

        notification.innerHTML = `
        <div class="flex items-center">
            <span class="font-bold mr-2">${icon}</span>
            <span>${message}</span>
            <button class="ml-auto text-gray-500 hover:text-gray-700" aria-label="Tutup"
                onclick="this.parentElement.parentElement.remove()">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;

        notificationArea.appendChild(notification);

        // Success, info, dan warning hilang otomatis
        if (type === 'success' || type === 'info' || type === 'warning') {
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 3000);
        }
    }

    // Fungsi untuk menampilkan indikator processing
    function showProcessing(message = 'Memproses...') {
        const notificationArea = document.getElementById('notification-area');
        if (!notificationArea) return;

        let alert = document.getElementById('processing-alert');
        if (!alert) {
            alert = document.createElement('div');
            alert.id = 'processing-alert';
            alert.className = 'mb-3 p-3 border rounded-lg bg-blue-50 border-blue-200 text-blue-800 animate-fade-in';
            notificationArea.appendChild(alert);
        }

        alert.textContent = message;
    }

    // Fungsi untuk menyembunyikan indikator processing
    function hideProcessing() {
        document.getElementById('processing-alert')?.remove();
    }

    // Fungsi untuk update kelas input adjustment
    function updateAdjInputClasses(input) {
        if (input.disabled || input.readOnly) return;

        const value = parseInputNumber(input.value);
        if (!value) {
            input.classList.add('bg-yellow-50', 'border-yellow-300');
            input.classList.remove('bg-white');
        } else {
            input.classList.remove('bg-yellow-50', 'border-yellow-300');
            input.classList.add('bg-white');
        }
    }

    // Fungsi untuk parse input number
    function parseInputNumber(value) {
        if (value === null || value === undefined) return 0;
        let str = String(value).trim();
        if (!str) return 0;

        str = str.replace(/\s+/g, '');

        if (str.includes('.') && str.includes(',')) {
            str = str.replace(/\./g, '').replace(',', '.');
        } else if (str.includes(',')) {
            str = str.replace(',', '.');
        }

        str = str.replace(/[^0-9.-]/g, '');

        const num = parseFloat(str);
        return isNaN(num) ? 0 : num;
    }

    // Fungsi untuk memformat tampilan input adjustment (Optimized)
    function formatAdjInputDisplay(input) {
        const value = parseInputNumber(input.value);
        if (!value) {
            input.value = '';
            return;
        }

        input.value = numberFormatter.format(value);
    }

    // Fungsi untuk menerapkan warna baris berdasarkan tanda
    function applySignRowColors(root = document) {
        let rows = [];
        if (root.matches && root.matches('tr[data-sign-row="1"]')) {
            rows = [root];
        } else if (root.querySelectorAll) {
            rows = root.querySelectorAll('tr[data-sign-row="1"]');
        }

        rows.forEach(row => {
            const firstTd = row.querySelector('td:first-child');
            const rowText = firstTd ? firstTd.textContent.toUpperCase() : '';
            const isProvince = row.classList.contains('row-provinsi') || rowText.includes('PROVINSI') || !!row.querySelector('[id*="-provinsi-"]');
            const isTotalKab = row.classList.contains('row-total-kabkota') || (!isProvince && (rowText.includes('TOTAL KABUPATEN/KOTA') || !!row.querySelector('[id*="-total-"]')));

            row.querySelectorAll('td[data-value]').forEach(cell => {
                const isRed = cell.classList.contains('bg-red-strong');
                const isYellow = cell.classList.contains('bg-yellow-strong');
                const isOrangeStrong = cell.classList.contains('bg-orange-strong');

                // Special handling for Total Kab/Kota "collision" (Red status vs Positive value)
                if ((isRed || isYellow || isOrangeStrong) && isTotalKab) {
                    const value = getCellGrowthValue(cell);
                    if (cell.dataset.signBaseTitle === undefined) {
                        cell.dataset.signBaseTitle = cell.getAttribute('title') ?? '';
                    }
                    const baseTitle = cell.dataset.signBaseTitle ?? '';

                    let reason = '';
                    if (isRed || isOrangeStrong) {
                        // Check if title already contains growth warning
                        if (cell.title.includes('melebihi batas')) {
                            reason = cell.title.split('|').pop().trim();
                        } else {
                            const threshold = (window.REKON_PENDEKATAN === 'pengeluaran') ? 4 : 5;
                            reason = (isRed ? 'Diskrepansi' : 'Alert') + ` > ${threshold}%`;
                        }
                    } else if (isYellow) {
                        reason = 'Beda Arah';
                    }

                    const signReason = value > 0 ? 'Nilai Positif' : (value < 0 ? 'Nilai Negatif' : '');
                    const fullReason = signReason ? `${reason} & ${signReason}` : reason;

                    cell.removeAttribute('title');
                    return;
                }

                // Lewati jika sel memiliki status khusus (Merah/Kuning/Orange) yang merupakan highlight ekstrim
                if (isRed || isYellow || isOrangeStrong) {
                    return;
                }

                const isStatusCell = cell.classList.contains('status-cell');
                const value = getCellGrowthValue(cell);

                cell.classList.remove(
                    'bg-white', 'bg-gray-50', 'bg-yellow-50', 'bg-yellow-100', 'bg-yellow-200',
                    'bg-red-200', 'bg-red-strong', 'bg-yellow-strong', 'bg-orange-strong', 'bg-green-soft', 'bg-orange-soft',
                    'hover:bg-gray-50', 'hover:bg-yellow-100', 'sign-positive', 'sign-negative'
                );

                if (cell.dataset.signBaseTitle === undefined) {
                    cell.dataset.signBaseTitle = cell.getAttribute('title') ?? '';
                }

                if (!isNaN(value) && value !== 0) {
                    if (value > 0) {
                        cell.classList.add('bg-green-soft');
                        if (!isStatusCell) {
                            const baseTitle = cell.dataset.signBaseTitle ?? '';
                            const reason = 'Nilai positif';
                            cell.removeAttribute('title');
                        }
                    } else if (value < 0) {
                        cell.classList.add('bg-orange-soft');
                        if (!isStatusCell) {
                            const baseTitle = cell.dataset.signBaseTitle ?? '';
                            const reason = 'Nilai negatif';
                            cell.removeAttribute('title');
                        }
                    }
                } else {
                    // Background default jika 0 atau NaN
                    // Hanya tambahkan bg-white jika baris tidak punya special class
                    if (!isProvince && !isTotalKab) {
                        cell.classList.add('bg-white');
                    }

                    if (!isStatusCell) {
                        const baseTitle = cell.dataset.signBaseTitle ?? '';
                        if (baseTitle) {
                            cell.title = baseTitle;
                        } else {
                            cell.removeAttribute('title');
                        }
                    }
                }
            });
        });

        // Hapus paksa semua title/tooltip bawaan di status-cell
        document.querySelectorAll('.status-cell').forEach(cell => {
            cell.removeAttribute('title');
        });
    }

    // Fungsi untuk update sel PDRB + adjustment
    function updatePdrbPlusAdjCell(input) {
        const pdrb = parseFloat(input.dataset.pdrb) || 0;
        const adj = parseInputNumber(input.value);
        const total = pdrb + adj;

        if (input.dataset.target) {
            const target = document.getElementById(input.dataset.target);
            if (target) {
                target.innerText = formatNumber(total);
                target.dataset.value = total;

                applySignRowColors(target.closest('tr') ?? document);
            }
        }

        if (input.dataset.group === 'total-kabkota') {
            updateTotalIndeksLaju(input.dataset.idTahun, input.dataset.idPeriode);
        } else if (input.dataset.group === 'kabkota') {
            // Update individual kabupaten annual total in real-time
            updateKabkotaAnnualTotal(input.dataset.idWilayah, input.dataset.idTahun, input.dataset.tipe);
        }
    }

    // Fungsi untuk memastikan modal history ada
    function ensureHistoryModal() {
        let modal = document.getElementById('history-modal');
        if (modal) return modal;

        modal = document.createElement('div');
        modal.id = 'history-modal';
        modal.className = 'fixed inset-0 hidden';
        modal.style.zIndex = '99999';
        modal.innerHTML = `
        <div class="absolute inset-0 bg-black/50" data-history-close></div>
        <div class="relative mx-auto mt-16 w-[92%] max-w-2xl rounded-lg bg-white shadow-lg">
            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                <h3 id="history-modal-title" class="text-sm font-semibold md:text-base">Histori Perubahan</h3>
                <button type="button" class="text-gray-500 hover:text-gray-700" data-history-close aria-label="Tutup Histori">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="history-modal-body" class="px-4 py-4 text-xs md:text-sm text-gray-700"></div>
        </div>
    `;

        document.body.appendChild(modal);

        const close = () => {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        };

        modal.querySelectorAll('[data-history-close]').forEach(btn => {
            btn.addEventListener('click', close);
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                close();
            }
        });

        return modal;
    }

    // Fungsi untuk membuka modal history
    function openHistoryModal() {
        const modal = ensureHistoryModal();
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        return modal;
    }

    // Fungsi untuk menampilkan history
    function showHistory(el) {
        const id = el.dataset.historyId;
        if (!id) {
            showNotification('Belum ada histori', 'warning');
            return;
        }

        const historyType = el.dataset.historyType || 'subkategori';
        const url = window.REKON_HISTORY_URL.replace('__ID__', id) + (historyType === 'kategori' ? '?type=kategori' : '');
        const modal = openHistoryModal();
        const body = modal.querySelector('#history-modal-body');
        const title = modal.querySelector('#history-modal-title');

        title.textContent = 'Histori Perubahan';
        body.innerHTML = '<div class="text-gray-500">Memuat data...</div>';

        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (!data.length) {
                    body.innerHTML = '<div class="text-gray-500">Belum ada histori.</div>';
                    return;
                }

                const rows = data.slice(0, 10).map(row => `
                <tr class="border-b last:border-b-0">
                    <td class="py-2 pr-3">${row.name ?? 'SYSTEM'}</td>
                    <td class="py-2 pr-3 whitespace-nowrap">${row.created_at ?? '-'}</td>
                    <td class="py-2 text-right whitespace-nowrap">${formatNumber(row.nilai_adj)}</td>
                    <td class="py-2 text-right whitespace-nowrap">${formatNumber(row.nilai_baru)}</td>
                </tr>
            `).join('');

                body.innerHTML = `
                <div class="max-h-[60vh] overflow-auto">
                    <table class="min-w-full text-xs md:text-sm">
                        <thead class="sticky top-0 bg-gray-50 text-gray-600">
                            <tr>
                                <th class="py-2 text-left font-semibold">User</th>
                                <th class="py-2 text-left font-semibold">Waktu</th>
                                <th class="py-2 text-right font-semibold">Nilai Adj</th>
                                <th class="py-2 text-right font-semibold">Nilai Baru</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows}
                        </tbody>
                    </table>
                </div>
            `;
            })
            .catch(() => {
                body.innerHTML = '<div class="text-red-600">Gagal mengambil data histori.</div>';
            });
    }

    // Fungsi untuk menghitung total kabupaten/kota
    function hitungTotalKabKota(idTahun, idPeriode, tipe) {
        let totalAdj = 0;
        let totalPdrb = 0;

        document.querySelectorAll(
            `.adj-kabkota[data-id-tahun="${idTahun}"][data-id-periode="${idPeriode}"][data-tipe="${tipe}"]`
        ).forEach(input => {
            if (input.disabled || input.readOnly) {
                // Baris Net Ekspor: adj tidak bisa diinput langsung.
                // Baca nilai PDRB+Adj dari cell target, hitung adj = (pdrb+adj) - pdrb
                const targetId = input.dataset.target;
                const targetCell = targetId ? document.getElementById(targetId) : null;
                const pdrbPlusAdj = targetCell ? parseFloat(targetCell.dataset.value) || 0 : 0;
                const pdrb = parseFloat(input.dataset.pdrb) || 0;
                totalPdrb += pdrb;
                totalAdj += (pdrbPlusAdj - pdrb);
            } else {
                totalAdj += parseInputNumber(input.value);
                totalPdrb += parseFloat(input.dataset.pdrb) || 0;
            }
        });

        const totalInput = document.getElementById(`adj-${tipe}-total-${idTahun}-${idPeriode}`);
        if (totalInput) {
            totalInput.value = totalAdj ? formatNumber(totalAdj) : '';
        }

        const totalValue = totalPdrb + totalAdj;

        const totalCell = document.getElementById(`pdrb-plus-adj-${tipe}-total-${idTahun}-${idPeriode}`);
        if (totalCell) {
            totalCell.innerText = formatNumber(totalValue);
            totalCell.dataset.value = totalValue;
            applySignRowColors(totalCell.closest('tr') ?? document);
        }

        const getPeriodeIdsForTotal = () => {
            const ids = new Set();
            const prefix = `pdrb-plus-adj-${tipe}-total-${idTahun}-`;
            const re = new RegExp(`^pdrb-plus-adj-${tipe}-total-${idTahun}-(\\d+)$`);
            document.querySelectorAll(`[id^="${prefix}"]`).forEach(el => {
                const match = el.id.match(re);
                if (match && match[1]) {
                    const pid = parseInt(match[1], 10);
                    if (!isNaN(pid)) ids.add(pid);
                }
            });
            return Array.from(ids).sort((a, b) => a - b);
        };

        const getCellValue = (id) => {
            const cell = document.getElementById(id);
            if (!cell) return 0;
            const val = parseFloat(cell.dataset.value);
            return isNaN(val) ? 0 : val;
        };

        let totalTahunan = 0;
        let totalAdjTahunan = 0;
        const periodeIds = getPeriodeIdsForTotal();
        if (periodeIds.length) {
            periodeIds.forEach(pid => {
                totalTahunan += getCellValue(`pdrb-plus-adj-${tipe}-total-${idTahun}-${pid}`);

                const adjInput = document.getElementById(`adj-${tipe}-total-${idTahun}-${pid}`);
                if (adjInput) {
                    totalAdjTahunan += parseInputNumber(adjInput.value);
                }
            });
        } else {
            totalTahunan = totalValue;
            totalAdjTahunan = totalAdj;
        }

        const adjAnnualTotalInput = document.getElementById(`adj-${tipe}-total-kab-total-${idTahun}`);
        if (adjAnnualTotalInput) {
            adjAnnualTotalInput.value = totalAdjTahunan ? formatNumber(totalAdjTahunan) : '';
        }

        document
            .querySelectorAll(`#pdrb-plus-adj-${tipe}-total-kab-total-${idTahun}`)
            .forEach(cell => {
                cell.innerText = formatNumber(totalTahunan);
                cell.dataset.value = totalTahunan;
                applySignRowColors(cell.closest('tr') ?? document);
            });

        // Update annual discrepancy too
        updateDiskrepansiNilai(idTahun, 'total-kab-total');
        updateDiskrepansiPersen(idTahun, 'total-kab-total');

        updateDiskrepansiNilai(idTahun, idPeriode);
        updateDiskrepansiPersen(idTahun, idPeriode);

        // Trigger real-time growth update for current periode
        updateTotalGrowth(idTahun, idPeriode);

        // Trigger real-time indeks & laju implisit update (TOTAL KAB/KOTA)
        updateTotalIndeksLaju(idTahun, idPeriode);

        // Helper: get all periode IDs for a given year from the DOM
        const getAllPeriodeIds = (tahunId) => {
            const ids = new Set();
            const prefix = `pdrb-plus-adj-konstan-total-${tahunId}-`;
            const re = new RegExp(`^pdrb-plus-adj-konstan-total-${tahunId}-(\\d+)$`);
            document.querySelectorAll(`[id^="${prefix}"]`).forEach(el => {
                const match = el.id.match(re);
                if (match && match[1]) {
                    const pid = parseInt(match[1], 10);
                    if (!isNaN(pid)) ids.add(pid);
                }
            });
            return Array.from(ids).sort((a, b) => a - b);
        };

        // Update triwulan-triwulan SETELAHNYA di tahun yang sama
        // (misal: Q1 berubah → QoQ Q2 harus update karena base-nya Q1)
        const samePeriodeIds = getAllPeriodeIds(idTahun);
        const currentPidIdx = samePeriodeIds.indexOf(parseInt(idPeriode, 10));
        for (let p = currentPidIdx + 1; p < samePeriodeIds.length; p++) {
            updateTotalGrowth(idTahun, samePeriodeIds[p]);
            updateTotalIndeksLaju(idTahun, samePeriodeIds[p]);
        }

        // Update SEMUA triwulan di SEMUA tahun berikutnya
        const order = Array.isArray(window.REKON_TAHUN_ORDER)
            ? window.REKON_TAHUN_ORDER.map(v => parseInt(v, 10)).filter(v => !isNaN(v))
            : [];
        const idx = order.indexOf(parseInt(idTahun, 10));
        for (let i = idx + 1; i < order.length; i++) {
            const yearPeriodeIds = getAllPeriodeIds(order[i]);
            for (let p = 0; p < yearPeriodeIds.length; p++) {
                updateTotalGrowth(order[i], yearPeriodeIds[p]);
                updateTotalIndeksLaju(order[i], yearPeriodeIds[p]);
            }
        }
    }

    // Fungsi untuk update total tahunan kabupaten secara lokal
    function updateKabkotaAnnualTotal(idWilayah, idTahun, tipe) {
        let totalPdrb = 0;
        let totalAdj = 0;

        // Ambil semua triwulan untuk wilayah dan tahun ini
        const order = Array.isArray(window.REKON_PERIODE_ORDER)
            ? window.REKON_PERIODE_ORDER.map(v => parseInt(v, 10)).filter(v => !isNaN(v))
            : [];

        order.forEach(pid => {
            const cell = document.getElementById(`pdrb-plus-adj-${tipe}-kabkota-${idWilayah}-${idTahun}-${pid}`);
            if (cell) {
                totalPdrb += parseFloat(cell.dataset.value) || 0;
            }

            // Sum adjustment row locally too
            const adjInput = document.querySelector(`.adj-kabkota[data-id-wilayah="${idWilayah}"][data-id-tahun="${idTahun}"][data-id-periode="${pid}"][data-tipe="${tipe}"]`);
            if (adjInput) {
                totalAdj += parseInputNumber(adjInput.value);
            }
        });

        const totalCell = document.getElementById(`pdrb-plus-adj-${tipe}-kabkota-total-${idWilayah}-${idTahun}`);
        if (totalCell) {
            totalCell.innerText = formatNumber(totalPdrb);
            totalCell.dataset.value = totalPdrb;
            applySignRowColors(totalCell.closest('tr') ?? document);
        }

        const adjTotalInput = document.getElementById(`adj-${tipe}-kabkota-total-${idWilayah}-${idTahun}`);
        if (adjTotalInput) {
            adjTotalInput.value = totalAdj ? formatNumber(totalAdj) : '';
        }

        // Trigger growth recalculation for annual total row
        updateKabkotaAnnualGrowth(idWilayah, idTahun);

        // Update Indeks & Laju Implisit for Annual Total
        updateKabkotaAnnualIndeksLaju(idWilayah, idTahun);
    }

    // Fungsi untuk update pertumbuhan tahunan kabupaten secara lokal
    function updateKabkotaAnnualGrowth(idWilayah, idTahun) {
        const order = Array.isArray(window.REKON_TAHUN_ORDER)
            ? window.REKON_TAHUN_ORDER.map(v => parseInt(v, 10)).filter(v => !isNaN(v))
            : [];

        const idx = order.indexOf(parseInt(idTahun, 10));
        if (idx < 0) return;

        const updateGrowthForYear = (targetYearId) => {
            const currentIdx = order.indexOf(parseInt(targetYearId, 10));
            const prevYearId = currentIdx > 0 ? order[currentIdx - 1] : null;

            const currentTotalCell = document.getElementById(`pdrb-plus-adj-konstan-kabkota-total-${idWilayah}-${targetYearId}`);
            const currentBaseTotalCell = document.getElementById(`konstan-kabkota-total-${idWilayah}-${targetYearId}`);

            if (!currentTotalCell || !currentBaseTotalCell) return;

            const currentValAdj = parseFloat(currentTotalCell.dataset.value) || 0;
            const currentValBase = parseFloat(currentBaseTotalCell.dataset.value) || 0;

            const prevTotalCell = prevYearId ? document.getElementById(`pdrb-plus-adj-konstan-kabkota-total-${idWilayah}-${prevYearId}`) : null;
            const prevBaseTotalCell = prevYearId ? document.getElementById(`konstan-kabkota-total-${idWilayah}-${prevYearId}`) : null;

            // Update QoQ, YoY, CtoC cells for 'total' row
            const metrics = [
                { id: 'qoq', base: 'qoq-konstan', adj: 'qoq-konstan-plus-adj' },
                { id: 'yoy', base: 'yoy-konstan', adj: 'yoy-konstan-plus-adj' },
                { id: 'ctoc', base: 'ctoc-konstan', adj: 'ctoc-konstan-plus-adj' }
            ];

            metrics.forEach(m => {
                const baseId = `${m.base}-kabkota-total-${idWilayah}-${targetYearId}`;
                const adjId = `${m.adj}-kabkota-total-${idWilayah}-${targetYearId}`;

                const baseCell = document.getElementById(baseId);
                if (!baseCell) return;

                let prevValAdj = 0;
                let prevValBase = 0;

                if (prevTotalCell && prevBaseTotalCell) {
                    prevValAdj = parseFloat(prevTotalCell.dataset.value) || 0;
                    prevValBase = parseFloat(prevBaseTotalCell.dataset.value) || 0;
                } else {
                    // Derive from base growth of the current year
                    const originalGrowth = parseFloat(baseCell.dataset.value);
                    if (!isNaN(originalGrowth) && originalGrowth !== -100) {
                        prevValBase = currentValBase / (1 + (originalGrowth / 100));
                        prevValAdj = prevValBase; // First year has no prior visible adj, so prev adj = prev base
                    } else {
                        // If no original growth exists, can't calculate
                        return;
                    }
                }

                let growthAdj = 0;
                if (prevValAdj !== 0) {
                    growthAdj = ((currentValAdj - prevValAdj) / prevValAdj) * 100;
                }

                let growthBase = 0;
                if (prevValBase !== 0) {
                    // Only update base growth if we have previous base values
                    growthBase = ((currentValBase - prevValBase) / prevValBase) * 100;
                } else {
                    growthBase = parseFloat(baseCell.dataset.value) || 0;
                }

                // Update Base cell
                const target = baseCell.querySelector('.main-value') || baseCell.querySelector('.font-semibold') || baseCell;
                target.innerText = formatNumber(growthBase, true);
                baseCell.dataset.value = growthBase;
                applySignRowColors(baseCell.closest('tr') ?? document);

                // Update Adj cell with Status Alert
                updateAnnualTotalCellWithStatus(adjId, growthBase, growthAdj, true);
            });
        };

        // Update current year and all subsequent years
        for (let i = idx; i < order.length; i++) {
            updateGrowthForYear(order[i]);
        }
    }

    // Helper function to update cell with real-time status alert (badge merah)
    function updateAnnualTotalCellWithStatus(cellId, baseVal, adjVal, percent = false) {
        const cell = document.getElementById(cellId);
        if (!cell) return;

        const wrapper = cell.querySelector('.main-value') || cell.querySelector('.font-semibold');
        const target = wrapper || cell;

        target.innerText = formatNumber(adjVal, true); // Always add % or not? The wrapper determines. formatNumber handles true.
        cell.dataset.value = adjVal;

        // Apply status (red alert)
        const cat = getDiffCategory(baseVal, adjVal);
        const bg = getBgByCategory(cat);
        const label = getStatusLabel(cat);

        // Clean status classes
        cell.classList.remove('bg-red-strong', 'bg-yellow-strong');

        // Remove old status tooltips if any
        const oldTooltip = cell.querySelector('.status-tooltip');
        if (oldTooltip) oldTooltip.remove();

        if (bg) {
            cell.classList.remove('sign-positive', 'sign-negative', 'bg-white', 'bg-gray-50', 'bg-yellow-50', 'hover:bg-gray-50');
            cell.classList.add(bg);
            cell.removeAttribute('title');
        } else {
            cell.classList.remove('bg-red-strong', 'bg-yellow-strong');
            cell.classList.add('bg-yellow-50');
            cell.removeAttribute('title');
            applySignRowColors(cell.closest('tr') ?? document);
        }
    }

    // Fungsi untuk update indeks & laju implisit tahunan kabupaten secara lokal
    function updateKabkotaAnnualIndeksLaju(idWilayah, idTahun) {
        // Base (without adj)
        const berlakuBaseCell = document.getElementById(`berlaku-kabkota-total-${idWilayah}-${idTahun}`);
        const konstanBaseCell = document.getElementById(`konstan-kabkota-total-${idWilayah}-${idTahun}`);

        // Plus Adj
        const berlakuAdjCell = document.getElementById(`pdrb-plus-adj-berlaku-kabkota-total-${idWilayah}-${idTahun}`);
        const konstanAdjCell = document.getElementById(`pdrb-plus-adj-konstan-kabkota-total-${idWilayah}-${idTahun}`);

        if (!berlakuAdjCell || !konstanAdjCell || !berlakuBaseCell || !konstanBaseCell) return;

        const bBase = parseFloat(berlakuBaseCell.dataset.value) || 0;
        const kBase = parseFloat(konstanBaseCell.dataset.value) || 0;
        const bAdj = parseFloat(berlakuAdjCell.dataset.value) || 0;
        const kAdj = parseFloat(konstanAdjCell.dataset.value) || 0;

        let indeksBase = 0;
        if (kBase !== 0) indeksBase = (bBase / kBase) * 100;

        let indeksAdj = 0;
        if (kAdj !== 0) indeksAdj = (bAdj / kAdj) * 100;

        // Update Indeks Base
        const indeksBaseCell = document.getElementById(`indeks-berlaku-kabkota-total-${idWilayah}-${idTahun}`);
        if (indeksBaseCell) {
            const container = indeksBaseCell.querySelector('div.font-semibold') || indeksBaseCell;
            container.innerText = formatNumber(indeksBase);
            indeksBaseCell.dataset.value = indeksBase;
            applySignRowColors(indeksBaseCell.closest('tr') ?? document);
        }

        // Update Indeks Adj with Status
        updateAnnualTotalCellWithStatus(`indeks-berlaku-plus-adj-kabkota-total-${idWilayah}-${idTahun}`, indeksBase, indeksAdj, false);

        // Update Laju Implisit (Growth of Indeks)
        const order = Array.isArray(window.REKON_TAHUN_ORDER)
            ? window.REKON_TAHUN_ORDER.map(v => parseInt(v, 10)).filter(v => !isNaN(v))
            : [];

        const idx = order.indexOf(parseInt(idTahun, 10));
        if (idx < 0) return;

        const updateLajuForYear = (targetYearId) => {
            const currentIdx = order.indexOf(parseInt(targetYearId, 10));
            const prevYearId = currentIdx > 0 ? order[currentIdx - 1] : null;

            const currentIndeksAdjCell = document.getElementById(`indeks-berlaku-plus-adj-kabkota-total-${idWilayah}-${targetYearId}`);
            const currentIndeksBaseCell = document.getElementById(`indeks-berlaku-kabkota-total-${idWilayah}-${targetYearId}`);

            if (!currentIndeksAdjCell || !currentIndeksBaseCell) return;

            const curIdxAdjVal = parseFloat(currentIndeksAdjCell.dataset.value) || 0;
            const curIdxBaseVal = parseFloat(currentIndeksBaseCell.dataset.value) || 0;

            const prevIndeksAdjCell = prevYearId ? document.getElementById(`indeks-berlaku-plus-adj-kabkota-total-${idWilayah}-${prevYearId}`) : null;
            const prevIndeksBaseCell = prevYearId ? document.getElementById(`indeks-berlaku-kabkota-total-${idWilayah}-${prevYearId}`) : null;

            let preIdxAdjVal = 0;
            let preIdxBaseVal = 0;

            const lajuBaseCell = document.getElementById(`laju-berlaku-kabkota-total-${idWilayah}-${targetYearId}`);
            if (!lajuBaseCell) return;

            if (prevIndeksAdjCell && prevIndeksBaseCell) {
                preIdxAdjVal = parseFloat(prevIndeksAdjCell.dataset.value) || 0;
                preIdxBaseVal = parseFloat(prevIndeksBaseCell.dataset.value) || 0;
            } else {
                // Derive from base laju
                const originalLaju = parseFloat(lajuBaseCell.dataset.value);
                if (!isNaN(originalLaju) && originalLaju !== -100) {
                    preIdxBaseVal = curIdxBaseVal / (1 + (originalLaju / 100));
                    preIdxAdjVal = preIdxBaseVal;
                } else {
                    return; // Can't calculate
                }
            }

            let lajuAdj = 0;
            if (preIdxAdjVal !== 0) {
                lajuAdj = ((curIdxAdjVal - preIdxAdjVal) / preIdxAdjVal) * 100;
            }

            let lajuBase = 0;
            if (preIdxBaseVal !== 0) {
                lajuBase = ((curIdxBaseVal - preIdxBaseVal) / preIdxBaseVal) * 100;
            } else {
                lajuBase = parseFloat(lajuBaseCell.dataset.value) || 0;
            }

            // Update Laju Base
            const container = lajuBaseCell.querySelector('div.font-semibold') || lajuBaseCell;
            container.innerText = formatNumber(lajuBase, true);
            lajuBaseCell.dataset.value = lajuBase;
            applySignRowColors(lajuBaseCell.closest('tr') ?? document);

            // Update Laju Adj with Status
            updateAnnualTotalCellWithStatus(`laju-berlaku-plus-adj-kabkota-total-${idWilayah}-${targetYearId}`, lajuBase, lajuAdj, true);
        };

        for (let i = idx; i < order.length; i++) {
            updateLajuForYear(order[i]);
        }
    }

    // Fungsi untuk mengupdate total growth
    function updateTotalGrowth(idTahun, idPeriode) {
        const getPrevYearId = (tahunId) => {
            const raw = Array.isArray(window.REKON_TAHUN_ORDER) ? window.REKON_TAHUN_ORDER : [];
            const order = raw
                .map(v => parseInt(v, 10))
                .filter(v => !isNaN(v));
            const current = parseInt(tahunId, 10);
            const idx = order.indexOf(current);
            if (idx > 0) return order[idx - 1];
            return null;
        };

        const getPeriodeIds = (pdrbPrefix, tahunId) => {
            const ids = new Set();
            const prefix = `${pdrbPrefix}${tahunId}-`;
            const re = new RegExp(`^${pdrbPrefix}${tahunId}-(\\d+)$`);

            document.querySelectorAll(`[id^="${prefix}"]`).forEach(el => {
                const match = el.id.match(re);
                if (match && match[1]) {
                    const pid = parseInt(match[1], 10);
                    if (!isNaN(pid)) ids.add(pid);
                }
            });

            return Array.from(ids).sort((a, b) => a - b);
        };

        const getCellValue = (id) => {
            const cell = document.getElementById(id);
            if (!cell) return 0;
            const val = parseFloat(cell.dataset.value);
            return isNaN(val) ? 0 : val;
        };

        const getCumulative = (pdrbPrefix, tahunId, periodeId, periodeIds) => {
            let total = 0;
            for (const pid of periodeIds) {
                if (pid > periodeId) break;
                total += getCellValue(`${pdrbPrefix}${tahunId}-${pid}`);
            }
            return total;
        };

        const getPrevPeriodeId = (pdrbPrefix, tahunId, periodeId) => {
            const periodeIds = getPeriodeIds(pdrbPrefix, tahunId);
            const currentPid = parseInt(periodeId, 10);
            const idx = periodeIds.indexOf(currentPid);
            if (idx > 0) return periodeIds[idx - 1];
            return null;
        };

        const updateCtocForType = (type, pdrbPrefix, ctocPrefix, tahunId, periodeId) => {
            const periodeIds = getPeriodeIds(pdrbPrefix, tahunId);
            if (!periodeIds.length) return;

            const prevYearId = getPrevYearId(tahunId);
            const prevPeriodeIds = prevYearId ? getPeriodeIds(pdrbPrefix, prevYearId) : [];
            const usePrevYear = prevYearId && prevPeriodeIds.length > 0;

            const getBaseCum = (ctocId) => {
                const cell = document.getElementById(ctocId);
                if (!cell) return null;
                const base = parseFloat(cell.dataset.base);
                return isNaN(base) ? null : base;
            };

            const startIdx = periodeIds.indexOf(parseInt(periodeId, 10));
            const updateIds = startIdx >= 0 ? periodeIds.slice(startIdx) : periodeIds;

            updateIds.forEach(pid => {
                const currentCum = getCumulative(pdrbPrefix, tahunId, pid, periodeIds);
                const ctocId = `${ctocPrefix}${tahunId}-${pid}`;

                let baseCum = null;
                if (usePrevYear) {
                    baseCum = getCumulative(pdrbPrefix, prevYearId, pid, prevPeriodeIds);
                } else {
                    baseCum = getBaseCum(ctocId);
                }

                const newCtoc = baseCum && baseCum !== 0
                    ? ((currentCum - baseCum) / baseCum) * 100
                    : 0;

                updateCell(ctocId, newCtoc, true);

                const ctocCell = document.getElementById(ctocId);
                if (!ctocCell) return;

                if (type === 'total') {
                    ctocCell.classList.remove('bg-red-strong', 'bg-yellow-strong');
                    const oldTooltip = ctocCell.querySelector('.status-tooltip');
                    if (oldTooltip) oldTooltip.remove();
                    ctocCell.title = '';
                    applySignRowColors(ctocCell.closest('tr'));
                    applyTotalKabGrowthAlertCell(ctocCell, tahunId, pid, 'ctoc');
                } else {
                    const baseGrowthId = `ctoc-konstan-provinsi-${tahunId}-${pid}`;
                    const baseGrowthCell = document.getElementById(baseGrowthId);
                    const baseGrowth = baseGrowthCell ? (parseFloat(baseGrowthCell.dataset.value) || 0) : 0;

                    const cat = getDiffCategory(baseGrowth, newCtoc);
                    const bg = getBgByCategory(cat);
                    const label = getStatusLabel(cat);

                    ctocCell.classList.remove('bg-red-strong', 'bg-yellow-strong', 'bg-green-soft', 'bg-orange-soft');
                    const oldTooltip = ctocCell.querySelector('.status-tooltip');
                    if (oldTooltip) oldTooltip.remove();

                    if (bg) {
                        ctocCell.classList.remove('sign-positive', 'sign-negative', 'bg-white', 'bg-gray-50', 'bg-yellow-50', 'hover:bg-gray-50', 'hover:bg-yellow-100');
                        ctocCell.classList.add(bg);
                        ctocCell.removeAttribute('title');
                        ctocCell.insertAdjacentHTML('beforeend', renderStatusPopup(label));
                    } else {
                        applySignRowColors(ctocCell.closest('tr'));
                        ctocCell.title = '';
                    }
                }
            });

            // Update total tahunan (gunakan C-to-C pada periode terakhir)
            const lastPid = periodeIds[periodeIds.length - 1];
            if (lastPid !== undefined) {
                const currentCumLast = getCumulative(pdrbPrefix, tahunId, lastPid, periodeIds);

                const totalCtocId = type === 'total'
                    ? `ctoc-konstan-plus-adj-total-kab-total-${tahunId}`
                    : `provinsi-total-ctoc-konstan-plus-adj-${tahunId}`;

                let baseCumLast = null;
                if (usePrevYear) {
                    baseCumLast = getCumulative(pdrbPrefix, prevYearId, lastPid, prevPeriodeIds);
                } else {
                    baseCumLast = getBaseCum(totalCtocId);
                    if (baseCumLast === null) {
                        const lastCtocId = `${ctocPrefix}${tahunId}-${lastPid}`;
                        baseCumLast = getBaseCum(lastCtocId);
                    }
                }

                const newCtocTotal = baseCumLast && baseCumLast !== 0
                    ? ((currentCumLast - baseCumLast) / Math.abs(baseCumLast)) * 100
                    : 0;

                updateCell(totalCtocId, newCtocTotal, true);

                const totalCtocCell = document.getElementById(totalCtocId);
                if (totalCtocCell) {
                    if (type === 'total') {
                        totalCtocCell.classList.remove('bg-red-strong', 'bg-yellow-strong');
                        const oldTooltip = totalCtocCell.querySelector('.status-tooltip');
                        if (oldTooltip) oldTooltip.remove();
                        totalCtocCell.title = '';
                        applySignRowColors(totalCtocCell.closest('tr'));
                    } else {
                        const baseGrowthId = `provinsi-total-ctoc-konstan-${tahunId}`;
                        const baseGrowthCell = document.getElementById(baseGrowthId);
                        const baseGrowth = baseGrowthCell ? (parseFloat(baseGrowthCell.dataset.value) || 0) : 0;

                        const cat = getDiffCategory(baseGrowth, newCtocTotal);
                        const bg = getBgByCategory(cat);
                        const label = getStatusLabel(cat);

                        totalCtocCell.classList.remove('bg-red-strong', 'bg-yellow-strong', 'bg-green-soft', 'bg-orange-soft');
                        const oldTooltip = totalCtocCell.querySelector('.status-tooltip');
                        if (oldTooltip) oldTooltip.remove();

                        if (bg) {
                            totalCtocCell.classList.remove('sign-positive', 'sign-negative', 'bg-white', 'bg-gray-50', 'bg-yellow-50', 'hover:bg-gray-50', 'hover:bg-yellow-100');
                            totalCtocCell.classList.add(bg);
                            totalCtocCell.removeAttribute('title');
                            totalCtocCell.insertAdjacentHTML('beforeend', renderStatusPopup(label));
                        } else {
                            applySignRowColors(totalCtocCell.closest('tr'));
                            totalCtocCell.title = '';
                        }
                    }
                }
            }
        };

        // Helper untuk update growth berdasarkan tipe
        const updateForType = (type, pdrbPrefix, qoqPrefix, yoyPrefix, ctocPrefix) => {
            const pdrbCell = document.getElementById(`${pdrbPrefix}${idTahun}-${idPeriode}`);
            const currentVal = pdrbCell ? (parseFloat(pdrbCell.dataset.value) || 0) : null;

            // Update QoQ
            const qoqCell = document.getElementById(`${qoqPrefix}${idTahun}-${idPeriode}`);
            if (qoqCell && currentVal !== null) {
                let base = parseFloat(qoqCell.dataset.base);

                if (type === 'total') {
                    const prevPid = getPrevPeriodeId(pdrbPrefix, idTahun, idPeriode);
                    if (prevPid !== null && prevPid !== undefined) {
                        const prevVal = getCellValue(`${pdrbPrefix}${idTahun}-${prevPid}`);
                        if (!isNaN(prevVal)) {
                            base = prevVal;
                        }
                    }
                }

                if (base && base !== 0) {
                    const newQoq = ((currentVal - base) / base) * 100;

                    updateCell(qoqCell.id, newQoq, true);

                    // For 'total' type: No status badges, only green/orange
                    if (type === 'total') {
                        qoqCell.classList.remove('bg-red-strong', 'bg-yellow-strong');
                        const oldTooltip = qoqCell.querySelector('.status-tooltip');
                        if (oldTooltip) oldTooltip.remove();
                        qoqCell.title = '';
                        applySignRowColors(qoqCell.closest('tr'));
                        applyTotalKabGrowthAlertCell(qoqCell, idTahun, idPeriode, 'qoq');
                    } else {
                        // For 'provinsi' type: Apply status badges
                        const baseGrowthId = `qoq-konstan-provinsi-${idTahun}-${idPeriode}`;
                        const baseGrowthCell = document.getElementById(baseGrowthId);
                        const baseGrowth = baseGrowthCell ? (parseFloat(baseGrowthCell.dataset.value) || 0) : 0;

                        const cat = getDiffCategory(baseGrowth, newQoq);
                        const bg = getBgByCategory(cat);
                        const label = getStatusLabel(cat);

                        qoqCell.classList.remove('bg-red-strong', 'bg-yellow-strong', 'bg-green-soft', 'bg-orange-soft');
                        const oldTooltip = qoqCell.querySelector('.status-tooltip');
                        if (oldTooltip) oldTooltip.remove();

                        if (bg) {
                            qoqCell.classList.remove('sign-positive', 'sign-negative', 'bg-white', 'bg-gray-50', 'bg-yellow-50', 'hover:bg-gray-50', 'hover:bg-yellow-100');
                            qoqCell.classList.add(bg);
                            qoqCell.removeAttribute('title');
                            qoqCell.insertAdjacentHTML('beforeend', renderStatusPopup(label));
                        } else {
                            applySignRowColors(qoqCell.closest('tr'));
                            qoqCell.title = '';
                        }
                    }
                }
            }

            // Update YoY
            const yoyCell = document.getElementById(`${yoyPrefix}${idTahun}-${idPeriode}`);
            if (yoyCell && currentVal !== null) {
                let base = parseFloat(yoyCell.dataset.base);

                if (type === 'total') {
                    const prevYearId = getPrevYearId(idTahun);
                    if (prevYearId) {
                        const prevVal = getCellValue(`${pdrbPrefix}${prevYearId}-${idPeriode}`);
                        if (!isNaN(prevVal)) {
                            base = prevVal;
                        }
                    }
                }

                if (base && base !== 0) {
                    const newYoy = ((currentVal - base) / base) * 100;

                    updateCell(yoyCell.id, newYoy, true);

                    // For 'total' type: No status badges, only green/orange
                    if (type === 'total') {
                        yoyCell.classList.remove('bg-red-strong', 'bg-yellow-strong');
                        const oldTooltip = yoyCell.querySelector('.status-tooltip');
                        if (oldTooltip) oldTooltip.remove();
                        yoyCell.title = '';
                        applySignRowColors(yoyCell.closest('tr'));
                    } else {
                        // For 'provinsi' type: Apply status badges
                        const baseGrowthId = `yoy-konstan-provinsi-${idTahun}-${idPeriode}`;
                        const baseGrowthCell = document.getElementById(baseGrowthId);
                        const baseGrowth = baseGrowthCell ? (parseFloat(baseGrowthCell.dataset.value) || 0) : 0;

                        const cat = getDiffCategory(baseGrowth, newYoy);
                        const bg = getBgByCategory(cat);
                        const label = getStatusLabel(cat);

                        yoyCell.classList.remove('bg-red-strong', 'bg-yellow-strong', 'bg-green-soft', 'bg-orange-soft');
                        const oldTooltip = yoyCell.querySelector('.status-tooltip');
                        if (oldTooltip) oldTooltip.remove();

                        if (bg) {
                            yoyCell.classList.remove('sign-positive', 'sign-negative', 'bg-white', 'bg-gray-50', 'bg-yellow-50', 'hover:bg-gray-50', 'hover:bg-yellow-100');
                            yoyCell.classList.add(bg);
                            yoyCell.removeAttribute('title');
                            yoyCell.insertAdjacentHTML('beforeend', renderStatusPopup(label));
                        } else {
                            applySignRowColors(yoyCell.closest('tr'));
                            yoyCell.title = '';
                        }
                    }
                }

                if (type === 'total') {
                    applyTotalKabGrowthAlertCell(yoyCell, idTahun, idPeriode, 'yoy');
                }
            }

            if (ctocPrefix) {
                updateCtocForType(type, pdrbPrefix, ctocPrefix, idTahun, idPeriode);
            }
        };

        // Update untuk total kab/kota
        updateForType('total', 'pdrb-plus-adj-konstan-total-', 'qoq-konstan-plus-adj-total-', 'yoy-konstan-plus-adj-total-', 'ctoc-konstan-plus-adj-total-');

        // Update untuk provinsi
        updateForType('provinsi', 'pdrb-plus-adj-konstan-provinsi-', 'qoq-konstan-plus-adj-provinsi-', 'yoy-konstan-plus-adj-provinsi-', 'ctoc-konstan-plus-adj-provinsi-');

        // Update QoQ & YoY total tahunan (TOTAL KAB/KOTA)
        const updateTotalKabTahunanMetric = (metric) => {
            const totalPdrbId = `pdrb-plus-adj-konstan-total-kab-total-${idTahun}`;
            const cellId = `${metric}-konstan-plus-adj-total-kab-total-${idTahun}`;
            const cell = document.getElementById(cellId);
            if (!cell) return;

            const current = getCellValue(totalPdrbId);
            let base = parseFloat(cell.dataset.base);
            const prevYearId = getPrevYearId(idTahun);
            if (prevYearId) {
                const prevVal = getCellValue(`pdrb-plus-adj-konstan-total-kab-total-${prevYearId}`);
                if (!isNaN(prevVal)) {
                    base = prevVal;
                }
            }
            cell.dataset.base = base;

            const newGrowth = base && base !== 0 ? ((current - base) / Math.abs(base)) * 100 : 0;
            updateCell(cellId, newGrowth, true);
            applyTotalKabGrowthAlertCell(cell, idTahun, idPeriode, metric);
        };

        updateTotalKabTahunanMetric('qoq');
        updateTotalKabTahunanMetric('yoy');

        // Alert pertumbuhan (QoQ/YoY/CtC) total kab/kota diprioritaskan setelah pewarnaan default.
        debounceApplyAllTotalKabGrowthAlerts();
    }

    // Hitung Indeks Implisit
    function hitungIndeksImplisit(berlaku, konstan) {
        const b = parseFloat(berlaku) || 0;
        const k = parseFloat(konstan) || 0;
        if (k === 0) return 0;
        return (b / k) * 100;
    }

    // Hitung Laju Implisit
    function hitungLajuImplisit(currentIndex, prevIndex) {
        const current = parseFloat(currentIndex) || 0;
        const prev = parseFloat(prevIndex) || 0;
        if (prev === 0) return 0;
        return ((current - prev) / Math.abs(prev)) * 100;
    }

    // Update Indeks & Laju Implisit untuk TOTAL KAB/KOTA (per periode & total tahunan)
    function updateTotalIndeksLaju(idTahun, idPeriode) {
        const getPrevYearId = (tahunId) => {
            const raw = Array.isArray(window.REKON_TAHUN_ORDER) ? window.REKON_TAHUN_ORDER : [];
            const order = raw
                .map(v => parseInt(v, 10))
                .filter(v => !isNaN(v));
            const current = parseInt(tahunId, 10);
            const idx = order.indexOf(current);
            if (idx > 0) return order[idx - 1];
            return null;
        };

        const getCellValue = (id) => {
            const cell = document.getElementById(id);
            if (!cell) return 0;
            const val = parseFloat(cell.dataset.value);
            return isNaN(val) ? 0 : val;
        };

        const applyImplisitStatus = (adjId) => {
            const cell = document.getElementById(adjId);
            if (!cell) return;

            // Remove previous alerts but don't just clear everything if we are about to re-alert
            cell.classList.remove('bg-red-strong', 'bg-yellow-strong');
            const oldTooltip = cell.querySelector('.status-tooltip');
            if (oldTooltip) oldTooltip.remove();

            // Default styling based on sign
            applySignRowColors(cell.closest('tr') ?? document);
        };

        // Per periode (TOTAL KAB/KOTA)
        const berlakuId = `pdrb-plus-adj-berlaku-total-${idTahun}-${idPeriode}`;
        const konstanId = `pdrb-plus-adj-konstan-total-${idTahun}-${idPeriode}`;
        const indeksId = `indeks-berlaku-plus-adj-total-${idTahun}-${idPeriode}`;
        const lajuId = `laju-berlaku-plus-adj-total-${idTahun}-${idPeriode}`;

        const berlaku = getCellValue(berlakuId);
        const konstan = getCellValue(konstanId);
        const indeks = hitungIndeksImplisit(berlaku, konstan);
        updateCell(indeksId, indeks);
        applyImplisitStatus(indeksId);

        const lajuCell = document.getElementById(lajuId);
        if (lajuCell) {
            let base = parseFloat(lajuCell.dataset.base);
            const prevYearId = getPrevYearId(idTahun);
            if (prevYearId) {
                const prevIndeks = getCellValue(`indeks-berlaku-plus-adj-total-${prevYearId}-${idPeriode}`);
                if (!isNaN(prevIndeks)) {
                    base = prevIndeks;
                }
            }
            const newLaju = hitungLajuImplisit(indeks, isNaN(base) ? 0 : base);
            updateCell(lajuId, newLaju, true);
            applyImplisitStatus(lajuId);
        }

        // Total tahunan (TOTAL KAB/KOTA)
        const berlakuTotalId = `pdrb-plus-adj-berlaku-total-kab-total-${idTahun}`;
        const konstanTotalId = `pdrb-plus-adj-konstan-total-kab-total-${idTahun}`;
        const indeksTotalId = `indeks-berlaku-plus-adj-total-kab-total-${idTahun}`;
        const lajuTotalId = `laju-berlaku-plus-adj-total-kab-total-${idTahun}`;

        const berlakuTotal = getCellValue(berlakuTotalId);
        const konstanTotal = getCellValue(konstanTotalId);
        const indeksTotal = hitungIndeksImplisit(berlakuTotal, konstanTotal);
        updateCell(indeksTotalId, indeksTotal);
        applyImplisitStatus(indeksTotalId);

        const lajuTotalCell = document.getElementById(lajuTotalId);
        if (lajuTotalCell) {
            let base = parseFloat(lajuTotalCell.dataset.base);
            const prevYearId = getPrevYearId(idTahun);
            if (prevYearId) {
                const prevIndeksTotal = getCellValue(`indeks-berlaku-plus-adj-total-kab-total-${prevYearId}`);
                if (!isNaN(prevIndeksTotal)) {
                    base = prevIndeksTotal;
                }
            }
            const newLaju = hitungLajuImplisit(indeksTotal, isNaN(base) ? 0 : base);
            updateCell(lajuTotalId, newLaju, true);
            applyImplisitStatus(lajuTotalId);
        }

        // Re-apply alert pertumbuhan agar tidak tertimpa pewarnaan lain.
        debounceApplyAllTotalKabGrowthAlerts();
    }

    // Inisialisasi base values untuk growth calculation
    let IS_BASE_INIT = false;

    function initGrowthBaseValues() {
        if (IS_BASE_INIT) return;

        const targets = [
            {
                type: 'total',
                pdrbPrefix: 'pdrb-plus-adj-konstan-total-',
                qoqPrefix: 'qoq-konstan-plus-adj-total-',
                yoyPrefix: 'yoy-konstan-plus-adj-total-',
                ctocPrefix: 'ctoc-konstan-plus-adj-total-'
            },
            {
                type: 'provinsi',
                pdrbPrefix: 'pdrb-plus-adj-konstan-provinsi-',
                qoqPrefix: 'qoq-konstan-plus-adj-provinsi-',
                yoyPrefix: 'yoy-konstan-plus-adj-provinsi-',
                ctocPrefix: 'ctoc-konstan-plus-adj-provinsi-'
            }
        ];

        targets.forEach(t => {
            // Iterate QoQ Cells
            document.querySelectorAll(`[id^="${t.qoqPrefix}"]`).forEach(cell => {
                const suffix = cell.id.replace(t.qoqPrefix, '');
                const pdrbCell = document.getElementById(`${t.pdrbPrefix}${suffix}`);

                if (pdrbCell) {
                    const growth = parseFloat(cell.dataset.value) || 0;
                    const current = parseFloat(pdrbCell.dataset.value) || 0;
                    const base = growth === -100 ? 0 : current / (1 + growth / 100);
                    cell.dataset.base = base;
                }
            });

            // Iterate YoY Cells
            document.querySelectorAll(`[id^="${t.yoyPrefix}"]`).forEach(cell => {
                const suffix = cell.id.replace(t.yoyPrefix, '');
                const pdrbCell = document.getElementById(`${t.pdrbPrefix}${suffix}`);

                if (pdrbCell) {
                    const growth = parseFloat(cell.dataset.value) || 0;
                    const current = parseFloat(pdrbCell.dataset.value) || 0;
                    const base = growth === -100 ? 0 : current / (1 + growth / 100);
                    cell.dataset.base = base;
                }
            });
        });

        // Init base untuk QoQ/YoY total tahunan (TOTAL KAB/KOTA)
        const initTotalKabBase = (metric) => {
            document.querySelectorAll(`[id^="${metric}-konstan-plus-adj-total-kab-total-"]`).forEach(cell => {
                const tahunId = cell.id.replace(`${metric}-konstan-plus-adj-total-kab-total-`, '');
                const pdrbCell = document.getElementById(`pdrb-plus-adj-konstan-total-kab-total-${tahunId}`);
                if (!pdrbCell) return;

                const growth = parseFloat(cell.dataset.value) || 0;
                const current = parseFloat(pdrbCell.dataset.value) || 0;
                const base = growth === -100 ? 0 : current / (1 + growth / 100);
                cell.dataset.base = base;
            });
        };

        initTotalKabBase('qoq');
        initTotalKabBase('yoy');

        // Init base untuk laju implisit (per periode & total tahunan)
        const initLajuBase = (lajuPrefix, indeksPrefix, skipTotalKab = false) => {
            document.querySelectorAll(`[id^="${lajuPrefix}"]`).forEach(cell => {
                if (skipTotalKab && cell.id.includes('total-kab-total-')) return;
                const suffix = cell.id.replace(lajuPrefix, '');
                const indeksId = `${indeksPrefix}${suffix}`;
                const indeksCell = document.getElementById(indeksId);
                if (!indeksCell) return;

                const growth = parseFloat(cell.dataset.value) || 0;
                const current = parseFloat(indeksCell.dataset.value) || 0;
                const base = growth === -100 ? 0 : current / (1 + growth / 100);
                cell.dataset.base = base;
            });
        };

        initLajuBase('laju-berlaku-plus-adj-total-', 'indeks-berlaku-plus-adj-total-', true);
        initLajuBase('laju-berlaku-plus-adj-total-kab-total-', 'indeks-berlaku-plus-adj-total-kab-total-');

        IS_BASE_INIT = true;
    }

    // Inisialisasi base values untuk CtC (fallback jika data tahun sebelumnya tidak ada di DOM)
    function initCtocBaseValues() {
        const escapeRegExp = (str) => str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

        const getPeriodeIds = (pdrbPrefix, tahunId) => {
            const ids = new Set();
            const prefix = `${pdrbPrefix}${tahunId}-`;
            const re = new RegExp(`^${escapeRegExp(pdrbPrefix)}${tahunId}-(\\d+)$`);

            document.querySelectorAll(`[id^="${prefix}"]`).forEach(el => {
                const match = el.id.match(re);
                if (match && match[1]) {
                    const pid = parseInt(match[1], 10);
                    if (!isNaN(pid)) ids.add(pid);
                }
            });

            return Array.from(ids).sort((a, b) => a - b);
        };

        const getCellValue = (id) => {
            const cell = document.getElementById(id);
            if (!cell) return 0;
            const val = parseFloat(cell.dataset.value);
            return isNaN(val) ? 0 : val;
        };

        const getCumulative = (pdrbPrefix, tahunId, periodeId, periodeIds) => {
            let total = 0;
            for (const pid of periodeIds) {
                if (pid > periodeId) break;
                total += getCellValue(`${pdrbPrefix}${tahunId}-${pid}`);
            }
            return total;
        };

        const setBaseFromGrowth = (cell, currentCum) => {
            if (!cell || cell.dataset.base) return;
            const growth = parseFloat(cell.dataset.value);
            if (isNaN(growth)) return;
            const base = growth === -100 ? 0 : currentCum / (1 + growth / 100);
            cell.dataset.base = base;
        };

        const targets = [
            {
                pdrbPrefix: 'pdrb-plus-adj-konstan-total-',
                ctocPrefix: 'ctoc-konstan-plus-adj-total-'
            },
            {
                pdrbPrefix: 'pdrb-plus-adj-konstan-provinsi-',
                ctocPrefix: 'ctoc-konstan-plus-adj-provinsi-'
            }
        ];

        targets.forEach(t => {
            document.querySelectorAll(`[id^="${t.ctocPrefix}"]`).forEach(cell => {
                if (cell.dataset.base) return;
                const suffix = cell.id.replace(t.ctocPrefix, '');
                const match = suffix.match(/^(\d+)-(\d+)$/);
                if (!match) return;

                const tahunId = match[1];
                const periodeId = parseInt(match[2], 10);
                if (isNaN(periodeId)) return;

                const periodeIds = getPeriodeIds(t.pdrbPrefix, tahunId);
                if (!periodeIds.length) return;

                const currentCum = getCumulative(t.pdrbPrefix, tahunId, periodeId, periodeIds);
                setBaseFromGrowth(cell, currentCum);
            });
        });

        const totalTargets = [
            {
                pdrbPrefix: 'pdrb-plus-adj-konstan-total-',
                ctocTotalPrefix: 'ctoc-konstan-plus-adj-total-kab-total-'
            },
            {
                pdrbPrefix: 'pdrb-plus-adj-konstan-provinsi-',
                ctocTotalPrefix: 'provinsi-total-ctoc-konstan-plus-adj-'
            }
        ];

        totalTargets.forEach(t => {
            document.querySelectorAll(`[id^="${t.ctocTotalPrefix}"]`).forEach(cell => {
                if (cell.dataset.base) return;
                const tahunId = cell.id.replace(t.ctocTotalPrefix, '');
                const periodeIds = getPeriodeIds(t.pdrbPrefix, tahunId);
                if (!periodeIds.length) return;
                const lastPid = periodeIds[periodeIds.length - 1];
                const currentCum = getCumulative(t.pdrbPrefix, tahunId, lastPid, periodeIds);
                setBaseFromGrowth(cell, currentCum);
            });
        });
    }

    // Inisialisasi base values untuk Laju Implisit TOTAL KAB/KOTA (fallback)
    function initLajuBaseValuesForTotal() {
        const setBaseFromLaju = (lajuCell, indeksCell) => {
            if (!lajuCell || lajuCell.dataset.base) return;
            const currentIndex = parseFloat(indeksCell?.dataset.value);
            const laju = parseFloat(lajuCell.dataset.value);
            if (isNaN(currentIndex) || isNaN(laju)) return;
            const base = laju === -100 ? 0 : currentIndex / (1 + laju / 100);
            lajuCell.dataset.base = base;
        };

        document.querySelectorAll('[id^="laju-berlaku-plus-adj-total-"]').forEach(cell => {
            if (cell.id.startsWith('laju-berlaku-plus-adj-total-kab-total-')) return;
            const suffix = cell.id.replace('laju-berlaku-plus-adj-total-', '');
            const indeksCell = document.getElementById(`indeks-berlaku-plus-adj-total-${suffix}`);
            setBaseFromLaju(cell, indeksCell);
        });

        document.querySelectorAll('[id^="laju-berlaku-plus-adj-total-kab-total-"]').forEach(cell => {
            const tahunId = cell.id.replace('laju-berlaku-plus-adj-total-kab-total-', '');
            const indeksCell = document.getElementById(`indeks-berlaku-plus-adj-total-kab-total-${tahunId}`);
            setBaseFromLaju(cell, indeksCell);
        });
    }

    // Fungsi untuk update diskrepansi nilai
    function updateDiskrepansiNilai(idTahun, idPeriode) {
        ['berlaku', 'konstan'].forEach(tipe => {
            const prov = document.getElementById(`pdrb-plus-adj-${tipe}-provinsi-${idTahun}-${idPeriode}`);
            const total = document.getElementById(`pdrb-plus-adj-${tipe}-total-${idTahun}-${idPeriode}`);

            const provVal = prov ? parseFloat(prov.dataset.value) || 0 : 0;
            const totalVal = total ? parseFloat(total.dataset.value) || 0 : 0;
            // Selisih = Total Kab/Kota - Provinsi
            const diff = totalVal - provVal;

            const cell = document.getElementById(`diskrepansi-pdrb-adj-${tipe}-${idTahun}-${idPeriode}`);
            if (cell) {
                cell.innerText = formatNumber(diff);
                cell.dataset.value = diff;
            }
        });
    }

    // Fungsi untuk update diskrepansi persentase
    function updateDiskrepansiPersen(idTahun, idPeriode) {
        ['berlaku', 'konstan'].forEach(tipe => {
            const prov = document.getElementById(`pdrb-plus-adj-${tipe}-provinsi-${idTahun}-${idPeriode}`);
            const total = document.getElementById(`pdrb-plus-adj-${tipe}-total-${idTahun}-${idPeriode}`);

            const provVal = prov ? parseFloat(prov.dataset.value) || 0 : 0;
            const totalVal = total ? parseFloat(total.dataset.value) || 0 : 0;
            // Rumus diskrepansi persen: (Total Kab/Kota / Provinsi) * 100 - 100
            const percent = provVal !== 0 ? ((totalVal / provVal) * 100) - 100 : 0;

            const cell = document.getElementById(`diskrepansi-persen-pdrb-adj-${tipe}-${idTahun}-${idPeriode}`);
            if (cell) {
                cell.innerText = formatNumber(percent, true);
                cell.dataset.value = percent;
                cell.classList.remove('text-red-600', 'bg-red-strong', 'text-yellow-600', 'bg-yellow-strong', 'text-gray-600');

                const absValue = Math.abs(percent);
                if (absValue >= 5) {
                    cell.classList.add('text-red-600', 'bg-red-strong');
                } else if (absValue >= 2) {
                    cell.classList.add('text-yellow-600', 'bg-yellow-strong');
                } else {
                    cell.classList.add('text-gray-600');
                }
            }
        });

        // Trigger real-time growth update
        updateTotalGrowth(idTahun, idPeriode);
    }

    // Fungsi untuk recalculate growth (async)
    let recalculateGrowthTimers = {};
    async function recalculateGrowth(input, silent = false) {
        if (!window.REKON_RECALCULATE_URL) return;

        const key = `${input.dataset.idWilayah}-${input.dataset.idTahun}-${input.dataset.idPeriode}`;

        if (recalculateGrowthTimers[key]) {
            clearTimeout(recalculateGrowthTimers[key]);
        }

        return new Promise((resolve) => {
            recalculateGrowthTimers[key] = setTimeout(async () => {
                if (!silent) {
                    showProcessing('Menghitung pertumbuhan...');
                }

                try {
                    const response = await fetch(window.REKON_RECALCULATE_URL, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': window.CSRF_TOKEN,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            id_sub_kategori: input.dataset.subKategori,
                            id_kategori: input.dataset.kategori,
                            id_wilayah: input.dataset.idWilayah,
                            id_tahun: input.dataset.idTahun,
                            id_periode: input.dataset.idPeriode,
                            periode_nama: input.dataset.periodeNama
                        })
                    });

                    const result = await response.json();
                    if (!response.ok || result.status !== 'success') {
                        const msg = result?.message || `HTTP ${response.status}`;
                        if (!silent) {
                            showNotification(`Gagal menghitung pertumbuhan: ${msg}`, 'error');
                        }
                        resolve();
                        return;
                    }

                    updateGrowthCells(input, result.data);
                    if (!silent) {
                        showNotification('Pertumbuhan diperbarui', 'success');
                    }
                } catch (e) {
                    if (!silent) {
                        showNotification('Gagal menghitung pertumbuhan', 'error');
                    }
                } finally {
                    if (!silent) {
                        hideProcessing();
                    }
                    resolve();
                }
            }, silent ? 50 : 400);
        });
    }

    // Fungsi untuk update cell
    function updateCell(id, value, percent = false) {
        const cell = document.getElementById(id);
        if (!cell) return;

        const wrapper = cell.querySelector('.main-value') || cell.querySelector('.font-semibold');
        const target = wrapper || cell;

        target.innerText = formatNumber(value, percent);
        cell.dataset.value = value;
        applySignRowColors(cell.closest('tr') ?? document);
    }

    // Fungsi untuk update growth cells
    function updateGrowthCells(input, data) {
        const base = input.dataset.group === 'kabkota'
            ? `kabkota-${input.dataset.idWilayah}`
            : input.dataset.group;

        const t = input.dataset.idTahun;
        const p = input.dataset.idPeriode;

        const updateWithStatus = (metricType, baseProp, adjProp) => {
            // Update Base Cell
            const baseId = `${metricType}-konstan-${base}-${t}-${p}`;
            updateCell(baseId, data[baseProp], true);

            // Update Adj Cell
            const adjId = `${metricType}-konstan-plus-adj-${base}-${t}-${p}`;
            updateCell(adjId, data[adjProp], true);

            const cell = document.getElementById(adjId);
            if (cell) {
                const baseVal = data[baseProp];
                const adjVal = data[adjProp];
                const cat = getDiffCategory(baseVal, adjVal);
                const bg = getBgByCategory(cat);
                const label = getStatusLabel(cat);

                // Clean status classes
                cell.classList.remove('bg-red-strong', 'bg-yellow-strong');

                // Remove old status tooltips if any
                const oldTooltip = cell.querySelector('.status-tooltip');
                if (oldTooltip) oldTooltip.remove();

                if (bg) {
                    cell.classList.remove('sign-positive', 'sign-negative', 'bg-white', 'bg-gray-50', 'bg-yellow-50', 'hover:bg-gray-50');
                    cell.classList.add(bg);
                    cell.removeAttribute('title');
                } else {
                    cell.removeAttribute('title');
                }
            }
        };

        const updateImplisitWithStatus = (metricPrefix, baseProp, adjProp, isPercent = false) => {
            const baseId = `${metricPrefix}-berlaku-${base}-${t}-${p}`;
            updateCell(baseId, data[baseProp], isPercent);

            const adjId = `${metricPrefix}-berlaku-plus-adj-${base}-${t}-${p}`;
            updateCell(adjId, data[adjProp], isPercent);

            const cell = document.getElementById(adjId);
            if (cell) {
                const baseVal = data[baseProp];
                const adjVal = data[adjProp];
                const cat = getDiffCategory(baseVal, adjVal);
                const bg = getBgByCategory(cat);
                const label = getStatusLabel(cat);

                cell.classList.remove('bg-red-strong', 'bg-yellow-strong');
                const oldTooltip = cell.querySelector('.status-tooltip');
                if (oldTooltip) oldTooltip.remove();

                if (bg) {
                    cell.classList.remove('sign-positive', 'sign-negative', 'bg-white', 'bg-gray-50', 'bg-yellow-50', 'hover:bg-gray-50', 'hover:bg-yellow-100');
                    cell.classList.add(bg);
                    cell.removeAttribute('title');
                } else {
                    cell.removeAttribute('title');
                }
            }
        };

        updateWithStatus('qoq', 'qoq_konstan', 'qoq_konstan_plus_adj');
        updateWithStatus('yoy', 'yoy_konstan', 'yoy_konstan_plus_adj');
        updateWithStatus('ctoc', 'ctoc_konstan', 'ctoc_konstan_plus_adj');

        updateImplisitWithStatus('indeks', 'indeks_berlaku', 'indeks_berlaku_plus_adj', false);
        updateImplisitWithStatus('laju', 'laju_berlaku', 'laju_berlaku_plus_adj', true);

        if (['provinsi', 'total-kabkota'].includes(input.dataset.group)) {
            updateDiskrepansiNilai(t, p);
            updateDiskrepansiPersen(t, p);
        }

        // Pastikan highlight pertumbuhan total kab/kota tidak tertimpa pewarnaan lain.
        debounceApplyAllTotalKabGrowthAlerts();
    }

    // Fungsi untuk sanitize decimal input
    function sanitizeDecimalInput(input) {
        input.value = input.value.replace(/[^0-9.,-]/g, '');
    }

    // Fungsi untuk menyimpan nilai adjustment
    async function saveAdjValue(input) {
        if (input.disabled || input.readOnly) return;
        if (window.REKON_LOCKED_FOR_USER) {
            showNotification('Input sedang dikunci oleh provinsi', 'warning');
            return;
        }

        const normalizedValue = parseInputNumber(input.value);
        if (normalizedValue !== null && !isNaN(normalizedValue)) {
            input.value = normalizedValue.toFixed(2);
        }

        if (input.dataset.lastValue === String(normalizedValue || 0)) {
            updateAdjInputClasses(input);
            return;
        }

        showProcessing('Menyimpan adjustment...');
        input.classList.add('opacity-50', 'cursor-not-allowed');

        // Yield to browser to paint DOM and register fast INP interaction
        await new Promise(r => requestAnimationFrame(r));
        await new Promise(r => setTimeout(r, 0));

        updateAdjInputClasses(input);
        updatePdrbPlusAdjCell(input);

        const nilai = normalizedValue || 0;

        const payload = {
            id_sub_kategori: input.dataset.subKategori,
            id_kategori: input.dataset.kategori,
            id_wilayah: input.dataset.idWilayah,
            id_tahun: input.dataset.idTahun,
            id_periode: input.dataset.idPeriode,
            tipe: input.dataset.tipe,
            nilai: nilai
        };

        if (!payload.id_sub_kategori && !payload.id_kategori) {
            showNotification('Sub kategori/kategori kosong!', 'error');
            console.error('dataset:', input.dataset);
            input.classList.remove('opacity-50', 'cursor-not-allowed');
            return;
        }

        try {
            const socketId = window.EchoInstance?.socketId ? window.EchoInstance.socketId() : null;
            const res = await fetch(window.REKON_UPDATE_ADJ_URL, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': window.CSRF_TOKEN,
                    ...(socketId ? { 'X-Socket-ID': socketId } : {}),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            let result = null;
            try {
                result = await res.json();
            } catch (e) {
                result = null;
            }

            if (!res.ok || (result && result.status === 'error') || (result && result.error)) {
                const msg = result?.message || result?.error || `HTTP ${res.status}`;
                showNotification(`Gagal menyimpan: ${msg}`, 'error');
                return;
            }

            showNotification('Adjustment disimpan', 'success');
            input.dataset.lastValue = String(normalizedValue);

            const historyId = result?.id;
            if (historyId) {
                input.dataset.historyId = String(historyId);
                if (input.dataset.target) {
                    const target = document.getElementById(input.dataset.target);
                    if (target) {
                        target.dataset.historyId = String(historyId);
                        target.classList.add('cursor-pointer');
                        target.onclick = function() { showHistory(this); };
                    }
                }
            }

            if (input.dataset.group === 'kabkota') {
                hitungTotalKabKota(
                    input.dataset.idTahun,
                    input.dataset.idPeriode,
                    input.dataset.tipe
                );
            }

            await recalculateGrowth(input);

            // Jika ini row kabkota, update annual total & growth secara persistent
            if (input.dataset.group === 'kabkota') {
                updateKabkotaAnnualTotal(input.dataset.idWilayah, input.dataset.idTahun, input.dataset.tipe);
            }

            // Cascade: recalculate growth untuk triwulan-triwulan berikutnya
            // di tahun yang sama DAN semua triwulan di tahun-tahun berikutnya
            const cascadePeriodes = [];
            const periodeOrder = Array.isArray(window.REKON_PERIODE_ORDER)
                ? window.REKON_PERIODE_ORDER.map(v => parseInt(v, 10)).filter(v => !isNaN(v))
                : [];
            const tahunOrder = Array.isArray(window.REKON_TAHUN_ORDER)
                ? window.REKON_TAHUN_ORDER.map(v => parseInt(v, 10)).filter(v => !isNaN(v))
                : [];

            const currentPeriode = parseInt(input.dataset.idPeriode, 10);
            const currentTahun = parseInt(input.dataset.idTahun, 10);

            // Triwulan setelahnya di tahun yang sama
            const pidIdx = periodeOrder.indexOf(currentPeriode);
            for (let pi = pidIdx + 1; pi < periodeOrder.length; pi++) {
                cascadePeriodes.push({ tahun: currentTahun, periode: periodeOrder[pi] });
            }

            // Semua triwulan di tahun-tahun berikutnya
            const tidx = tahunOrder.indexOf(currentTahun);
            for (let ti = tidx + 1; ti < tahunOrder.length; ti++) {
                for (let pi = 0; pi < periodeOrder.length; pi++) {
                    cascadePeriodes.push({ tahun: tahunOrder[ti], periode: periodeOrder[pi] });
                }
            }

            // Panggil recalculateGrowth untuk setiap periode yang terpengaruh
            for (const cp of cascadePeriodes) {
                // Buat virtual input dgn data yang sama tapi tahun/periode berbeda
                const virtualInput = {
                    dataset: {
                        subKategori: input.dataset.subKategori,
                        kategori: input.dataset.kategori,
                        idWilayah: input.dataset.idWilayah,
                        group: input.dataset.group,
                        tipe: input.dataset.tipe,
                        periodeNama: input.dataset.periodeNama,
                        idTahun: String(cp.tahun),
                        idPeriode: String(cp.periode)
                    }
                };
                await recalculateGrowth(virtualInput, true);
            }

        } catch (err) {
            console.error(err);
            showNotification('Gagal menyimpan adjustment', 'error');
        } finally {
            hideProcessing();
            input.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    // Fungsi untuk inisialisasi input adjustment
    const hitungTotalKabKotaTimers = {};
    function debounceHitungTotalKabKota(idTahun, idPeriode, tipe) {
        const key = `${idTahun}-${idPeriode}-${tipe}`;
        if (hitungTotalKabKotaTimers[key]) clearTimeout(hitungTotalKabKotaTimers[key]);
        hitungTotalKabKotaTimers[key] = setTimeout(() => {
            hitungTotalKabKota(idTahun, idPeriode, tipe);
        }, 400); // 400ms debounce
    }

    function initAdjInputs(root = document) {
        const inputs = [];

        if (root instanceof Element && root.classList.contains('adj-input')) {
            inputs.push(root);
        }

        if (root.querySelectorAll) {
            root.querySelectorAll('.adj-input').forEach(input => inputs.push(input));
        }

        inputs.forEach(input => {
            if (input.dataset.lockBaseDisabled === undefined) {
                input.dataset.lockBaseDisabled = (input.disabled || input.readOnly) ? '1' : '0';
            }

            if (input.dataset.initialized === 'true') return;
            input.dataset.initialized = 'true';

            updateAdjInputClasses(input);
            input.dataset.lastValue = String(parseInputNumber(input.value));
            formatAdjInputDisplay(input);

            updatePdrbPlusAdjCell(input);

            input.addEventListener('input', () => {
                sanitizeDecimalInput(input);
                updateAdjInputClasses(input);

                if (input.calcTimeout) {
                    clearTimeout(input.calcTimeout);
                }

                input.calcTimeout = setTimeout(() => {
                    updatePdrbPlusAdjCell(input);

                    if (input.dataset.group === 'kabkota') {
                        debounceHitungTotalKabKota(
                            input.dataset.idTahun,
                            input.dataset.idPeriode,
                            input.dataset.tipe
                        );
                    }
                }, 200);
            });

            input.addEventListener('blur', () => {
                saveAdjValue(input).then(() => {
                    formatAdjInputDisplay(input);
                });
            });

            input.addEventListener('focus', () => {
                if (input.disabled || input.readOnly) return;
                const value = parseInputNumber(input.value);
                input.value = value ? value.toFixed(2) : '';
                input.classList.remove('bg-yellow-50', 'bg-white');
                input.classList.add('bg-blue-50');
            });

            input.addEventListener('keydown', e => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    input.blur();
                }
            });
        });
    }

    function updateLockUI(locked) {
        const btn = document.getElementById('lock-toggle-btn');
        if (btn) {
            btn.dataset.locked = locked ? '1' : '0';
            btn.classList.toggle('bg-red-600', locked);
            btn.classList.toggle('hover:bg-red-700', locked);
            btn.classList.toggle('bg-emerald-600', !locked);
            btn.classList.toggle('hover:bg-emerald-700', !locked);
            const label = btn.querySelector('[data-lock-label]');
            if (label) {
                label.textContent = locked ? 'Unlock Input' : 'Lock Input';
            }
        }

        const status = document.getElementById('lock-status');
        if (status) {
            status.classList.toggle('bg-red-50', locked);
            status.classList.toggle('border-red-200', locked);
            status.classList.toggle('text-red-700', locked);
            status.classList.toggle('bg-emerald-50', !locked);
            status.classList.toggle('border-emerald-200', !locked);
            status.classList.toggle('text-emerald-700', !locked);
            const text = status.querySelector('[data-lock-text]');
            if (text) {
                text.textContent = locked ? 'Locked' : 'Open';
            }
        }
    }

    function applyLockState(locked) {
        const isRecordLocked = !!locked;
        window.REKON_IS_RECORD_LOCKED = isRecordLocked;
        window.REKON_LOCKED_FOR_USER = isRecordLocked && window.ROLE_IS_KAB_KOTA;

        document.querySelectorAll('.adj-input').forEach(input => {
            const baseDisabled = input.dataset.lockBaseDisabled === '1';
            const shouldDisable = window.REKON_LOCKED_FOR_USER || baseDisabled;

            input.disabled = shouldDisable;
            input.readOnly = shouldDisable;
            input.classList.toggle('opacity-60', window.REKON_LOCKED_FOR_USER && !baseDisabled);
            input.classList.toggle('cursor-not-allowed', shouldDisable);

            if (shouldDisable) {
                input.classList.add('bg-gray-200');
                input.classList.remove('bg-white', 'bg-yellow-50', 'border-yellow-300');
            } else {
                input.classList.remove('bg-gray-200');
                updateAdjInputClasses(input);
            }
        });

        updateLockUI(isRecordLocked);
    }

    async function toggleLockState() {
        if (!window.REKON_LOCK_URL) return;
        const detail = window.REKON_DETAIL || {};
        if (!detail.id || !detail.type) return;

        const btn = document.getElementById('lock-toggle-btn');
        if (btn) btn.disabled = true;

        try {
            const res = await fetch(window.REKON_LOCK_URL, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': window.CSRF_TOKEN,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    type: detail.type,
                    id: detail.id,
                    pendekatan: window.REKON_PENDEKATAN
                })
            });

            const result = await res.json();
            if (!res.ok || result.status !== 'ok') {
                const msg = result?.error || result?.message || `HTTP ${res.status}`;
                showNotification(`Gagal mengubah lock: ${msg}`, 'error');
                return;
            }

            applyLockState(!!result.locked);
            showNotification(
                result.locked ? 'Halaman ini telah dikunci oleh Provinsi' : 'Halaman ini telah dibuka oleh Provinsi',
                result.locked ? 'info' : 'success'
            );
        } catch (e) {
            showNotification('Gagal mengubah lock', 'error');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    function handleRekonP1Update(payload) {
        if (!payload) return;

        if (payload.updated_by && window.REKON_USER_ID && parseInt(payload.updated_by, 10) === parseInt(window.REKON_USER_ID, 10)) {
            return;
        }

        const detail = window.REKON_DETAIL || {};
        if (payload.pendekatan && detail.pendekatan && payload.pendekatan !== detail.pendekatan) {
            return;
        }
        if (detail.type && payload.type && payload.type !== detail.type) {
            return;
        }
        if (detail.id && payload.entity_id && parseInt(payload.entity_id, 10) !== parseInt(detail.id, 10)) {
            return;
        }

        const parts = [
            `.adj-input[data-id-wilayah="${payload.id_wilayah}"]`,
            `[data-id-tahun="${payload.id_tahun}"]`,
            `[data-id-periode="${payload.id_periode}"]`,
            `[data-tipe="${payload.tipe}"]`
        ];

        if (payload.type === 'kategori') {
            parts.push(`[data-kategori="${payload.entity_id}"]`);
        } else {
            parts.push(`[data-sub-kategori="${payload.entity_id}"]`);
        }

        const input = document.querySelector(parts.join(''));

        // --- Fallback untuk Net Ekspor (input di-disabled, tidak bisa diquery via selector biasa) ---
        if (!input) {
            const tipe = payload.tipe;
            const wilayah = payload.id_wilayah;
            const tahun = payload.id_tahun;
            const periode = payload.id_periode;
            const adjVal = parseFloat(payload.adj);

            const cellId = `pdrb-plus-adj-${tipe}-kabkota-${wilayah}-${tahun}-${periode}`;
            const cell = document.getElementById(cellId);
            if (cell) {
                // Cari pdrb awal untuk menghitung nilai baru (PDRB + Adj)
                const pdrbCellId = `${tipe}-kabkota-${wilayah}-${tahun}-${periode}`;
                const pdrbCell = document.getElementById(pdrbCellId);
                const pdrbAwal = pdrbCell ? parseFloat(pdrbCell.dataset.value) || 0 : (parseFloat(cell.dataset.value) - (isNaN(adjVal) ? 0 : adjVal));

                const newPdrbPlusAdj = isNaN(adjVal) ? pdrbAwal : (pdrbAwal + adjVal);
                cell.dataset.value = String(newPdrbPlusAdj);
                cell.textContent = newPdrbPlusAdj !== 0
                    ? newPdrbPlusAdj.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    : '-';

                // Trigger update Total Kab/Kota agar aggregate ter-refresh
                hitungTotalKabKota(String(tahun), String(periode), tipe);
            }
            return;
        }

        if (document.activeElement === input) return;

        const adjVal = parseFloat(payload.adj);
        input.value = isNaN(adjVal) || adjVal === 0 ? '' : adjVal.toFixed(2);
        formatAdjInputDisplay(input);
        input.dataset.lastValue = String(isNaN(adjVal) ? 0 : adjVal);

        if (payload.history_id) {
            input.dataset.historyId = String(payload.history_id);
            if (input.dataset.target) {
                const target = document.getElementById(input.dataset.target);
                if (target) {
                    target.dataset.historyId = String(payload.history_id);
                    target.classList.add('cursor-pointer');
                    target.onclick = function() { showHistory(this); };
                }
            }
        }

        updateAdjInputClasses(input);
        updatePdrbPlusAdjCell(input);

        if (input.dataset.group === 'kabkota') {
            hitungTotalKabKota(input.dataset.idTahun, input.dataset.idPeriode, input.dataset.tipe);
        }

        recalculateGrowth(input, true);
    }

    function setSyncIndicator(state) {
        const indicator = document.getElementById('sync-indicator');
        if (!indicator) return;

        const dot = indicator.querySelector('[data-sync-dot]');
        const text = indicator.querySelector('[data-sync-text]');
        if (!dot || !text) return;

        indicator.classList.remove('border-gray-200', 'border-blue-200', 'border-green-200', 'border-yellow-200', 'border-red-200');
        dot.classList.remove('bg-gray-400', 'bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-red-500', 'animate-pulse');

        if (state === 'syncing') {
            indicator.classList.add('border-blue-200');
            dot.classList.add('bg-blue-500', 'animate-pulse');
            text.textContent = 'Syncing...';
            return;
        }

        if (state === 'paused') {
            indicator.classList.add('border-yellow-200');
            dot.classList.add('bg-yellow-500');
            text.textContent = 'Paused';
            return;
        }

        if (state === 'error') {
            indicator.classList.add('border-red-200');
            dot.classList.add('bg-red-500');
            text.textContent = 'Sync error';
            return;
        }

        indicator.classList.add('border-green-200');
        dot.classList.add('bg-green-500');
        text.textContent = 'Synced';
    }

    // Inisialisasi WebSocket menggunakan Laravel Echo
    function initWebSocketConnection(retries = 10) {
        if (!window.Echo) {
            if (retries > 0) {
                console.warn('Echo belum siap, mencoba lagi... (' + retries + ' tersisa)');
                setTimeout(() => initWebSocketConnection(retries - 1), 500);
                return;
            }
            console.error('Laravel Echo belum siap. Fallback ke indikator error.');
            setSyncIndicator('error');
            return;
        }

        // Simpan referensi instansi Echo ke window global
        window.EchoInstance = window.Echo;

        // Set status awal
        setSyncIndicator('syncing');

        // Menghubungkan ke channel rekon-p1
        const channel = window.Echo.channel('rekon-p1');

        // 1. Mendengarkan update data adjustment
        channel.listen('.rekon-p1.updated', (payload) => {
            console.log('Real-time data update received:', payload);
            handleRekonP1Update(payload);
        });

        // 2. Mendengarkan update status lock/unlock
        channel.listen('.rekon-p1.lock-updated', (payload) => {
            console.log('Real-time lock update received:', payload);

            const detail = window.REKON_DETAIL || {};
            if (
                payload.pendekatan === window.REKON_PENDEKATAN &&
                payload.type === detail.type &&
                parseInt(payload.entity_id, 10) === parseInt(detail.id, 10)
            ) {
                applyLockState(!!payload.locked);

                // Mencegah double notification: jangan tampilkan notifikasi dari Echo broadcast jika user ini yang menginisiasi aksi tersebut
                if (payload.user_id && window.REKON_USER_ID && parseInt(payload.user_id, 10) === parseInt(window.REKON_USER_ID, 10)) {
                    return;
                }

                showNotification(
                    payload.locked ? 'Halaman ini telah dikunci oleh Provinsi' : 'Halaman ini telah dibuka oleh Provinsi',
                    payload.locked ? 'info' : 'success'
                );
            }
        });

        // Event listener bawaan Pusher/Echo untuk status koneksi (agar indikator akurat)
        const pusherConnection = window.Echo.connector?.pusher?.connection;
        if (pusherConnection) {
            pusherConnection.bind('state_change', (states) => {
                // states.current bisa berupa: 'connecting', 'connected', 'disconnected', 'unavailable', 'failed'
                console.log('WS Connection state changed to:', states.current);
                if (states.current === 'connected') {
                    setSyncIndicator('synced');
                } else if (states.current === 'connecting') {
                    setSyncIndicator('syncing');
                } else if (states.current === 'unavailable' || states.current === 'failed') {
                    setSyncIndicator('error');
                } else {
                    setSyncIndicator('paused');
                }
            });

            // Set status sukses jika sudah terkoneksi sejak awal
            if (pusherConnection.state === 'connected') {
                setSyncIndicator('synced');
            }
        } else {
            // Fallback jika pusher connection tidak terdeteksi langsung
            setSyncIndicator('synced');
        }
    }

    // Event listener ketika DOM siap
    document.addEventListener('DOMContentLoaded', () => {
        initAdjInputs();
        initGrowthBaseValues();
        initCtocBaseValues();
        initLajuBaseValuesForTotal();

        resetTotalKabStatusVisuals();
        applySignRowColors();
        applyAllTotalKabGrowthAlerts();
        applyLockState(window.REKON_IS_RECORD_LOCKED);
        @if(config('broadcasting.default') === 'reverb')
        initWebSocketConnection();
        @else
        // WebSocket dinonaktifkan (BROADCAST_CONNECTION={{ config('broadcasting.default') }})
        setSyncIndicator('synced');
        @endif

        const lockBtn = document.getElementById('lock-toggle-btn');
        if (lockBtn) {
            lockBtn.addEventListener('click', toggleLockState);
        }

        const observer = new MutationObserver(mutations => {
            let hasNewInputs = false;
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) {
                        if (node.classList.contains('adj-input') || node.querySelector('.adj-input')) {
                            hasNewInputs = true;
                        }
                    }
                });
            });

            if (hasNewInputs) {
                if (mutationDebounceTimer) clearTimeout(mutationDebounceTimer);
                mutationDebounceTimer = setTimeout(() => {
                    initAdjInputs(document);
                    applyLockState(window.REKON_IS_RECORD_LOCKED);
                }, 500);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });




</script>

<script>
    function initializeSpecialRowColors() {
        const reapply = () => {
            document.querySelectorAll('tr').forEach(row => {
                const firstTd = row.querySelector('td:first-child');
                if (!firstTd) return;

                const text = firstTd.textContent.toUpperCase();
                const isProvince = text.includes('PROVINSI') || !!row.querySelector('[id*="-provinsi-"]');
                const isTotalKab = !isProvince && (text.includes('TOTAL KABUPATEN/KOTA') || !!row.querySelector('[id*="-total-"]'));

                if (isProvince || isTotalKab) {
                    row.classList.add(isProvince ? 'row-provinsi' : 'row-total-kabkota');
                    row.querySelectorAll('td').forEach(td => {
                        if (td.classList.contains('bg-white')) {
                            td.classList.remove('bg-white');
                        }
                    });
                }
            });
        };

        reapply();
        setTimeout(reapply, 500);
        setTimeout(reapply, 1500);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeSpecialRowColors);
    } else {
        initializeSpecialRowColors();
    }
</script>