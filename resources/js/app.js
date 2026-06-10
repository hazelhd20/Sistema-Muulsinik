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
            this.$wire.set("statusFilter", this.filterStatus, false);
            this.$wire.set("projectFilter", this.filterProject, false);
            this.$wire.set("creatorFilter", this.filterCreator, false);
            this.$wire.set("vendorFilter", this.filterVendor, false);
            this.$wire.set("periodFilter", this.filterPeriod, false);
            this.$wire.$refresh();
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
});
