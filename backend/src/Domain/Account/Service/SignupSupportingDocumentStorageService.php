<?php

namespace App\Domain\Account\Service;

use App\Shared\Utils\AppClock;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SignupSupportingDocumentStorageService
{
    public function store(UploadedFile $uploadedFile, string $expectedBaseName): array
    {
        $uploadDate = AppClock::now();
        $extension = strtolower($uploadedFile->getClientOriginalExtension());
        $storageDirectory = $this->ensureStorageDirectory($uploadDate);
        $storageFileName = sprintf(
            '%s_%s.%s',
            $expectedBaseName,
            bin2hex(random_bytes(6)),
            $extension
        );

        $uploadedFile->move($storageDirectory, $storageFileName);

        return [
            'fileName' => (string)$uploadedFile->getClientOriginalName(),
            'filePath' => $this->buildRelativePath($uploadDate, $storageFileName),
            'fileType' => (string)($uploadedFile->getClientMimeType() ?: $uploadedFile->getMimeType() ?: ''),
            'fileSize' => (int)$uploadedFile->getSize(),
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
}
