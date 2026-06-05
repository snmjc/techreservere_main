const MAX_SUPPORTING_FILE_SIZE_BYTES = 5 * 1024 * 1024;
const ALLOWED_SUPPORTING_FILE_EXTENSIONS = ['pdf', 'jpg'];
const ALLOWED_SUPPORTING_FILE_MIME_TYPES = ['application/pdf', 'image/jpeg'];

export function validateSignupSupportingFile(file, formData) {
  if (!file) return '';

  const extension = getFileExtension(file.name);
  if (!ALLOWED_SUPPORTING_FILE_EXTENSIONS.includes(extension)) {
    return 'Supporting document must be a PDF or JPG file.';
  }

  if (!ALLOWED_SUPPORTING_FILE_MIME_TYPES.includes(String(file.type || '').toLowerCase())) {
    return 'Supporting document must be a PDF or JPG file.';
  }

  if (file.size > MAX_SUPPORTING_FILE_SIZE_BYTES) {
    return 'Supporting document must be 5 MB or smaller.';
  }

  const expectedFileName = buildExpectedSupportingFileName(formData, extension);
  if (file.name.toLowerCase() !== expectedFileName.toLowerCase()) {
    return `Supporting document file name must follow ${expectedFileName}.`;
  }

  return '';
}

export function buildExpectedSupportingFileName(formData, extension = 'pdf') {
  return `${buildExpectedSupportingFileBaseName(formData)}.${extension}`;
}

export function buildExpectedSupportingFileBaseName(formData) {
  return [
    normalizeSupportingFileToken(formData.idNumber),
    normalizeSupportingFileToken(formData.lastName),
    normalizeSupportingFileToken(formData.firstName),
    'PROOF',
  ].join('_');
}

export function getSupportingFileAcceptValue() {
  return '.pdf,.jpg,application/pdf,image/jpeg';
}

function normalizeSupportingFileToken(value) {
  return String(value || '').trim().replace(/[^A-Za-z0-9]+/g, '');
}

function getFileExtension(fileName) {
  const segments = String(fileName || '').toLowerCase().split('.');
  return segments.length > 1 ? segments.pop() : '';
}
