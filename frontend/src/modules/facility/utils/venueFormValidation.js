const JPG_DATA_URL_PATTERN = /^data:image\/jpeg;base64,[A-Za-z0-9+/=\r\n]+$/;
const PHOTO_FILE_EXTENSION_PATTERN = /\.jpe?g$/i;
const FLOOR_CATEGORY_PATTERN = /^(?:\d+(?:st|nd|rd|th)\s+Floor|GF \/ 1st Floor|MH Floor|Pool|Outdoor)$/i;
const APP_FONT_STACK = "'Inter', 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif";
const MAX_VENUE_IMAGE_DATA_URL_LENGTH = 1_500_000;
const VENUE_PHOTO_COMPRESSION_STEPS = [
  { maxDimension: 1280, quality: 0.82 },
  { maxDimension: 1180, quality: 0.76 },
  { maxDimension: 1024, quality: 0.7 },
  { maxDimension: 900, quality: 0.64 },
  { maxDimension: 768, quality: 0.58 },
];

const VENUE_FORM_VALIDATORS = [
  {
    isInvalid: (form) => form.venueName.length < 2,
    message: 'Venue name must be at least 2 characters.',
  },
  {
    isInvalid: (form) => form.venueLocation.length < 2,
    message: 'Location must be at least 2 characters.',
  },
  {
    isInvalid: (form) => form.floorLevel === '',
    message: 'Floor level is required.',
  },
  {
    isInvalid: (form) => !Number.isInteger(form.capacityLimit) || form.capacityLimit <= 0,
    message: 'Capacity must be a whole number greater than zero.',
  },
  {
    isInvalid: (form) => form.availabilityDate === '',
    message: 'Availability date is required.',
  },
  {
    isInvalid: (form) => form.operationalStatus === '',
    message: 'Operational status is required.',
  },
  {
    isInvalid: (form) => form.availabilityStatus === '',
    message: 'Room availability is required.',
  },
  {
    isInvalid: (form) => {
      const photoValue = String(form.imageUrl || form.photoData || '').trim();
      return Boolean(photoValue) && JPG_DATA_URL_PATTERN.test(photoValue) !== true;
    },
    message: 'Venue photo must be a valid JPG image.',
  },
  {
    isInvalid: (form) => {
      const photoValue = String(form.imageUrl || form.photoData || '').trim();
      return photoValue.length > MAX_VENUE_IMAGE_DATA_URL_LENGTH;
    },
    message: 'Venue photo is too large. Please upload a smaller JPG image.',
  },
];

export const venueOperationalStatuses = ['Active', 'Inactive', 'Maintenance'];
export const venueAvailabilityStatuses = ['Available', 'Unavailable'];

export function normalizeVenueForm(form) {
  const normalizedPhotoData = normalizeOptionalPhotoData(form?.photoData);

  return {
    venueName: String(form?.venueName || '').trim(),
    venueLocation: String(form?.venueLocation || '').trim(),
    floorLevel: String(form?.floorLevel || '').trim(),
    capacityLimit: Number(form?.capacityLimit ?? 0),
    availabilityDate: String(form?.availabilityDate || '').trim(),
    operationalStatus: String(form?.operationalStatus || '').trim(),
    availabilityStatus: String(form?.availabilityStatus || '').trim(),
    description: String(form?.description || '').trim(),
    imageUrl: normalizedPhotoData,
  };
}

export function validateVenueForm(form) {
  const normalizedForm = normalizeVenueForm(form);
  const failedRule = VENUE_FORM_VALIDATORS.find(({ isInvalid }) => isInvalid(normalizedForm));
  return failedRule?.message || '';
}

export function validateVenuePhotoFile(file) {
  if (!file) {
    return '';
  }

  if (!isJpgPhotoFile(file)) {
    return 'Venue photo must be a .jpg image only.';
  }

  return '';
}

export function readVenuePhotoFileAsDataUrl(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = async () => {
      try {
        const imageDataUrl = await compressVenuePhoto(String(reader.result || ''));
        resolve(imageDataUrl);
      } catch (error) {
        reject(error);
      }
    };
    reader.onerror = () => reject(new Error('Unable to read the selected venue photo.'));
    reader.readAsDataURL(file);
  });
}

export function formatVenueText(value) {
  const normalizedValue = String(value || '').trim();
  return normalizedValue === '' ? 'N/A' : normalizedValue;
}

export function formatVenueCapacity(value) {
  return Number.isFinite(Number(value)) && Number(value) > 0 ? Number(value) : 'N/A';
}

export function sanitizeVenuePayload(form) {
  const normalizedForm = normalizeVenueForm(form);
  return {
    venueName: normalizedForm.venueName,
    venueLocation: normalizedForm.venueLocation,
    floorLevel: normalizedForm.floorLevel,
    capacityLimit: normalizedForm.capacityLimit,
    availabilityDate: normalizedForm.availabilityDate,
    operationalStatus: normalizedForm.operationalStatus,
    availabilityStatus: normalizedForm.availabilityStatus,
    description: normalizedForm.description,
    imageUrl: normalizedForm.imageUrl,
  };
}

export function resolveVenuePhoto(record) {
  const photoData = String(record?.photoData || record?.imageUrl || '').trim();
  if (photoData !== '') {
    return photoData;
  }

  return `data:image/svg+xml;utf8,${encodeURIComponent(`
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 480 320">
      <defs>
        <linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#f5fbf7"/>
          <stop offset="100%" stop-color="#dcefe1"/>
        </linearGradient>
      </defs>
      <rect width="480" height="320" fill="url(#g)"/>
      <rect x="54" y="48" width="372" height="224" rx="24" fill="#ffffff" stroke="#b7d4c0" stroke-width="6"/>
      <rect x="98" y="100" width="284" height="114" rx="16" fill="#e7f5ea"/>
      <path d="M118 214l74-60 50 38 60-74 76 96H118z" fill="#bfe1c8"/>
      <text x="240" y="286" text-anchor="middle" font-family="${APP_FONT_STACK}" font-size="28" font-weight="700" fill="#386641">No Venue Photo</text>
    </svg>
  `)}`;
}

export function deriveVenueAvailabilityForDate(venueRecord, selectedDate) {
  const normalizedAvailabilityStatus = String(venueRecord?.availabilityStatus || '').trim();
  if (normalizedAvailabilityStatus === 'Available' || normalizedAvailabilityStatus === 'Unavailable') {
    return normalizedAvailabilityStatus;
  }

  const operationalStatus = String(venueRecord?.operationalStatus || '').trim();
  if (operationalStatus !== 'Active') {
    return 'Unavailable';
  }

  const normalizedDate = String(selectedDate || '').trim();
  const availableDate = String(venueRecord?.availabilityDate || '').trim();

  if (normalizedDate === '' || availableDate === '') {
    return venueRecord?.availabilityStatus === 'Available' ? 'Available' : 'Unavailable';
  }

  return normalizedDate >= availableDate ? 'Available' : 'Unavailable';
}

export function isVenueFloorPlaceholderRecord(venueRecord) {
  const venueName = String(venueRecord?.venueName || '').trim();
  const floorLevel = String(venueRecord?.floorLevel || '').trim();

  if (venueName === '') {
    return false;
  }

  const normalizedVenueName = venueName.toLowerCase();
  const normalizedFloorLevel = floorLevel.toLowerCase();

  return FLOOR_CATEGORY_PATTERN.test(venueName)
    && (normalizedFloorLevel === '' || normalizedVenueName === normalizedFloorLevel);
}

function normalizeOptionalPhotoData(photoData) {
  const normalizedValue = String(photoData || '').trim();
  return normalizedValue === '' ? null : normalizedValue;
}

function isJpgPhotoFile(file) {
  const fileName = String(file?.name || '');
  return PHOTO_FILE_EXTENSION_PATTERN.test(fileName) && file?.type === 'image/jpeg';
}

function compressVenuePhoto(sourceDataUrl) {
  return new Promise((resolve, reject) => {
    const image = new Image();

    image.onload = () => {
      const canvas = document.createElement('canvas');
      const context = canvas.getContext('2d');
      if (!context) {
        reject(new Error('Unable to prepare the selected venue photo.'));
        return;
      }

      for (const step of VENUE_PHOTO_COMPRESSION_STEPS) {
        const { width, height } = scaleVenuePhotoDimensions(image.width, image.height, step.maxDimension);
        canvas.width = width;
        canvas.height = height;
        context.clearRect(0, 0, width, height);
        context.drawImage(image, 0, 0, width, height);

        const compressedDataUrl = canvas.toDataURL('image/jpeg', step.quality);
        if (compressedDataUrl.length <= MAX_VENUE_IMAGE_DATA_URL_LENGTH) {
          resolve(compressedDataUrl);
          return;
        }
      }

      reject(new Error('Venue photo is too large. Please upload a smaller JPG image.'));
    };

    image.onerror = () => reject(new Error('Unable to process the selected venue photo.'));
    image.src = sourceDataUrl;
  });
}

function scaleVenuePhotoDimensions(width, height, maxDimension) {
  if (width <= maxDimension && height <= maxDimension) {
    return { width, height };
  }

  if (width >= height) {
    const ratio = maxDimension / width;
    return {
      width: maxDimension,
      height: Math.round(height * ratio),
    };
  }

  const ratio = maxDimension / height;
  return {
    width: Math.round(width * ratio),
    height: maxDimension,
  };
}
