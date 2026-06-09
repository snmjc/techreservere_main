const MAX_SUPPORTING_FILE_SIZE_BYTES = 5 * 1024 * 1024;
const ALLOWED_SUPPORTING_FILE_EXTENSIONS = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
const ALLOWED_SUPPORTING_FILE_MIME_TYPES = [
  'application/pdf',
  'application/msword',
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  'image/jpeg',
  'image/pjpeg',
  'image/png',
];

export function validateSignupSupportingFile(file) {
  if (!file) return '';

  const extension = getFileExtension(file.name);
  if (!ALLOWED_SUPPORTING_FILE_EXTENSIONS.includes(extension)) {
    return 'Supporting document must be a PDF, DOC, DOCX, JPG, or PNG file.';
  }

  if (!ALLOWED_SUPPORTING_FILE_MIME_TYPES.includes(String(file.type || '').toLowerCase())) {
    return 'Supporting document must be a PDF, DOC, DOCX, JPG, or PNG file.';
  }

  if (file.size > MAX_SUPPORTING_FILE_SIZE_BYTES) {
    return 'Supporting document must be 5 MB or smaller.';
  }

  return '';
}

export function getSupportingFileAcceptValue() {
  return '.pdf,.doc,.docx,.jpg,.jpeg,.png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png';
}

function getFileExtension(fileName) {
  const segments = String(fileName || '').toLowerCase().split('.');
  return segments.length > 1 ? segments.pop() : '';
}
