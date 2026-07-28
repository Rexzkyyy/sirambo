// Premium Tooltip Styles and logic are moved here for better performance and caching.
// This file contains the logic for PDRB Rekonsiliasi Detail page.

(function() {
    // Performance Optimization: Persistent Formatters
    const numberFormatter = new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    const percentFormatter = new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    // Global Functions for the page
    window.getDiffCategory = function(baseValue, adjValue) {
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
    };

    window.getBgByCategory = function(category) {
        if (!category) return '';
        if (category.includes('extreme') || category.includes('beda_arah')) return 'bg-red-strong';
        return '';
    };

    window.getStatusLabel = function(category) {
        if (category === 'extreme_beda_arah') return 'Extreme Beda Arah';
        if (category === 'extreme') return 'Extreme';
        if (category === 'beda_arah') return 'Beda Arah';
        return '';
    };

    window.getStatusLabelForCell = function(status) {
        return status || '';
    };

    window.shouldApplyTotalKabGrowthAlert = function() {
        return window.REKON_YOY_TOTAL_KAB_ALERT_ENABLED && window.REKON_PENDEKATAN === 'pengeluaran';
    };

    window.shouldApplyKabGrowthAlert = function() {
        return window.REKON_KAB_GROWTH_ALERT_ENABLED && window.REKON_PENDEKATAN === 'pengeluaran';
    };

    window.isOppositeDirection = function(a, b) {
        return (a > 0 && b < 0) || (a < 0 && b > 0);
    };

    window.parseGrowthNumber = function(val) {
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
    };

    window.getCellGrowthValue = function(cell) {
        if (!cell) return NaN;
        const raw = cell.dataset ? cell.dataset.value : null;
        let num = window.parseGrowthNumber(raw);
        if (Number.isFinite(num)) return num;
        const text = cell.textContent || '';
        return window.parseGrowthNumber(text);
    };

    window.applyKabGrowthAlertCell = function(cell, provCell, origCell = null) {
        if (!cell) return;
        const clearAlert = () => {
            if (cell.classList.contains('kab-growth-alert')) {
                cell.classList.remove('kab-growth-alert', 'bg-red-strong', 'bg-orange-strong', 'bg-yellow-strong');
                if (cell.dataset.kabAlertApplied === '1') {
                    const baseTitle = cell.dataset.kabAlertBaseTitle ?? '';
                    if (baseTitle) cell.title = baseTitle;
                    else cell.removeAttribute('title');
                    delete cell.dataset.kabAlertApplied;
                }
            }
        };

        if (!window.shouldApplyKabGrowthAlert()) {
            clearAlert();
            return;
        }

        const value = window.getCellGrowthValue(cell);
        const prov = window.getCellGrowthValue(provCell);
        if (!Number.isFinite(value) || !Number.isFinite(prov)) {
            clearAlert();
            return;
        }

        const diffProv = Math.abs(value - prov);
        const isBedaArahProv = window.isOppositeDirection(value, prov);
        let isInternalBedaArah = false;
        let isInternalExtremeGrowth = false;
        let growthDiffInternal = 0;

        if (origCell) {
            const origVal = window.getCellGrowthValue(origCell);
            if (Number.isFinite(origVal)) {
                isInternalBedaArah = window.isOppositeDirection(value, origVal);
                growthDiffInternal = Math.abs(value - origVal);
                if (growthDiffInternal > 4) isInternalExtremeGrowth = true;
            }
        }

        let isExtremeAdj = false;
        let adjPercent = 0;
        const cellId = cell.id || '';
        const matchReg = cellId.match(/-kabkota-(\d+)-(\d+)-(\d+)$/);
        if (matchReg) {
            const adjInputId = `adj-${matchReg[1]}-${matchReg[2]}-${matchReg[3]}`;
            const adjInput = document.getElementById(adjInputId);
            if (adjInput) {
                const pdrb = parseFloat(adjInput.dataset.pdrb) || 0;
                const adj = window.parseInputNumber(adjInput.value);
                adjPercent = pdrb !== 0 ? Math.abs(adj / pdrb) * 100 : 0;
                if (adjPercent > 4) isExtremeAdj = true;
            }
        }

        const isExtremeBedaArahProv = (diffProv > 4 && isBedaArahProv);
        const isRed = isExtremeAdj || isInternalBedaArah || isInternalExtremeGrowth || isExtremeBedaArahProv;

        if (isRed || diffProv > 4 || isBedaArahProv) {
            cell.classList.remove('sign-positive', 'sign-negative', 'bg-white', 'bg-gray-50', 'bg-yellow-50', 'bg-green-soft', 'bg-orange-soft', 'hover:bg-gray-50', 'hover:bg-yellow-100');
            delete cell.dataset.status;
            if (cell.dataset.kabAlertBaseTitle === undefined) cell.dataset.kabAlertBaseTitle = cell.getAttribute('title') ?? '';
            let baseTitle = (cell.dataset.kabAlertBaseTitle ?? '').replace(/\s*\|\s*Status:.*$/, '').replace(/^Status:.*$/, '').trim();
            let reason = '';
            if (isRed) {
                cell.classList.remove('bg-orange-strong', 'bg-yellow-strong');
                cell.classList.add('kab-growth-alert', 'bg-red-strong');
                const reasons = ['Extreme'];
                if (isInternalBedaArah) reasons.push('Beda Arah PDRB');
                if (isInternalExtremeGrowth) reasons.push(`Extreme Pertumbuhan (${growthDiffInternal.toFixed(2)}% vs Orig)`);
                if (isExtremeAdj) reasons.push(`Extreme Adjustment (${adjPercent.toFixed(2)}%)`);
                if (isExtremeBedaArahProv) reasons.push('Extreme Beda Arah Provinsi');
                reason = [...new Set(reasons)].join(' | ');
            } else if (diffProv > 4) {
                cell.classList.remove('bg-red-strong', 'bg-yellow-strong');
                cell.classList.add('kab-growth-alert', 'bg-orange-strong');
                reason = '+4% Provinsi';
            } else if (isBedaArahProv) {
                cell.classList.remove('bg-red-strong', 'bg-orange-strong');
                cell.classList.add('kab-growth-alert', 'bg-yellow-strong');
                reason = 'Beda Arah dari Provinsi';
            }
            cell.title = baseTitle ? `${baseTitle} | ${reason}` : reason;
            cell.dataset.kabAlertApplied = '1';
            return;
        }
        clearAlert();
    };

    window.applyKabkotaGrowthAlerts = function() {
        if (!window.shouldApplyKabGrowthAlert()) {
            document.querySelectorAll('.kab-growth-alert').forEach(cell => {
                cell.classList.remove('kab-growth-alert', 'bg-red-strong', 'bg-orange-strong');
            });
            return;
        }
        const defs = [
            { selector: '[id^="qoq-konstan-kabkota-"]', regex: /^qoq-konstan-kabkota-(\d+)-(\d+)-(\d+)$/, prov: m => `qoq-konstan-provinsi-${m[2]}-${m[3]}` },
            { selector: '[id^="qoq-konstan-plus-adj-kabkota-"]', regex: /^qoq-konstan-plus-adj-kabkota-(\d+)-(\d+)-(\d+)$/, prov: m => `qoq-konstan-plus-adj-provinsi-${m[2]}-${m[3]}`, orig: m => `qoq-konstan-kabkota-${m[1]}-${m[2]}-${m[3]}` },
            { selector: '[id^="yoy-konstan-kabkota-"]', regex: /^yoy-konstan-kabkota-(\d+)-(\d+)-(\d+)$/, prov: m => `yoy-konstan-provinsi-${m[2]}-${m[3]}` },
            { selector: '[id^="yoy-konstan-plus-adj-kabkota-"]', regex: /^yoy-konstan-plus-adj-kabkota-(\d+)-(\d+)-(\d+)$/, prov: m => `yoy-konstan-plus-adj-provinsi-${m[2]}-${m[3]}`, orig: m => `yoy-konstan-kabkota-${m[1]}-${m[2]}-${m[3]}` },
            { selector: '[id^="ctoc-konstan-kabkota-"]', regex: /^ctoc-konstan-kabkota-(\d+)-(\d+)-(\d+)$/, prov: m => `ctoc-konstan-provinsi-${m[2]}-${m[3]}` },
            { selector: '[id^="ctoc-konstan-plus-adj-kabkota-"]', regex: /^ctoc-konstan-plus-adj-kabkota-(\d+)-(\d+)-(\d+)$/, prov: m => `ctoc-konstan-plus-adj-provinsi-${m[2]}-${m[3]}`, orig: m => `ctoc-konstan-kabkota-${m[1]}-${m[2]}-${m[3]}` },
            { selector: '[id^="laju-berlaku-kabkota-"]', regex: /^laju-berlaku-kabkota-(\d+)-(\d+)-(\d+)$/, prov: m => `laju-berlaku-provinsi-${m[2]}-${m[3]}` },
            { selector: '[id^="laju-berlaku-plus-adj-kabkota-"]', regex: /^laju-berlaku-plus-adj-kabkota-(\d+)-(\d+)-(\d+)$/, prov: m => `laju-berlaku-plus-adj-provinsi-${m[2]}-${m[3]}`, orig: m => `laju-berlaku-kabkota-${m[1]}-${m[2]}-${m[3]}` },
            { selector: '[id^="qoq-konstan-kabkota-total-"]', regex: /^qoq-konstan-kabkota-total-(\d+)-(\d+)$/, prov: m => `provinsi-total-qoq-konstan-${m[2]}` },
            { selector: '[id^="qoq-konstan-plus-adj-kabkota-total-"]', regex: /^qoq-konstan-plus-adj-kabkota-total-(\d+)-(\d+)$/, prov: m => `provinsi-total-qoq-konstan-plus-adj-${m[2]}`, orig: m => `qoq-konstan-kabkota-total-${m[1]}-${m[2]}` },
            { selector: '[id^="yoy-konstan-kabkota-total-"]', regex: /^yoy-konstan-kabkota-total-(\d+)-(\d+)$/, prov: m => `provinsi-total-yoy-konstan-${m[2]}` },
            { selector: '[id^="yoy-konstan-plus-adj-kabkota-total-"]', regex: /^yoy-konstan-plus-adj-kabkota-total-(\d+)-(\d+)$/, prov: m => `provinsi-total-yoy-konstan-plus-adj-${m[2]}`, orig: m => `yoy-konstan-kabkota-total-${m[1]}-${m[2]}` },
            { selector: '[id^="ctoc-konstan-kabkota-total-"]', regex: /^ctoc-konstan-kabkota-total-(\d+)-(\d+)$/, prov: m => `provinsi-total-ctoc-konstan-${m[2]}` },
            { selector: '[id^="ctoc-konstan-plus-adj-kabkota-total-"]', regex: /^ctoc-konstan-plus-adj-kabkota-total-(\d+)-(\d+)$/, prov: m => `provinsi-total-ctoc-konstan-plus-adj-${m[2]}`, orig: m => `ctoc-konstan-kabkota-total-${m[1]}-${m[2]}` },
            { selector: '[id^="laju-berlaku-kabkota-total-"]', regex: /^laju-berlaku-kabkota-total-(\d+)-(\d+)$/, prov: m => `provinsi-total-laju-berlaku-${m[2]}` },
            { selector: '[id^="laju-berlaku-plus-adj-kabkota-total-"]', regex: /^laju-berlaku-plus-adj-kabkota-total-(\d+)-(\d+)$/, prov: m => `provinsi-total-laju-berlaku-plus-adj-${m[2]}`, orig: m => `laju-berlaku-kabkota-total-${m[1]}-${m[2]}` }
        ];
        defs.forEach(def => {
            document.querySelectorAll(def.selector).forEach(cell => {
                const match = cell.id.match(def.regex);
                if (!match) return;
                const provCell = document.getElementById(def.prov(match));
                const originalCell = def.orig ? document.getElementById(def.orig(match)) : null;
                window.applyKabGrowthAlertCell(cell, provCell, originalCell);
            });
        });
    };

    window.getPeriodeRank = function(periodeId) {
        const order = Array.isArray(window.REKON_PERIODE_ORDER) ? window.REKON_PERIODE_ORDER.map(v => parseInt(v, 10)).filter(v => !isNaN(v)) : [];
        const idx = order.indexOf(parseInt(periodeId, 10));
        return idx >= 0 ? idx + 1 : 0;
    };

    window.getRunningPeriodeId = function() {
        const order = Array.isArray(window.REKON_PERIODE_ORDER) ? window.REKON_PERIODE_ORDER.map(v => parseInt(v, 10)).filter(v => !isNaN(v)) : [];
        const running = parseInt(window.REKON_RUNNING_QUARTER, 10);
        if (!running || running < 1) return null;
        return order[running - 1] ?? null;
    };

    window.getTotalKabGrowthThreshold = function(metric, rank, tahunId) {
        return 1.0;
    };

    window.applyTotalKabGrowthAlertCell = function(cell, tahunId, periodeId, metric, provCell) {
        if (!cell) return;
        const rank = window.getPeriodeRank(periodeId);
        const clearAlert = () => {
            const hadAlert = cell.classList.contains('bg-red-strong') || cell.classList.contains('bg-orange-strong');
            cell.classList.remove('bg-red-strong', 'bg-orange-strong');
            if (hadAlert) window.applySignRowColors(cell.closest('tr') ?? document);
        };
        if (!window.shouldApplyTotalKabGrowthAlert()) {
            clearAlert();
            return;
        }
        const value = window.parseGrowthNumber(cell.dataset.value);
        const prov = provCell ? window.getCellGrowthValue(provCell) : NaN;
        if (!Number.isFinite(value) || !Number.isFinite(prov)) {
            clearAlert();
            return;
        }
        const absVal = Math.abs(value - prov);
        if (absVal <= 0.001) {
            clearAlert();
            return;
        }
        const threshold = window.getTotalKabGrowthThreshold(metric, rank, tahunId);
        if (threshold === null) {
            clearAlert();
            return;
        }
        if (absVal > threshold) {
            cell.classList.remove('sign-positive', 'sign-negative', 'bg-white', 'bg-gray-50', 'bg-yellow-50', 'bg-green-soft', 'bg-orange-soft', 'hover:bg-gray-50', 'hover:bg-yellow-100');
            cell.classList.add('bg-red-strong');
            const reason = `Extreme | ${metric.toUpperCase()} melebihi batas (${threshold}%)`;
            if (cell.dataset.signBaseTitle === undefined) cell.dataset.signBaseTitle = cell.getAttribute('title') ?? '';
            const baseTitle = cell.dataset.signBaseTitle ?? '';
            cell.title = baseTitle ? `${baseTitle} | ${reason}` : reason;
            return;
        }
        clearAlert();
    };

    let kabGrowthAlertTimeout = null;
    window.debounceApplyAllTotalKabGrowthAlerts = function() {
        if (kabGrowthAlertTimeout) clearTimeout(kabGrowthAlertTimeout);
        kabGrowthAlertTimeout = setTimeout(window.applyAllTotalKabGrowthAlerts, 150);
    };

    window.applyAllTotalKabGrowthAlerts = function() {
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
                    const runningPeriodeId = window.getRunningPeriodeId();
                    if (!runningPeriodeId) return;
                    window.applyTotalKabGrowthAlertCell(cell, parseInt(match[1], 10), runningPeriodeId, def.metric, provCell);
                    return;
                }
                if (!match[2]) return;
                window.applyTotalKabGrowthAlertCell(cell, parseInt(match[1], 10), parseInt(match[2], 10), def.metric, provCell);
            });
        });
        window.applyKabkotaGrowthAlerts();
    };

    window.resetTotalKabStatusVisuals = function() {
        const selectors = ['[id^="qoq-konstan-total-"]', '[id^="qoq-konstan-plus-adj-total-"]', '[id^="yoy-konstan-total-"]', '[id^="yoy-konstan-plus-adj-total-"]', '[id^="ctoc-konstan-total-"]', '[id^="ctoc-konstan-plus-adj-total-"]', '[id^="laju-berlaku-total-"]', '[id^="laju-berlaku-plus-adj-total-"]', '[id^="indeks-berlaku-plus-adj-total-"]'];
        selectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(cell => {
                delete cell.dataset.status;
                cell.classList.remove('bg-red-strong', 'bg-yellow-strong', 'bg-orange-strong');
                cell.title = '';
            });
        });
        window.applySignRowColors();
    };

    window.formatNumber = function(num, isPercent = false) {
        if (num === null || num === undefined || isNaN(num)) return '-';
        const formatted = numberFormatter.format(num);
        return isPercent ? formatted + '%' : formatted;
    };

    window.showNotification = function(message, type = 'info') {
        const notificationArea = document.getElementById('notification-area');
        if (!notificationArea) return;
        const alertClass = type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : type === 'error' ? 'bg-red-50 border-red-200 text-red-800' : type === 'warning' ? 'bg-yellow-50 border-yellow-200 text-yellow-800' : 'bg-blue-50 border-blue-200 text-blue-800';
        const icon = type === 'success' ? 'OK' : type === 'error' ? 'X' : type === 'warning' ? '!' : 'i';
        const notification = document.createElement('div');
        notification.className = `mb-3 p-3 border rounded-lg ${alertClass} animate-fade-in`;
        notification.innerHTML = `<div class="flex items-center"><span class="font-bold mr-2">${icon}</span><span>${message}</span><button class="ml-auto text-gray-500 hover:text-gray-700" onclick="this.parentElement.parentElement.remove()"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button></div>`;
        notificationArea.appendChild(notification);
        if (type === 'success') setTimeout(() => { if (notification.parentElement) notification.remove(); }, 3000);
    };

    window.showProcessing = function(message = 'Memproses...') {
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
    };

    window.hideProcessing = function() {
        document.getElementById('processing-alert')?.remove();
    };

    window.updateAdjInputClasses = function(input) {
        if (input.disabled || input.readOnly) return;
        const value = window.parseInputNumber(input.value);
        if (!value) {
            input.classList.add('bg-yellow-50', 'border-yellow-300');
            input.classList.remove('bg-white');
        } else {
            input.classList.remove('bg-yellow-50', 'border-yellow-300');
            input.classList.add('bg-white');
        }
    };

    window.parseInputNumber = function(value) {
        if (value === null || value === undefined) return 0;
        let str = String(value).trim().replace(/\s+/g, '');
        if (!str) return 0;
        if (str.includes('.') && str.includes(',')) str = str.replace(/\./g, '').replace(',', '.');
        else if (str.includes(',')) str = str.replace(',', '.');
        str = str.replace(/[^0-9.-]/g, '');
        const num = parseFloat(str);
        return isNaN(num) ? 0 : num;
    };

    window.formatAdjInputDisplay = function(input) {
        const value = window.parseInputNumber(input.value);
        if (!value) { input.value = ''; return; }
        input.value = numberFormatter.format(value);
    };

    window.applySignRowColors = function(root = document) {
        let rows = [];
        if (root.matches && root.matches('tr[data-sign-row="1"]')) rows = [root];
        else if (root.querySelectorAll) rows = root.querySelectorAll('tr[data-sign-row="1"]');
        rows.forEach(row => {
            const firstTd = row.querySelector('td:first-child');
            const rowText = firstTd ? firstTd.textContent.toUpperCase() : '';
            const isProvince = row.classList.contains('row-provinsi') || rowText.includes('PROVINSI') || !!row.querySelector('[id*="-provinsi-"]');
            const isTotalKab = row.classList.contains('row-total-kabkota') || (!isProvince && (rowText.includes('TOTAL KABUPATEN/KOTA') || !!row.querySelector('[id*="-total-"]')));
            row.querySelectorAll('td[data-value]').forEach(cell => {
                const isRed = cell.classList.contains('bg-red-strong');
                const isYellow = cell.classList.contains('bg-yellow-strong');
                const isOrangeStrong = cell.classList.contains('bg-orange-strong');
                if ((isRed || isYellow || isOrangeStrong) && isTotalKab) {
                    const value = window.getCellGrowthValue(cell);
                    if (cell.dataset.signBaseTitle === undefined) cell.dataset.signBaseTitle = cell.getAttribute('title') ?? '';
                    const baseTitle = cell.dataset.signBaseTitle ?? '';
                    let reason = '';
                    if (isRed || isOrangeStrong) {
                        if (cell.title.includes('melebihi batas')) reason = cell.title.split('|').pop().trim();
                        else { const threshold = (window.REKON_PENDEKATAN === 'pengeluaran') ? 4 : 5; reason = (isRed ? 'Diskrepansi' : 'Alert') + ` > ${threshold}%`; }
                    } else if (isYellow) reason = 'Beda Arah';
                    const signReason = value > 0 ? 'Nilai Positif' : (value < 0 ? 'Nilai Negatif' : '');
                    cell.title = baseTitle ? `${baseTitle} | ${reason}${signReason ? ' & ' + signReason : ''}` : `${reason}${signReason ? ' & ' + signReason : ''}`;
                    return;
                }
                if (isRed || isYellow || isOrangeStrong) return;
                const isStatusCell = cell.classList.contains('status-cell');
                const value = window.getCellGrowthValue(cell);
                cell.classList.remove('bg-white', 'bg-gray-50', 'bg-yellow-50', 'bg-yellow-100', 'bg-yellow-200', 'bg-red-200', 'bg-red-strong', 'bg-yellow-strong', 'bg-orange-strong', 'bg-green-soft', 'bg-orange-soft', 'hover:bg-gray-50', 'hover:bg-yellow-100', 'sign-positive', 'sign-negative');
                if (cell.dataset.signBaseTitle === undefined) cell.dataset.signBaseTitle = cell.getAttribute('title') ?? '';
                if (!isNaN(value) && value !== 0) {
                    if (value > 0) {
                        cell.classList.add('bg-green-soft');
                        if (!isStatusCell) {
                            const baseTitle = cell.dataset.signBaseTitle ?? '';
                            cell.title = baseTitle ? `${baseTitle} | Nilai positif` : 'Nilai positif';
                        }
                    } else if (value < 0) {
                        cell.classList.add('bg-orange-soft');
                        if (!isStatusCell) {
                            const baseTitle = cell.dataset.signBaseTitle ?? '';
                            cell.title = baseTitle ? `${baseTitle} | Nilai negatif` : 'Nilai negatif';
                        }
                    }
                } else {
                    if (!isProvince && !isTotalKab) cell.classList.add('bg-white');
                    if (!isStatusCell) {
                        const baseTitle = cell.dataset.signBaseTitle ?? '';
                        if (baseTitle) cell.title = baseTitle;
                        else cell.removeAttribute('title');
                    }
                }
            });
        });
    };

    window.updatePdrbPlusAdjCell = function(input) {
        const pdrb = parseFloat(input.dataset.pdrb) || 0;
        const adj = window.parseInputNumber(input.value);
        const total = pdrb + adj;
        if (input.dataset.target) {
            const target = document.getElementById(input.dataset.target);
            if (target) {
                target.innerText = window.formatNumber(total);
                target.dataset.value = total;
                window.applySignRowColors(target.closest('tr') ?? document);
            }
        }
        if (input.dataset.group === 'total-kabkota') window.updateTotalIndeksLaju(input.dataset.idTahun, input.dataset.idPeriode);
        else if (input.dataset.group === 'kabkota') window.updateKabkotaAnnualTotal(input.dataset.idWilayah, input.dataset.idTahun, input.dataset.tipe);
    };

    window.ensureHistoryModal = function() {
        let modal = document.getElementById('history-modal');
        if (modal) return modal;
        modal = document.createElement('div');
        modal.id = 'history-modal';
        modal.className = 'fixed inset-0 z-[60] hidden';
        modal.innerHTML = `<div class="absolute inset-0 bg-black/50" data-history-close></div><div class="relative mx-auto mt-16 w-[92%] max-w-2xl rounded-lg bg-white shadow-lg"><div class="flex items-center justify-between border-b border-gray-200 px-4 py-3"><h3 id="history-modal-title" class="text-sm font-semibold md:text-base">Histori Perubahan</h3><button type="button" class="text-gray-500 hover:text-gray-700" data-history-close><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button></div><div id="history-modal-body" class="px-4 py-4 text-xs md:text-sm text-gray-700"></div></div>`;
        document.body.appendChild(modal);
        const close = () => { modal.classList.add('hidden'); document.body.classList.remove('overflow-hidden'); };
        modal.querySelectorAll('[data-history-close]').forEach(btn => btn.addEventListener('click', close));
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.classList.contains('hidden')) close(); });
        return modal;
    };

    window.openHistoryModal = function() {
        const modal = window.ensureHistoryModal();
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        return modal;
    };

    window.showHistory = function(el) {
        const id = el.dataset.historyId;
        if (!id) { window.showNotification('Belum ada histori', 'warning'); return; }
        const historyType = el.dataset.historyType || 'subkategori';
        const url = window.REKON_HISTORY_URL.replace('__ID__', id) + (historyType === 'kategori' ? '?type=kategori' : '');
        const modal = window.openHistoryModal();
        const body = modal.querySelector('#history-modal-body');
        const title = modal.querySelector('#history-modal-title');
        title.textContent = 'Histori Perubahan';
        body.innerHTML = '<div class="text-gray-500">Memuat data...</div>';
        fetch(url).then(r => r.json()).then(data => {
            if (!data.length) { body.innerHTML = '<div class="text-gray-500">Belum ada histori.</div>'; return; }
            const rows = data.slice(0, 10).map(row => `<tr class="border-b last:border-b-0"><td class="py-2 pr-3">${row.name ?? 'SYSTEM'}</td><td class="py-2 pr-3 whitespace-nowrap">${row.created_at ?? '-'}</td><td class="py-2 text-right whitespace-nowrap">${window.formatNumber(row.nilai_adj)}</td><td class="py-2 text-right whitespace-nowrap">${window.formatNumber(row.nilai_baru)}</td></tr>`).join('');
            body.innerHTML = `<div class="max-h-[60vh] overflow-auto"><table class="min-w-full text-xs md:text-sm"><thead class="sticky top-0 bg-gray-50 text-gray-600"><tr><th class="py-2 text-left font-semibold">User</th><th class="py-2 text-left font-semibold">Waktu</th><th class="py-2 text-right font-semibold">Nilai Adj</th><th class="py-2 text-right font-semibold">Nilai Baru</th></tr></thead><tbody>${rows}</tbody></table></div>`;
        }).catch(() => { body.innerHTML = '<div class="text-red-600">Gagal mengambil data histori.</div>'; });
    };

    window.hitungTotalKabKota = function(idTahun, idPeriode, tipe) {
        let totalAdj = 0;
        let totalPdrb = 0;
        document.querySelectorAll(`.adj-kabkota[data-id-tahun="${idTahun}"][data-id-periode="${idPeriode}"][data-tipe="${tipe}"]`).forEach(input => {
            totalAdj += window.parseInputNumber(input.value);
            totalPdrb += parseFloat(input.dataset.pdrb) || 0;
        });
        const totalInput = document.getElementById(`adj-${tipe}-total-${idTahun}-${idPeriode}`);
        if (totalInput) totalInput.value = totalAdj ? window.formatNumber(totalAdj) : '';
        const totalValue = totalPdrb + totalAdj;
        const totalCell = document.getElementById(`pdrb-plus-adj-${tipe}-total-${idTahun}-${idPeriode}`);
        if (totalCell) {
            totalCell.innerText = window.formatNumber(totalValue);
            totalCell.dataset.value = totalValue;
            window.applySignRowColors(totalCell.closest('tr') ?? document);
        }
        const getPeriodeIdsForTotal = () => {
            const ids = new Set();
            const re = new RegExp(`^pdrb-plus-adj-${tipe}-total-${idTahun}-(\\d+)$`);
            document.querySelectorAll(`[id^="pdrb-plus-adj-${tipe}-total-${idTahun}-"]`).forEach(el => {
                const match = el.id.match(re);
                if (match && match[1]) ids.add(parseInt(match[1], 10));
            });
            return Array.from(ids).sort((a, b) => a - b);
        };
        let totalTahunan = 0;
        const pids = getPeriodeIdsForTotal();
        if (pids.length) pids.forEach(pid => {
            const c = document.getElementById(`pdrb-plus-adj-${tipe}-total-${idTahun}-${pid}`);
            totalTahunan += c ? (parseFloat(c.dataset.value) || 0) : 0;
        });
        else totalTahunan = totalValue;
        document.querySelectorAll(`#pdrb-plus-adj-${tipe}-total-kab-total-${idTahun}`).forEach(cell => {
            cell.innerText = window.formatNumber(totalTahunan);
            cell.dataset.value = totalTahunan;
            window.applySignRowColors(cell.closest('tr') ?? document);
        });
        window.updateDiskrepansiNilai(idTahun, idPeriode);
        window.updateDiskrepansiPersen(idTahun, idPeriode);
        window.updateTotalGrowth(idTahun, idPeriode);
        window.updateTotalIndeksLaju(idTahun, idPeriode);
        const getAllPids = (tId) => {
            const ids = new Set();
            const re = new RegExp(`^pdrb-plus-adj-konstan-total-${tId}-(\\d+)$`);
            document.querySelectorAll(`[id^="pdrb-plus-adj-konstan-total-${tId}-"]`).forEach(el => {
                const match = el.id.match(re);
                if (match && match[1]) ids.add(parseInt(match[1], 10));
            });
            return Array.from(ids).sort((a, b) => a - b);
        };
        const samePids = getAllPids(idTahun);
        const cIdx = samePids.indexOf(parseInt(idPeriode, 10));
        for (let p = cIdx + 1; p < samePids.length; p++) {
            window.updateTotalGrowth(idTahun, samePids[p]);
            window.updateTotalIndeksLaju(idTahun, samePids[p]);
        }
        const order = Array.isArray(window.REKON_TAHUN_ORDER) ? window.REKON_TAHUN_ORDER.map(v => parseInt(v, 10)).filter(v => !isNaN(v)) : [];
        const idx = order.indexOf(parseInt(idTahun, 10));
        for (let i = idx + 1; i < order.length; i++) {
            const yearPids = getAllPids(order[i]);
            yearPids.forEach(pid => {
                window.updateTotalGrowth(order[i], pid);
                window.updateTotalIndeksLaju(order[i], pid);
            });
        }
    };

    window.updateKabkotaAnnualTotal = function(idWilayah, idTahun, tipe) {
        let totalPdrb = 0;
        let totalAdj = 0;
        const order = Array.isArray(window.REKON_PERIODE_ORDER) ? window.REKON_PERIODE_ORDER.map(v => parseInt(v, 10)).filter(v => !isNaN(v)) : [];
        order.forEach(pid => {
            const cell = document.getElementById(`pdrb-plus-adj-${tipe}-kabkota-${idWilayah}-${idTahun}-${pid}`);
            if (cell) totalPdrb += parseFloat(cell.dataset.value) || 0;
            const adjInput = document.querySelector(`.adj-kabkota[data-id-wilayah="${idWilayah}"][data-id-tahun="${idTahun}"][data-id-periode="${pid}"][data-tipe="${tipe}"]`);
            if (adjInput) totalAdj += window.parseInputNumber(adjInput.value);
        });
        const totalCell = document.getElementById(`pdrb-plus-adj-${tipe}-kabkota-total-${idWilayah}-${idTahun}`);
        if (totalCell) {
            totalCell.innerText = window.formatNumber(totalPdrb);
            totalCell.dataset.value = totalPdrb;
            window.applySignRowColors(totalCell.closest('tr') ?? document);
        }
        const adjTotalInput = document.getElementById(`adj-${tipe}-kabkota-total-${idWilayah}-${idTahun}`);
        if (adjTotalInput) adjTotalInput.value = totalAdj ? window.formatNumber(totalAdj) : '';
        window.updateKabkotaAnnualGrowth(idWilayah, idTahun);
        window.updateKabkotaAnnualIndeksLaju(idWilayah, idTahun);
    };

    window.updateKabkotaAnnualGrowth = function(idWilayah, idTahun) {
        const order = Array.isArray(window.REKON_TAHUN_ORDER) ? window.REKON_TAHUN_ORDER.map(v => parseInt(v, 10)).filter(v => !isNaN(v)) : [];
        const idx = order.indexOf(parseInt(idTahun, 10));
        if (idx < 0) return;
        for (let i = idx; i < order.length; i++) {
            const targetYearId = order[i];
            const currentIdx = order.indexOf(parseInt(targetYearId, 10));
            const prevYearId = currentIdx > 0 ? order[currentIdx - 1] : null;
            const currentTotalCell = document.getElementById(`pdrb-plus-adj-konstan-kabkota-total-${idWilayah}-${targetYearId}`);
            const currentBaseTotalCell = document.getElementById(`konstan-kabkota-total-${idWilayah}-${targetYearId}`);
            if (!currentTotalCell || !currentBaseTotalCell) continue;
            const currentValAdj = parseFloat(currentTotalCell.dataset.value) || 0;
            const currentValBase = parseFloat(currentBaseTotalCell.dataset.value) || 0;
            const prevTotalCell = prevYearId ? document.getElementById(`pdrb-plus-adj-konstan-kabkota-total-${idWilayah}-${prevYearId}`) : null;
            const prevBaseTotalCell = prevYearId ? document.getElementById(`konstan-kabkota-total-${idWilayah}-${prevYearId}`) : null;
            const metrics = [{ id: 'qoq', base: 'qoq-konstan', adj: 'qoq-konstan-plus-adj' }, { id: 'yoy', base: 'yoy-konstan', adj: 'yoy-konstan-plus-adj' }, { id: 'ctoc', base: 'ctoc-konstan', adj: 'ctoc-konstan-plus-adj' }];
            metrics.forEach(m => {
                const baseId = `${m.base}-kabkota-total-${idWilayah}-${targetYearId}`;
                const adjId = `${m.adj}-kabkota-total-${idWilayah}-${targetYearId}`;
                const baseCell = document.getElementById(baseId);
                if (!baseCell) return;
                let prevValAdj = 0, prevValBase = 0;
                if (prevTotalCell && prevBaseTotalCell) {
                    prevValAdj = parseFloat(prevTotalCell.dataset.value) || 0;
                    prevValBase = parseFloat(prevBaseTotalCell.dataset.value) || 0;
                } else {
                    const originalGrowth = parseFloat(baseCell.dataset.value);
                    if (!isNaN(originalGrowth) && originalGrowth !== -100) {
                        prevValBase = currentValBase / (1 + (originalGrowth / 100));
                        prevValAdj = prevValBase;
                    } else return;
                }
                const growthAdj = prevValAdj !== 0 ? ((currentValAdj - prevValAdj) / Math.abs(prevValAdj)) * 100 : 0;
                const growthBase = prevValBase !== 0 ? ((currentValBase - prevValBase) / Math.abs(prevValBase)) * 100 : (parseFloat(baseCell.dataset.value) || 0);
                const target = baseCell.querySelector('.main-value') || baseCell.querySelector('.font-semibold') || baseCell;
                target.innerText = window.formatNumber(growthBase, true);
                baseCell.dataset.value = growthBase;
                window.applySignRowColors(baseCell.closest('tr') ?? document);
                window.updateAnnualTotalCellWithStatus(adjId, growthBase, growthAdj, true);
            });
        }
    };

    window.updateAnnualTotalCellWithStatus = function(cellId, baseVal, adjVal, percent = false) {
        const cell = document.getElementById(cellId);
        if (!cell) return;
        const target = cell.querySelector('.main-value') || cell.querySelector('.font-semibold') || cell;
        target.innerText = window.formatNumber(adjVal, true);
        cell.dataset.value = adjVal;
        const cat = window.getDiffCategory(baseVal, adjVal);
        const bg = window.getBgByCategory(cat);
        const label = window.getStatusLabel(cat);
        if (bg) {
            cell.classList.remove('sign-positive', 'sign-negative', 'bg-white', 'bg-gray-50', 'bg-yellow-50', 'hover:bg-gray-50');
            cell.classList.add(bg);
            cell.dataset.status = label;
        } else {
            cell.classList.remove('bg-red-strong', 'bg-yellow-strong');
            delete cell.dataset.status;
            cell.classList.add('bg-yellow-50');
            window.applySignRowColors(cell.closest('tr') ?? document);
        }
    };

    window.updateKabkotaAnnualIndeksLaju = function(idWilayah, idTahun) {
        const bBaseCell = document.getElementById(`berlaku-kabkota-total-${idWilayah}-${idTahun}`);
        const kBaseCell = document.getElementById(`konstan-kabkota-total-${idWilayah}-${idTahun}`);
        const bAdjCell = document.getElementById(`pdrb-plus-adj-berlaku-kabkota-total-${idWilayah}-${idTahun}`);
        const kAdjCell = document.getElementById(`pdrb-plus-adj-konstan-kabkota-total-${idWilayah}-${idTahun}`);
        if (!bAdjCell || !kAdjCell || !bBaseCell || !kBaseCell) return;
        const bBase = parseFloat(bBaseCell.dataset.value) || 0, kBase = parseFloat(kBaseCell.dataset.value) || 0;
        const bAdj = parseFloat(bAdjCell.dataset.value) || 0, kAdj = parseFloat(kAdjCell.dataset.value) || 0;
        const iBase = kBase !== 0 ? (bBase / kBase) * 100 : 0, iAdj = kAdj !== 0 ? (bAdj / kAdj) * 100 : 0;
        const iBaseCell = document.getElementById(`indeks-berlaku-kabkota-total-${idWilayah}-${idTahun}`);
        if (iBaseCell) {
            const container = iBaseCell.querySelector('div.font-semibold') || iBaseCell;
            container.innerText = window.formatNumber(iBase);
            iBaseCell.dataset.value = iBase;
            window.applySignRowColors(iBaseCell.closest('tr') ?? document);
        }
        window.updateAnnualTotalCellWithStatus(`indeks-berlaku-plus-adj-kabkota-total-${idWilayah}-${idTahun}`, iBase, iAdj, false);
        const order = Array.isArray(window.REKON_TAHUN_ORDER) ? window.REKON_TAHUN_ORDER.map(v => parseInt(v, 10)).filter(v => !isNaN(v)) : [];
        const idx = order.indexOf(parseInt(idTahun, 10));
        if (idx < 0) return;
        for (let i = idx; i < order.length; i++) {
            const tId = order[i], cIdx = order.indexOf(parseInt(tId, 10)), pId = cIdx > 0 ? order[cIdx - 1] : null;
            const curIdxAdjCell = document.getElementById(`indeks-berlaku-plus-adj-kabkota-total-${idWilayah}-${tId}`);
            const curIdxBaseCell = document.getElementById(`indeks-berlaku-kabkota-total-${idWilayah}-${tId}`);
            if (!curIdxAdjCell || !curIdxBaseCell) continue;
            const curIdxAdjVal = parseFloat(curIdxAdjCell.dataset.value) || 0, curIdxBaseVal = parseFloat(curIdxBaseCell.dataset.value) || 0;
            const preIdxAdjCell = pId ? document.getElementById(`indeks-berlaku-plus-adj-kabkota-total-${idWilayah}-${pId}`) : null;
            const preIdxBaseCell = pId ? document.getElementById(`indeks-berlaku-kabkota-total-${idWilayah}-${pId}`) : null;
            const lajuBaseCell = document.getElementById(`laju-berlaku-kabkota-total-${idWilayah}-${tId}`);
            if (!lajuBaseCell) continue;
            let preIdxAdjVal = 0, preIdxBaseVal = 0;
            if (preIdxAdjCell && preIdxBaseCell) {
                preIdxAdjVal = parseFloat(preIdxAdjCell.dataset.value) || 0;
                preIdxBaseVal = parseFloat(preIdxBaseCell.dataset.value) || 0;
            } else {
                const origLaju = parseFloat(lajuBaseCell.dataset.value);
                if (!isNaN(origLaju) && origLaju !== -100) { preIdxBaseVal = curIdxBaseVal / (1 + (origLaju / 100)); preIdxAdjVal = preIdxBaseVal; }
                else continue;
            }
            const lajuAdj = preIdxAdjVal !== 0 ? ((curIdxAdjVal - preIdxAdjVal) / Math.abs(preIdxAdjVal)) * 100 : 0;
            const lajuBase = preIdxBaseVal !== 0 ? ((curIdxBaseVal - preIdxBaseVal) / Math.abs(preIdxBaseVal)) * 100 : (parseFloat(lajuBaseCell.dataset.value) || 0);
            const container = lajuBaseCell.querySelector('div.font-semibold') || lajuBaseCell;
            container.innerText = window.formatNumber(lajuBase, true);
            lajuBaseCell.dataset.value = lajuBase;
            window.applySignRowColors(lajuBaseCell.closest('tr') ?? document);
            window.updateAnnualTotalCellWithStatus(`laju-berlaku-plus-adj-kabkota-total-${idWilayah}-${tId}`, lajuBase, lajuAdj, true);
        }
    };

    window.updateTotalGrowth = function(idTahun, idPeriode) {
        const getPrevYearId = (tId) => {
            const order = Array.isArray(window.REKON_TAHUN_ORDER) ? window.REKON_TAHUN_ORDER.map(v => parseInt(v, 10)).filter(v => !isNaN(v)) : [];
            const idx = order.indexOf(parseInt(tId, 10));
            return idx > 0 ? order[idx - 1] : null;
        };
        const getPids = (prefix, tId) => {
            const ids = new Set();
            const re = new RegExp(`^${prefix}${tId}-(\\d+)$`);
            document.querySelectorAll(`[id^="${prefix}${tId}-"]`).forEach(el => {
                const match = el.id.match(re);
                if (match && match[1]) ids.add(parseInt(match[1], 10));
            });
            return Array.from(ids).sort((a, b) => a - b);
        };
        const getVal = (id) => { const c = document.getElementById(id); return c ? (parseFloat(c.dataset.value) || 0) : 0; };
        const getCum = (prefix, tId, pId, pids) => {
            let total = 0;
            for (const id of pids) { if (id > pId) break; total += getVal(`${prefix}${tId}-${id}`); }
            return total;
        };
        const getPrevPid = (prefix, tId, pId) => {
            const pids = getPids(prefix, tId);
            const idx = pids.indexOf(parseInt(pId, 10));
            return idx > 0 ? pids[idx - 1] : null;
        };
        const updateCtoc = (type, pPrefix, cPrefix, tId, pId) => {
            const pids = getPids(pPrefix, tId);
            if (!pids.length) return;
            const pYearId = getPrevYearId(tId);
            const pPids = pYearId ? getPids(pPrefix, pYearId) : [];
            const usePrev = pYearId && pPids.length > 0;
            const getBaseCum = (cId) => { const c = document.getElementById(cId); return c ? (parseFloat(c.dataset.base) || null) : null; };
            const startIdx = pids.indexOf(parseInt(pId, 10));
            const uPids = startIdx >= 0 ? pids.slice(startIdx) : pids;
            uPids.forEach(pid => {
                const cCum = getCum(pPrefix, tId, pid, pids);
                const cId = `${cPrefix}${tId}-${pid}`;
                let bCum = usePrev ? getCum(pPrefix, pYearId, pid, pPids) : getBaseCum(cId);
                const nCtoc = bCum && bCum !== 0 ? ((cCum - bCum) / Math.abs(bCum)) * 100 : 0;
                window.updateCell(cId, nCtoc, true);
                const cell = document.getElementById(cId);
                if (!cell) return;
                if (type === 'total') {
                    cell.classList.remove('bg-red-strong', 'bg-yellow-strong');
                    delete cell.dataset.status;
                    cell.title = '';
                    window.applySignRowColors(cell.closest('tr'));
                    window.applyTotalKabGrowthAlertCell(cell, tId, pid, 'ctoc');
                } else {
                    const bGrowthId = `ctoc-konstan-provinsi-${tId}-${pid}`;
                    const bGrowth = getVal(bGrowthId);
                    const cat = window.getDiffCategory(bGrowth, nCtoc);
                    const bg = window.getBgByCategory(cat);
                    const label = window.getStatusLabel(cat);
                    cell.classList.remove('bg-red-strong', 'bg-yellow-strong', 'bg-green-soft', 'bg-orange-soft');
                    delete cell.dataset.status;
                    if (bg) {
                        cell.classList.remove('sign-positive', 'sign-negative', 'bg-white', 'bg-gray-50', 'bg-yellow-50', 'hover:bg-gray-50', 'hover:bg-yellow-100');
                        cell.classList.add(bg);
                        cell.title = `Status: ${label}`;
                        cell.dataset.status = label;
                    } else { window.applySignRowColors(cell.closest('tr')); cell.title = ''; }
                }
            });
            const lastPid = pids[pids.length - 1];
            if (lastPid !== undefined) {
                const cCumLast = getCum(pPrefix, tId, lastPid, pids);
                const tCtocId = type === 'total' ? `ctoc-konstan-plus-adj-total-kab-total-${tId}` : `provinsi-total-ctoc-konstan-plus-adj-${tId}`;
                let bCumLast = usePrev ? getCum(pPrefix, pYearId, lastPid, pPids) : getBaseCum(tCtocId);
                if (bCumLast === null) bCumLast = getBaseCum(`${cPrefix}${tId}-${lastPid}`);
                const nCtocTotal = bCumLast && bCumLast !== 0 ? ((cCumLast - bCumLast) / Math.abs(bCumLast)) * 100 : 0;
                window.updateCell(tCtocId, nCtocTotal, true);
                const tCell = document.getElementById(tCtocId);
                if (tCell) {
                    if (type === 'total') { tCell.classList.remove('bg-red-strong', 'bg-yellow-strong'); delete tCell.dataset.status; tCell.title = ''; window.applySignRowColors(tCell.closest('tr')); }
                    else {
                        const bGrowth = getVal(`provinsi-total-ctoc-konstan-${tId}`);
                        const cat = window.getDiffCategory(bGrowth, nCtocTotal);
                        const bg = window.getBgByCategory(cat);
                        const label = window.getStatusLabel(cat);
                        tCell.classList.remove('bg-red-strong', 'bg-yellow-strong', 'bg-green-soft', 'bg-orange-soft');
                        delete tCell.dataset.status;
                        if (bg) {
                            tCell.classList.remove('sign-positive', 'sign-negative', 'bg-white', 'bg-gray-50', 'bg-yellow-50', 'hover:bg-gray-50', 'hover:bg-yellow-100');
                            tCell.classList.add(bg);
                            tCell.title = `Status: ${label}`;
                            tCell.dataset.status = label;
                        } else { window.applySignRowColors(tCell.closest('tr')); tCell.title = ''; }
                    }
                }
            }
        };
        const updateType = (type, pPrefix, qPrefix, yPrefix, cPrefix) => {
            const currentVal = getVal(`${pPrefix}${idTahun}-${idPeriode}`);
            const qCell = document.getElementById(`${qPrefix}${idTahun}-${idPeriode}`);
            if (qCell) {
                let base = parseFloat(qCell.dataset.base);
                if (type === 'total') { const prevPid = getPrevPid(pPrefix, idTahun, idPeriode); if (prevPid !== null) base = getVal(`${pPrefix}${idTahun}-${prevPid}`); }
                if (base && base !== 0) {
                    const nQoq = ((currentVal - base) / base) * 100;
                    window.updateCell(qCell.id, nQoq, true);
                    if (type === 'total') { qCell.classList.remove('bg-red-strong', 'bg-yellow-strong'); delete qCell.dataset.status; qCell.title = ''; window.applySignRowColors(qCell.closest('tr')); window.applyTotalKabGrowthAlertCell(qCell, idTahun, idPeriode, 'qoq'); }
                    else {
                        const bGrowth = getVal(`qoq-konstan-provinsi-${idTahun}-${idPeriode}`);
                        const cat = window.getDiffCategory(bGrowth, nQoq);
                        const bg = window.getBgByCategory(cat), label = window.getStatusLabel(cat);
                        qCell.classList.remove('bg-red-strong', 'bg-yellow-strong', 'bg-green-soft', 'bg-orange-soft');
                        delete qCell.dataset.status;
                        if (bg) { qCell.classList.remove('sign-positive', 'sign-negative', 'bg-white', 'bg-gray-50', 'bg-yellow-50', 'hover:bg-gray-50', 'hover:bg-yellow-100'); qCell.classList.add(bg); qCell.title = `Status: ${label}`; qCell.dataset.status = label; }
                        else { window.applySignRowColors(qCell.closest('tr')); qCell.title = ''; }
                    }
                }
            }
            const yCell = document.getElementById(`${yPrefix}${idTahun}-${idPeriode}`);
            if (yCell) {
                let base = parseFloat(yCell.dataset.base);
                if (type === 'total') { const pYearId = getPrevYearId(idTahun); if (pYearId) base = getVal(`${pPrefix}${pYearId}-${idPeriode}`); }
                if (base && base !== 0) {
                    const nYoy = ((currentVal - base) / base) * 100;
                    window.updateCell(yCell.id, nYoy, true);
                    if (type === 'total') { yCell.classList.remove('bg-red-strong', 'bg-yellow-strong'); delete yCell.dataset.status; yCell.title = ''; window.applySignRowColors(yCell.closest('tr')); }
                    else {
                        const bGrowth = getVal(`yoy-konstan-provinsi-${idTahun}-${idPeriode}`);
                        const cat = window.getDiffCategory(bGrowth, nYoy), bg = window.getBgByCategory(cat), label = window.getStatusLabel(cat);
                        yCell.classList.remove('bg-red-strong', 'bg-yellow-strong', 'bg-green-soft', 'bg-orange-soft');
                        delete yCell.dataset.status;
                        if (bg) { yCell.classList.remove('sign-positive', 'sign-negative', 'bg-white', 'bg-gray-50', 'bg-yellow-50', 'hover:bg-gray-50', 'hover:bg-yellow-100'); yCell.classList.add(bg); yCell.title = `Status: ${label}`; yCell.dataset.status = label; }
                        else { window.applySignRowColors(yCell.closest('tr')); yCell.title = ''; }
                    }
                }
                if (type === 'total') window.applyTotalKabGrowthAlertCell(yCell, idTahun, idPeriode, 'yoy');
            }
            if (cPrefix) updateCtoc(type, pPrefix, cPrefix, idTahun, idPeriode);
        };
        updateType('total', 'pdrb-plus-adj-konstan-total-', 'qoq-konstan-plus-adj-total-', 'yoy-konstan-plus-adj-total-', 'ctoc-konstan-plus-adj-total-');
        updateType('provinsi', 'pdrb-plus-adj-konstan-provinsi-', 'qoq-konstan-plus-adj-provinsi-', 'yoy-konstan-plus-adj-provinsi-', 'ctoc-konstan-plus-adj-provinsi-');
        const updateTahunMetric = (metric) => {
            const cellId = `${metric}-konstan-plus-adj-total-kab-total-${idTahun}`, cell = document.getElementById(cellId);
            if (!cell) return;
            const current = getVal(`pdrb-plus-adj-konstan-total-kab-total-${idTahun}`);
            let base = parseFloat(cell.dataset.base);
            const pYearId = getPrevYearId(idTahun);
            if (pYearId) { const pVal = getVal(`pdrb-plus-adj-konstan-total-kab-total-${pYearId}`); if (!isNaN(pVal)) base = pVal; }
            cell.dataset.base = base;
            const nGrowth = base && base !== 0 ? ((current - base) / Math.abs(base)) * 100 : 0;
            window.updateCell(cellId, nGrowth, true);
            window.applyTotalKabGrowthAlertCell(cell, idTahun, idPeriode, metric);
        };
        updateTahunMetric('qoq'); updateTahunMetric('yoy');
        window.debounceApplyAllTotalKabGrowthAlerts();
    };

    window.hitungIndeksImplisit = function(berlaku, konstan) { const b = parseFloat(berlaku) || 0, k = parseFloat(konstan) || 0; return k === 0 ? 0 : (b / k) * 100; };
    window.hitungLajuImplisit = function(currentIndex, prevIndex) { const c = parseFloat(currentIndex) || 0, p = parseFloat(prevIndex) || 0; return p === 0 ? 0 : ((c - p) / Math.abs(p)) * 100; };

    window.updateTotalIndeksLaju = function(idTahun, idPeriode) {
        const getPrevYearId = (tId) => {
            const order = Array.isArray(window.REKON_TAHUN_ORDER) ? window.REKON_TAHUN_ORDER.map(v => parseInt(v, 10)).filter(v => !isNaN(v)) : [];
            const idx = order.indexOf(parseInt(tId, 10));
            return idx > 0 ? order[idx - 1] : null;
        };
        const getVal = (id) => { const c = document.getElementById(id); return c ? (parseFloat(c.dataset.value) || 0) : 0; };
        const applyStatus = (adjId) => { const c = document.getElementById(adjId); if (c) { c.classList.remove('bg-red-strong', 'bg-yellow-strong'); delete c.dataset.status; window.applySignRowColors(c.closest('tr') ?? document); } };
        const bId = `pdrb-plus-adj-berlaku-total-${idTahun}-${idPeriode}`, kId = `pdrb-plus-adj-konstan-total-${idTahun}-${idPeriode}`, iId = `indeks-berlaku-plus-adj-total-${idTahun}-${idPeriode}`, lId = `laju-berlaku-plus-adj-total-${idTahun}-${idPeriode}`;
        const bVal = getVal(bId), kVal = getVal(kId), iVal = window.hitungIndeksImplisit(bVal, kVal);
        window.updateCell(iId, iVal); applyStatus(iId);
        const lCell = document.getElementById(lId);
        if (lCell) {
            let base = parseFloat(lCell.dataset.base);
            const pYearId = getPrevYearId(idTahun);
            if (pYearId) { const pIndeks = getVal(`indeks-berlaku-plus-adj-total-${pYearId}-${idPeriode}`); if (!isNaN(pIndeks)) base = pIndeks; }
            window.updateCell(lId, window.hitungLajuImplisit(iVal, isNaN(base) ? 0 : base), true);
            applyStatus(lId);
        }
        const btId = `pdrb-plus-adj-berlaku-total-kab-total-${idTahun}`, ktId = `pdrb-plus-adj-konstan-total-kab-total-${idTahun}`, itId = `indeks-berlaku-plus-adj-total-kab-total-${idTahun}`, ltId = `laju-berlaku-plus-adj-total-kab-total-${idTahun}`;
        const btVal = getVal(btId), ktVal = getVal(ktId), itVal = window.hitungIndeksImplisit(btVal, ktVal);
        window.updateCell(itId, itVal); applyStatus(itId);
        const ltCell = document.getElementById(ltId);
        if (ltCell) {
            let base = parseFloat(ltCell.dataset.base);
            const pYearId = getPrevYearId(idTahun);
            if (pYearId) { const ptIndeks = getVal(`indeks-berlaku-plus-adj-total-kab-total-${pYearId}`); if (!isNaN(ptIndeks)) base = ptIndeks; }
            window.updateCell(ltId, window.hitungLajuImplisit(itVal, isNaN(base) ? 0 : base), true);
            applyStatus(ltId);
        }
        window.debounceApplyAllTotalKabGrowthAlerts();
    };

    let IS_BASE_INIT = false;
    window.initGrowthBaseValues = function() {
        if (IS_BASE_INIT) return;
        const targets = [{ p: 'pdrb-plus-adj-konstan-total-', q: 'qoq-konstan-plus-adj-total-', y: 'yoy-konstan-plus-adj-total-' }, { p: 'pdrb-plus-adj-konstan-provinsi-', q: 'qoq-konstan-plus-adj-provinsi-', y: 'yoy-konstan-plus-adj-provinsi-' }];
        targets.forEach(t => {
            document.querySelectorAll(`[id^="${t.q}"]`).forEach(c => { const s = c.id.replace(t.q, ''), p = document.getElementById(`${t.p}${s}`); if (p) { const g = parseFloat(c.dataset.value) || 0, v = parseFloat(p.dataset.value) || 0; c.dataset.base = g === -100 ? 0 : v / (1 + g / 100); } });
            document.querySelectorAll(`[id^="${t.y}"]`).forEach(c => { const s = c.id.replace(t.y, ''), p = document.getElementById(`${t.p}${s}`); if (p) { const g = parseFloat(c.dataset.value) || 0, v = parseFloat(p.dataset.value) || 0; c.dataset.base = g === -100 ? 0 : v / (1 + g / 100); } });
        });
        const initTotalBase = (m) => { document.querySelectorAll(`[id^="${m}-konstan-plus-adj-total-kab-total-"]`).forEach(c => { const t = c.id.replace(`${m}-konstan-plus-adj-total-kab-total-`, ''), p = document.getElementById(`pdrb-plus-adj-konstan-total-kab-total-${t}`); if (p) { const g = parseFloat(c.dataset.value) || 0, v = parseFloat(p.dataset.value) || 0; c.dataset.base = g === -100 ? 0 : v / (1 + g / 100); } }); };
        initTotalBase('qoq'); initTotalBase('yoy');
        const initLBase = (l, i, s = false) => { document.querySelectorAll(`[id^="${l}"]`).forEach(c => { if (s && c.id.includes('total-kab-total-')) return; const sfx = c.id.replace(l, ''), idx = document.getElementById(`${i}${sfx}`); if (idx) { const g = parseFloat(c.dataset.value) || 0, v = parseFloat(idx.dataset.value) || 0; c.dataset.base = g === -100 ? 0 : v / (1 + g / 100); } }); };
        initLBase('laju-berlaku-plus-adj-total-', 'indeks-berlaku-plus-adj-total-', true);
        initLBase('laju-berlaku-plus-adj-total-kab-total-', 'indeks-berlaku-plus-adj-total-kab-total-');
        IS_BASE_INIT = true;
    };

    window.initCtocBaseValues = function() {
        const getPids = (prefix, tId) => { const ids = new Set(); const re = new RegExp(`^${prefix}${tId}-(\\d+)$`); document.querySelectorAll(`[id^="${prefix}${tId}-"]`).forEach(el => { const match = el.id.match(re); if (match && match[1]) ids.add(parseInt(match[1], 10)); }); return Array.from(ids).sort((a, b) => a - b); };
        const getVal = (id) => { const c = document.getElementById(id); return c ? (parseFloat(c.dataset.value) || 0) : 0; };
        const getCum = (prefix, tId, pId, pids) => { let total = 0; for (const id of pids) { if (id > pId) break; total += getVal(`${prefix}${tId}-${id}`); } return total; };
        const targets = [{ p: 'pdrb-plus-adj-konstan-total-', c: 'ctoc-konstan-plus-adj-total-' }, { p: 'pdrb-plus-adj-konstan-provinsi-', c: 'ctoc-konstan-plus-adj-provinsi-' }];
        targets.forEach(t => {
            document.querySelectorAll(`[id^="${t.c}"]`).forEach(cell => {
                if (cell.dataset.base) return;
                const m = cell.id.replace(t.c, '').match(/^(\d+)-(\d+)$/);
                if (!m) return;
                const pids = getPids(t.p, m[1]);
                if (pids.length) { const cum = getCum(t.p, m[1], parseInt(m[2], 10), pids), g = parseFloat(cell.dataset.value); cell.dataset.base = isNaN(g) ? null : (g === -100 ? 0 : cum / (1 + g / 100)); }
            });
        });
        const tTargets = [{ p: 'pdrb-plus-adj-konstan-total-', cp: 'ctoc-konstan-plus-adj-total-kab-total-' }, { p: 'pdrb-plus-adj-konstan-provinsi-', cp: 'provinsi-total-ctoc-konstan-plus-adj-' }];
        tTargets.forEach(t => {
            document.querySelectorAll(`[id^="${t.cp}"]`).forEach(cell => {
                if (cell.dataset.base) return;
                const tId = cell.id.replace(t.cp, ''), pids = getPids(t.p, tId);
                if (pids.length) { const cum = getCum(t.p, tId, pids[pids.length-1], pids), g = parseFloat(cell.dataset.value); cell.dataset.base = isNaN(g) ? null : (g === -100 ? 0 : cum / (1 + g / 100)); }
            });
        });
    };

    window.initLajuBaseValuesForTotal = function() {
        const setB = (lc, ic) => { if (!lc || lc.dataset.base) return; const ci = parseFloat(ic?.dataset.value), l = parseFloat(lc.dataset.value); if (!isNaN(ci) && !isNaN(l)) lc.dataset.base = l === -100 ? 0 : ci / (1 + l / 100); };
        document.querySelectorAll('[id^="laju-berlaku-plus-adj-total-"]').forEach(c => { if (c.id.startsWith('laju-berlaku-plus-adj-total-kab-total-')) return; setB(c, document.getElementById(`indeks-berlaku-plus-adj-total-${c.id.replace('laju-berlaku-plus-adj-total-', '')}`)); });
        document.querySelectorAll('[id^="laju-berlaku-plus-adj-total-kab-total-"]').forEach(c => setB(c, document.getElementById(`indeks-berlaku-plus-adj-total-kab-total-${c.id.replace('laju-berlaku-plus-adj-total-kab-total-', '')}`)));
    };

    window.updateDiskrepansiNilai = function(idTahun, idPeriode) {
        ['berlaku', 'konstan'].forEach(t => {
            const pVal = getVal(`pdrb-plus-adj-${t}-provinsi-${idTahun}-${idPeriode}`), tVal = getVal(`pdrb-plus-adj-${t}-total-${idTahun}-${idPeriode}`), diff = pVal - tVal, c = document.getElementById(`diskrepansi-pdrb-adj-${t}-${idTahun}-${idPeriode}`);
            if (c) { c.innerText = window.formatNumber(diff); c.dataset.value = diff; }
        });
        function getVal(id) { const c = document.getElementById(id); return c ? (parseFloat(c.dataset.value) || 0) : 0; }
    };

    window.updateDiskrepansiPersen = function(idTahun, idPeriode) {
        ['berlaku', 'konstan'].forEach(t => {
            const pVal = getVal(`pdrb-plus-adj-${t}-provinsi-${idTahun}-${idPeriode}`), tVal = getVal(`pdrb-plus-adj-${t}-total-${idTahun}-${idPeriode}`), pct = tVal !== 0 ? ((pVal - tVal) / Math.abs(tVal)) * 100 : 0, c = document.getElementById(`diskrepansi-persen-pdrb-adj-${t}-${idTahun}-${idPeriode}`);
            if (c) {
                c.innerText = window.formatNumber(pct, true); c.dataset.value = pct;
                c.classList.remove('text-red-600', 'bg-red-strong', 'text-yellow-600', 'bg-yellow-strong', 'text-gray-600');
                const abs = Math.abs(pct);
                if (abs >= 5) c.classList.add('text-red-600', 'bg-red-strong');
                else if (abs >= 2) c.classList.add('text-yellow-600', 'bg-yellow-strong');
                else c.classList.add('text-gray-600');
            }
        });
        window.updateTotalGrowth(idTahun, idPeriode);
        function getVal(id) { const c = document.getElementById(id); return c ? (parseFloat(c.dataset.value) || 0) : 0; }
    };

    window.recalculateGrowth = async function(input, silent = false) {
        if (!window.REKON_RECALCULATE_URL) return;
        const row = input.closest('tr');
        if (!silent) window.showProcessing('Menghitung pertumbuhan...');
        try {
            const res = await fetch(window.REKON_RECALCULATE_URL, { 
                method: 'POST', 
                headers: { 'X-CSRF-TOKEN': window.CSRF_TOKEN, 'Content-Type': 'application/json', 'Accept': 'application/json' }, 
                body: JSON.stringify({ 
                    id_sub_kategori: input.dataset.subKategori || row?.dataset?.subKategori, 
                    id_kategori: input.dataset.kategori || row?.dataset?.kategori, 
                    id_wilayah: input.dataset.idWilayah || row?.dataset?.idWilayah, 
                    id_tahun: input.dataset.idTahun, 
                    id_periode: input.dataset.idPeriode, 
                    periode_nama: input.dataset.periodeNama 
                }) 
            });
            const result = await res.json();
            if (!res.ok || result.status !== 'success') { if (!silent) window.showNotification(`Gagal menghitung pertumbuhan: ${result?.message || 'Error'}`, 'error'); return; }
            window.updateGrowthCells(input, result.data);
            if (!silent) window.showNotification('Pertumbuhan diperbarui', 'success');
        } catch (e) { if (!silent) window.showNotification('Gagal menghitung pertumbuhan', 'error'); }
        finally { if (!silent) window.hideProcessing(); }
    };

    window.updateCell = function(id, value, percent = false) { const c = document.getElementById(id); if (c) { const t = c.querySelector('.main-value') || c.querySelector('.font-semibold') || c; t.innerText = window.formatNumber(value, percent); c.dataset.value = value; window.applySignRowColors(c.closest('tr') ?? document); } };

    window.updateGrowthCells = function(input, data) {
        const base = input.dataset.group === 'kabkota' ? `kabkota-${input.dataset.idWilayah}` : input.dataset.group, t = input.dataset.idTahun, p = input.dataset.idPeriode;
        const updateWS = (mT, bP, aP) => {
            const bId = `${mT}-konstan-${base}-${t}-${p}`, aId = `${mT}-konstan-plus-adj-${base}-${t}-${p}`;
            window.updateCell(bId, data[bP], true); window.updateCell(aId, data[aP], true);
            const c = document.getElementById(aId);
            if (c) { const cat = window.getDiffCategory(data[bP], data[aP]), bg = window.getBgByCategory(cat); if (bg) { c.classList.remove('sign-positive', 'sign-negative', 'bg-white', 'bg-gray-50', 'bg-yellow-50', 'hover:bg-gray-50'); c.classList.add(bg); c.dataset.status = window.getStatusLabel(cat); } }
        };
        const updateIWS = (mP, bP, aP, isP = false) => {
            const bId = `${mP}-berlaku-${base}-${t}-${p}`, aId = `${mP}-berlaku-plus-adj-${base}-${t}-${p}`;
            window.updateCell(bId, data[bP], isP); window.updateCell(aId, data[aP], isP);
            const c = document.getElementById(aId);
            if (c) { const cat = window.getDiffCategory(data[bP], data[aP]), bg = window.getBgByCategory(cat); c.classList.remove('bg-red-strong', 'bg-yellow-strong'); delete c.dataset.status; if (bg) { c.classList.remove('sign-positive', 'sign-negative', 'bg-white', 'bg-gray-50', 'bg-yellow-50', 'hover:bg-gray-50', 'hover:bg-yellow-100'); c.classList.add(bg); c.dataset.status = window.getStatusLabel(cat); } }
        };
        updateWS('qoq', 'qoq_konstan', 'qoq_konstan_plus_adj'); updateWS('yoy', 'yoy_konstan', 'yoy_konstan_plus_adj'); updateWS('ctoc', 'ctoc_konstan', 'ctoc_konstan_plus_adj');
        updateIWS('indeks', 'indeks_berlaku', 'indeks_berlaku_plus_adj', false); updateIWS('laju', 'laju_berlaku', 'laju_berlaku_plus_adj', true);
        if (['provinsi', 'total-kabkota'].includes(input.dataset.group)) { window.updateDiskrepansiNilai(t, p); window.updateDiskrepansiPersen(t, p); }
        window.debounceApplyAllTotalKabGrowthAlerts();
    };

    window.sanitizeDecimalInput = function(input) { input.value = input.value.replace(/[^0-9.,-]/g, ''); };

    window.saveAdjValue = async function(input) {
        if (input.disabled || input.readOnly) return;
        if (window.REKON_LOCKED_FOR_USER) { window.showNotification('Input sedang dikunci oleh provinsi', 'warning'); return; }
        const nVal = window.parseInputNumber(input.value);
        if (nVal !== null && !isNaN(nVal)) input.value = nVal.toFixed(2);
        if (input.dataset.lastValue === String(nVal || 0)) { window.updateAdjInputClasses(input); return; }
        window.showProcessing('Menyimpan adjustment...');
        input.classList.add('opacity-50', 'cursor-not-allowed');
        await new Promise(r => requestAnimationFrame(r));
        window.updateAdjInputClasses(input); window.updatePdrbPlusAdjCell(input);
        const row = input.closest('tr');
        const payload = { 
            id_sub_kategori: input.dataset.subKategori || row.dataset.subKategori, 
            id_kategori: input.dataset.kategori || row.dataset.kategori, 
            id_wilayah: input.dataset.idWilayah || row.dataset.idWilayah, 
            id_tahun: input.dataset.idTahun, 
            id_periode: input.dataset.idPeriode, 
            tipe: input.dataset.tipe, 
            nilai: nVal || 0 
        };
        if (!payload.id_sub_kategori && !payload.id_kategori) { window.showNotification('Sub kategori/kategori kosong!', 'error'); input.classList.remove('opacity-50', 'cursor-not-allowed'); return; }
        try {
            const socketId = window.EchoInstance?.socketId ? window.EchoInstance.socketId() : null;
            const res = await fetch(window.REKON_UPDATE_ADJ_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': window.CSRF_TOKEN, ...(socketId ? { 'X-Socket-ID': socketId } : {}), 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(payload) });
            const result = await res.json().catch(() => null);
            if (!res.ok || (result && result.status === 'error') || (result && result.error)) { window.showNotification(`Gagal menyimpan: ${result?.message || result?.error || 'Error'}`, 'error'); return; }
            window.showNotification('Adjustment disimpan', 'success'); input.dataset.lastValue = String(nVal);
            if (result?.id) { input.dataset.historyId = String(result.id); if (input.dataset.target) { const t = document.getElementById(input.dataset.target); if (t) t.dataset.historyId = String(result.id); } }
            if (input.dataset.group === 'kabkota') window.hitungTotalKabKota(input.dataset.idTahun, input.dataset.idPeriode, input.dataset.tipe);
            await window.recalculateGrowth(input);
            if (input.dataset.group === 'kabkota') window.updateKabkotaAnnualTotal(input.dataset.idWilayah, input.dataset.idTahun, input.dataset.tipe);
            const cPeriodes = [];
            const pO = Array.isArray(window.REKON_PERIODE_ORDER) ? window.REKON_PERIODE_ORDER.map(v => parseInt(v, 10)).filter(v => !isNaN(v)) : [];
            const tO = Array.isArray(window.REKON_TAHUN_ORDER) ? window.REKON_TAHUN_ORDER.map(v => parseInt(v, 10)).filter(v => !isNaN(v)) : [];
            const cP = parseInt(input.dataset.idPeriode, 10), cT = parseInt(input.dataset.idTahun, 10);
            const pIdx = pO.indexOf(cP);
            for (let pi = pIdx + 1; pi < pO.length; pi++) cPeriodes.push({ tahun: cT, periode: pO[pi] });
            const tIdx = tO.indexOf(cT);
            for (let ti = tIdx + 1; ti < tO.length; ti++) pO.forEach(pid => cPeriodes.push({ tahun: tO[ti], periode: pid }));
            for (const cp of cPeriodes) {
                const vI = { dataset: { ...input.dataset, idTahun: String(cp.tahun), idPeriode: String(cp.periode) } };
                await window.recalculateGrowth(vI, true);
            }
        } catch (err) { console.error(err); window.showNotification('Gagal menyimpan adjustment', 'error'); }
        finally { window.hideProcessing(); input.classList.remove('opacity-50', 'cursor-not-allowed'); }
    };

    const hitungTotalKabKotaTimers = {};
    window.debounceHitungTotalKabKota = function(idTahun, idPeriode, tipe) {
        const key = `${idTahun}-${idPeriode}-${tipe}`;
        if (hitungTotalKabKotaTimers[key]) clearTimeout(hitungTotalKabKotaTimers[key]);
        hitungTotalKabKotaTimers[key] = setTimeout(() => { window.hitungTotalKabKota(idTahun, idPeriode, tipe); }, 400);
    };

    window.initAdjInputs = function(root = document) {
        const inputs = [];
        if (root instanceof Element && root.classList.contains('adj-input')) inputs.push(root);
        if (root.querySelectorAll) root.querySelectorAll('.adj-input').forEach(i => inputs.push(i));
        inputs.forEach(input => {
            if (input.dataset.lockBaseDisabled === undefined) input.dataset.lockBaseDisabled = (input.disabled || input.readOnly) ? '1' : '0';
            if (input.dataset.initialized === 'true') return;
            input.dataset.initialized = 'true';
            window.updateAdjInputClasses(input);
            input.dataset.lastValue = String(window.parseInputNumber(input.value));
            window.formatAdjInputDisplay(input);
            window.updatePdrbPlusAdjCell(input);
            input.addEventListener('input', () => {
                window.sanitizeDecimalInput(input); window.updateAdjInputClasses(input);
                if (input.calcTimeout) clearTimeout(input.calcTimeout);
                input.calcTimeout = setTimeout(() => {
                    window.updatePdrbPlusAdjCell(input);
                    if (input.dataset.group === 'kabkota') window.debounceHitungTotalKabKota(input.dataset.idTahun, input.dataset.idPeriode, input.dataset.tipe);
                }, 300);
            });
            input.addEventListener('blur', () => { window.saveAdjValue(input).then(() => window.formatAdjInputDisplay(input)); });
            input.addEventListener('focus', () => { if (input.disabled || input.readOnly) return; const v = window.parseInputNumber(input.value); input.value = v ? v.toFixed(2) : ''; input.classList.remove('bg-yellow-50', 'bg-white'); input.classList.add('bg-blue-50'); });
            input.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); input.blur(); } });
        });
    };

    window.updateLockUI = function(locked) {
        const btn = document.getElementById('lock-toggle-btn');
        if (btn) {
            btn.dataset.locked = locked ? '1' : '0';
            btn.classList.toggle('bg-red-600', locked); btn.classList.toggle('bg-emerald-600', !locked);
            const label = btn.querySelector('[data-lock-label]');
            if (label) label.textContent = locked ? 'Unlock Input' : 'Lock Input';
        }
        const status = document.getElementById('lock-status');
        if (status) {
            status.classList.toggle('bg-red-50', locked); status.classList.toggle('text-red-700', locked);
            status.classList.toggle('bg-emerald-50', !locked); status.classList.toggle('text-emerald-700', !locked);
            const text = status.querySelector('[data-lock-text]');
            if (text) text.textContent = locked ? 'Locked' : 'Open';
        }
    };

    window.applyLockState = function(locked) {
        window.REKON_LOCKED_FOR_USER = !!locked && window.ROLE_IS_KAB_KOTA;
        document.querySelectorAll('.adj-input').forEach(input => {
            const bD = input.dataset.lockBaseDisabled === '1', sD = window.REKON_LOCKED_FOR_USER || bD;
            input.disabled = sD; input.readOnly = sD;
            input.classList.toggle('opacity-60', window.REKON_LOCKED_FOR_USER && !bD);
            input.classList.toggle('cursor-not-allowed', sD);
        });
        window.updateLockUI(!!locked);
    };

    window.toggleLockState = async function() {
        if (!window.REKON_LOCK_URL) return;
        const detail = window.REKON_DETAIL || {};
        if (!detail.id || !detail.type) return;
        const btn = document.getElementById('lock-toggle-btn');
        if (btn) btn.disabled = true;
        try {
            const res = await fetch(window.REKON_LOCK_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': window.CSRF_TOKEN, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ type: detail.type, id: detail.id, pendekatan: window.REKON_PENDEKATAN }) });
            const result = await res.json();
            if (!res.ok || result.status !== 'ok') { window.showNotification(`Gagal mengubah lock: ${result?.error || 'Error'}`, 'error'); return; }
            window.applyLockState(!!result.locked);
            window.showNotification(result.locked ? 'Input dikunci' : 'Input dibuka', 'success');
        } catch (e) { window.showNotification('Gagal mengubah lock', 'error'); }
        finally { if (btn) btn.disabled = false; }
    };

    window.handleRekonP1Update = function(payload) {
        if (!payload) return;
        if (payload.updated_by && window.REKON_USER_ID && parseInt(payload.updated_by, 10) === parseInt(window.REKON_USER_ID, 10)) return;
        const d = window.REKON_DETAIL || {};
        if (payload.pendekatan && d.pendekatan && payload.pendekatan !== d.pendekatan) return;
        if (d.type && payload.type && payload.type !== d.type) return;
        if (d.id && payload.entity_id && parseInt(payload.entity_id, 10) !== parseInt(d.id, 10)) return;
        const parts = [`.adj-input[data-id-wilayah="${payload.id_wilayah}"]`, `[data-id-tahun="${payload.id_tahun}"]`, `[data-id-periode="${payload.id_periode}"]`, `[data-tipe="${payload.tipe}"]`];
        parts.push(payload.type === 'kategori' ? `[data-kategori="${payload.entity_id}"]` : `[data-sub-kategori="${payload.entity_id}"]`);
        const input = document.querySelector(parts.join(''));
        if (!input || document.activeElement === input) return;
        const v = parseFloat(payload.adj);
        input.value = isNaN(v) || v === 0 ? '' : v.toFixed(2);
        window.formatAdjInputDisplay(input);
        input.dataset.lastValue = String(isNaN(v) ? 0 : v);
        if (payload.history_id) { input.dataset.historyId = String(payload.history_id); if (input.dataset.target) { const t = document.getElementById(input.dataset.target); if (t) t.dataset.historyId = String(payload.history_id); } }
        window.updateAdjInputClasses(input); window.updatePdrbPlusAdjCell(input);
        if (input.dataset.group === 'kabkota') window.hitungTotalKabKota(input.dataset.idTahun, input.dataset.idPeriode, input.dataset.tipe);
        window.recalculateGrowth(input, true);
    };

    window.setSyncIndicator = function(state) {
        const ind = document.getElementById('sync-indicator'); if (!ind) return;
        const dot = ind.querySelector('[data-sync-dot]'), txt = ind.querySelector('[data-sync-text]'); if (!dot || !txt) return;
        ind.classList.remove('border-gray-200', 'border-blue-200', 'border-green-200', 'border-yellow-200', 'border-red-200');
        dot.classList.remove('bg-gray-400', 'bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-red-500', 'animate-pulse');
        if (state === 'syncing') { ind.classList.add('border-blue-200'); dot.classList.add('bg-blue-500', 'animate-pulse'); txt.textContent = 'Syncing...'; return; }
        if (state === 'paused') { ind.classList.add('border-yellow-200'); dot.classList.add('bg-yellow-500'); txt.textContent = 'Paused'; return; }
        if (state === 'error') { ind.classList.add('border-red-200'); dot.classList.add('bg-red-500'); txt.textContent = 'Sync error'; return; }
        ind.classList.add('border-green-200'); dot.classList.add('bg-green-500'); txt.textContent = 'Synced';
    };

    let pollTimer = null, pollInFlight = false, lastPollAt = Date.now() - 3000, pollActive = !document.hidden;
    let lastActivityAt = Date.now(), currentInterval = 15000;
    const MAX_INTERVAL = 60000, MIN_INTERVAL = 15000, IDLE_THRESHOLD = 300000;

    window.pollRekonP1Updates = async function() {
        if (pollInFlight || !pollActive || document.hidden || !window.REKON_POLL_URL) { if (document.hidden) window.setSyncIndicator('paused'); return; }
        const d = window.REKON_DETAIL || {}; if (!d.id || !d.type) return;
        pollInFlight = true; window.setSyncIndicator('syncing');
        try {
            const p = new URLSearchParams({ type: d.type, id: d.id, since: String(lastPollAt), pendekatan: window.REKON_PENDEKATAN || '' });
            const res = await fetch(`${window.REKON_POLL_URL}?${p.toString()}`, { headers: { 'Accept': 'application/json' } });
            const result = await res.json();
            if (res.ok && result?.status === 'ok') {
                if (result.has_changes) { (result.updates || []).forEach(window.handleRekonP1Update); currentInterval = MIN_INTERVAL; }
                else if (Date.now() - lastActivityAt > IDLE_THRESHOLD) currentInterval = Math.min(MAX_INTERVAL, currentInterval + 5000);
                if (typeof result.locked !== 'undefined') window.applyLockState(!!result.locked);
                window.setSyncIndicator('idle');
                if (pollTimer && currentInterval > MIN_INTERVAL) window.initPolling();
            } else { window.setSyncIndicator('error'); currentInterval = Math.min(MAX_INTERVAL, currentInterval + 10000); }
            lastPollAt = result?.server_time_ms || Date.now();
        } catch (e) { console.error(e); lastPollAt = Date.now(); window.setSyncIndicator('error'); }
        finally { pollInFlight = false; }
    };

    window.initPolling = function() {
        if (!window.REKON_POLL_URL) return;
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(window.pollRekonP1Updates, currentInterval);
        pollActive = !document.hidden;
        window.setSyncIndicator(pollActive ? 'idle' : 'paused');
    };

    // Removed initializeSpecialRowColors as it is now handled by CSS :has selectors

    // Centralized Tooltip Handler
    (function() {
        let tooltip = null, activeTarget = null;
        const ensureT = () => { if (tooltip) return tooltip; tooltip = document.createElement('div'); tooltip.className = 'premium-tooltip hidden'; document.body.appendChild(tooltip); return tooltip; };
        document.addEventListener('mouseover', e => {
            const target = e.target.closest('[data-status]');
            if (!target || !target.dataset.status) return;
            activeTarget = target; const tt = ensureT();
            tt.innerHTML = `<div class="tt-header">Alert Detail</div><div class="tt-body">${target.dataset.status}</div>`;
            tt.classList.remove('hidden');
            const updateP = () => {
                if (!activeTarget) return; const r = activeTarget.getBoundingClientRect(), tr = tt.getBoundingClientRect();
                let t = r.bottom + window.scrollY + 8, l = r.left + window.scrollX;
                if (l + tr.width > window.innerWidth) l = window.innerWidth - tr.width - 20;
                if (t + tr.height > window.innerHeight + window.scrollY) t = r.top + window.scrollY - tr.height - 8;
                tt.style.top = t + 'px'; tt.style.left = l + 'px';
            };
            updateP();
            const onL = () => { if (activeTarget === target) { tt.classList.add('hidden'); activeTarget = null; } target.removeEventListener('mouseleave', onL); window.removeEventListener('scroll', onS); };
            const onS = () => updateP();
            target.addEventListener('mouseleave', onL); window.addEventListener('scroll', onS, { passive: true });
        });
    })();

    // Main Initialization
    document.addEventListener('DOMContentLoaded', () => {
        // Run heavy init in chunks to avoid blocking main thread
        const tasks = [
            window.initAdjInputs,
            window.initGrowthBaseValues,
            window.initCtocBaseValues,
            window.initLajuBaseValuesForTotal,
            window.resetTotalKabStatusVisuals,
            window.applySignRowColors,
            window.applyAllTotalKabGrowthAlerts,
            () => window.applyLockState(window.REKON_IS_RECORD_LOCKED),
            window.initPolling
        ];
        
        let i = 0;
        const runNext = () => {
            if (i < tasks.length) {
                tasks[i]();
                i++;
                setTimeout(runNext, 0);
            }
        };
        runNext();

        const lBtn = document.getElementById('lock-toggle-btn'); if (lBtn) lBtn.addEventListener('click', window.toggleLockState);
        
        const obs = new MutationObserver(mutations => {
            let hasNew = false; mutations.forEach(m => m.addedNodes.forEach(n => { if (n.nodeType === 1 && (n.classList.contains('adj-input') || n.querySelector('.adj-input'))) hasNew = true; }));
            if (hasNew) setTimeout(() => { window.initAdjInputs(document); window.applyLockState(window.REKON_IS_RECORD_LOCKED); }, 500);
        });
        obs.observe(document.body, { childList: true, subtree: true });
    });

    ['mousedown', 'keydown', 'scroll', 'touchstart'].forEach(evt => window.addEventListener(evt, () => { lastActivityAt = Date.now(); if (currentInterval > MIN_INTERVAL) { currentInterval = MIN_INTERVAL; window.initPolling(); } }, { passive: true }));
    document.addEventListener('visibilitychange', () => { pollActive = !document.hidden; window.setSyncIndicator(pollActive ? 'syncing' : 'paused'); if (pollActive) { lastPollAt = Date.now() - 3000; window.initPolling(); window.pollRekonP1Updates(); } });
    window.addEventListener('pageshow', () => { lastPollAt = Date.now() - 3000; window.initPolling(); window.pollRekonP1Updates(); });
    window.addEventListener('focus', () => { pollActive = true; window.setSyncIndicator('syncing'); lastPollAt = Date.now() - 3000; window.initPolling(); window.pollRekonP1Updates(); });

})();
