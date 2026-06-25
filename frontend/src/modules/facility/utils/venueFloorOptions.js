export const VENUE_FLOOR_OPTIONS = [
  '18th Floor',
  '17th Floor',
  '16th Floor',
  '15th Floor',
  '11th Floor',
  '10th Floor',
  '9th Floor',
  '8th Floor',
  '7th Floor',
  '6th Floor',
  '5th Floor',
  '4th Floor',
  '3rd Floor',
  '2nd Floor',
  '1st Floor',
  'GF / 1st Floor',
  'MH Floor',
  'Pool',
  'Outdoor',
];

export function sortVenueFloorLabels(floorLabels = []) {
  const normalizedFloorLabels = Array.from(
    new Set(
      (Array.isArray(floorLabels) ? floorLabels : [])
        .map((floorLabel) => String(floorLabel || '').trim())
        .filter(Boolean)
    )
  );

  return [
    ...VENUE_FLOOR_OPTIONS.filter((floorLabel) => normalizedFloorLabels.includes(floorLabel)),
    ...normalizedFloorLabels
      .filter((floorLabel) => !VENUE_FLOOR_OPTIONS.includes(floorLabel))
      .sort((left, right) => left.localeCompare(right)),
  ];
}
