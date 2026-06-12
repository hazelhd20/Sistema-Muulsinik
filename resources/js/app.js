import "./bootstrap";
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import { Spanish } from "flatpickr/dist/l10n/es.js";
flatpickr.localize(Spanish);
window.flatpickr = flatpickr;

document.addEventListener("alpine:init", () => {
    // Estandarización de Gráficas con Chart.js
    // Uso: x-data="chartCanvas((data) => ({ type: 'line', data: {...}, options: {...} }))"
    Alpine.data("chartCanvas", (configCallback) => ({
        chart: null,
        chartData: [],
        _observer: null,

        init() {
            this.chartData = JSON.parse(this.$el.getAttribute('data-chart') || '[]');

            // Esperar a que el canvas esté en el DOM antes de renderizar
            // (necesario en Livewire 4 con tabs @if que hacen round-trip al servidor)
            this.waitForCanvas();

            // Observar cambios de datos enviados por Livewire
            this._observer = new MutationObserver(() => {
                const raw = this.$el.getAttribute('data-chart');
                if (!raw) return;
                this.chartData = JSON.parse(raw);
                this.renderChart();
            });
            this._observer.observe(this.$el, { attributes: true, attributeFilter: ['data-chart'] });
        },

        waitForCanvas(attempts = 0) {
            const canvas = this.$el.querySelector('canvas');
            if (canvas) {
                this.renderChart();
            } else if (attempts < 10) {
                // Reintentar hasta 10 veces (~200ms total) en caso de que el DOM aún no esté listo
                setTimeout(() => this.waitForCanvas(attempts + 1), 20);
            }
        },

        renderChart() {
            const canvas = this.$el.querySelector('canvas');
            if (!canvas) return;

            // Destruir instancia previa para evitar el error "Canvas is already in use"
            const existingChart = window.Chart?.getChart(canvas);
            if (existingChart) existingChart.destroy();

            // Si Livewire @assets ya inyectó Chart.js, lo usamos
            if (typeof window.Chart === 'undefined') {
                console.warn('Chart.js no está cargado. Usa la directiva @assets de Livewire.');
                return;
            }

            const config = configCallback(this.chartData);
            this.chart = new window.Chart(canvas, config);
        },

        destroy() {
            if (this._observer) this._observer.disconnect();
            if (this.chart) this.chart.destroy();
        }
    }));
});

// Alpine component: Listado de Requisiciones
// Gestiona: tabs, preview modal, filtros, selección masiva
document.addEventListener("alpine:init", () => {
    Alpine.data("requisitionIndex", (selectedRows, pageStatuses = {}) => ({
        activeTab: "todas",
        showFilters: false,
        
        statuses: pageStatuses,

        // -- Preview Modal --
        showPreviewModal: false,
        previewUrl: null,
        previewType: null,
        isPdf() {
            return (
                this.previewType === "application/pdf" ||
                (this.previewUrl &&
                    this.previewUrl.toLowerCase().includes(".pdf"))
            );
        },
        isImage() {
            return (
                (this.previewType && this.previewType.startsWith("image/")) ||
                (this.previewUrl &&
                    this.previewUrl.match(/\.(jpeg|jpg|gif|png)$/i))
            );
        },
        openPreview(url, mimeType) {
            this.previewUrl = url;
            this.previewType = mimeType;
            this.showPreviewModal = true;
        },

        // -- Filtros (sincronizados con $wire Livewire) --
        filterStatus: "",
        filterProject: "",
        filterCreator: "",
        filterVendor: "",
        filterPeriod: "",
        initFilters() {
            this.filterStatus = this.$wire.statusFilter || "";
            this.filterProject = this.$wire.projectFilter || "";
            this.filterCreator = this.$wire.creatorFilter || "";
            this.filterVendor = this.$wire.vendorFilter || "";
            this.filterPeriod = this.$wire.periodFilter || "";
        },
        applyFilters() {
            if (this.$wire.statusFilter !== this.filterStatus) this.$wire.set("statusFilter", this.filterStatus);
            if (this.$wire.projectFilter !== this.filterProject) this.$wire.set("projectFilter", this.filterProject);
            if (this.$wire.creatorFilter !== this.filterCreator) this.$wire.set("creatorFilter", this.filterCreator);
            if (this.$wire.vendorFilter !== this.filterVendor) this.$wire.set("vendorFilter", this.filterVendor);
            if (this.$wire.periodFilter !== this.filterPeriod) this.$wire.set("periodFilter", this.filterPeriod);
        },
        clearFilters() {
            this.filterStatus = "";
            this.filterProject = "";
            this.filterCreator = "";
            this.filterVendor = "";
            this.filterPeriod = "";
            this.applyFilters();
        },

        // -- Selección masiva (totalOnPage se inyecta desde x-init en la vista) --
        selectedRows: selectedRows,
        totalOnPage: 0,
        get allSelected() {
            return (
                this.selectedRows.length > 0 &&
                this.selectedRows.length === this.totalOnPage
            );
        },
        toggleAll(ids) {
            if (this.allSelected) {
                this.selectedRows = [];
            } else {
                this.selectedRows = ids.map(String);
            }
        },
        get canApproveSelection() {
            if (this.selectedRows.length === 0) return false;
            return this.selectedRows.some(id => this.statuses[id] === 'pendiente');
        },

        // -- Lifecycle --
        init() {
            this.initFilters();
        },
    }));

    // -- Expense Index --
    Alpine.data("expenseIndex", (selectedRows) => ({
        selectedRows: selectedRows,
        totalOnPage: 0,
        filterProject: "",
        filterCategory: "",
        filterPeriod: "",
        filterUser: "",
        initFilters() {
            this.filterProject = this.$wire.projectFilter || "";
            this.filterCategory = this.$wire.categoryFilter || "";
            this.filterPeriod = this.$wire.periodFilter || "";
            this.filterUser = this.$wire.userFilter || "";
        },
        applyFilters() {
            if (this.$wire.projectFilter !== this.filterProject) this.$wire.set("projectFilter", this.filterProject);
            if (this.$wire.categoryFilter !== this.filterCategory) this.$wire.set("categoryFilter", this.filterCategory);
            if (this.$wire.periodFilter !== this.filterPeriod) this.$wire.set("periodFilter", this.filterPeriod);
            if (this.$wire.userFilter !== this.filterUser) this.$wire.set("userFilter", this.filterUser);
        },
        clearFilters() {
            this.filterProject = "";
            this.filterCategory = "";
            this.filterPeriod = "";
            this.filterUser = "";
            this.applyFilters();
        },
        get allSelected() {
            return this.selectedRows.length > 0 && this.selectedRows.length === this.totalOnPage;
        },
        toggleAll(ids) {
            if (this.allSelected) {
                this.selectedRows = [];
            } else {
                this.selectedRows = ids.map(String);
            }
        },
        init() {
            this.initFilters();
        }
    }));

    // -- Project Index --
    Alpine.data("projectIndex", (selectedRows) => ({
        selectedRows: selectedRows,
        totalOnPage: 0,
        filterStatus: "",
        filterPeriod: "",
        initFilters() {
            this.filterStatus = this.$wire.statusFilter || "";
            this.filterPeriod = this.$wire.periodFilter || "";
        },
        applyFilters() {
            if (this.$wire.statusFilter !== this.filterStatus) this.$wire.set("statusFilter", this.filterStatus);
            if (this.$wire.periodFilter !== this.filterPeriod) this.$wire.set("periodFilter", this.filterPeriod);
        },
        clearFilters() {
            this.filterStatus = "";
            this.filterPeriod = "";
            this.applyFilters();
        },
        get allSelected() {
            return this.selectedRows.length > 0 && this.selectedRows.length === this.totalOnPage;
        },
        toggleAll(ids) {
            if (this.allSelected) {
                this.selectedRows = [];
            } else {
                this.selectedRows = ids.map(String);
            }
        },
        init() {
            this.initFilters();
        }
    }));

    // -- User Index --
    Alpine.data("userIndex", (selectedRows) => ({
        selectedRows: selectedRows,
        totalOnPage: 0,
        filterRole: "",
        filterStatus: "",
        initFilters() {
            this.filterRole = this.$wire.roleFilter || "";
            this.filterStatus = this.$wire.statusFilter || "";
        },
        applyFilters() {
            if (this.$wire.roleFilter !== this.filterRole) this.$wire.set("roleFilter", this.filterRole);
            if (this.$wire.statusFilter !== this.filterStatus) this.$wire.set("statusFilter", this.filterStatus);
        },
        clearFilters() {
            this.filterRole = "";
            this.filterStatus = "";
            this.applyFilters();
        },
        get allSelected() {
            return this.selectedRows.length > 0 && this.selectedRows.length === this.totalOnPage;
        },
        toggleAll(ids) {
            if (this.allSelected) {
                this.selectedRows = [];
            } else {
                this.selectedRows = ids.map(String);
            }
        },
        init() {
            this.initFilters();
        }
    }));

    // -- Product Index --
    Alpine.data("productIndex", (selectedRows) => ({
        selectedRows: selectedRows,
        totalOnPage: 0,
        filterCategory: "",
        filterMeasure: "",
        initFilters() {
            this.filterCategory = this.$wire.categoryFilter || "";
            this.filterMeasure = this.$wire.measureFilter || "";
        },
        applyFilters() {
            if (this.$wire.categoryFilter !== this.filterCategory) this.$wire.set("categoryFilter", this.filterCategory);
            if (this.$wire.measureFilter !== this.filterMeasure) this.$wire.set("measureFilter", this.filterMeasure);
        },
        clearFilters() {
            this.filterCategory = "";
            this.filterMeasure = "";
            this.applyFilters();
        },
        get allSelected() {
            return this.selectedRows.length > 0 && this.selectedRows.length === this.totalOnPage;
        },
        toggleAll(ids) {
            if (this.allSelected) {
                this.selectedRows = [];
            } else {
                this.selectedRows = ids.map(String);
            }
        },
        init() {
            this.initFilters();
        }
    }));

    // -- Supplier Index --
    Alpine.data("supplierIndex", (selectedRows) => ({
        selectedRows: selectedRows,
        totalOnPage: 0,
        filterCategory: "",
        initFilters() {
            this.filterCategory = this.$wire.categoryFilter || "";
        },
        applyFilters() {
            if (this.$wire.categoryFilter !== this.filterCategory) this.$wire.set("categoryFilter", this.filterCategory);
        },
        clearFilters() {
            this.filterCategory = "";
            this.applyFilters();
        },
        get allSelected() {
            return this.selectedRows.length > 0 && this.selectedRows.length === this.totalOnPage;
        },
        toggleAll(ids) {
            if (this.allSelected) {
                this.selectedRows = [];
            } else {
                this.selectedRows = ids.map(String);
            }
        },
        init() {
            this.initFilters();
        }
    }));

    // -- Quick Budget Index --
    Alpine.data("quickBudgetIndex", (selectedRows) => ({
        selectedRows: selectedRows,
        totalOnPage: 0,
        filterStatus: "",
        filterPeriod: "",
        filterUser: "",
        initFilters() {
            this.filterStatus = this.$wire.statusFilter || "";
            this.filterPeriod = this.$wire.periodFilter || "";
            this.filterUser = this.$wire.userFilter || "";
        },
        applyFilters() {
            if (this.$wire.statusFilter !== this.filterStatus) this.$wire.set("statusFilter", this.filterStatus);
            if (this.$wire.periodFilter !== this.filterPeriod) this.$wire.set("periodFilter", this.filterPeriod);
            if (this.$wire.userFilter !== this.filterUser) this.$wire.set("userFilter", this.filterUser);
        },
        clearFilters() {
            this.filterStatus = "";
            this.filterPeriod = "";
            this.filterUser = "";
            this.applyFilters();
        },
        get allSelected() {
            return this.selectedRows.length > 0 && this.selectedRows.length === this.totalOnPage;
        },
        toggleAll(ids) {
            if (this.allSelected) {
                this.selectedRows = [];
            } else {
                this.selectedRows = ids.map(String);
            }
        },
        init() {
            this.initFilters();
        }
    }));

    // -- Category Index & Measure Index (Only selection, no complex filters) --
    Alpine.data("basicIndex", (selectedRows) => ({
        selectedRows: selectedRows,
        totalOnPage: 0,
        get allSelected() {
            return this.selectedRows.length > 0 && this.selectedRows.length === this.totalOnPage;
        },
        toggleAll(ids) {
            if (this.allSelected) {
                this.selectedRows = [];
            } else {
                this.selectedRows = ids.map(String);
            }
        },
        init() {}
    }));

});
