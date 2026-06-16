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
  const activityEndDate = ref('');
  const activityTimeFrom = ref('');
  const activityTimeTo = ref('');
  const activityNameTitle = ref('');
  const purposeText = ref('');
  const departmentName = ref('');
  const participantCount = ref('');
  const selectedVenueName = ref(null);
  const selectedVenueRecord = ref(null);
  const selectedEquipmentItems = ref([]);
  const securityGuardCount = ref('None');
  const securityCrewCount = ref('None');
  const supportingDocumentsList = ref([]);
  const recommendationDocumentsList = ref([]);
  const additionalDocumentsList = ref([]);
  const documentType = ref('Reservation');

  function resetForm() {
    reservationType.value = 'Venue';
    requestDate.value = '';
    activityDate.value = '';
    activityEndDate.value = '';
    activityTimeFrom.value = '';
    activityTimeTo.value = '';
    activityNameTitle.value = '';
    purposeText.value = '';
    departmentName.value = '';
    participantCount.value = '';
    selectedVenueName.value = null;
    selectedVenueRecord.value = null;
    selectedEquipmentItems.value = [];
    securityGuardCount.value = 'None';
    securityCrewCount.value = 'None';
    supportingDocumentsList.value = [];
    recommendationDocumentsList.value = [];
    additionalDocumentsList.value = [];
    documentType.value = 'Reservation';
  }

  return {
    reservationType,
    requestDate,
    activityDate,
    activityEndDate,
    activityTimeFrom,
    activityTimeTo,
    activityNameTitle,
    purposeText,
    departmentName,
    participantCount,
    selectedVenueName,
    selectedVenueRecord,
    selectedEquipmentItems,
    securityGuardCount,
    securityCrewCount,
    supportingDocumentsList,
    recommendationDocumentsList,
    additionalDocumentsList,
    documentType,
    resetForm,
  };
});
