import { formatNumber } from './formatter';

export function hitungTotalKabKota(idTahun, idPeriode, tipe) {
    let totalAdj = 0;
    let totalPdrb = 0;

    document.querySelectorAll(
        
        `.adj-kabkota[data-id-tahun="${idTahun}"][data-id-periode="${idPeriode}"][data-tipe="${tipe}"]`
    
    ).forEach(input => {

      
        totalAdj += parseFloat(input.value) || 0;
        totalPdrb += parseFloat(input.dataset.pdrb) || 0;
        
    });

    const totalInput = document.getElementById(`adj-${tipe}-total-${idTahun}-${idPeriode}`);
    
    
    if (!totalInput) return;

    totalInput.value = totalAdj.toFixed(2);
const totalValue = totalPdrb + totalAdj;

document
  .querySelectorAll(`#pdrb-plus-adj-${tipe}-total-kab-total-${idTahun}`)
  .forEach(cell => {
      cell.innerText = formatNumber(totalValue);
      cell.dataset.value = totalValue;
  });



   

    updateDiskrepansiNilai(idTahun, idPeriode);
    updateDiskrepansiPersen(idTahun, idPeriode);
}

export function updateDiskrepansiNilai(idTahun, idPeriode) {
    ['berlaku', 'konstan'].forEach(tipe => {
        const prov = document.getElementById(`pdrb-plus-adj-${tipe}-provinsi-${idTahun}-${idPeriode}`);
        const total = document.getElementById(`pdrb-plus-adj-${tipe}-total-${idTahun}-${idPeriode}`);

        const provVal = prov ? parseFloat(prov.dataset.value) || 0 : 0;
        const totalVal = total ? parseFloat(total.dataset.value) || 0 : 0;

        const diff = provVal - totalVal;

        const cell = document.getElementById(`diskrepansi-pdrb-adj-${tipe}-${idTahun}-${idPeriode}`);
        if (cell) {
            cell.innerText = formatNumber(diff);
            cell.dataset.value = diff;
        }
    });
}

export function updateDiskrepansiPersen(idTahun, idPeriode) {
    ['berlaku', 'konstan'].forEach(tipe => {
        const prov = document.getElementById(`pdrb-plus-adj-${tipe}-provinsi-${idTahun}-${idPeriode}`);
        const total = document.getElementById(`pdrb-plus-adj-${tipe}-total-${idTahun}-${idPeriode}`);

        const provVal = prov ? parseFloat(prov.dataset.value) || 0 : 0;
        const totalVal = total ? parseFloat(total.dataset.value) || 0 : 0;

        const percent = totalVal !== 0 ? ((provVal - totalVal) / Math.abs(totalVal)) * 100 : 0;

        const cell = document.getElementById(`diskrepansi-persen-pdrb-adj-${tipe}-${idTahun}-${idPeriode}`);
        if (cell) {
            cell.innerText = formatNumber(percent, true);
            cell.dataset.value = percent;
        }
    });
}
