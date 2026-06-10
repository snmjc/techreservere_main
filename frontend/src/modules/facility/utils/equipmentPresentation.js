const EQUIPMENT_PLACEHOLDER_IMAGE = `data:image/svg+xml;utf8,${encodeURIComponent(`
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 480 320">
    <defs>
      <linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" stop-color="#eff6f0"/>
        <stop offset="100%" stop-color="#dcefe1"/>
      </linearGradient>
    </defs>
    <rect width="480" height="320" fill="url(#g)"/>
    <rect x="66" y="56" width="348" height="208" rx="24" fill="#ffffff" stroke="#b7d4c0" stroke-width="6"/>
    <circle cx="168" cy="138" r="28" fill="#d3ead8"/>
    <path d="M114 228l68-62 46 44 58-70 80 88H114z" fill="#bfe1c8"/>
    <text x="240" y="286" text-anchor="middle" font-family="Arial, sans-serif" font-size="28" font-weight="700" fill="#386641">No Photo</text>
  </svg>
`)}`;

export function formatEquipmentText(value) {
  const normalizedValue = String(value || '').trim();
  return normalizedValue === '' ? 'N/A' : normalizedValue;
}

export function formatEquipmentQuantity(value) {
  return Number.isFinite(Number(value)) ? Number(value) : 'N/A';
}

export function formatEquipmentStatus(equipmentRecord) {
  const operationalStatus = String(equipmentRecord?.operationalStatus || '').trim();
  if (operationalStatus === 'Active') return 'Available';
  if (operationalStatus === 'Inactive') return 'Unavailable';
  if (operationalStatus === 'Maintenance') return 'Under Maintenance';
  return operationalStatus || formatEquipmentText(equipmentRecord?.equipmentState);
}

export function resolveEquipmentPhoto(equipmentRecord) {
  const photoData = String(equipmentRecord?.photoData || '').trim();
  return photoData !== '' ? photoData : EQUIPMENT_PLACEHOLDER_IMAGE;
}

