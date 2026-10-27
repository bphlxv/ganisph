/* ==========================================================================
   GANISPH — aplikasi antarmuka
   - Mode terang/gelap
   - Toggle password & indikator kekuatan
   - Konfirmasi tindakan, loading state, dan pencegahan submit ganda
   - Autofill pegawai, validasi tanggal, dan penghitung karakter
   - Salin NIP, tooltip, serta grafik dashboard
   ========================================================================== */

(() => {
    'use strict';

    const THEME_KEY = 'ganisph-theme';
    const root = document.documentElement;
    const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)');

    /* ----------------------------------------------------------------------
       Utilitas
       ---------------------------------------------------------------------- */

    const $all = (selector, scope = document) => Array.from(scope.querySelectorAll(selector));
    const $one = (selector, scope = document) => scope.querySelector(selector);

    const readTheme = () => {
        try {
            const stored = localStorage.getItem(THEME_KEY);
            if (stored === 'light' || stored === 'dark') {
                return stored;
            }
        } catch (error) {
            /* localStorage dapat diblokir; gunakan preferensi sistem. */
        }

        return prefersDark?.matches ? 'dark' : 'light';
    };

    const currentTheme = () => (root.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light');

    const applyTheme = (theme, persist = true) => {
        const next = theme === 'dark' ? 'dark' : 'light';
        root.setAttribute('data-bs-theme', next);
        root.style.colorScheme = next;

        $all('[data-theme-toggle]').forEach((button) => {
            button.setAttribute('aria-pressed', next === 'dark' ? 'true' : 'false');
            button.setAttribute('title', next === 'dark' ? 'Gunakan mode terang' : 'Gunakan mode gelap');
        });

        if (persist) {
            try {
                localStorage.setItem(THEME_KEY, next);
            } catch (error) {
                /* Abaikan bila penyimpanan tidak tersedia. */
            }
        }

        document.dispatchEvent(new CustomEvent('ganisph:themechange', { detail: { theme: next } }));
    };

    const csrfName = () => {
        const meta = $one('meta[name="csrf-header"]');
        const input = $one('input[type="hidden"][name*="csrf"]');
        return {
            header: meta?.getAttribute('content') || 'X-CSRF-TOKEN',
            field: input?.getAttribute('name') || 'csrf_test_name',
            token: input?.value || '',
        };
    };

    const ensureToastContainer = () => {
        let container = $one('#ganisphToastContainer');
        if (container) {
            return container;
        }

        container = document.createElement('div');
        container.id = 'ganisphToastContainer';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '1090';
        document.body.appendChild(container);
        return container;
    };

    const showToast = (message, variant = 'dark') => {
        const container = ensureToastContainer();
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${variant} border-0`;
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        toast.setAttribute('aria-atomic', 'true');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Tutup"></button>
            </div>`;
        $one('.toast-body', toast).textContent = message;
        container.appendChild(toast);

        if (window.bootstrap?.Toast) {
            const instance = new window.bootstrap.Toast(toast, { delay: 3200 });
            toast.addEventListener('hidden.bs.toast', () => toast.remove(), { once: true });
            instance.show();
        } else {
            setTimeout(() => toast.remove(), 3200);
        }
    };

    const showFieldError = (input, message) => {
        const wrapper = input.closest('.input-group') || input.parentElement;
        let feedback = wrapper?.parentElement?.querySelector('.ganisph-field-error');

        if (!feedback && wrapper?.parentElement) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback d-block ganisph-field-error';
            wrapper.parentElement.appendChild(feedback);
        }

        input.classList.add('is-invalid');
        if (feedback) {
            feedback.textContent = message;
        }
    };

    const clearFieldError = (input) => {
        input.classList.remove('is-invalid');
        const wrapper = input.closest('.input-group') || input.parentElement;
        wrapper?.parentElement?.querySelector('.ganisph-field-error')?.remove();
    };

    /* ----------------------------------------------------------------------
       Tema terang / gelap
       ---------------------------------------------------------------------- */

    applyTheme(currentTheme(), false);

    document.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-theme-toggle]');
        if (!toggle) {
            return;
        }

        event.preventDefault();
        applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
    });

    prefersDark?.addEventListener?.('change', (event) => {
        let stored = null;
        try {
            stored = localStorage.getItem(THEME_KEY);
        } catch (error) {
            /* Abaikan. */
        }

        if (stored !== 'light' && stored !== 'dark') {
            applyTheme(event.matches ? 'dark' : 'light', false);
        }
    });

    /* ----------------------------------------------------------------------
       Toggle visibilitas password
       ---------------------------------------------------------------------- */

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-password-toggle]');
        if (!button) {
            return;
        }

        const target = $one(button.getAttribute('data-password-toggle'));
        if (!target) {
            return;
        }

        const willShow = target.getAttribute('type') === 'password';
        target.setAttribute('type', willShow ? 'text' : 'password');

        const icon = $one('i', button);
        if (icon) {
            icon.classList.toggle('bi-eye', !willShow);
            icon.classList.toggle('bi-eye-slash', willShow);
        }

        button.setAttribute('aria-label', willShow ? 'Sembunyikan password' : 'Tampilkan password');
        button.setAttribute('aria-pressed', willShow ? 'true' : 'false');

        if (willShow) {
            target.focus({ preventScroll: true });
            const length = target.value.length;
            try {
                target.setSelectionRange(length, length);
            } catch (error) {
                /* Beberapa tipe input tidak mendukung selection range. */
            }
        }
    });

    /* ----------------------------------------------------------------------
       Indikator kekuatan password
       ---------------------------------------------------------------------- */

    const strengthLabels = ['Sangat lemah', 'Lemah', 'Cukup', 'Kuat', 'Sangat kuat'];

    const scorePassword = (value) => {
        if (!value) {
            return 0;
        }

        let score = 0;

        if (value.length >= 6) score++;
        if (value.length >= 10) score++;
        if (value.length >= 14) score++;

        const classes = [/[a-z]/, /[A-Z]/, /[0-9]/, /[^A-Za-z0-9]/].filter((pattern) => pattern.test(value)).length;
        score += classes >= 3 ? 1 : 0;
        score += classes === 4 ? 1 : 0;

        if (/^(.)\1+$/.test(value) || /^(12345|password|qwerty|admin|ganisph)/i.test(value)) {
            score = Math.min(score, 1);
        }

        return Math.max(0, Math.min(4, score));
    };

    const setupStrengthMeter = (input) => {
        const wrapper = $one(input.getAttribute('data-password-strength'));
        if (!wrapper) {
            return;
        }

        const label = $one('[data-strength-label]', wrapper);
        const update = () => {
            const score = scorePassword(input.value);
            wrapper.setAttribute('data-visible', input.value.length > 0 ? 'true' : 'false');
            wrapper.setAttribute('data-score', String(score));

            if (label) {
                label.textContent = input.value.length > 0 ? strengthLabels[score] : '-';
            }
        };

        input.addEventListener('input', update);
        update();
    };

    /* ----------------------------------------------------------------------
       Konfirmasi tindakan
       ---------------------------------------------------------------------- */

    const confirmModalElement = $one('#confirmModal');
    let pendingForm = null;
    let pendingSubmitter = null;
    let confirmed = false;

    if (confirmModalElement && window.bootstrap?.Modal) {
        const modal = new window.bootstrap.Modal(confirmModalElement);
        const message = $one('#confirmModalMessage', confirmModalElement);
        const submitButton = $one('#confirmModalSubmit', confirmModalElement);

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) {
                return;
            }

            if (confirmed) {
                confirmed = false;
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            pendingForm = form;
            pendingSubmitter = event.submitter || null;

            if (message) {
                message.textContent = form.getAttribute('data-confirm') || 'Apakah Anda yakin ingin melanjutkan?';
            }

            modal.show();
        }, true);

        modalElementListener();

        function modalElementListener() {
            confirmModalElement.addEventListener('hidden.bs.modal', () => {
                pendingForm = null;
                pendingSubmitter = null;
            });

            submitButton?.addEventListener('click', () => {
                if (!pendingForm) {
                    return;
                }

                const form = pendingForm;
                const submitter = pendingSubmitter;

                confirmed = true;
                modal.hide();
                pendingForm = null;
                pendingSubmitter = null;

                if (submitter) {
                    form.requestSubmit(submitter);
                } else {
                    form.requestSubmit();
                }
            });
        }
    }

    /* ----------------------------------------------------------------------
       Loading state & pencegahan submit ganda
       ---------------------------------------------------------------------- */

    const setButtonLoading = (button, loading) => {
        if (!button) {
            return;
        }

        if (loading) {
            if (button.dataset.loadingOriginal !== undefined) {
                return;
            }

            button.dataset.loadingOriginal = button.innerHTML;
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Memproses...';
        } else {
            if (button.dataset.loadingOriginal === undefined) {
                return;
            }

            button.innerHTML = button.dataset.loadingOriginal;
            delete button.dataset.loadingOriginal;
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
    };

    let submittingForms = new WeakSet();

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented) {
            return;
        }

        if (form.hasAttribute('data-confirm') && !confirmed) {
            return;
        }

        if (submittingForms.has(form)) {
            event.preventDefault();
            return;
        }

        submittingForms.add(form);

        const submitter = event.submitter;
        const button = submitter instanceof HTMLButtonElement || submitter instanceof HTMLInputElement
            ? submitter
            : $one('[data-loading-button], button[type="submit"], input[type="submit"]', form);

        if (form.hasAttribute('data-loading-form') || button?.hasAttribute('data-loading-button')) {
            setButtonLoading(button, true);

            // Jika navigasi dibatalkan (mis. validasi gagal), pulihkan tombol.
            setTimeout(() => {
                if (document.visibilityState === 'visible' && !button?.isConnected) {
                    return;
                }
            }, 0);
        }

        form.addEventListener('invalid', () => {
            submittingForms.delete(form);
            setButtonLoading(button, false);
        }, { capture: true, once: true });
    });

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            submittingForms = new WeakSet();
            $all('[data-loading-button]').forEach((button) => setButtonLoading(button, false));
        }
    });

    /* ----------------------------------------------------------------------
       Autofill data pegawai pada form penugasan
       ---------------------------------------------------------------------- */

    const userSelect = $one('#user_id');
    if (userSelect) {
        const nipField = $one('#pegawai_nip');
        const jabatanField = $one('#pegawai_jabatan');

        const fillEmployee = () => {
            const option = userSelect.selectedOptions?.[0];
            if (nipField) {
                nipField.value = option?.getAttribute('data-nip') || '';
            }
            if (jabatanField) {
                jabatanField.value = option?.getAttribute('data-jabatan') || '';
            }
        };

        userSelect.addEventListener('change', fillEmployee);
        fillEmployee();
    }

    /* ----------------------------------------------------------------------
       Validasi tanggal dan penghitung karakter
       ---------------------------------------------------------------------- */

    const dateStart = $one('#tanggal_mulai');
    const dateEnd = $one('#tanggal_selesai');

    if (dateStart && dateEnd) {
        const syncEndDate = () => {
            dateEnd.min = dateStart.value || '';
            if (dateEnd.value && dateStart.value && dateEnd.value < dateStart.value) {
                dateEnd.value = dateStart.value;
                showToast('Tanggal selesai disesuaikan dengan tanggal mulai.', 'warning');
            }
        };

        dateStart.addEventListener('change', syncEndDate);
        syncEndDate();

        dateEnd.addEventListener('change', () => {
            if (dateStart.value && dateEnd.value && dateEnd.value < dateStart.value) {
                showFieldError(dateEnd, 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.');
            } else {
                clearFieldError(dateEnd);
            }
        });
    }

    $all('[data-char-count]').forEach((input) => {
        const output = $one(input.getAttribute('data-char-count'));
        if (!output) {
            return;
        }

        const update = () => {
            output.textContent = String(input.value.length);
        };

        input.addEventListener('input', update);
        update();
    });

    /* ----------------------------------------------------------------------
       Salin teks (NIP, dsb.)
       ---------------------------------------------------------------------- */

    const fallbackCopy = (text) => {
        const helper = document.createElement('textarea');
        helper.value = text;
        helper.setAttribute('readonly', '');
        helper.style.position = 'fixed';
        helper.style.opacity = '0';
        document.body.appendChild(helper);
        helper.select();

        let success = false;
        try {
            success = document.execCommand('copy');
        } catch (error) {
            success = false;
        }

        helper.remove();
        return success;
    };

    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-copy]');
        if (!button) {
            return;
        }

        event.preventDefault();
        const text = button.getAttribute('data-copy') || '';

        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(text);
            } else if (!fallbackCopy(text)) {
                throw new Error('Clipboard tidak tersedia.');
            }

            const icon = $one('i', button);
            if (icon) {
                icon.className = 'bi bi-check2';
                setTimeout(() => {
                    icon.className = 'bi bi-copy';
                }, 1600);
            }

            showToast('NIP ' + text + ' disalin ke clipboard.', 'success');
        } catch (error) {
            showToast('Gagal menyalin. Salin teks secara manual: ' + text, 'danger');
        }
    });

    /* ----------------------------------------------------------------------
       Tooltip & auto-dismiss alert
       ---------------------------------------------------------------------- */

    if (window.bootstrap?.Tooltip) {
        $all('[title]').forEach((element) => {
            if (element.closest('.dropdown-menu') || element.hasAttribute('data-bs-toggle')) {
                return;
            }

            new window.bootstrap.Tooltip(element, { placement: 'top', trigger: 'hover focus' });
        });
    }

    $all('.alert-success.app-alert').forEach((alert) => {
        setTimeout(() => {
            if (!alert.isConnected || !window.bootstrap?.Alert) {
                return;
            }
            window.bootstrap.Alert.getOrCreateInstance(alert).close();
        }, 8000);
    });

    /* ----------------------------------------------------------------------
       Grafik dashboard
       ---------------------------------------------------------------------- */

    const chartCanvas = $one('#monthlyChart');
    if (chartCanvas && window.Chart && window.ganisphMonthlyChart) {
        const data = window.ganisphMonthlyChart;
        let chart = null;

        const themeColors = () => {
            const dark = currentTheme() === 'dark';
            return {
                text: dark ? '#94a3b8' : '#64748b',
                grid: dark ? 'rgba(148, 163, 184, 0.14)' : 'rgba(15, 23, 42, 0.08)',
                line: dark ? '#60a5fa' : '#2563eb',
                fillTop: dark ? 'rgba(96, 165, 250, 0.30)' : 'rgba(37, 99, 235, 0.22)',
                fillBottom: dark ? 'rgba(96, 165, 250, 0.02)' : 'rgba(37, 99, 235, 0.02)',
                point: dark ? '#0e1a2c' : '#ffffff',
            };
        };

        const buildChart = () => {
            const colors = themeColors();
            const context = chartCanvas.getContext('2d');
            const gradient = context.createLinearGradient(0, 0, 0, chartCanvas.parentElement?.clientHeight || 260);
            gradient.addColorStop(0, colors.fillTop);
            gradient.addColorStop(1, colors.fillBottom);

            chart = new window.Chart(context, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Jumlah penugasan',
                        data: data.values,
                        borderColor: colors.line,
                        backgroundColor: gradient,
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.38,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: colors.point,
                        pointBorderColor: colors.line,
                        pointBorderWidth: 2,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: currentTheme() === 'dark' ? '#0b1526' : '#0b2f5b',
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: false,
                            callbacks: {
                                label: (item) => ` ${item.formattedValue} penugasan`,
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: { color: colors.text, font: { size: 11 } },
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: colors.text,
                                precision: 0,
                                maxTicksLimit: 6,
                                font: { size: 11 },
                            },
                            grid: { color: colors.grid, drawTicks: false },
                            border: { display: false },
                        },
                    },
                },
            });
        };

        const refreshChartTheme = () => {
            if (!chart) {
                return;
            }

            const colors = themeColors();
            const gradient = chartCanvas.getContext('2d').createLinearGradient(0, 0, 0, chartCanvas.parentElement?.clientHeight || 260);
            gradient.addColorStop(0, colors.fillTop);
            gradient.addColorStop(1, colors.fillBottom);

            chart.data.datasets[0].borderColor = colors.line;
            chart.data.datasets[0].backgroundColor = gradient;
            chart.data.datasets[0].pointBackgroundColor = colors.point;
            chart.data.datasets[0].pointBorderColor = colors.line;
            chart.options.plugins.tooltip.backgroundColor = currentTheme() === 'dark' ? '#0b1526' : '#0b2f5b';
            chart.options.scales.x.ticks.color = colors.text;
            chart.options.scales.y.ticks.color = colors.text;
            chart.options.scales.y.grid.color = colors.grid;
            chart.update('none');
        };

        buildChart();
        document.addEventListener('ganisph:themechange', refreshChartTheme);
    }
})();
