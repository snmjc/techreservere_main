<?php

namespace App\Domain\Account\Service;

use App\Shared\Utils\AppClock;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SignupSupportingDocumentStorageService
{
    public function store(UploadedFile $uploadedFile, ?string $storageBaseName = null): array
    {
        if (!$uploadedFile->isValid()) {
            throw new \RuntimeException('The supporting document upload is invalid.');
        }

        $temporaryPath = $uploadedFile->getPathname();
        if ($temporaryPath === '' || !is_file($temporaryPath)) {
            throw new \RuntimeException('The uploaded supporting document is no longer available.');
        }

        $uploadDate = AppClock::now();
        $extension = strtolower($uploadedFile->getClientOriginalExtension());
        $originalFileName = (string) $uploadedFile->getClientOriginalName();
        $fileType = (string) ($uploadedFile->getClientMimeType() ?: $uploadedFile->getMimeType() ?: '');
        $fileSize = (int) $uploadedFile->getSize();
        $storageDirectory = $this->ensureStorageDirectory($uploadDate);
        $storageFileName = sprintf(
            '%s_%s.%s',
            $this->buildStorageBaseName($uploadedFile, $storageBaseName),
            bin2hex(random_bytes(6)),
            $extension
        );

        $uploadedFile->move($storageDirectory, $storageFileName);

        return [
            'fileName' => $originalFileName,
            'filePath' => $this->buildRelativePath($uploadDate, $storageFileName),
            'fileType' => $fileType,
            'fileSize' => $fileSize,
            'uploadDate' => $uploadDate->format('Y-m-d H:i:s'),
            'verificationStatus' => 'pending',
        ];
    }

    public function resolveAbsolutePath(string $relativePath): string
    {
        $normalizedRelativePath = ltrim(str_replace(['\\', '..'], ['/', ''], $relativePath), '/');
        return rtrim($this->storageRoot(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalizedRelativePath);
    }

    public function fileExists(?string $relativePath): bool
    {
        if ($relativePath === null || trim($relativePath) === '') {
            return false;
        }

        return is_file($this->resolveAbsolutePath($relativePath));
    }

    public function delete(?string $relativePath): void
    {
        if (!$this->fileExists($relativePath)) {
            return;
        }

        @unlink($this->resolveAbsolutePath((string)$relativePath));
    }

    private function ensureStorageDirectory(\DateTimeImmutable $uploadDate): string
    {
        $directory = rtrim($this->storageRoot(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . $uploadDate->format('Y')
            . DIRECTORY_SEPARATOR . $uploadDate->format('m');

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create supporting document storage directory.');
        }

        return $directory;
    }

    private function buildRelativePath(\DateTimeImmutable $uploadDate, string $storageFileName): string
    {
        return $uploadDate->format('Y') . '/' . $uploadDate->format('m') . '/' . $storageFileName;
    }

    private function storageRoot(): string
    {
        $configuredPath = trim((string)($_ENV['SIGNUP_SUPPORTING_DOCUMENT_STORAGE_PATH'] ?? ''));
        if ($configuredPath !== '') {
            return $configuredPath;
        }

        return dirname(__DIR__, 4) . '/var/storage/signup-supporting-documents';
    }

    private function buildStorageBaseName(UploadedFile $uploadedFile, ?string $storageBaseName): string
    {
        $candidate = trim((string) $storageBaseName);
        if ($candidate === '') {
            $candidate = pathinfo((string) $uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        }

        $normalized = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $candidate));
        $normalized = trim($normalized, '-');

        return $normalized !== '' ? $normalized : 'supporting-document';
    }
}
