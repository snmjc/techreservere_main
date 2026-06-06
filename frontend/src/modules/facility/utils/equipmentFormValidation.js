const ASSET_ID_PATTERN = /^F\d{3}-\d{3}-\d{3}$/;
const REQUIRED_FIELD_KEYS = [
  'equipmentName',
  'equipmentCategory',
  'equipmentBrand',
  'availableQuantity',
  'operationalStatus',
  'barcode',
  'assetId',
];

export function isValidAssetId(assetId) {
  return ASSET_ID_PATTERN.test(String(assetId || '').trim().toUpperCase());
}

export function normalizeEquipmentForm(form) {
  return {
    equipmentName: String(form?.equipmentName || '').trim(),
    equipmentCategory: String(form?.equipmentCategory || '').trim(),
    equipmentBrand: String(form?.equipmentBrand || '').trim(),
    availableQuantity: Number(form?.availableQuantity ?? 0),
    operationalStatus: String(form?.operationalStatus || '').trim(),
    description: normalizeOptionalText(form?.description),
    barcode: String(form?.barcode || '').trim(),
    assetId: String(form?.assetId || '').trim().toUpperCase(),
  };
}

export function validateEquipmentForm(form) {
  const normalizedForm = normalizeEquipmentForm(form);

  for (const fieldKey of REQUIRED_FIELD_KEYS) {
    const fieldValue = normalizedForm[fieldKey];
    if (fieldKey === 'availableQuantity') {
      if (!Number.isInteger(fieldValue) || fieldValue <= 0) {
        return 'Available quantity must be a whole number greater than zero.';
      }
      continue;
    }

    if (fieldValue === '') {
      return getRequiredFieldMessage(fieldKey);
    }
  }

  if (!isValidAssetId(normalizedForm.assetId)) {
    return 'Asset ID must follow the format F123-456-789.';
  }

  return '';
}

function normalizeOptionalText(value) {
  const normalizedValue = String(value || '').trim();
  return normalizedValue === '' ? null : normalizedValue;
}

function getRequiredFieldMessage(fieldKey) {
  return {
    equipmentName: 'Equipment name is required.',
    equipmentCategory: 'Equipment type/category is required.',
    equipmentBrand: 'Equipment brand is required.',
    operationalStatus: 'Operational status is required.',
    barcode: 'Barcode is required.',
    assetId: 'Asset ID is required.',
  }[fieldKey] || 'Please complete all required fields.';
}
