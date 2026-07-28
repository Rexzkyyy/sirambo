import { formatNumber } from './formatter';
import { hitungTotalKabKota } from './calculations';
import { showNotification, showProcessing, hideProcessing } from './notifications';
import { recalculateGrowth } from './growth';

export function initAdjInput() {
    document.querySelectorAll('.adj-input').forEach(input => {

        input.addEventListener('input', () => {
            const adj = parseFloat(input.value) || 0;
            const pdrb = parseFloat(input.dataset.pdrb) || 0;
            const total = pdrb + adj;

            if (input.dataset.target) {
                const target = document.getElementById(input.dataset.target);
                if (target) {
                    target.innerText = formatNumber(total);
                    target.dataset.value = total;
                }
            }

            if (input.dataset.group === 'kabkota') {
                hitungTotalKabKota(
                    input.dataset.idTahun,
                    input.dataset.idPeriode,
                    input.dataset.tipe
                );
            }
        });

       input.addEventListener('blur', async () => {
    const nilai = input.value === '' ? 0 : parseFloat(input.value);

    const payload = {
        id_sub_kategori: input.dataset.subKategori,
        id_wilayah: input.dataset.idWilayah,
        id_tahun: input.dataset.idTahun,
        id_periode: input.dataset.idPeriode,
        tipe: input.dataset.tipe,
        nilai: nilai
    };

    console.log('Payload dikirim:', payload);

    // VALIDASI KERAS
    if (!payload.id_sub_kategori) {
        showNotification('Sub kategori kosong!', 'error');
        console.error('dataset:', input.dataset);
        return;
    }

    showProcessing('Menyimpan adjustment...');

    try {
        const res = await fetch(window.REKON_UPDATE_ADJ_URL, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.CSRF_TOKEN,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const result = await res.json();
        console.log('Response:', result);

        showNotification('Adjustment disimpan', 'success');

        // update total UI
        const adj = nilai;
        const pdrb = parseFloat(input.dataset.pdrb) || 0;
        const total = pdrb + adj;

        if (input.dataset.target) {
            const target = document.getElementById(input.dataset.target);
            if (target) {
                target.innerText = formatNumber(total);
                target.dataset.value = total;
            }
        }

        // recalculation tambahan
        if (input.dataset.group === 'kabkota') {
            hitungTotalKabKota(
                input.dataset.idTahun,
                input.dataset.idPeriode,
                input.dataset.tipe
            );
        }

        recalculateGrowth(input);

    } catch (err) {
        console.error(err);
        showNotification('Gagal menyimpan adjustment', 'error');
    } finally {
        hideProcessing();
    }
});



        input.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                input.blur();
            }
        });
    });
}
