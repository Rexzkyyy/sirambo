/**
 * LK Dynamic Calculator
 * A generic engine to handle calculations for PDRB work sheets based on JSON schemas.
 */

class LkDynamicCalculator {
    constructor(table) {
        this.table = table;
        this.tipePdrb = table.dataset.tipePdrb || 'berlaku';
        this.bindEvents();
        this.initialCalculate();
    }

    bindEvents() {
        this.table.addEventListener('input', (e) => {
            if (e.target.classList.contains('lk-dynamic-input')) {
                const row = e.target.closest('tr');
                this.calculateRow(row);
                this.updateTotals();
            }
        });

        this.table.addEventListener('focusin', (e) => {
            if (e.target.classList.contains('lk-dynamic-input')) {
                const input = e.target;
                if (input.readOnly) return;
                
                const raw = this.getRawValue(input);
                if (raw === 0) {
                    input.value = '';
                } else {
                    input.value = this.formatInput(raw);
                    setTimeout(() => input.select(), 10);
                }
            }
        });

        this.table.addEventListener('focusout', (e) => {
            if (e.target.classList.contains('lk-dynamic-input')) {
                this.setShortDisplay(e.target);
            }
        });

        this.table.addEventListener('paste', (e) => this.handlePaste(e));
    }

    handlePaste(e) {
        // Only trigger if an input is focused
        const target = e.target;
        if (!target.tagName === 'INPUT') return;

        const clipboardData = e.clipboardData || window.clipboardData;
        const pastedData = clipboardData.getData('text');
        
        // Split into rows and columns (Excel uses tabs for columns, newlines for rows)
        const rows = pastedData.split(/\r?\n/).filter(row => row.trim() !== '');
        if (rows.length === 0) return;

        // Check if it's multi-column or multi-row
        const firstRowCols = rows[0].split(/\t/);
        if (rows.length === 1 && firstRowCols.length === 1) return; // Standard single value paste

        e.preventDefault();

        let currentRow = target.closest('tr');
        const startInput = target;
        // Include ALL inputs (including readonly) to keep alignment with Excel
        const rowInputs = Array.from(currentRow.querySelectorAll('input'));
        const startIndex = rowInputs.indexOf(startInput);

        rows.forEach((rowStr, rowIndex) => {
            if (!currentRow) {
                // If we ran out of rows, try to add one by clicking the "Tambah Baris" button
                const addBtnId = this.tipePdrb === 'berlaku' ? 'lk-add-rows-berlaku' : 'lk-add-rows-konstan';
                const addBtn = document.getElementById(addBtnId);
                if (addBtn) {
                    addBtn.click();
                    currentRow = this.table.querySelector(`tbody tr:last-child`);
                } else {
                    return; // Can't add more rows
                }
            }

            let dataCols = rowStr.split('\t');
            if (dataCols.length === 1 && rowStr.split(/\s{2,}/).length > 1) {
                dataCols = rowStr.split(/\s{2,}/);
            }

            const currentInputs = Array.from(currentRow.querySelectorAll('input'));
            
            // Heuristic: If first column looks like a row index (number) and we are pasting into Nama,
            // and the second column exists and is likely a name (not a pure number), skip first column.
            let colOffset = 0;
            if (startIndex === 0 && dataCols.length > 1) {
                const firstIsNum = /^\d+$/.test(dataCols[0].trim().replace(/\./g, ''));
                const secondIsNum = /^-?[\d.,]+$/.test(dataCols[1].trim());
                if (firstIsNum && !secondIsNum) colOffset = 1;
            }

            dataCols.slice(colOffset).forEach((val, colIndex) => {
                const targetIdx = startIndex + colIndex;
                const input = currentInputs[targetIdx];
                
                if (input) {
                    // Clean value (remove thousand separators, fix decimal)
                    let cleanVal = val.trim();
                    if (cleanVal === '-') cleanVal = '0';
                    
                    // If it looks like a number with dot thousand and comma decimal (Indonesian)
                    if (cleanVal.includes(',') && cleanVal.split(',').length === 2 && !cleanVal.includes('.')) {
                        cleanVal = cleanVal.replace(',', '.');
                    } else if (cleanVal.includes('.') && cleanVal.includes(',')) {
                        cleanVal = cleanVal.replace(/\./g, '').replace(',', '.');
                    }
                    
                    input.value = cleanVal;
                    if (input.classList.contains('lk-dynamic-input')) {
                        input.dataset.rawValue = cleanVal;
                    }
                }
            });

            this.calculateRow(currentRow);
            currentRow = currentRow.nextElementSibling;
        });

        this.updateTotals();
    }

    initialCalculate() {
        this.table.querySelectorAll('tbody tr[data-template]').forEach(row => {
            this.calculateRow(row);
        });
        this.updateTotals();
    }

    getRawValue(input) {
        // Always prefer dataset.rawValue which contains the clean number
        let val = input.dataset.rawValue;
        if (val !== undefined && val !== null && val !== "") {
            let n = parseFloat(val);
            return isNaN(n) ? 0 : n;
        }
        
        // Fallback to parsing the input value
        let str = input.value.trim();
        if (str === '-' || str === '') return 0;
        
        // Indonesian: 1.234,56 -> 1234.56
        str = str.replace(/\./g, '').replace(',', '.');
        let num = parseFloat(str);
        return isNaN(num) ? 0 : num;
    }

    formatInput(val) {
        return String(val).replace('.', ',');
    }

    formatDisplay(val, decimals = 2) {
        if (isNaN(val)) return '0';
        return new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: decimals
        }).format(val);
    }

    setShortDisplay(input) {
        const raw = this.getRawValue(input);
        const type = input.getAttribute('data-type');
        
        if (type === 'text') {
            input.value = input.dataset.rawValue || input.value;
            return;
        }
        
        const key = input.dataset.key;
        let decimals = 2;
        if (key.includes('rasio') || key.includes('deflator')) decimals = 4;
        
        input.value = this.formatDisplay(raw, decimals);
        input.dataset.rawValue = String(raw);
    }

    setInputValue(input, val) {
        input.dataset.rawValue = String(val);
        this.setShortDisplay(input);
    }

    calculateRow(row) {
        const inputs = Array.from(row.querySelectorAll('.lk-dynamic-input'));
        const data = {};
        
        // 1. Collect all raw values
        inputs.forEach(input => {
            data[input.dataset.key] = this.getRawValue(input);
        });

        // 2. Iterative formula solving (15 passes to handle deep dependencies)
        let changed = true;
        let passes = 0;
        while (changed && passes < 15) {
            changed = false;
            passes++;
            
            inputs.forEach(input => {
                const formula = input.dataset.formula;
                if (formula) {
                    try {
                        const result = this.evaluateExpression(formula, data);
                        // Using a small epsilon for float comparison
                        if (Math.abs((data[input.dataset.key] || 0) - result) > 0.0000001) {
                            data[input.dataset.key] = result;
                            this.setInputValue(input, result);
                            changed = true;
                        }
                    } catch (err) {
                        console.error('Formula error:', formula, err);
                    }
                }
            });
        }
    }

    evaluateExpression(formula, data) {
        // Replace variable names with values
        // We use a safe regex to match only whole words that are keys in data
        let expr = formula;
        const sortedKeys = Object.keys(data).sort((a, b) => b.length - a.length);
        
        sortedKeys.forEach(key => {
            const regex = new RegExp(`\\b${key}\\b`, 'g');
            let val = data[key];
            // If it's a string that's not a number, use 0 for the math formula to prevent crashes
            if (typeof val === 'string' && isNaN(parseFloat(val))) {
                val = 0;
            }
            expr = expr.replace(regex, `(${val || 0})`);
        });

        // Replace || with JS OR operator (already standard)
        // Clean up any double operators if any
        
        try {
            // Using Function as a safe-ish sandbox for math
            return new Function(`return (${expr})`)();
        } catch (e) {
            return 0;
        }
    }

    updateTotals() {
        const totals = {};
        
        // Iterate through all non-template rows
        const rows = Array.from(this.table.querySelectorAll('tbody tr[data-template="false"]'));
        
        rows.forEach(row => {
            row.querySelectorAll('.lk-dynamic-input').forEach(input => {
                const key = input.dataset.key;
                if (input.dataset.type === 'text') return;
                
                const val = this.getRawValue(input);
                totals[key] = (totals[key] || 0) + val;
            });
        });

        const prefix = this.tipePdrb === 'berlaku' ? 'data-total' : 'data-total-konstan';
        const skipKeys = ['wujud', 'satuan', 'harga_produsen'];

        // 1. First, set all footer cells from the raw totals
        const footerCells = this.table.querySelectorAll(`tfoot [${prefix}]`);
        footerCells.forEach(cell => {
            const key = cell.getAttribute(prefix);
            if (skipKeys.includes(key)) {
                cell.textContent = '-';
                return;
            }
            
            let decimals = 2;
            if (key.includes('rasio') || key.includes('deflator')) decimals = 4;
            
            cell.textContent = this.formatDisplay(totals[key] || 0, decimals);
        });

        // 2. Then, override specific cells that need recalculation (Ratios)
        this.calculateFooterRatios(totals, prefix);
    }

    calculateFooterRatios(totals, prefix) {
        const utama = totals['nilai_output_utama'] || 0;
        const ikut = totals['nilai_output_ikut'] || 0;
        const adh = totals['output_adh'] || 0;
        const ka = totals['konsumsi_antara'] || 0;

        // Effective Rasio Ikutan = Total Nilai Ikut / Total Nilai Utama
        if (utama > 0) {
            this.setFooterValue(prefix, 'rasio_output_ikut', ikut / utama, 4);
        }

        // Effective Rasio Konsumsi = Total Nilai KA / Total Output ADH
        if (adh > 0) {
            this.setFooterValue(prefix, 'rasio_konsumsi_antara', ka / adh, 4);
        }
        
        // NTB should already be the sum of rows, but let's double check it matches ADH - KA
        // For PDRB, Sum(NTB) MUST equal Sum(ADH) - Sum(KA)
        // If there's a discrepancy, we trust ADH - KA for the total
        const ntb = adh - ka;
        this.setFooterValue(prefix, 'nilai_ntb', ntb, 2);
    }

    setFooterValue(prefix, key, val, decimals = 2) {
        const cell = document.querySelector(`[${prefix}="${key}"]`);
        if (cell) cell.textContent = this.formatDisplay(val, decimals);
    }
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.lk-dynamic-table').forEach(table => {
        new LkDynamicCalculator(table);
    });
});
