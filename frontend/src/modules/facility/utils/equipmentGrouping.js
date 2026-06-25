import { formatEquipmentStatus } from '@/modules/facility/utils/equipmentPresentation.js';

export function groupBorrowerEquipmentRecords(records) {
  const groupedRecords = new Map();

  for (const record of Array.isArray(records) ? records : []) {
    const normalizedRecord = normalizeEquipmentRecord(record);
    if (!normalizedRecord) {
      continue;
    }

    const groupingKey = buildGroupingKey(normalizedRecord);
    const currentGroup = groupedRecords.get(groupingKey);

    if (!currentGroup) {
      groupedRecords.set(groupingKey, {
        ...normalizedRecord,
        totalQuantity: normalizedRecord.totalQuantity,
        availableQuantity: normalizedRecord.availableQuantity,
        groupedItemCount: 1,
        groupedStatuses: [normalizedRecord.operationalStatus],
        inventoryItems: [buildInventoryItem(normalizedRecord)],
      });
      continue;
    }

    currentGroup.totalQuantity += normalizedRecord.totalQuantity;
    currentGroup.availableQuantity += normalizedRecord.availableQuantity;
    currentGroup.groupedItemCount += 1;
    currentGroup.groupedStatuses.push(normalizedRecord.operationalStatus);
    currentGroup.inventoryItems.push(buildInventoryItem(normalizedRecord));

    if (!currentGroup.description && normalizedRecord.description) {
      currentGroup.description = normalizedRecord.description;
      currentGroup.scheduleDescription = normalizedRecord.description;
    }

    if (!currentGroup.photoData && normalizedRecord.photoData) {
      currentGroup.photoData = normalizedRecord.photoData;
      currentGroup.photoDisplayMode = normalizedRecord.photoDisplayMode;
      currentGroup.photoPositionX = normalizedRecord.photoPositionX;
      currentGroup.photoPositionY = normalizedRecord.photoPositionY;
    }
  }

  return Array.from(groupedRecords.values()).map(finalizeGroupedRecord);
}

function normalizeEquipmentRecord(record) {
  const equipmentIdentifier = Number(record?.equipmentIdentifier);
  if (!Number.isFinite(equipmentIdentifier) || equipmentIdentifier <= 0) {
    return null;
  }

  const normalizedName = normalizeDisplayText(record?.equipmentName);
  const normalizedCategory = normalizeDisplayText(record?.equipmentCategory || record?.categoryName || 'Miscellaneous');
  const normalizedBrand = normalizeBrandLabel(record?.equipmentBrand);

  return {
    equipmentIdentifier,
    equipmentName: normalizedName,
    equipmentCategory: normalizedCategory,
    equipmentBrand: normalizedBrand,
    totalQuantity: Math.max(Number(record?.totalQuantity ?? record?.availableQuantity ?? 0) || 0, 0),
    availableQuantity: Math.max(Number(record?.availableQuantity ?? record?.totalQuantity ?? 0) || 0, 0),
    operationalStatus: normalizeDisplayText(record?.operationalStatus || record?.equipmentState || ''),
    equipmentState: normalizeDisplayText(record?.equipmentState || record?.operationalStatus || ''),
    description: normalizeDisplayText(record?.description || record?.scheduleDescription || ''),
    scheduleDescription: normalizeDisplayText(record?.description || record?.scheduleDescription || ''),
    imageUrl: record?.imageUrl || null,
    barcode: normalizeDisplayText(record?.barcode),
    assetId: normalizeDisplayText(record?.assetId || record?.serialNumber || ''),
    serialNumber: normalizeDisplayText(record?.assetId || record?.serialNumber || ''),
    photoData: record?.photoData || null,
    photoDisplayMode: normalizeDisplayText(record?.photoDisplayMode || 'contain'),
    photoPositionX: Number(record?.photoPositionX ?? 50),
    photoPositionY: Number(record?.photoPositionY ?? 50),
  };
}

function buildGroupingKey(record) {
  const normalizedName = normalizeKeyPart(record.equipmentName);
  const normalizedBrand = normalizeKeyPart(record.equipmentBrand);
  const normalizedCategory = normalizeKeyPart(record.equipmentCategory);

  return `${normalizedName}::${normalizedBrand}::${normalizedCategory}`;
}

function buildInventoryItem(record) {
  return {
    equipmentIdentifier: record.equipmentIdentifier,
    equipmentName: record.equipmentName,
    equipmentBrand: record.equipmentBrand,
    equipmentCategory: record.equipmentCategory,
    totalQuantity: record.totalQuantity,
    availableQuantity: record.availableQuantity,
    operationalStatus: record.operationalStatus,
    equipmentState: record.equipmentState,
    description: record.description,
    barcode: record.barcode,
    assetId: record.assetId,
    serialNumber: record.serialNumber,
  };
}

function finalizeGroupedRecord(record) {
  const uniqueDescriptions = Array.from(new Set(
    (record.inventoryItems || [])
      .map((item) => normalizeDisplayText(item.description))
      .filter(Boolean)
  ));

  const derivedStatus = resolveGroupedStatus(record.groupedStatuses || []);
  const visibleInventoryItems = [...(record.inventoryItems || [])].sort((left, right) => (
    normalizeKeyPart(left.assetId).localeCompare(normalizeKeyPart(right.assetId))
    || normalizeKeyPart(left.barcode).localeCompare(normalizeKeyPart(right.barcode))
    || Number(left.equipmentIdentifier) - Number(right.equipmentIdentifier)
  ));
  const inventoryPreview = visibleInventoryItems
    .slice(0, 2)
    .map((item) => normalizeDisplayText(item.assetId || item.serialNumber || item.barcode))
    .filter(Boolean);

  return {
    ...record,
    operationalStatus: derivedStatus,
    equipmentState: derivedStatus,
    description: record.description || uniqueDescriptions[0] || '',
    scheduleDescription: record.description || uniqueDescriptions[0] || '',
    remarksNotes: uniqueDescriptions,
    inventoryItems: visibleInventoryItems,
    brandGroupLabel: record.equipmentBrand || 'Unbranded',
    groupedQuantityLabel: `${record.groupedItemCount} item${record.groupedItemCount === 1 ? '' : 's'} grouped`,
    availableQuantityLabel: `${record.availableQuantity} available`,
    inventoryPreview,
  };
}

function resolveGroupedStatus(statuses) {
  const normalizedStatuses = statuses.map((status) => normalizeKeyPart(formatEquipmentStatus({ operationalStatus: status })));

  if (normalizedStatuses.includes('available')) {
    return 'Available';
  }

  if (normalizedStatuses.includes('under maintenance')) {
    return 'Under Maintenance';
  }

  if (normalizedStatuses.includes('retired')) {
    return 'Retired';
  }

  return 'Unavailable';
}

function normalizeKeyPart(value) {
  return normalizeDisplayText(value).toLowerCase();
}

function normalizeDisplayText(value) {
  return String(value || '').replace(/\s+/g, ' ').trim();
}

function normalizeBrandLabel(value) {
  const normalizedValue = normalizeDisplayText(value);
  return normalizedValue === '' ? 'Unbranded' : normalizedValue;
}
