<?php

namespace App\Domain\Account\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class SignupSupportingDocumentValidationService
{
    private const MAX_FILE_SIZE_BYTES = 5242880;
    private const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    private const ALLOWED_MIME_TYPES = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'jpg' => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'png' => ['image/png'],
    ];

    public function validateRequiredUpload(array $payload, ?UploadedFile $uploadedFile): ?string
    {
        if ($this->isUploadRequired($payload) && $uploadedFile === null) {
            return 'Proof of enrollment or School ID is required.';
        }

        if ($uploadedFile === null) {
            return null;
        }

        if (!$uploadedFile->isValid() || $uploadedFile->getError() !== UPLOAD_ERR_OK) {
            return 'The supporting document upload could not be completed. Please try again.';
        }

        $temporaryPath = $uploadedFile->getPathname();
        if ($temporaryPath === '' || !is_file($temporaryPath)) {
            return 'The supporting document upload is missing. Please upload the file again.';
        }

        $originalName = (string)$uploadedFile->getClientOriginalName();
        if ($originalName === '') {
            return 'Supporting document file name is required.';
        }

        $extension = strtolower($uploadedFile->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return 'Supporting document must be a PDF, DOC, DOCX, JPG, or PNG file.';
        }

        $mimeType = strtolower((string)($uploadedFile->getClientMimeType() ?: $uploadedFile->getMimeType() ?: ''));
        if ($mimeType === '' || !in_array($mimeType, self::ALLOWED_MIME_TYPES[$extension], true)) {
            return 'Supporting document must be a PDF, DOC, DOCX, JPG, or PNG file.';
        }

        if ($uploadedFile->getSize() > self::MAX_FILE_SIZE_BYTES) {
            return 'Supporting document must be 5 MB or smaller.';
        }

        return null;
    }

    public function buildVerificationStatus(): string
    {
        return 'pending';
    }

    private function isUploadRequired(array $payload): bool
    {
        return strtolower(trim((string)($payload['role'] ?? 'student'))) === 'student';
    }

}
