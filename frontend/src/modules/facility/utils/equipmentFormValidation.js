const PHOTO_DATA_URL_PATTERN = /^data:image\/(?:jpeg|jpg|png|webp);base64,[A-Za-z0-9+/=\r\n]+$/i;
const PHOTO_FILE_EXTENSION_PATTERN = /\.(jpg|jpeg|png|webp)$/i;
const GENERATED_ASSET_ID_PATTERN = /^TR-[A-Z]{3}-\d{4}$/;
const LEGACY_ASSET_ID_PATTERN = /^F\d{3}-\d{3}-\d{3}$/;
const ALLOWED_PHOTO_MIME_TYPES = new Set(['image/jpeg', 'image/jpg', 'image/png', 'image/webp']);

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
    isInvalid: (form) => Boolean(form.assetId) && !isSupportedAssetId(form.assetId),
    message: 'Asset ID must match the generated TechReserve format.',
  },
  {
    isInvalid: (form) => Boolean(form.photoData) && PHOTO_DATA_URL_PATTERN.test(form.photoData) !== true,
    message: 'Equipment photo must be a valid JPG, PNG, or WebP image.',
  },
];

export function normalizeEquipmentForm(form) {
  const assetId = String(form?.assetId ?? form?.serialNumber ?? '').trim();

  const normalizedAssetId = assetId.toUpperCase();

  return {
    equipmentName: String(form?.equipmentName || '').trim(),
    equipmentCategory: String(form?.equipmentCategory || '').trim(),
    equipmentBrand: String(form?.equipmentBrand || '').trim(),
    availableQuantity: Number(form?.availableQuantity ?? 0),
    operationalStatus: String(form?.operationalStatus || '').trim(),
    description: String(form?.description || '').trim(),
    barcode: String(form?.barcode || '').trim(),
    assetId: normalizedAssetId,
    serialNumber: normalizedAssetId,
    photoData: normalizeOptionalPhotoData(form?.photoData),
    photoDisplayMode: normalizePhotoDisplayMode(form?.photoDisplayMode),
    photoPositionX: normalizePhotoPosition(form?.photoPositionX),
    photoPositionY: normalizePhotoPosition(form?.photoPositionY),
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
    return 'Equipment photo must be a JPG, PNG, or WebP image.';
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
  return PHOTO_FILE_EXTENSION_PATTERN.test(fileName) && ALLOWED_PHOTO_MIME_TYPES.has(String(file?.type || '').toLowerCase());
}

function isSupportedAssetId(assetId) {
  return GENERATED_ASSET_ID_PATTERN.test(assetId) || LEGACY_ASSET_ID_PATTERN.test(assetId);
}

function normalizePhotoDisplayMode(value) {
  return String(value || '').trim().toLowerCase() === 'cover' ? 'cover' : 'contain';
}

function normalizePhotoPosition(value) {
  const numericValue = Number(value);
  if (!Number.isFinite(numericValue)) {
    return 50;
  }

  return Math.max(0, Math.min(100, Math.round(numericValue)));
}
