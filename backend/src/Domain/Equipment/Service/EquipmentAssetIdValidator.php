<?php

namespace App\Domain\Equipment\Service;

class EquipmentAssetIdValidator
{
    private const GENERATED_ASSET_ID_PATTERN = '/^F\d{3}-\d{3}-\d{3}$/';
    private const LEGACY_ASSET_ID_PATTERN = '/^TR-[A-Z]{3}-\d{4}$/';

    public function isValid(string $assetId): bool
    {
        $normalizedAssetId = trim($assetId);
        return preg_match(self::GENERATED_ASSET_ID_PATTERN, $normalizedAssetId) === 1
            || preg_match(self::LEGACY_ASSET_ID_PATTERN, $normalizedAssetId) === 1;
    }
}
