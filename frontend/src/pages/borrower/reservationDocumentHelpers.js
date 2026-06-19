const ALLOWED_RESERVATION_DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
const ALLOWED_RESERVATION_DOCUMENT_MIME_TYPES = [
  'application/pdf',
  'application/msword',
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  'image/jpeg',
  'image/pjpeg',
  'image/png',
];

export function getReservationDocumentAcceptValue() {
  return '.pdf,.doc,.docx,.jpg,.jpeg,.png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png';
}

export function validateReservationDocumentFile(file) {
  if (!file) return '';

  const extension = getFileExtension(file.name);
  if (!ALLOWED_RESERVATION_DOCUMENT_EXTENSIONS.includes(extension)) {
    return 'Only PDF, DOC, DOCX, JPG, and PNG files are allowed.';
  }

  const normalizedMimeType = String(file.type || '').toLowerCase();
  if (!ALLOWED_RESERVATION_DOCUMENT_MIME_TYPES.includes(normalizedMimeType)) {
    return 'Only PDF, DOC, DOCX, JPG, and PNG files are allowed.';
  }

  return '';
}

function getFileExtension(fileName) {
  const segments = String(fileName || '').toLowerCase().split('.');
  return segments.length > 1 ? segments.pop() : '';
}
