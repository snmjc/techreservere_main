<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <section class="facilities-page">
      <header class="facilities-hero" :class="{ 'facilities-hero--venue': activeTab === 'venue' }">
        <div>
          <h1>{{ activeTab === 'equipment' ? 'Facilities' : 'Manage Facilities' }}</h1>
          <p v-if="activeTab === 'venue'">Dashboard <span>/</span> Manage Facilities</p>
        </div>

        <div class="facilities-hero-actions">
          <button
            v-if="activeTab === 'venue'"
            class="facilities-hero-button facilities-hero-button--secondary"
            type="button"
            :disabled="!selectedVenueRecord"
            @click="handleEditVenue(selectedVenueRecord)"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 20h9" />
              <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" />
            </svg>
            Edit Venue
          </button>
          <button
            v-if="activeTab === 'venue'"
            class="facilities-hero-button facilities-hero-button--primary"
            type="button"
            @click="handleAddVenue"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 5v14" />
              <path d="M5 12h14" />
            </svg>
            Add Venue
          </button>
          <button
            v-else
            class="facilities-hero-button facilities-hero-button--primary"
            type="button"
            @click="handleAddEquipment"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 5v14" />
              <path d="M5 12h14" />
            </svg>
            Add Equipment
          </button>
        </div>
      </header>

      <div class="facilities-tabs">
        <button
          class="facilities-tab"
          :class="{ 'facilities-tab--active': activeTab === 'venue' }"
          type="button"
          @click="activeTab = 'venue'"
        >
          Venue
        </button>
        <button
          class="facilities-tab"
          :class="{ 'facilities-tab--active': activeTab === 'equipment' }"
          type="button"
          @click="activeTab = 'equipment'"
        >
          Equipment
        </button>
      </div>

      <section v-if="activeTab === 'venue'" class="facilities-panel facilities-panel--venue">
        <div class="facilities-filter-bar">
          <label class="facilities-search">
            <span class="sr-only">Search venue</span>
            <input
              v-model.trim="venueSearchQuery"
              type="search"
              placeholder="Search venue..."
            />
          </label>

          <label>
            <span>Floor</span>
            <select v-model="venueFloorFilter">
              <option value="all">All Floors</option>
              <option v-for="floor in venueFloorOptions" :key="floor" :value="floor">{{ floor }}</option>
            </select>
          </label>

          <label>
            <span>Status</span>
            <select v-model="venueStatusFilter">
              <option value="all">All</option>
              <option value="available">Available</option>
              <option value="unavailable">Unavailable</option>
            </select>
          </label>

          <label>
            <span>Sort By</span>
            <select v-model="venueSortValue">
              <option value="name-asc">Venue Name (A - Z)</option>
              <option value="name-desc">Venue Name (Z - A)</option>
              <option value="floor-asc">Floor (Low - High)</option>
              <option value="floor-desc">Floor (High - Low)</option>
              <option value="capacity-desc">Capacity (High - Low)</option>
              <option value="capacity-asc">Capacity (Low - High)</option>
            </select>
          </label>

          <button class="facilities-reset-button" type="button" @click="resetVenueFilters">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 6h18" />
              <path d="M7 12h10" />
              <path d="M10 18h4" />
            </svg>
            Reset Filters
          </button>
        </div>

        <div class="facilities-table-card">
          <div class="facilities-table-wrap">
            <table class="facilities-table">
              <thead>
                <tr>
                  <th>Floor</th>
                  <th>Venue Name</th>
                  <th>Status</th>
                  <th>Capacity</th>
                  <th>Type</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="venue in paginatedVenues"
                  :key="venue.venueIdentifier"
                  :class="{ 'is-selected': selectedVenueRecord?.venueIdentifier === venue.venueIdentifier }"
                  @click="selectedVenueRecord = venue"
                >
                  <td>{{ formatFloorLabel(venue.floorLevel) }}</td>
                  <td class="facilities-table-name">{{ venue.venueName }}</td>
                  <td>
                    <span
                      class="facilities-status-pill"
                      :class="venue.venueAvailable ? 'facilities-status-pill--available' : 'facilities-status-pill--unavailable'"
                    >
                      {{ venue.venueAvailable ? 'Available' : 'Unavailable' }}
                    </span>
                  </td>
                  <td>{{ formatCapacity(venue.capacityLimit) }}</td>
                  <td>{{ resolveVenueType(venue) }}</td>
                  <td>
                    <div class="facilities-row-actions">
                      <button
                        class="facilities-row-action"
                        type="button"
                        title="View Venue"
                        @click.stop="handleViewVenue(venue)"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z" />
                          <circle cx="12" cy="12" r="3" />
                        </svg>
                      </button>
                      <button
                        class="facilities-row-action"
                        type="button"
                        title="Edit Venue"
                        @click.stop="handleEditVenue(venue)"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M12 20h9" />
                          <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" />
                        </svg>
                      </button>
                      <button
                        class="facilities-row-action facilities-row-action--danger"
                        type="button"
                        title="Delete Venue"
                        @click.stop="handleDeleteVenue(venue)"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M3 6h18" />
                          <path d="M8 6V4h8v2" />
                          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!venueLoading && paginatedVenues.length === 0">
                  <td colspan="6" class="facilities-table-empty">No venues found matching the selected filters.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="facilities-table-footer">
            <p>Showing {{ venuePageStart }} to {{ venuePageEnd }} of {{ filteredVenueRecords.length }} venues</p>

            <div class="facilities-pagination">
              <button type="button" :disabled="venueCurrentPage === 1" @click="venueCurrentPage -= 1">&laquo;</button>
              <button type="button" :disabled="venueCurrentPage === 1" @click="venueCurrentPage -= 1">&lsaquo;</button>
              <button
                v-for="pageNumber in visibleVenuePages"
                :key="pageNumber"
                type="button"
                :class="{ 'is-active': pageNumber === venueCurrentPage }"
                @click="venueCurrentPage = pageNumber"
              >
                {{ pageNumber }}
              </button>
              <button type="button" :disabled="venueCurrentPage === venueTotalPages" @click="venueCurrentPage += 1">&rsaquo;</button>
              <button type="button" :disabled="venueCurrentPage === venueTotalPages" @click="venueCurrentPage += 1">&raquo;</button>
            </div>

            <label class="facilities-page-size">
              <span>Items per page:</span>
              <select v-model.number="venuePageSize">
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="15">15</option>
              </select>
            </label>
          </div>
        </div>

        <div class="facilities-legend-card">
          <strong>Legend:</strong>
          <span><i class="facilities-legend-dot facilities-legend-dot--available" />Available <small>The venue is free and can be reserved.</small></span>
          <span><i class="facilities-legend-dot facilities-legend-dot--unavailable" />Unavailable <small>The venue is already reserved or not accessible.</small></span>
        </div>
      </section>

      <section v-else class="facilities-panel facilities-panel--equipment">
        <div class="facilities-filter-bar facilities-filter-bar--equipment">
          <label class="facilities-search">
            <span class="sr-only">Search equipment</span>
            <span class="facilities-search-input-wrap">
              <input
                v-model.trim="equipmentSearchQuery"
                type="search"
                placeholder="Search equipment..."
              />
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="7" />
                <path d="m20 20-3.5-3.5" />
              </svg>
            </span>
          </label>

          <label>
            <span>Status</span>
            <select v-model="equipmentFilterValue">
              <option value="all">All</option>
              <option value="available">Available</option>
              <option value="unavailable">Unavailable</option>
              <option value="maintenance">Under Maintenance</option>
              <option value="retired">Retired</option>
            </select>
          </label>

          <label>
            <span>Category</span>
            <select v-model="equipmentCategoryFilter">
              <option value="all">All Categories</option>
              <option v-for="category in equipmentCategoryOptions" :key="category" :value="category">{{ category }}</option>
            </select>
          </label>

          <label>
            <span>Sort By</span>
            <select v-model="equipmentSortValue">
              <option value="name-asc">Equipment Name (A - Z)</option>
              <option value="name-desc">Equipment Name (Z - A)</option>
              <option value="category-asc">Category (A - Z)</option>
              <option value="category-desc">Category (Z - A)</option>
              <option value="quantity-desc">Quantity (High - Low)</option>
              <option value="quantity-asc">Quantity (Low - High)</option>
            </select>
          </label>

          <button class="facilities-reset-button" type="button" @click="resetEquipmentFilters">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 6h18" />
              <path d="M7 12h10" />
              <path d="M10 18h4" />
            </svg>
            Reset Filters
          </button>
        </div>

        <p v-if="equipmentError" class="facilities-feedback facilities-feedback--error">{{ equipmentError }}</p>
        <div v-if="equipmentLoading" class="facilities-empty-state">
          <p>Loading equipment records...</p>
        </div>
        <div v-else class="facilities-table-card">
          <div class="facilities-table-wrap">
            <table class="facilities-table facilities-table--equipment">
              <thead>
                <tr>
                  <th>
                    <span class="facilities-table-head-label">
                      Equipment Name
                      <span class="facilities-table-head-sort" aria-hidden="true">↕</span>
                    </span>
                  </th>
                  <th>Category</th>
                  <th>Status</th>
                  <th>
                    <span class="facilities-table-head-label">
                      Quantity
                      <span class="facilities-table-head-sort" aria-hidden="true">↕</span>
                    </span>
                  </th>
                  <th>Description</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="equipment in paginatedEquipment"
                  :key="equipment.equipmentIdentifier"
                  :class="{ 'is-selected': selectedEquipmentRecord?.equipmentIdentifier === equipment.equipmentIdentifier }"
                  @click="selectedEquipmentRecord = equipment"
                >
                  <td class="facilities-table-name">
                    <div class="facilities-equipment-name-cell">
                      <span class="facilities-equipment-name-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                          <path d="M7 7h10v10H7z" />
                          <path d="M9 4h6" />
                          <path d="M9 20h6" />
                        </svg>
                      </span>
                      <strong>{{ equipment.equipmentName }}</strong>
                    </div>
                  </td>
                  <td>{{ equipment.categoryName || 'Uncategorized' }}</td>
                  <td>
                    <span
                      class="facilities-status-pill"
                      :class="resolveEquipmentStatusTone(equipment)"
                    >
                      {{ normalizeEquipmentStatus(equipment.equipmentState) }}
                    </span>
                  </td>
                  <td>{{ resolveEquipmentQuantity(equipment) }}</td>
                  <td>{{ resolveEquipmentDescription(equipment) }}</td>
                  <td>
                    <div class="facilities-row-actions">
                      <button
                        class="facilities-row-action"
                        type="button"
                        title="View Equipment"
                        @click.stop="handleViewEquipment(equipment)"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z" />
                          <circle cx="12" cy="12" r="3" />
                        </svg>
                      </button>
                      <button
                        class="facilities-row-action"
                        type="button"
                        title="Edit Equipment"
                        @click.stop="handleEditEquipment(equipment)"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M12 20h9" />
                          <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="paginatedEquipment.length === 0">
                  <td colspan="6" class="facilities-table-empty">No equipment records match the selected filters.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="facilities-table-footer">
            <p>Showing {{ equipmentPageStart }} to {{ equipmentPageEnd }} of {{ filteredEquipment.length }} equipment records</p>

            <div class="facilities-pagination">
              <button type="button" :disabled="equipmentCurrentPage === 1" @click="equipmentCurrentPage -= 1">&laquo;</button>
              <button type="button" :disabled="equipmentCurrentPage === 1" @click="equipmentCurrentPage -= 1">&lsaquo;</button>
              <button
                v-for="pageNumber in visibleEquipmentPages"
                :key="pageNumber"
                type="button"
                :class="{ 'is-active': pageNumber === equipmentCurrentPage }"
                @click="equipmentCurrentPage = pageNumber"
              >
                {{ pageNumber }}
              </button>
              <button type="button" :disabled="equipmentCurrentPage === equipmentTotalPages" @click="equipmentCurrentPage += 1">&rsaquo;</button>
              <button type="button" :disabled="equipmentCurrentPage === equipmentTotalPages" @click="equipmentCurrentPage += 1">&raquo;</button>
            </div>

            <label class="facilities-page-size">
              <span>Items per page:</span>
              <select v-model.number="equipmentPageSize">
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="30">30</option>
              </select>
            </label>
          </div>
        </div>

        <div class="facilities-legend-card">
          <strong>Legend:</strong>
          <span><i class="facilities-legend-dot facilities-legend-dot--available" />Available <small>The equipment is available for use.</small></span>
          <span><i class="facilities-legend-dot facilities-legend-dot--unavailable" />Unavailable <small>The equipment is currently not available.</small></span>
        </div>
      </section>

      <EquipmentModalComponent
        :show="showEquipmentModal"
        :equipment="selectedEquipmentRecord"
        @close="closeEquipmentModal"
        @saved="handleEquipmentSaved"
      />

      <div v-if="showEquipmentViewModal" class="equipment-details-overlay" @click.self="closeEquipmentViewModal">
        <section class="equipment-details-card">
          <header class="equipment-details-header">
            <h2>Equipment Details</h2>
            <button class="equipment-details-close" type="button" aria-label="Close" @click="closeEquipmentViewModal">&times;</button>
          </header>

          <div v-if="equipmentDetailsLoading" class="equipment-details-loading">
            <p>Loading equipment details...</p>
          </div>

          <div v-else class="equipment-details-body">
            <div class="equipment-details-grid">
              <div class="equipment-details-item">
                <dt>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m20 7-8 8-4-4" />
                    <path d="M16 7h4v4" />
                    <path d="M4 13v7h7" />
                  </svg>
                  Equipment Name
                </dt>
                <dd>{{ selectedEquipmentDetails?.equipmentName || 'N/A' }}</dd>
              </div>

              <div class="equipment-details-item">
                <dt>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="4" y="4" width="6" height="6" />
                    <rect x="14" y="4" width="6" height="6" />
                    <rect x="4" y="14" width="6" height="6" />
                    <rect x="14" y="14" width="6" height="6" />
                  </svg>
                  Equipment Type/Category
                </dt>
                <dd>{{ resolveEquipmentCategory(selectedEquipmentDetails) }}</dd>
              </div>

              <div class="equipment-details-item">
                <dt>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 7h12" />
                    <path d="M6 12h12" />
                    <path d="M6 17h8" />
                  </svg>
                  Equipment Brand
                </dt>
                <dd>{{ selectedEquipmentDetails?.equipmentBrand || 'N/A' }}</dd>
              </div>

              <div class="equipment-details-item">
                <dt>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 3v18" />
                    <path d="M8 7h8" />
                    <path d="M8 17h8" />
                  </svg>
                  Available Quantity
                </dt>
                <dd>{{ resolveEquipmentQuantity(selectedEquipmentDetails || {}) }} units</dd>
              </div>

              <div class="equipment-details-item">
                <dt>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 12h4l3-7 4 14 3-7h4" />
                  </svg>
                  Operational Status (Status)
                </dt>
                <dd>
                  <span
                    class="facilities-status-pill"
                    :class="resolveEquipmentStatusTone(selectedEquipmentDetails || {})"
                  >
                    {{ normalizeEquipmentStatus(resolveEquipmentStatusValue(selectedEquipmentDetails)) }}
                  </span>
                </dd>
              </div>

              <div class="equipment-details-item equipment-details-item--description">
                <dt>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 5h16" />
                    <path d="M4 10h16" />
                    <path d="M4 15h10" />
                  </svg>
                  Description
                </dt>
                <dd>{{ resolveEquipmentDescription(selectedEquipmentDetails || {}) }}</dd>
              </div>
            </div>

            <div class="equipment-details-divider" />

            <div class="equipment-details-grid equipment-details-grid--meta">
              <div class="equipment-details-item">
                <dt>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 7h16" />
                    <path d="M4 12h16" />
                    <path d="M4 17h16" />
                    <path d="M8 5v14" />
                    <path d="M16 5v14" />
                  </svg>
                  Barcode
                </dt>
                <dd>{{ selectedEquipmentDetails?.barcode || 'N/A' }}</dd>
              </div>

              <div class="equipment-details-item">
                <dt>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="4" y="4" width="6" height="6" />
                    <rect x="14" y="4" width="6" height="6" />
                    <rect x="4" y="14" width="6" height="6" />
                    <rect x="14" y="14" width="6" height="6" />
                  </svg>
                  Serial Number
                </dt>
                <dd>{{ selectedEquipmentDetails?.assetId || selectedEquipmentDetails?.serialNumber || 'N/A' }}</dd>
              </div>
            </div>
          </div>

          <footer class="equipment-details-footer">
            <button class="equipment-details-button" type="button" @click="closeEquipmentViewModal">Close</button>
          </footer>
        </section>
      </div>

      <div v-if="showVenueViewModal" class="venue-modal-overlay" @click.self="closeVenueModals">
        <section class="venue-modal-card venue-modal-card--details">
          <header class="venue-modal-header">
            <h2>View Venue Details</h2>
            <button class="venue-modal-close" type="button" aria-label="Close" @click="closeVenueModals">&times;</button>
          </header>

          <div class="venue-modal-details">
            <div class="venue-modal-image">
              <span>Venue Image Placeholder</span>
            </div>
            <dl class="venue-modal-detail-list">
              <div class="venue-modal-detail-item">
                <dt>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19h16" />
                    <path d="M5 19V9l7-5 7 5v10" />
                  </svg>
                  Venue Name
                </dt>
                <dd>{{ selectedVenueRecord?.venueName || 'N/A' }}</dd>
              </div>
              <div class="venue-modal-detail-item">
                <dt>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 20h16" />
                    <path d="M6 20V8h12v12" />
                    <path d="M9 4h6v4H9z" />
                  </svg>
                  Floor
                </dt>
                <dd>{{ formatFloorLabel(selectedVenueRecord?.floorLevel) }}</dd>
              </div>
              <div class="venue-modal-detail-item">
                <dt>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="4" y="5" width="16" height="14" rx="2" />
                    <path d="M8 9h8" />
                    <path d="M8 13h5" />
                  </svg>
                  Type
                </dt>
                <dd>{{ resolveVenueType(selectedVenueRecord || {}) }}</dd>
              </div>
              <div class="venue-modal-detail-item">
                <dt>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 3v18" />
                    <path d="M8 7h8" />
                    <path d="M8 17h8" />
                  </svg>
                  Capacity
                </dt>
                <dd>{{ formatCapacity(selectedVenueRecord?.capacityLimit) }} People</dd>
              </div>
              <div class="venue-modal-detail-item">
                <dt>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="9" />
                    <path d="M9 12l2 2 4-4" />
                  </svg>
                  Status
                </dt>
                <dd>
                  <span
                    class="facilities-status-pill"
                    :class="getVenueModalStatusClass(selectedVenueRecord?.venueAvailable)"
                  >
                    {{ selectedVenueRecord?.venueAvailable ? 'Available' : 'Unavailable' }}
                  </span>
                </dd>
              </div>
              <div class="venue-modal-detail-item">
                <dt>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 5h16" />
                    <path d="M4 10h16" />
                    <path d="M4 15h10" />
                  </svg>
                  Description
                </dt>
                <dd>{{ selectedVenueRecord?.venueDescription || 'Open rooftop area ideal for events, gatherings, and outdoor activities.' }}</dd>
              </div>
            </dl>
          </div>

          <footer class="venue-modal-footer">
            <button class="venue-modal-button venue-modal-button--ghost" type="button" @click="closeVenueModals">Close</button>
          </footer>
        </section>
      </div>

      <div v-if="showVenueEditModal || showVenueAddModal" class="venue-modal-overlay" @click.self="closeVenueModals">
        <section class="venue-modal-card">
          <header class="venue-modal-header">
            <h2>{{ showVenueAddModal ? 'Add Venue' : 'Edit Venue' }}</h2>
            <button class="venue-modal-close" type="button" aria-label="Close" @click="closeVenueModals">&times;</button>
          </header>

          <div class="venue-modal-form">
            <label>
              <span>Venue Name <em>*</em></span>
              <input v-model.trim="venueModalForm.venueName" type="text" placeholder="Enter venue name" />
            </label>
            <label>
              <span>Floor <em>*</em></span>
              <select v-model="venueModalForm.floorLevel">
                <option value="">Select floor</option>
                <option v-for="floor in venueFloorOptions" :key="floor" :value="extractFloorNumber(floor)">{{ floor }}</option>
              </select>
            </label>
            <label>
              <span>Type <em>*</em></span>
              <select v-model="venueModalForm.venueType">
                <option value="">Select type</option>
                <option>Open Space</option>
                <option>Multipurpose Room</option>
                <option>Sports Facility</option>
                <option>Audio Visual Room</option>
                <option>Case Room</option>
                <option>Executive Lounge</option>
                <option>Student Plaza</option>
              </select>
            </label>
            <label>
              <span>Capacity <em>*</em></span>
              <div class="venue-modal-input-with-suffix">
                <input v-model.number="venueModalForm.capacityLimit" type="number" min="1" placeholder="Enter capacity" />
                <small>People</small>
              </div>
            </label>
            <label class="venue-modal-field--full">
              <span>Status <em>*</em></span>
              <select v-model="venueModalForm.venueAvailable">
                <option :value="true">Available</option>
                <option :value="false">Unavailable</option>
              </select>
            </label>
            <label class="venue-modal-field--full">
              <span>Description</span>
              <textarea v-model.trim="venueModalForm.venueDescription" rows="4" placeholder="Enter venue description..." />
            </label>
          </div>

          <footer class="venue-modal-footer">
            <button class="venue-modal-button venue-modal-button--ghost" type="button" @click="closeVenueModals">Cancel</button>
            <button class="venue-modal-button venue-modal-button--primary" type="button" @click="closeVenueModals">
              {{ showVenueAddModal ? 'Add Venue' : 'Save Changes' }}
            </button>
          </footer>
        </section>
      </div>

      <div v-if="showVenueDeleteModal" class="venue-modal-overlay" @click.self="closeVenueModals">
        <section class="venue-modal-card venue-modal-card--delete">
          <header class="venue-modal-header">
            <span class="venue-modal-delete-icon">!</span>
            <button class="venue-modal-close" type="button" aria-label="Close" @click="closeVenueModals">&times;</button>
          </header>

          <div class="venue-modal-delete-copy">
            <h2>Are you sure you want to delete this venue?</h2>
            <p>This action cannot be undone.</p>
          </div>

          <div class="venue-modal-delete-summary">
            <div><strong>Venue Name</strong><span>{{ selectedVenueRecord?.venueName || 'N/A' }}</span></div>
            <div><strong>Floor</strong><span>{{ formatFloorLabel(selectedVenueRecord?.floorLevel) }}</span></div>
            <div><strong>Type</strong><span>{{ resolveVenueType(selectedVenueRecord || {}) }}</span></div>
            <div><strong>Capacity</strong><span>{{ formatCapacity(selectedVenueRecord?.capacityLimit) }} People</span></div>
          </div>

          <footer class="venue-modal-footer">
            <button class="venue-modal-button venue-modal-button--ghost" type="button" @click="closeVenueModals">Cancel</button>
            <button class="venue-modal-button venue-modal-button--danger" type="button" @click="closeVenueModals">Delete Venue</button>
          </footer>
        </section>
      </div>
    </section>
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import EquipmentModalComponent from '@/modules/facility/components/EquipmentModalComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/Facilities.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import equipmentApi from '@/modules/reservation/services/equipmentApi.js';

const router = useRouter();
const activeTab = ref('venue');
const equipmentFilterValue = ref('all');
const equipmentList = ref([]);
const equipmentLoading = ref(false);
const equipmentError = ref('');
const venueLoading = ref(false);

const equipmentSearchQuery = ref('');
const equipmentCategoryFilter = ref('all');
const equipmentSortValue = ref('name-asc');
const equipmentCurrentPage = ref(1);
const equipmentPageSize = ref(20);
const selectedEquipmentRecord = ref(null);
const selectedEquipmentDetails = ref(null);
const showEquipmentModal = ref(false);
const showEquipmentViewModal = ref(false);
const equipmentDetailsLoading = ref(false);

const venueSearchQuery = ref('');
const venueFloorFilter = ref('all');
const venueStatusFilter = ref('all');
const venueSortValue = ref('name-asc');
const venueCurrentPage = ref(1);
const venuePageSize = ref(10);
const selectedVenueRecord = ref(null);
const showVenueViewModal = ref(false);
const showVenueEditModal = ref(false);
const showVenueAddModal = ref(false);
const showVenueDeleteModal = ref(false);
const venueModalForm = reactive({
  venueName: '',
  floorLevel: '',
  venueType: '',
  capacityLimit: '',
  venueAvailable: true,
  venueDescription: '',
});

const venueRecords = ref([
  { venueIdentifier: 1, venueName: '18F Roofdeck', venueAvailable: true, venueLocation: 'Rooftop', floorLevel: '18', capacityLimit: 150, venueType: 'Open Space' },
  { venueIdentifier: 2, venueName: '17F MPR', venueAvailable: true, venueLocation: 'Main Building', floorLevel: '17', capacityLimit: 200, venueType: 'Multipurpose Room' },
  { venueIdentifier: 3, venueName: 'Basketball without Aircon', venueAvailable: true, venueLocation: 'Sports Wing', floorLevel: '17', capacityLimit: 50, venueType: 'Sports Facility' },
  { venueIdentifier: 4, venueName: 'Basketball gym with Aircon', venueAvailable: false, venueLocation: 'Sports Wing', floorLevel: '17', capacityLimit: 50, venueType: 'Sports Facility' },
  { venueIdentifier: 5, venueName: 'Basketball gym with Aircon and Green Matting', venueAvailable: true, venueLocation: 'Sports Wing', floorLevel: '17', capacityLimit: 50, venueType: 'Sports Facility' },
  { venueIdentifier: 6, venueName: 'F1603 Audio Visual Room', venueAvailable: false, venueLocation: 'Tech Building', floorLevel: '16', capacityLimit: 40, venueType: 'Audio Visual Room' },
  { venueIdentifier: 7, venueName: 'F1604 Case Room', venueAvailable: true, venueLocation: 'Tech Building', floorLevel: '16', capacityLimit: 40, venueType: 'Case Room' },
  { venueIdentifier: 8, venueName: 'F1502 Multipurpose Room', venueAvailable: true, venueLocation: 'Main Building', floorLevel: '15', capacityLimit: 100, venueType: 'Multipurpose Room' },
  { venueIdentifier: 9, venueName: 'F1503 Multipurpose Room', venueAvailable: true, venueLocation: 'Main Building', floorLevel: '15', capacityLimit: 100, venueType: 'Multipurpose Room' },
  { venueIdentifier: 10, venueName: 'F1504 Multipurpose Room', venueAvailable: true, venueLocation: 'Main Building', floorLevel: '15', capacityLimit: 100, venueType: 'Multipurpose Room' },
  { venueIdentifier: 11, venueName: '8F Exec. Lounge 1', venueAvailable: false, venueLocation: 'Executive Wing', floorLevel: '8', capacityLimit: 35, venueType: 'Executive Lounge' },
  { venueIdentifier: 12, venueName: '2F FIT Student Plaza', venueAvailable: false, venueLocation: 'Student Area', floorLevel: '2', capacityLimit: 180, venueType: 'Student Plaza' },
]);

const venueFloorOptions = computed(() => [...new Set(
  venueRecords.value
    .map((venue) => formatFloorLabel(venue.floorLevel))
    .filter(Boolean),
)].sort((first, second) => extractFloorNumber(second) - extractFloorNumber(first)));

const equipmentCategoryOptions = computed(() => [...new Set(
  equipmentList.value
    .map((equipment) => String(equipment.categoryName || '').trim())
    .filter(Boolean),
)].sort((first, second) => first.localeCompare(second)));

const filteredVenueRecords = computed(() => {
  const query = venueSearchQuery.value.trim().toLowerCase();

  return [...venueRecords.value]
    .filter((venue) => {
      if (venueFloorFilter.value !== 'all' && formatFloorLabel(venue.floorLevel) !== venueFloorFilter.value) {
        return false;
      }

      if (venueStatusFilter.value === 'available' && !venue.venueAvailable) return false;
      if (venueStatusFilter.value === 'unavailable' && venue.venueAvailable) return false;

      if (!query) return true;

      return [
        venue.venueName,
        venue.venueLocation,
        venue.venueType,
        formatFloorLabel(venue.floorLevel),
      ].filter(Boolean).join(' ').toLowerCase().includes(query);
    })
    .sort((first, second) => sortVenueRecords(first, second, venueSortValue.value));
});

const venueTotalPages = computed(() => Math.max(1, Math.ceil(filteredVenueRecords.value.length / venuePageSize.value)));

const paginatedVenues = computed(() => {
  const startIndex = (venueCurrentPage.value - 1) * venuePageSize.value;
  return filteredVenueRecords.value.slice(startIndex, startIndex + venuePageSize.value);
});

const venuePageStart = computed(() => filteredVenueRecords.value.length === 0 ? 0 : ((venueCurrentPage.value - 1) * venuePageSize.value) + 1);
const venuePageEnd = computed(() => Math.min(venueCurrentPage.value * venuePageSize.value, filteredVenueRecords.value.length));

const visibleVenuePages = computed(() => {
  const pages = [];
  for (let pageNumber = 1; pageNumber <= venueTotalPages.value; pageNumber += 1) {
    pages.push(pageNumber);
  }
  return pages.slice(0, 5);
});

const filteredEquipment = computed(() => {
  const query = equipmentSearchQuery.value.trim().toLowerCase();

  return [...equipmentList.value]
    .filter((equipment) => {
      const normalizedStatus = normalizeEquipmentStatus(equipment.equipmentState).toLowerCase();
      const normalizedCategory = String(equipment.categoryName || '').trim();

      if (equipmentFilterValue.value !== 'all') {
        if (equipmentFilterValue.value === 'available' && normalizedStatus !== 'available') return false;
        if (equipmentFilterValue.value === 'unavailable' && normalizedStatus !== 'unavailable') return false;
        if (equipmentFilterValue.value === 'maintenance' && normalizedStatus !== 'under maintenance') return false;
        if (equipmentFilterValue.value === 'retired' && normalizedStatus !== 'retired') return false;
      }

      if (equipmentCategoryFilter.value !== 'all' && normalizedCategory !== equipmentCategoryFilter.value) {
        return false;
      }

      if (!query) return true;

      return [
        equipment.equipmentName,
        equipment.categoryName,
        resolveEquipmentDescription(equipment),
        normalizeEquipmentStatus(equipment.equipmentState),
      ].filter(Boolean).join(' ').toLowerCase().includes(query);
    })
    .sort((first, second) => sortEquipmentRecords(first, second, equipmentSortValue.value));
});

const equipmentTotalPages = computed(() => Math.max(1, Math.ceil(filteredEquipment.value.length / equipmentPageSize.value)));

const paginatedEquipment = computed(() => {
  const startIndex = (equipmentCurrentPage.value - 1) * equipmentPageSize.value;
  return filteredEquipment.value.slice(startIndex, startIndex + equipmentPageSize.value);
});

const equipmentPageStart = computed(() => filteredEquipment.value.length === 0 ? 0 : ((equipmentCurrentPage.value - 1) * equipmentPageSize.value) + 1);
const equipmentPageEnd = computed(() => Math.min(equipmentCurrentPage.value * equipmentPageSize.value, filteredEquipment.value.length));

const visibleEquipmentPages = computed(() => {
  const pages = [];
  for (let pageNumber = 1; pageNumber <= equipmentTotalPages.value; pageNumber += 1) {
    pages.push(pageNumber);
  }
  return pages.slice(0, 5);
});

watch([venueSearchQuery, venueFloorFilter, venueStatusFilter, venueSortValue, venuePageSize], () => {
  venueCurrentPage.value = 1;
});

watch([equipmentSearchQuery, equipmentFilterValue, equipmentCategoryFilter, equipmentSortValue, equipmentPageSize], () => {
  equipmentCurrentPage.value = 1;
});

watch(venueTotalPages, (pageCount) => {
  if (venueCurrentPage.value > pageCount) {
    venueCurrentPage.value = pageCount;
  }
});

watch(equipmentTotalPages, (pageCount) => {
  if (equipmentCurrentPage.value > pageCount) {
    equipmentCurrentPage.value = pageCount;
  }
});

function handleAddVenue() {
  resetVenueModalForm();
  showVenueAddModal.value = true;
}

function handleEditVenue(venue) {
  if (!venue) return;
  selectedVenueRecord.value = venue;
  hydrateVenueModalForm(venue);
  showVenueEditModal.value = true;
}

function handleViewVenue(venue) {
  selectedVenueRecord.value = venue;
  showVenueViewModal.value = true;
}

function handleDeleteVenue(venue) {
  selectedVenueRecord.value = venue;
  showVenueDeleteModal.value = true;
}

async function handleViewEquipment(equipment) {
  selectedEquipmentRecord.value = equipment;
  selectedEquipmentDetails.value = equipment;
  showEquipmentViewModal.value = true;

  if (!equipment?.equipmentIdentifier) {
    return;
  }

  try {
    equipmentDetailsLoading.value = true;
    const response = await equipmentApi.getEquipmentById(equipment.equipmentIdentifier);
    selectedEquipmentDetails.value = response?.data?.equipment || response?.data || equipment;
  } catch (error) {
    selectedEquipmentDetails.value = equipment;
  } finally {
    equipmentDetailsLoading.value = false;
  }
}

function handleAddEquipment() {
  selectedEquipmentRecord.value = null;
  showEquipmentModal.value = true;
}

function handleEditEquipment(equipment) {
  selectedEquipmentRecord.value = equipment;
  showEquipmentModal.value = true;
}

function openEquipmentManager() {
  router.push({ name: 'adminManageEquipmentPage' });
}

function closeEquipmentModal() {
  showEquipmentModal.value = false;
  selectedEquipmentRecord.value = null;
}

function closeEquipmentViewModal() {
  showEquipmentViewModal.value = false;
  equipmentDetailsLoading.value = false;
  selectedEquipmentDetails.value = null;
}

async function handleEquipmentSaved() {
  showEquipmentModal.value = false;
  selectedEquipmentRecord.value = null;
  await fetchEquipment();
}

function resetVenueFilters() {
  venueSearchQuery.value = '';
  venueFloorFilter.value = 'all';
  venueStatusFilter.value = 'all';
  venueSortValue.value = 'name-asc';
  venuePageSize.value = 10;
}

function closeVenueModals() {
  showVenueViewModal.value = false;
  showVenueEditModal.value = false;
  showVenueAddModal.value = false;
  showVenueDeleteModal.value = false;
}

function hydrateVenueModalForm(venue) {
  venueModalForm.venueName = venue?.venueName || '';
  venueModalForm.floorLevel = venue?.floorLevel || '';
  venueModalForm.venueType = venue?.venueType || resolveVenueType(venue || {});
  venueModalForm.capacityLimit = venue?.capacityLimit || '';
  venueModalForm.venueAvailable = venue?.venueAvailable ?? true;
  venueModalForm.venueDescription = venue?.venueDescription || 'Open rooftop area ideal for events, gatherings, and outdoor activities.';
}

function resetVenueModalForm() {
  venueModalForm.venueName = '';
  venueModalForm.floorLevel = '';
  venueModalForm.venueType = '';
  venueModalForm.capacityLimit = '';
  venueModalForm.venueAvailable = true;
  venueModalForm.venueDescription = '';
}

function resetEquipmentFilters() {
  equipmentSearchQuery.value = '';
  equipmentFilterValue.value = 'all';
  equipmentCategoryFilter.value = 'all';
  equipmentSortValue.value = 'name-asc';
  equipmentPageSize.value = 20;
}

function sortVenueRecords(first, second, sortValue) {
  if (sortValue === 'name-desc') {
    return second.venueName.localeCompare(first.venueName);
  }
  if (sortValue === 'floor-asc') {
    return extractFloorNumber(first.floorLevel) - extractFloorNumber(second.floorLevel);
  }
  if (sortValue === 'floor-desc') {
    return extractFloorNumber(second.floorLevel) - extractFloorNumber(first.floorLevel);
  }
  if (sortValue === 'capacity-desc') {
    return Number(second.capacityLimit || 0) - Number(first.capacityLimit || 0);
  }
  if (sortValue === 'capacity-asc') {
    return Number(first.capacityLimit || 0) - Number(second.capacityLimit || 0);
  }
  return first.venueName.localeCompare(second.venueName);
}

function sortEquipmentRecords(first, second, sortValue) {
  if (sortValue === 'name-desc') {
    return String(second.equipmentName || '').localeCompare(String(first.equipmentName || ''));
  }
  if (sortValue === 'category-asc') {
    return String(first.categoryName || '').localeCompare(String(second.categoryName || ''));
  }
  if (sortValue === 'category-desc') {
    return String(second.categoryName || '').localeCompare(String(first.categoryName || ''));
  }
  if (sortValue === 'quantity-desc') {
    return resolveEquipmentQuantityValue(second) - resolveEquipmentQuantityValue(first);
  }
  if (sortValue === 'quantity-asc') {
    return resolveEquipmentQuantityValue(first) - resolveEquipmentQuantityValue(second);
  }
  return String(first.equipmentName || '').localeCompare(String(second.equipmentName || ''));
}

function formatFloorLabel(value) {
  if (!value && value !== 0) return 'No Floor';
  return `${value}th Floor`;
}

function extractFloorNumber(value) {
  const parsedNumber = Number.parseInt(String(value || '').replace(/[^\d-]/g, ''), 10);
  return Number.isNaN(parsedNumber) ? 0 : parsedNumber;
}

function formatCapacity(value) {
  return Number(value || 0);
}

function resolveVenueType(venue) {
  return venue.venueType || (String(venue.venueName || '').toLowerCase().includes('gym') ? 'Sports Facility' : 'Venue');
}

function getVenueModalStatusClass(isAvailable) {
  return isAvailable ? 'facilities-status-pill--available' : 'facilities-status-pill--unavailable';
}

function normalizeEquipmentStatus(value) {
  return String(value || '').trim() || 'Unavailable';
}

function resolveEquipmentStatusTone(equipment) {
  return normalizeEquipmentStatus(equipment.equipmentState) === 'Available'
    ? 'facilities-status-pill--available'
    : 'facilities-status-pill--unavailable';
}

function resolveEquipmentQuantityValue(equipment) {
  return Number(equipment.availableQuantity ?? equipment.totalQuantity ?? 0);
}

function resolveEquipmentQuantity(equipment) {
  return resolveEquipmentQuantityValue(equipment);
}

function resolveEquipmentDescription(equipment) {
  return String(equipment.description || equipment.scheduleDescription || 'No description provided.').trim();
}

function resolveEquipmentCategory(equipment) {
  return equipment?.equipmentCategory || equipment?.categoryName || 'Uncategorized';
}

function resolveEquipmentStatusValue(equipment) {
  return equipment?.operationalStatus || equipment?.equipmentState || 'Unavailable';
}

async function fetchEquipment() {
  try {
    equipmentLoading.value = true;
    equipmentError.value = '';
    const response = await equipmentApi.listEquipment();
    equipmentList.value = response?.data?.equipment || [];
  } catch (error) {
    equipmentList.value = [];
    equipmentError.value = error?.response?.data?.errorMessage || 'Failed to load equipment records.';
  } finally {
    equipmentLoading.value = false;
  }
}

function formatDateTime(value) {
  if (!value) return 'N/A';

  const parsedDate = new Date(value);
  if (Number.isNaN(parsedDate.getTime())) return 'N/A';

  return new Intl.DateTimeFormat('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(parsedDate);
}

onMounted(() => {
  venueLoading.value = false;
  fetchEquipment();
});
</script>
