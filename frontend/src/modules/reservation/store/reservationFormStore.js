import { defineStore } from 'pinia';
import { ref } from 'vue';
import {
  clearReservationWizardCache,
  readReservationWizardCache,
  writeReservationWizardCache,
} from '@/modules/reservation/utils/reservationWizard.js';

/**
 * @description Shared Pinia store for the multi-step reservation form.
 * Persists form state (especially reservationType) across Page 1 → Page 2 → Page 3.
 */
export const useReservationFormStore = defineStore('reservationFormStore', () => {
  const cachedState = readReservationWizardCache() || {};
  const reservationType = ref(cachedState.reservationType || 'Venue');
  const requestDate = ref(cachedState.requestDate || '');
  const activityDate = ref(cachedState.activityDate || '');
  const activityEndDate = ref(cachedState.activityEndDate || '');
  const activityTimeFrom = ref(cachedState.activityTimeFrom || '');
  const activityTimeTo = ref(cachedState.activityTimeTo || '');
  const activityNameTitle = ref(cachedState.activityNameTitle || '');
  const purposeText = ref(cachedState.purposeText || '');
  const purposeOtherText = ref(cachedState.purposeOtherText || '');
  const departmentName = ref(cachedState.departmentName || '');
  const participantCount = ref(cachedState.participantCount || '');
  const selectedVenueName = ref(cachedState.selectedVenueName || null);
  const selectedVenueRecord = ref(cachedState.selectedVenueRecord || null);
  const selectedEquipmentItems = ref(Array.isArray(cachedState.selectedEquipmentItems) ? cachedState.selectedEquipmentItems : []);
  const securityGuardCount = ref(cachedState.securityGuardCount || 'None');
  const securityCrewCount = ref(cachedState.securityCrewCount || 'None');
  const borrowerRemarks = ref(cachedState.borrowerRemarks || '');
  const supportingDocumentsList = ref(Array.isArray(cachedState.supportingDocumentsList) ? cachedState.supportingDocumentsList : []);
  const recommendationDocumentsList = ref(Array.isArray(cachedState.recommendationDocumentsList) ? cachedState.recommendationDocumentsList : []);
  const additionalDocumentsList = ref(Array.isArray(cachedState.additionalDocumentsList) ? cachedState.additionalDocumentsList : []);
  const documentType = ref(cachedState.documentType || 'Reservation');

  function persistForm() {
    writeReservationWizardCache({
      reservationType: reservationType.value,
      requestDate: requestDate.value,
      activityDate: activityDate.value,
      activityEndDate: activityEndDate.value,
      activityTimeFrom: activityTimeFrom.value,
      activityTimeTo: activityTimeTo.value,
      activityNameTitle: activityNameTitle.value,
      purposeText: purposeText.value,
      purposeOtherText: purposeOtherText.value,
      departmentName: departmentName.value,
      participantCount: participantCount.value,
      selectedVenueName: selectedVenueName.value,
      selectedVenueRecord: selectedVenueRecord.value,
      selectedEquipmentItems: selectedEquipmentItems.value,
      securityGuardCount: securityGuardCount.value,
      securityCrewCount: securityCrewCount.value,
      borrowerRemarks: borrowerRemarks.value,
      supportingDocumentsList: supportingDocumentsList.value,
      recommendationDocumentsList: recommendationDocumentsList.value,
      additionalDocumentsList: additionalDocumentsList.value,
      documentType: documentType.value,
    });
  }

  function clearWizardCache() {
    clearReservationWizardCache();
  }

  function hasReservationDetails() {
    return Boolean(
      String(requestDate.value || '').trim()
      && String(activityDate.value || '').trim()
      && String(activityEndDate.value || '').trim()
      && String(activityTimeFrom.value || '').trim()
      && String(activityTimeTo.value || '').trim()
      && String(activityNameTitle.value || '').trim()
      && String(purposeText.value || '').trim()
      && (
        String(purposeText.value || '').trim() !== 'Others: Specify'
        || String(purposeOtherText.value || '').trim()
      )
      && String(participantCount.value || '').trim()
    );
  }

  function hasSelectionForCurrentType() {
    if (reservationType.value === 'Venue') {
      return Boolean(selectedVenueRecord.value?.venueIdentifier);
    }

    if (reservationType.value === 'Equipment') {
      return selectedEquipmentItems.value.length > 0;
    }

    return Boolean(selectedVenueRecord.value?.venueIdentifier) && selectedEquipmentItems.value.length > 0;
  }

  function hasDocumentUploads() {
    return supportingDocumentsList.value.length > 0 || recommendationDocumentsList.value.length > 0 || additionalDocumentsList.value.length > 0;
  }

  function resetForm() {
    reservationType.value = 'Venue';
    requestDate.value = '';
    activityDate.value = '';
    activityEndDate.value = '';
    activityTimeFrom.value = '';
    activityTimeTo.value = '';
    activityNameTitle.value = '';
    purposeText.value = '';
    purposeOtherText.value = '';
    departmentName.value = '';
    participantCount.value = '';
    selectedVenueName.value = null;
    selectedVenueRecord.value = null;
    selectedEquipmentItems.value = [];
    securityGuardCount.value = 'None';
    securityCrewCount.value = 'None';
    borrowerRemarks.value = '';
    supportingDocumentsList.value = [];
    recommendationDocumentsList.value = [];
    additionalDocumentsList.value = [];
    documentType.value = 'Reservation';
    clearWizardCache();
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
    purposeOtherText,
    departmentName,
    participantCount,
    selectedVenueName,
    selectedVenueRecord,
    selectedEquipmentItems,
    securityGuardCount,
    securityCrewCount,
    borrowerRemarks,
    supportingDocumentsList,
    recommendationDocumentsList,
    additionalDocumentsList,
    documentType,
    persistForm,
    clearWizardCache,
    hasReservationDetails,
    hasSelectionForCurrentType,
    hasDocumentUploads,
    resetForm,
  };
});
