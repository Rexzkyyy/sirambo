import { formatNumber } from './formatter';
import { showNotification, showProcessing, hideProcessing } from './notifications';
import { updateDiskrepansiNilai, updateDiskrepansiPersen } from './calculations';

export async function recalculateGrowth(input) {
    showProcessing('Menghitung pertumbuhan...');

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
                id_wilayah: input.dataset.idWilayah,
                id_tahun: input.dataset.idTahun,
                id_periode: input.dataset.idPeriode,
                periode_nama: input.dataset.periodeNama
            })
        });

        const result = await response.json();
        updateGrowthCells(input, result.data);
        showNotification('Pertumbuhan diperbarui', 'success');
    } catch (e) {
        showNotification('Gagal menghitung pertumbuhan', 'error');
    } finally {
        hideProcessing();
    }
}

function updateCell(id, value, percent = false) {
    const cell = document.getElementById(id);
    if (!cell) return;
    cell.innerText = formatNumber(value, percent);
    cell.dataset.value = value;
}

function updateGrowthCells(input, data) {
    const base = input.dataset.group === 'kabkota'
        ? `kabkota-${input.dataset.idWilayah}`
        : input.dataset.group;

    const t = input.dataset.idTahun;
    const p = input.dataset.idPeriode;

    updateCell(`qoq-konstan-${base}-${t}-${p}`, data.qoq_konstan, true);
    updateCell(`qoq-konstan-plus-adj-${base}-${t}-${p}`, data.qoq_konstan_plus_adj, true);

    updateCell(`yoy-konstan-${base}-${t}-${p}`, data.yoy_konstan, true);
    updateCell(`yoy-konstan-plus-adj-${base}-${t}-${p}`, data.yoy_konstan_plus_adj, true);

    updateCell(`ctoc-konstan-${base}-${t}-${p}`, data.ctoc_konstan, true);
    updateCell(`ctoc-konstan-plus-adj-${base}-${t}-${p}`, data.ctoc_konstan_plus_adj, true);

    updateCell(`indeks-berlaku-${base}-${t}-${p}`, data.indeks_berlaku);
    updateCell(`indeks-berlaku-plus-adj-${base}-${t}-${p}`, data.indeks_berlaku_plus_adj);

    updateCell(`laju-berlaku-${base}-${t}-${p}`, data.laju_berlaku, true);
    updateCell(`laju-berlaku-plus-adj-${base}-${t}-${p}`, data.laju_berlaku_plus_adj, true);

    if (['provinsi', 'total-kabkota'].includes(input.dataset.group)) {
        updateDiskrepansiNilai(t, p);
        updateDiskrepansiPersen(t, p);
    }
}
