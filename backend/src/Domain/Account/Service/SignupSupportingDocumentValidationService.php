<?php

namespace App\Domain\Account\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class SignupSupportingDocumentValidationService
{
    private const MAX_FILE_SIZE_BYTES = 5242880;
    private const ALLOWED_EXTENSIONS = ['pdf', 'jpg'];
    private const ALLOWED_MIME_TYPES = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg', 'image/pjpeg'],
    ];

    public function validateRequiredUpload(array $payload, ?UploadedFile $uploadedFile): ?string
    {
        if ($this->isUploadRequired($payload) && $uploadedFile === null) {
            return 'Proof of enrollment or School ID is required.';
        }

        if ($uploadedFile === null) {
            return null;
        }

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            return 'The supporting document upload could not be completed. Please try again.';
        }

        $originalName = (string)$uploadedFile->getClientOriginalName();
        if ($originalName === '') {
            return 'Supporting document file name is required.';
        }

        $extension = strtolower($uploadedFile->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return 'Supporting document must be a PDF or JPG file.';
        }

        $mimeType = strtolower((string)($uploadedFile->getClientMimeType() ?: $uploadedFile->getMimeType() ?: ''));
        if ($mimeType === '' || !in_array($mimeType, self::ALLOWED_MIME_TYPES[$extension], true)) {
            return 'Supporting document must be a PDF or JPG file.';
        }

        if ($uploadedFile->getSize() > self::MAX_FILE_SIZE_BYTES) {
            return 'Supporting document must be 5 MB or smaller.';
        }

        $expectedBaseName = $this->buildExpectedBaseName(
            (string)($payload['idNumber'] ?? ''),
            (string)($payload['lastName'] ?? ''),
            (string)($payload['firstName'] ?? '')
        );
        $expectedFileName = $expectedBaseName . '.' . $extension;

        if (strtolower($originalName) !== strtolower($expectedFileName)) {
            return sprintf(
                'Supporting document file name must follow %s.',
                $expectedFileName
            );
        }

        return null;
    }

    public function buildExpectedBaseName(string $idNumber, string $lastName, string $firstName): string
    {
        return implode('_', [
            $this->normalizeToken($idNumber),
            $this->normalizeToken($lastName),
            $this->normalizeToken($firstName),
            'PROOF',
        ]);
    }

    public function buildVerificationStatus(): string
    {
        return 'pending';
    }

    private function isUploadRequired(array $payload): bool
    {
        return strtolower(trim((string)($payload['role'] ?? 'student'))) === 'student';
    }

    private function normalizeToken(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9]+/', '', trim($value)) ?? '';
    }
}
