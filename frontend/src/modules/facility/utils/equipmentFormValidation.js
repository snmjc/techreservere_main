const JPG_DATA_URL_PATTERN = /^data:image\/jpeg;base64,[A-Za-z0-9+/=\r\n]+$/;

export function normalizeEquipmentForm(form) {
  const assetId = String(form?.assetId ?? form?.serialNumber ?? '').trim();

  return {
    equipmentName: String(form?.equipmentName || '').trim(),
    equipmentCategory: String(form?.equipmentCategory || '').trim(),
    equipmentBrand: String(form?.equipmentBrand || '').trim(),
    availableQuantity: Number(form?.availableQuantity ?? 0),
    operationalStatus: String(form?.operationalStatus || '').trim(),
    description: String(form?.description || '').trim(),
    barcode: String(form?.barcode || '').trim(),
    assetId,
    serialNumber: assetId,
    photoData: normalizeOptionalPhotoData(form?.photoData),
  };
}

export function validateEquipmentForm(form) {
  const normalizedForm = normalizeEquipmentForm(form);

  if (normalizedForm.equipmentName.length < 2) {
    return 'Equipment name must be at least 2 characters.';
  }

  if (normalizedForm.equipmentCategory === '') {
    return 'Equipment type/category is required.';
  }

  if (normalizedForm.equipmentBrand.length < 2) {
    return 'Equipment brand must be at least 2 characters.';
  }

  if (!Number.isInteger(normalizedForm.availableQuantity) || normalizedForm.availableQuantity <= 0) {
    return 'Available quantity must be a whole number greater than zero.';
  }

  if (normalizedForm.operationalStatus === '') {
    return 'Operational status is required.';
  }

  if (normalizedForm.description === '') {
    return 'Description is required.';
  }

  if (normalizedForm.barcode === '') {
    return 'Barcode is required.';
  }

  if (normalizedForm.assetId === '') {
    return 'Asset ID is required.';
  }

  if (normalizedForm.photoData && JPG_DATA_URL_PATTERN.test(normalizedForm.photoData) !== true) {
    return 'Equipment photo must be a valid JPG image.';
  }

  return '';
}

export function validateEquipmentPhotoFile(file) {
  if (!file) {
    return '';
  }

  const lowerName = String(file.name || '').toLowerCase();
  if (!lowerName.endsWith('.jpg') && !lowerName.endsWith('.jpeg')) {
    return 'Equipment photo must be a .jpg image only.';
  }

  if (file.type !== 'image/jpeg') {
    return 'Equipment photo must be a .jpg image only.';
  }

  return '';
}

export function readPhotoFileAsDataUrl(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(String(reader.result || ''));
    reader.onerror = () => reject(new Error('Unable to read the selected equipment photo.'));
    reader.readAsDataURL(file);
  });
}

function normalizeOptionalPhotoData(photoData) {
  const normalizedValue = String(photoData || '').trim();
  return normalizedValue === '' ? null : normalizedValue;
}
