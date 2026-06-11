import "./bootstrap";

// -- Global Lucide Icons Initialization --
const initIcons = () => {
    if (window.lucide) {
        lucide.createIcons();
    }
};

document.addEventListener('DOMContentLoaded', initIcons);
document.addEventListener('livewire:navigated', initIcons);

document.addEventListener('livewire:initialized', () => {
    Livewire.hook('morph.updated', ({ el, component }) => {
        if (window.lucide) {
            // Se invoca sin parámetros para que escanee todo el DOM recién insertado
            lucide.createIcons();
        }
    });
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
