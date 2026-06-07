import { ref, onMounted } from 'vue';
import equipmentApi from '@/modules/reservation/services/equipmentApi.js';
import venueApi from '@/modules/reservation/services/venueApi.js';

export function useReservationData() {
  const equipmentList = ref([]);
  const venueList = ref([]);
  const isLoading = ref(false);
  const error = ref(null);

  async function fetchEquipment() {
    try {
      isLoading.value = true;
      error.value = null;
      const response = await equipmentApi.listEquipment();
      const equipment = response?.data?.equipment || [];
      equipmentList.value = equipment.map(eq => ({
          equipmentIdentifier: eq.equipmentIdentifier,
          equipmentName: eq.equipmentName,
          categoryName: eq.categoryName,
          totalQuantity: eq.totalQuantity,
          availableQuantity: eq.availableQuantity,
          operationalStatus: eq.operationalStatus,
          equipmentState: eq.equipmentState,
        }));
    } catch (err) {
      error.value = err.message || 'Failed to fetch equipment';
      console.error('Equipment fetch error:', err);
    } finally {
      isLoading.value = false;
    }
  }

  async function fetchVenues() {
    try {
      isLoading.value = true;
      error.value = null;
      const response = await venueApi.listVenues();
      const venues = response?.data?.venues || response?.venues || [];
      if (Array.isArray(venues)) {
        venueList.value = venues.map(venue => ({
          venueIdentifier: venue.venueIdentifier,
          venueName: venue.venueName,
          venueLocation: venue.venueLocation,
          capacityLimit: venue.capacityLimit,
          availabilityDate: venue.availabilityDate,
          availabilityStatus: venue.availabilityStatus,
          operationalStatus: venue.operationalStatus || 'Active',
          venueState: venue.availabilityStatus === 'Available' ? 'Available' : 'Unavailable',
        }));
      }
    } catch (err) {
      error.value = err.message || 'Failed to fetch venues';
      console.error('Venue fetch error:', err);
    } finally {
      isLoading.value = false;
    }
  }

  async function loadAllData() {
    await Promise.all([fetchEquipment(), fetchVenues()]);
  }

  return {
    equipmentList,
    venueList,
    isLoading,
    error,
    fetchEquipment,
    fetchVenues,
    loadAllData,
  };
}
