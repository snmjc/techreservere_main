import { defineStore } from 'pinia';
import { ref } from 'vue';

/**
 * @description Shared Pinia store for the multi-step reservation form.
 * Persists form state (especially reservationType) across Page 1 → Page 2 → Page 3.
 */
export const useReservationFormStore = defineStore('reservationFormStore', () => {
  const reservationType = ref('Venue');
  const requestDate = ref('');
  const activityDate = ref('');
  const activityTime = ref('');
  const activityNameTitle = ref('');
  const purposeText = ref('');
  const departmentName = ref('');
  const participantCount = ref('');
  const selectedVenueName = ref(null);
  const selectedEquipmentItems = ref([]);

  function resetForm() {
    reservationType.value = 'Venue';
    requestDate.value = '';
    activityDate.value = '';
    activityTime.value = '';
    activityNameTitle.value = '';
    purposeText.value = '';
    departmentName.value = '';
    participantCount.value = '';
    selectedVenueName.value = null;
    selectedEquipmentItems.value = [];
  }

  return {
    reservationType,
    requestDate,
    activityDate,
    activityTime,
    activityNameTitle,
    purposeText,
    departmentName,
    participantCount,
    selectedVenueName,
    selectedEquipmentItems,
    resetForm,
  };
});
