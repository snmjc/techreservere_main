const JPG_DATA_URL_PATTERN = /^data:image\/jpeg;base64,[A-Za-z0-9+/=\r\n]+$/;
const PHOTO_FILE_EXTENSION_PATTERN = /\.jpe?g$/i;

const EQUIPMENT_FORM_VALIDATORS = [
  {
    isInvalid: (form) => form.equipmentName.length < 2,
    message: 'Equipment name must be at least 2 characters.',
  },
  {
    isInvalid: (form) => form.equipmentCategory === '',
    message: 'Equipment type/category is required.',
  },
  {
    isInvalid: (form) => form.equipmentBrand.length < 2,
    message: 'Equipment brand must be at least 2 characters.',
  },
  {
    isInvalid: (form) => !Number.isInteger(form.availableQuantity) || form.availableQuantity <= 0,
    message: 'Available quantity must be a whole number greater than zero.',
  },
  {
    isInvalid: (form) => form.operationalStatus === '',
    message: 'Operational status is required.',
  },
  {
    isInvalid: (form) => form.description === '',
    message: 'Description is required.',
  },
  {
    isInvalid: (form) => form.barcode === '',
    message: 'Barcode is required.',
  },
  {
    isInvalid: (form) => form.assetId === '',
    message: 'Asset ID is required.',
  },
  {
    isInvalid: (form) => Boolean(form.photoData) && JPG_DATA_URL_PATTERN.test(form.photoData) !== true,
    message: 'Equipment photo must be a valid JPG image.',
  },
];

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
  const failedRule = EQUIPMENT_FORM_VALIDATORS.find(({ isInvalid }) => isInvalid(normalizedForm));
  return failedRule?.message || '';
}

export function validateEquipmentPhotoFile(file) {
  if (!file) {
    return '';
  }

  if (!isJpgPhotoFile(file)) {
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

function isJpgPhotoFile(file) {
  const fileName = String(file?.name || '');
  return PHOTO_FILE_EXTENSION_PATTERN.test(fileName) && file?.type === 'image/jpeg';
}
