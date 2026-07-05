import "./bootstrap";
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import { Spanish } from "flatpickr/dist/l10n/es.js";
flatpickr.localize(Spanish);
window.flatpickr = flatpickr;

document.addEventListener('alpine:init', () => {
    Alpine.data('datePicker', (config = {}) => ({
        value: config.value || '',
        picker: null,
        init() {
            this.picker = flatpickr(this.$refs.input, {
                defaultDate: this.value,
                dateFormat: 'Y-m-d',
                ...config.options,
                onChange: (selectedDates, dateStr) => {
                    this.value = dateStr;
                    this.$dispatch('input', dateStr);
                },
                onClose: (selectedDates, dateStr, instance) => {
                    setTimeout(() => {
                        instance.input.blur();
                    }, 0);
                }
            });

            this.$watch('value', (newValue) => {
                if (this.picker && newValue !== this.picker.input.value) {
                    this.picker.setDate(newValue);
                }
            });
        },
        destroy() {
            if (this.picker) {
                this.picker.destroy();
            }
        }
    }));
});

document.addEventListener("alpine:init", () => {
    Alpine.data("chartCanvas", (configCallback) => ({
        chart: null,
        chartData: [],
        _observer: null,
        _themeObserver: null,

        init() {
            this.chartData = JSON.parse(this.$el.getAttribute('data-chart') || '[]');
            this.waitForCanvas();
            this._observer = new MutationObserver(() => {
                const raw = this.$el.getAttribute('data-chart');
                if (!raw) return;
                this.chartData = JSON.parse(raw);
                this.renderChart();
            });
            this._observer.observe(this.$el, { attributes: true, attributeFilter: ['data-chart'] });

            // Redibujar automáticamente cuando el usuario cambie entre modo claro y oscuro
            this._themeObserver = new MutationObserver(() => {
                this.renderChart();
            });
            this._themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        },

        waitForCanvas(attempts = 0) {
            const canvas = this.$el.querySelector('canvas');
            if (canvas) {
                this.renderChart();
            } else if (attempts < 10) {
                setTimeout(() => this.waitForCanvas(attempts + 1), 20);
            }
        },

        renderChart() {
            const canvas = this.$el.querySelector('canvas');
            if (!canvas) return;

            const existingChart = window.Chart?.getChart(canvas);
            if (existingChart) existingChart.destroy();

            if (typeof window.Chart === 'undefined') {
                console.warn('Chart.js no está cargado. Usa la directiva @assets de Livewire.');
                return;
            }

            // Adaptar colores de texto y líneas divisorias de Chart.js al tema actual (Claro/Oscuro)
            const style = getComputedStyle(document.documentElement);
            const textColor = style.getPropertyValue('--text-secondary').trim() || '#64748b';
            const borderColor = style.getPropertyValue('--border').trim() || '#e2e8f0';

            window.Chart.defaults.color = textColor;
            window.Chart.defaults.borderColor = borderColor;

            const config = configCallback(this.chartData);
            this.chart = new window.Chart(canvas, config);
        },

        destroy() {
            if (this._observer) this._observer.disconnect();
            if (this._themeObserver) this._themeObserver.disconnect();
            if (this.chart) this.chart.destroy();
        }
    }));
});

// ─── Fábrica genérica para TODOS los índices ───────────────────
function createIndexComponent(filterMap = {}) {
    return (selectedRows = [], extras = {}) => {
        const base = {
            selectedRows,
            totalOnPageStatic: 0,
            get totalOnPage() {
                return parseInt(this.$el?.getAttribute('data-total-on-page')) || this.totalOnPageStatic || 0;
            },
            ...Object.fromEntries(Object.keys(filterMap).map(k => [k, ''])),

            initFilters() {
                Object.entries(filterMap).forEach(([alpineKey, livewireKey]) => {
                    this[alpineKey] = this.$wire[livewireKey] || '';
                });
            },
            applyFilters() {
                Object.entries(filterMap).forEach(([alpineKey, livewireKey]) => {
                    if (this.$wire[livewireKey] !== this[alpineKey]) {
                        this.$wire.set(livewireKey, this[alpineKey]);
                    }
                });
            },
            clearFilters() {
                Object.keys(filterMap).forEach(alpineKey => { this[alpineKey] = ''; });
                this.applyFilters();
            },
            get allSelected() {
                return this.selectedRows && this.selectedRows.length > 0 && this.selectedRows.length === this.totalOnPage;
            },
            toggleAll(ids) {
                if (this.allSelected) {
                    this.selectedRows = [];
                } else {
                    this.selectedRows = ids.map(String);
                }
            },
            init() { this.initFilters(); }
        };

        const descriptors = Object.getOwnPropertyDescriptors(extras);
        Object.defineProperties(base, descriptors);

        return base;
    };
}

document.addEventListener('alpine:init', () => {
    const requisitionStatuses = {};

    Alpine.data('requisitionIndex', (rows, statuses = {}) => {
        Object.assign(requisitionStatuses, statuses);
        return createIndexComponent({
            filterStatus: 'statusFilter',
            filterProject: 'projectFilter',
            filterCreator: 'creatorFilter',
            filterVendor: 'vendorFilter',
            filterPeriod: 'periodFilter',
            filterDateFrom: 'dateFrom',
            filterDateTo: 'dateTo'
        })(rows, {
            statuses: requisitionStatuses,
            get canApproveSelection() {
                return this.selectedRows.length > 0
                    && this.selectedRows.some(id => this.statuses[id] === 'pendiente');
            }
        });
    });

    Alpine.data('expenseIndex',
        createIndexComponent({
            filterProject: 'projectFilter',
            filterCategory: 'categoryFilter',
            filterPeriod: 'periodFilter',
            filterUser: 'userFilter',
            filterDateFrom: 'dateFrom',
            filterDateTo: 'dateTo'
        })
    );

    Alpine.data('projectIndex',
        createIndexComponent({
            filterStatus: 'statusFilter',
            filterPeriod: 'periodFilter',
            filterDateFrom: 'dateFrom',
            filterDateTo: 'dateTo'
        })
    );

    Alpine.data('userIndex',
        createIndexComponent({
            filterRole: 'roleFilter',
            filterStatus: 'statusFilter',
            filterTrashed: 'trashedFilter'
        })
    );

    Alpine.data('productIndex',
        createIndexComponent({
            filterCategory: 'categoryFilter',
            filterMeasure: 'measureFilter',
            filterType: 'typeFilter',
            filterTrashed: 'trashedFilter'
        })
    );

    Alpine.data('supplierIndex',
        createIndexComponent({
            filterCategory: 'categoryFilter',
            filterStatus: 'statusFilter',
            filterTrashed: 'trashedFilter'
        })
    );

    Alpine.data('clientIndex',
        createIndexComponent({
            filterActive: 'activeFilter',
            filterTrashed: 'trashedFilter'
        })
    );

    Alpine.data('quickBudgetIndex',
        createIndexComponent({
            filterStatus: 'statusFilter',
            filterPeriod: 'periodFilter',
            filterUser: 'userFilter',
            filterDateFrom: 'dateFrom',
            filterDateTo: 'dateTo'
        })
    );

    Alpine.data('measureIndex',
        createIndexComponent({
            filterUsage: 'usageFilter',
            filterTrashed: 'trashedFilter'
        })
    );

    Alpine.data('categoryIndex',
        createIndexComponent({
            filterUsage: 'usageFilter',
            filterTrashed: 'trashedFilter'
        })
    );

    Alpine.data('basicIndex',
        createIndexComponent({})
    );
});
