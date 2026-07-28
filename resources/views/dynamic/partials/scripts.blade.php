<script>
    document.addEventListener('DOMContentLoaded', () => {
        const showButton = document.getElementById('showTableBtn');
        const tableWrapper = document.getElementById('dynamic-table-wrapper');
        const previewUrl = '{{ route('pdrb.dynamic.preview') }}';
        const exportUrl = '{{ route('pdrb.dynamic.export') }}';
        const exportButton = document.getElementById('exportXlsxBtn');
        const selectAllYears = document.getElementById('selectAllYears');
        const selectAllComponents = document.getElementById('selectAllComponents');
        const selectAllPeriods = document.getElementById('selectAllPeriods');
        const scrollKey = 'pdrb_dynamic_scroll';
        const indicatorScrollKey = 'pdrb_dynamic_indicator_scroll';
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }

        const getScrollContainer = () => {
            const main = document.querySelector('main');
            if (main && main.scrollHeight > main.clientHeight + 1) {
                return { type: 'main', el: main };
            }
            const scrollingEl = document.scrollingElement || document.documentElement;
            return { type: 'window', el: scrollingEl };
        };

        const saveScrollPosition = () => {
            const { type, el } = getScrollContainer();
            const payload = {
                type,
                top: type === 'main' ? el.scrollTop : window.scrollY
            };
            sessionStorage.setItem(scrollKey, JSON.stringify(payload));

            const indicatorList = document.getElementById('indicator-list-scroll');
            if (indicatorList) {
                sessionStorage.setItem(indicatorScrollKey, String(indicatorList.scrollTop || 0));
            }
        };

        const restoreScrollPosition = () => {
            const raw = sessionStorage.getItem(scrollKey);
            if (!raw) {
                // still try to restore indicator list scroll
                const indicatorRaw = sessionStorage.getItem(indicatorScrollKey);
                if (indicatorRaw !== null) {
                    sessionStorage.removeItem(indicatorScrollKey);
                    const indicatorList = document.getElementById('indicator-list-scroll');
                    if (indicatorList) {
                        indicatorList.scrollTop = parseInt(indicatorRaw, 10) || 0;
                    }
                }
                return;
            }
            sessionStorage.removeItem(scrollKey);
            let data = null;
            try {
                data = JSON.parse(raw);
            } catch (e) {
                return;
            }

            const targetTop = typeof data?.top === 'number' ? data.top : null;
            if (targetTop === null) return;

            const attemptRestore = (tries = 0) => {
                const { type, el } = getScrollContainer();
                if (type === 'main') {
                    el.scrollTop = targetTop;
                    if (tries < 8 && Math.abs(el.scrollTop - targetTop) > 2) {
                        setTimeout(() => attemptRestore(tries + 1), 60);
                    }
                } else {
                    window.scrollTo(0, targetTop);
                    if (tries < 8 && Math.abs(window.scrollY - targetTop) > 2) {
                        setTimeout(() => attemptRestore(tries + 1), 60);
                    }
                }

                const indicatorRaw = sessionStorage.getItem(indicatorScrollKey);
                if (indicatorRaw !== null) {
                    sessionStorage.removeItem(indicatorScrollKey);
                    const indicatorList = document.getElementById('indicator-list-scroll');
                    if (indicatorList) {
                        indicatorList.scrollTop = parseInt(indicatorRaw, 10) || 0;
                    }
                }
            };

            requestAnimationFrame(() => attemptRestore());
            setTimeout(() => attemptRestore(), 120);
        };

        const collectFormData = () => {
            const params = new URLSearchParams();
            const indicatorChecked = document.querySelector('input[name="indicator"]:checked');
            if (indicatorChecked) {
                params.append('indicator', indicatorChecked.value);
            }
            const yearCheckboxes = document.querySelectorAll('input[name="tahun_ids[]"]:checked');
            yearCheckboxes.forEach(cb => params.append('tahun_ids[]', cb.value));
            document.querySelectorAll('input[name="periode_checks[]"]:checked').forEach(cb => params.append('periode_checks[]', cb.value));
            const componentCheckboxes = document.querySelectorAll('input[name="component_ids[]"]:checked');
            componentCheckboxes.forEach(cb => params.append('component_ids[]', cb.value));
            const totalCheckbox = document.querySelector('input[name="include_total"]');
            if (totalCheckbox && totalCheckbox.checked) {
                params.append('include_total', '1');
            }
            return params;
        };

        const isFormComplete = () => {
            const indicatorChecked = document.querySelector('input[name="indicator"]:checked');
            const yearChecked = document.querySelectorAll('input[name="tahun_ids[]"]:checked').length > 0;
            const periodChecked = document.querySelectorAll('input[name="periode_checks[]"]:checked').length > 0;
            const totalChecked = document.querySelector('input[name="include_total"]')?.checked;
            const componentChecked = document.querySelectorAll('input[name="component_ids[]"]:checked').length > 0;
            return !!indicatorChecked && yearChecked && (periodChecked || totalChecked) && componentChecked;
        };

        const updateButtonState = () => {
            const canShow = isFormComplete();
            showButton.disabled = !canShow;
            showButton.classList.toggle('opacity-60', !canShow);
            showButton.classList.toggle('cursor-not-allowed', !canShow);
            if (exportButton) {
                exportButton.disabled = !canShow;
                exportButton.classList.toggle('opacity-60', !canShow);
                exportButton.classList.toggle('cursor-not-allowed', !canShow);
            }
            const hint = document.getElementById('showTableHint');
            if (hint) {
                hint.textContent = canShow
                    ? 'Siap menampilkan tabel.'
                    : 'Lengkapi pilihan di tiap tahap agar tombol aktif.';
            }
        };

        const updatePeriodeState = () => {
            const selectedIndicator = document.querySelector('input[name="indicator"]:checked');
            const calcMode = selectedIndicator?.dataset?.calc || '';
            const isPerkapita = calcMode === 'perkapita';

            const periodCheckboxes = document.querySelectorAll('input[name="periode_checks[]"]');
            periodCheckboxes.forEach(cb => {
                if (isPerkapita) {
                    cb.checked = false;
                }
                cb.disabled = isPerkapita;
                cb.closest('label')?.classList.toggle('opacity-60', isPerkapita);
            });

            if (selectAllPeriods) {
                selectAllPeriods.checked = false;
                selectAllPeriods.disabled = isPerkapita;
                selectAllPeriods.closest('label')?.classList.toggle('opacity-60', isPerkapita);
            }

            const totalCheckbox = document.querySelector('input[name="include_total"]');
            if (totalCheckbox) {
                if (isPerkapita) {
                    totalCheckbox.checked = true;
                    totalCheckbox.disabled = true;
                } else {
                    totalCheckbox.disabled = false;
                }
            }

            updateButtonState();
        };

        document.querySelectorAll('input[name="indicator"]').forEach(radio => {
            radio.addEventListener('change', () => {
                saveScrollPosition();
                const params = collectFormData();
                window.location.href = `${window.location.pathname}?${params.toString()}`;
            });
        });

        if (selectAllYears) {
            selectAllYears.addEventListener('change', () => {
                const checked = selectAllYears.checked;
                document.querySelectorAll('input[name="tahun_ids[]"]').forEach(cb => {
                    cb.checked = checked;
                });
                updateButtonState();
            });
        }

        if (selectAllComponents) {
            selectAllComponents.addEventListener('click', () => {
                const checkboxes = document.querySelectorAll('input[name="component_ids[]"]');
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                checkboxes.forEach(cb => cb.checked = !allChecked);
                updateButtonState();
            });
        }

        if (selectAllPeriods) {
            selectAllPeriods.addEventListener('change', () => {
                const checked = selectAllPeriods.checked;
                document.querySelectorAll('input[name="periode_checks[]"]').forEach(cb => {
                    cb.checked = checked;
                });
                const includeTotal = document.querySelector('input[name="include_total"]');
                if (includeTotal && !includeTotal.disabled) {
                    includeTotal.checked = checked;
                }
                updateButtonState();
            });
        }

        document.querySelectorAll('input[name="tahun_ids[]"]').forEach(cb => {
            cb.addEventListener('change', () => {
                if (!selectAllYears) return;
                const allChecked = Array.from(document.querySelectorAll('input[name="tahun_ids[]"]'))
                    .every(el => el.checked);
                selectAllYears.checked = allChecked;
                updateButtonState();
            });
        });

        document.querySelectorAll('input[name="component_ids[]"]').forEach(cb => {
            cb.addEventListener('change', () => {
                if (!selectAllComponents) return;
                const allChecked = Array.from(document.querySelectorAll('input[name="component_ids[]"]'))
                    .every(el => el.checked);
                selectAllComponents.checked = allChecked;
                updateButtonState();
            });
        });

        document.querySelectorAll('input[name="periode_checks[]"]').forEach(cb => {
            cb.addEventListener('change', () => {
                if (!selectAllPeriods) return;
                const periods = Array.from(document.querySelectorAll('input[name="periode_checks[]"]'));
                const includeTotal = document.querySelector('input[name="include_total"]');
                const allChecked = periods.every(el => el.checked) && (!includeTotal || includeTotal.checked);
                selectAllPeriods.checked = allChecked;
            });
        });

        const includeTotalCb = document.querySelector('input[name="include_total"]');
        if (includeTotalCb) {
            includeTotalCb.addEventListener('change', () => {
                if (!selectAllPeriods) return;
                const periods = Array.from(document.querySelectorAll('input[name="periode_checks[]"]'));
                const allChecked = periods.every(el => el.checked) && includeTotalCb.checked;
                selectAllPeriods.checked = allChecked;
            });
        }

        document.querySelectorAll('input[name="periode_checks[]"], input[name="include_total"]').forEach(cb => {
            cb.addEventListener('change', updateButtonState);
        });

        showButton.addEventListener('click', async () => {
            if (!isFormComplete()) {
                updateButtonState();
                return;
            }
            const params = collectFormData();
            try {
                const response = await fetch(`${previewUrl}?${params.toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) throw new Error('preview');
                tableWrapper.innerHTML = await response.text();
            } catch (error) {
                console.error('Preview update failed', error);
                tableWrapper.innerHTML = '<p class="text-sm text-red-500">Gagal memuat tabel. Coba lagi.</p>';
            }
        });

        if (exportButton) {
            exportButton.addEventListener('click', () => {
                if (!isFormComplete()) {
                    updateButtonState();
                    return;
                }
                const params = collectFormData();
                window.location.href = `${exportUrl}?${params.toString()}`;
            });
        }

        window.addEventListener('beforeunload', saveScrollPosition);
        restoreScrollPosition();
        updatePeriodeState();
        updateButtonState();
    });
</script>