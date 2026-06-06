<?php

namespace App\Domain\Equipment\Service;

class EquipmentAssetIdValidator
{
    private const ASSET_ID_PATTERN = '/^F\d{3}-\d{3}-\d{3}$/';

    public function isValid(string $assetId): bool
    {
        return preg_match(self::ASSET_ID_PATTERN, trim($assetId)) === 1;
    }
}
