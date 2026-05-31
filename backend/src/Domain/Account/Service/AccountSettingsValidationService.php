<?php

namespace App\Domain\Account\Service;

class AccountSettingsValidationService
{
    public function normalizePersonName(string $value): string
    {
        return preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);
    }

    public function normalizeEmailForConfirmation(string $emailAddress): string
    {
        $normalizedEmailAddress = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\s]+/u', '', $emailAddress) ?? $emailAddress;
        return strtolower(trim($normalizedEmailAddress));
    }

    public function validateEditableAccountSettings(
        string $firstName,
        string $lastName,
        string $contactNumber,
        ?string $profilePhotoData,
        string $profilePhotoName = ''
    ): ?string {
        if ($firstName === '' || $lastName === '' || $contactNumber === '') {
            return 'First name, last name, and phone number are required.';
        }

        if (!$this->isValidPersonName($firstName) || !$this->isValidPersonName($lastName)) {
            return 'First name and last name must contain letters and spaces only, with at least 2 characters each.';
        }

        if (preg_match('/^9\d{9}$/', $contactNumber) !== 1) {
            return 'Phone number must be 10 digits and begin with 9.';
        }

        if ($profilePhotoData !== null && $profilePhotoData !== '') {
            if ($profilePhotoName !== '' && !str_ends_with(strtolower($profilePhotoName), '.jpg')) {
                return 'Profile photo must be a .jpg image only.';
            }

            if (!$this->isValidJpegDataUrl($profilePhotoData)) {
                return 'Profile photo must be a .jpg image only.';
            }
        }

        return null;
    }

    private function isValidPersonName(string $value): bool
    {
        return mb_strlen($value) >= 2 && preg_match('/^[A-Za-z]+(?: [A-Za-z]+)*$/', $value) === 1;
    }

    private function isValidJpegDataUrl(string $profilePhotoData): bool
    {
        if (preg_match('/^data:image\/jpeg;base64,([A-Za-z0-9+\/=\r\n]+)$/', $profilePhotoData, $matches) !== 1) {
            return false;
        }

        $base64Data = preg_replace('/\s+/', '', $matches[1]) ?? $matches[1];
        $binaryData = base64_decode($base64Data, true);

        if ($binaryData === false || strlen($binaryData) > 2 * 1024 * 1024) {
            return false;
        }

        return str_starts_with($binaryData, "\xFF\xD8\xFF");
    }
}
