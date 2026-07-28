export function formatNumber(num, isPercent = false, decimals = 2) {
    if (num === null || num === undefined || isNaN(num)) return '-';

    const formatted = parseFloat(num).toLocaleString('id-ID', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });

    return isPercent ? formatted + '%' : formatted;
}
