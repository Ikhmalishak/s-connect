<template>
  <div class="container-table">
    <!-- Header -->
    <div class="table-header">
      <h2 class="table-title">Container/Truck Info (By Shipping)</h2>
      <div class="header-actions">
        <button class="btn-icon" @click="editColumns">
          <span class="icon">⚙️</span> Edit columns
        </button>
        <button class="btn-icon" @click="editFilters">
          <span class="icon">🔍</span> Edit filters
        </button>
        <input
          type="text"
          class="search-input"
          placeholder="Filter by keyword"
          v-model="searchKeyword"
          @input="debouncedSearch"
        />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <p>Loading container shipments...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <p>Error loading data: {{ error }}</p>
      <button @click="fetchShipments" class="btn-retry">Retry</button>
    </div>

    <!-- Table -->
    <div v-else class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th class="checkbox-col">
              <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" />
            </th>
            <th v-for="column in visibleColumns" :key="column.key" :class="column.class">
              <div class="th-content">
                <span>{{ column.label }}</span>
                <button class="sort-btn" @click="sortBy(column.key)">
                  <span v-if="sortColumn === column.key">
                    {{ sortDirection === 'asc' ? '▲' : '▼' }}
                  </span>
                  <span v-else>⋮</span>
                </button>
              </div>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, index) in filteredData" :key="row.id || index" :class="{ 'selected': row.selected }">
            <td class="checkbox-col">
              <input type="checkbox" v-model="row.selected" />
            </td>
            <td>{{ row.skp_site }}</td>
            <td>{{ row.container_type }}</td>
            <td>
              <a href="#" class="link">{{ row.container_number }}</a>
            </td>
            <td>{{ formatDate(row.shipment_date) }}</td>
            <td>{{ row.country }}</td>
            <td>{{ row.forwarder }}</td>
            <td>{{ row.hauler }}</td>
            <td>{{ row.sku_number }}</td>
            <td>{{ row.container_size }}</td>
            <td>{{ row.model }}</td>
            <td>{{ row.work_order }}</td>
            <td>{{ row.high_sec ? 'Yes' : 'No' }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="!loading && !error" class="pagination">
      <button
        @click="goToPage(currentPage - 1)"
        :disabled="currentPage <= 1"
        class="btn-page"
      >
        Previous
      </button>
      <span class="page-info">
        Page {{ currentPage }} of {{ totalPages }}
      </span>
      <button
        @click="goToPage(currentPage + 1)"
        :disabled="currentPage >= totalPages"
        class="btn-page"
      >
        Next
      </button>
    </div>

    <!-- Footer -->
    <div class="table-footer">
      <span class="row-count">Rows: {{ totalRecords }}</span>
    </div>

    <!-- Column Editor Modal -->
    <div v-if="showColumnEditor" class="modal-overlay" @click="closeColumnEditor">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Edit Columns</h3>
          <button class="modal-close" @click="closeColumnEditor">&times;</button>
        </div>
        <div class="modal-body">
          <div class="column-list">
            <div
              v-for="column in allColumns"
              :key="column.key"
              class="column-item"
            >
              <label class="column-checkbox">
                <input
                  type="checkbox"
                  v-model="column.visible"
                  @change="updateColumnVisibility"
                />
                <span class="checkmark"></span>
                {{ column.label }}
              </label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="resetColumns">Reset to Default</button>
          <button class="btn-primary" @click="closeColumnEditor">Done</button>
        </div>
      </div>
    </div>

    <!-- Filter Editor Modal -->
    <div v-if="showFilterEditor" class="modal-overlay" @click="closeFilterEditor">
      <div class="modal-content filter-modal" @click.stop>
        <div class="modal-header">
          <h3>Advanced Filters</h3>
          <button class="modal-close" @click="closeFilterEditor">&times;</button>
        </div>
        <div class="modal-body">
          <div class="filter-grid">
            <div class="filter-group">
              <label class="filter-label">SKP Site</label>
              <input
                type="text"
                class="filter-input"
                v-model="filters.skp_site"
                placeholder="Enter SKP site"
              />
            </div>

            <div class="filter-group">
              <label class="filter-label">Container Type</label>
              <input
                type="text"
                class="filter-input"
                v-model="filters.container_type"
                placeholder="Enter container type"
              />
            </div>

            <div class="filter-group">
              <label class="filter-label">Container Number</label>
              <input
                type="text"
                class="filter-input"
                v-model="filters.container_number"
                placeholder="Enter container number"
              />
            </div>

            <div class="filter-group">
              <label class="filter-label">Shipment Date From</label>
              <input
                type="date"
                class="filter-input"
                v-model="filters.shipment_date_from"
              />
            </div>

            <div class="filter-group">
              <label class="filter-label">Shipment Date To</label>
              <input
                type="date"
                class="filter-input"
                v-model="filters.shipment_date_to"
              />
            </div>

            <div class="filter-group">
              <label class="filter-label">Country</label>
              <input
                type="text"
                class="filter-input"
                v-model="filters.country"
                placeholder="Enter country"
              />
            </div>

            <div class="filter-group">
              <label class="filter-label">Forwarder</label>
              <input
                type="text"
                class="filter-input"
                v-model="filters.forwarder"
                placeholder="Enter forwarder"
              />
            </div>

            <div class="filter-group">
              <label class="filter-label">Hauler</label>
              <input
                type="text"
                class="filter-input"
                v-model="filters.hauler"
                placeholder="Enter hauler"
              />
            </div>

            <div class="filter-group">
              <label class="filter-label">SKU Number</label>
              <input
                type="text"
                class="filter-input"
                v-model="filters.sku_number"
                placeholder="Enter SKU number"
              />
            </div>

            <div class="filter-group">
              <label class="filter-label">Container Size</label>
              <input
                type="text"
                class="filter-input"
                v-model="filters.container_size"
                placeholder="Enter container size"
              />
            </div>

            <div class="filter-group">
              <label class="filter-label">Model</label>
              <input
                type="text"
                class="filter-input"
                v-model="filters.model"
                placeholder="Enter model"
              />
            </div>

            <div class="filter-group">
              <label class="filter-label">Work Order</label>
              <input
                type="text"
                class="filter-input"
                v-model="filters.work_order"
                placeholder="Enter work order"
              />
            </div>

            <div class="filter-group">
              <label class="filter-label">High Security</label>
              <select class="filter-input" v-model="filters.high_sec">
                <option value="">All</option>
                <option value="1">Yes</option>
                <option value="0">No</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="clearFilters">Clear All</button>
          <button class="btn-secondary" @click="closeFilterEditor">Cancel</button>
          <button class="btn-primary" @click="applyFilters">Apply Filters</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'ContainerShipmentTable',
  data() {
    return {
      searchKeyword: '',
      selectAll: false,
      sortColumn: null,
      sortDirection: 'asc',
      allColumns: [
        { key: 'skp_site', label: 'SKP Site', class: 'col-skp', visible: true },
        { key: 'container_type', label: 'Container/Tr...', class: 'col-type', visible: true },
        { key: 'container_number', label: 'Container/Truck Number', class: 'col-number', visible: true },
        { key: 'shipment_date', label: 'Date', class: 'col-date', visible: true },
        { key: 'country', label: 'Country', class: 'col-country', visible: true },
        { key: 'forwarder', label: 'Forward...', class: 'col-forward', visible: true },
        { key: 'hauler', label: 'Hauler', class: 'col-hauler', visible: true },
        { key: 'sku_number', label: 'SKU Numb...', class: 'col-sku', visible: true },
        { key: 'container_size', label: 'Container S...', class: 'col-size', visible: true },
        { key: 'model', label: 'Model- Proj...', class: 'col-model', visible: true },
        { key: 'work_order', label: 'Work Ord...', class: 'col-work', visible: true },
        { key: 'high_sec', label: 'High Sec', class: 'col-high', visible: true }
      ],
      showColumnEditor: false,
      showFilterEditor: false,
      filters: {
        skp_site: '',
        container_type: '',
        container_number: '',
        shipment_date_from: '',
        shipment_date_to: '',
        country: '',
        forwarder: '',
        hauler: '',
        sku_number: '',
        container_size: '',
        model: '',
        work_order: '',
        high_sec: ''
      },
      appliedFilters: {},
      shipments: [],
      filteredData: [],
      loading: false,
      error: null,
      currentPage: 1,
      totalPages: 1,
      totalRecords: 0,
      searchTimeout: null
    };
  },
  computed: {
    visibleColumns() {
      return this.allColumns.filter(column => column.visible);
    }
  },
  mounted() {
    this.fetchShipments();
  },
  methods: {
    async fetchShipments() {
      this.loading = true;
      this.error = null;

      try {
        const params = new URLSearchParams({
          page: this.currentPage,
          per_page: 50
        });

        if (this.searchKeyword) {
          params.append('search', this.searchKeyword);
        }

        if (this.sortColumn) {
          params.append('sort_by', this.sortColumn);
          params.append('sort_direction', this.sortDirection);
        }

        // Add applied filters to the request
        Object.keys(this.appliedFilters).forEach(key => {
          if (this.appliedFilters[key] !== '' && this.appliedFilters[key] !== null) {
            params.append(key, this.appliedFilters[key]);
          }
        });

        const response = await axios.get(`/api/container-shipments?${params.toString()}`);
        const data = response.data;

        this.shipments = data.data.map(shipment => ({
          ...shipment,
          selected: false
        }));

        this.filteredData = [...this.shipments];
        this.currentPage = data.current_page;
        this.totalPages = data.last_page;
        this.totalRecords = data.total;

      } catch (error) {
        console.error('Error fetching shipments:', error);
        this.error = error.response?.data?.message || 'Failed to load shipments';
      } finally {
        this.loading = false;
      }
    },

    toggleSelectAll() {
      this.filteredData.forEach(row => {
        row.selected = this.selectAll;
      });
    },

    editColumns() {
      this.showColumnEditor = true;
    },

    closeColumnEditor() {
      this.showColumnEditor = false;
    },

    updateColumnVisibility() {
      // Force reactivity update
      this.$forceUpdate();
    },

    resetColumns() {
      this.allColumns.forEach(column => {
        column.visible = true;
      });
      this.updateColumnVisibility();
    },

    editFilters() {
      this.showFilterEditor = true;
    },

    closeFilterEditor() {
      this.showFilterEditor = false;
    },

    applyFilters() {
      // Copy current filters to applied filters
      this.appliedFilters = { ...this.filters };

      // Reset to first page when applying filters
      this.currentPage = 1;

      // Close modal and fetch filtered data
      this.showFilterEditor = false;
      this.fetchShipments();
    },

    clearFilters() {
      // Clear all filter values
      Object.keys(this.filters).forEach(key => {
        this.filters[key] = '';
      });

      // Clear applied filters
      this.appliedFilters = {};

      // Reset to first page
      this.currentPage = 1;

      // Fetch all data
      this.fetchShipments();
    },

    debouncedSearch() {
      if (this.searchTimeout) {
        clearTimeout(this.searchTimeout);
      }
      this.searchTimeout = setTimeout(() => {
        this.currentPage = 1; // Reset to first page when searching
        this.fetchShipments();
      }, 300);
    },

    sortBy(column) {
      if (this.sortColumn === column) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortColumn = column;
        this.sortDirection = 'asc';
      }

      this.fetchShipments();
    },

    goToPage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page;
        this.fetchShipments();
      }
    },

    formatDate(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-GB'); // DD/MM/YYYY format
    }
  },

  watch: {
    searchKeyword() {
      this.debouncedSearch();
    }
  }
};
</script>

<style scoped>
.container-table {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #e5e7eb;
}

.table-title {
  font-size: 18px;
  font-weight: 600;
  margin: 0;
  color: #111827;
}

.header-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

.btn-icon {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  cursor: pointer;
  color: #374151;
  transition: all 0.2s;
}

.btn-icon:hover {
  background: #f9fafb;
  border-color: #9ca3af;
}

.search-input {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  width: 200px;
  outline: none;
}

.search-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.loading-state, .error-state {
  padding: 40px;
  text-align: center;
  color: #6b7280;
}

.error-state {
  color: #dc2626;
}

.btn-retry {
  margin-top: 10px;
  padding: 8px 16px;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.btn-retry:hover {
  background: #b91c1c;
}

.table-wrapper {
  overflow-x: auto;
  max-height: 600px;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.data-table thead {
  background: #f9fafb;
  position: sticky;
  top: 0;
  z-index: 10;
}

.data-table th {
  text-align: left;
  padding: 12px 16px;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #e5e7eb;
  white-space: nowrap;
}

.th-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.sort-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  color: #9ca3af;
  font-size: 12px;
}

.sort-btn:hover {
  color: #374151;
}

.data-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #e5e7eb;
  color: #111827;
}

.data-table tbody tr:hover {
  background: #f9fafb;
}

.data-table tbody tr.selected {
  background: #eff6ff;
}

.checkbox-col {
  width: 40px;
  text-align: center;
}

.link {
  color: #2563eb;
  text-decoration: none;
}

.link:hover {
  text-decoration: underline;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  padding: 16px;
  border-top: 1px solid #e5e7eb;
}

.btn-page {
  padding: 8px 16px;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  cursor: pointer;
  color: #374151;
}

.btn-page:hover:not(:disabled) {
  background: #f9fafb;
  border-color: #9ca3af;
}

.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-info {
  font-size: 14px;
  color: #6b7280;
}

.table-footer {
  padding: 12px 20px;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
}

.row-count {
  font-size: 13px;
  color: #6b7280;
}

input[type="checkbox"] {
  cursor: pointer;
  width: 16px;
  height: 16px;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  max-width: 500px;
  width: 90%;
  max-height: 80vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #111827;
}

.modal-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #6b7280;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}

.modal-close:hover {
  background: #f3f4f6;
  color: #374151;
}

.modal-body {
  padding: 20px;
  overflow-y: auto;
  flex: 1;
}

.column-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.column-item {
  display: flex;
  align-items: center;
}

.column-checkbox {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  font-size: 14px;
  color: #374151;
  width: 100%;
  padding: 8px;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.column-checkbox:hover {
  background: #f9fafb;
}

.column-checkbox input[type="checkbox"] {
  margin: 0;
  width: 18px;
  height: 18px;
}

.checkmark {
  position: relative;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
}

.btn-secondary, .btn-primary {
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}

.btn-secondary {
  background: white;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover {
  background: #f9fafb;
  border-color: #9ca3af;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}

/* Filter Modal Styles */
.filter-modal {
  max-width: 800px;
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.filter-label {
  font-size: 14px;
  font-weight: 500;
  color: #374151;
}

.filter-input {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s;
}

.filter-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.filter-input[type="date"] {
  cursor: pointer;
}

.filter-input[type="date"]::-webkit-calendar-picker-indicator {
  cursor: pointer;
}
</style>
