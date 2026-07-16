<?php

namespace App\Domain\Equipment\Service;

use ZipArchive;

class EquipmentExcelExportService
{
    private const WORKSHEET_TITLE = 'Equipment Inventory';

    /**
     * @param array<int, array<string, mixed>> $equipmentRows
     * @param array<string, mixed> $filters
     * @return array{fileName: string, content: string, exportedRowCount: int}
     */
    public function generateWorkbook(array $equipmentRows, array $filters, string $generatedBy, \DateTimeImmutable $exportedAt): array
    {
        $normalizedRows = $this->buildExportRows($equipmentRows);
        $worksheetRows = $this->buildWorksheetRows($normalizedRows, $filters, $generatedBy, $exportedAt);
        $columnCount = count($worksheetRows['headers']);
        $headerRowNumber = $worksheetRows['headerRowNumber'];
        $lastRowNumber = $headerRowNumber + count($normalizedRows);

        $workbookXml = $this->buildWorkbookXml();
        $worksheetXml = $this->buildWorksheetXml(
            $worksheetRows['rows'],
            $worksheetRows['headers'],
            $worksheetRows['columnWidths'],
            $headerRowNumber,
            $lastRowNumber,
            $columnCount
        );

        $archiveEntries = [
            '[Content_Types].xml' => $this->buildContentTypesXml(),
            '_rels/.rels' => $this->buildRootRelationshipsXml(),
            'docProps/app.xml' => $this->buildAppPropertiesXml(),
            'docProps/core.xml' => $this->buildCorePropertiesXml($generatedBy, $exportedAt),
            'xl/workbook.xml' => $workbookXml,
            'xl/_rels/workbook.xml.rels' => $this->buildWorkbookRelationshipsXml(),
            'xl/styles.xml' => $this->buildStylesXml(),
            'xl/worksheets/sheet1.xml' => $worksheetXml,
        ];
        $content = $this->buildArchiveContent($archiveEntries);

        if ($content === false) {
            throw new \RuntimeException('Unable to read the generated Excel export file.');
        }

        return [
            'fileName' => sprintf('equipment_inventory_%s.xlsx', $exportedAt->format('Y-m-d')),
            'content' => $content,
            'exportedRowCount' => count($normalizedRows),
        ];
    }

    /**
     * @param array<string, string> $archiveEntries
     */
    private function buildArchiveContent(array $archiveEntries): string|false
    {
        if (class_exists(ZipArchive::class)) {
            return $this->buildArchiveWithZipArchive($archiveEntries);
        }

        return $this->buildArchiveWithCommandFallback($archiveEntries);
    }

    /**
     * @param array<string, string> $archiveEntries
     */
    private function buildArchiveWithZipArchive(array $archiveEntries): string|false
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'techreserve_equipment_export_');
        if ($tempFile === false) {
            throw new \RuntimeException('Unable to create a temporary export file.');
        }

        $zipArchive = new ZipArchive();
        if ($zipArchive->open($tempFile, ZipArchive::OVERWRITE) !== true) {
            @unlink($tempFile);
            throw new \RuntimeException('Unable to open the Excel export archive.');
        }

        foreach ($archiveEntries as $path => $content) {
            $zipArchive->addFromString($path, $content);
        }

        $zipArchive->close();
        $content = file_get_contents($tempFile);
        @unlink($tempFile);

        return $content;
    }

    /**
     * @param array<string, string> $archiveEntries
     */
    private function buildArchiveWithCommandFallback(array $archiveEntries): string|false
    {
        $tempRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'techreserve_equipment_export_' . bin2hex(random_bytes(6));
        $sourceDirectory = $tempRoot . DIRECTORY_SEPARATOR . 'source';
        $archivePath = $tempRoot . DIRECTORY_SEPARATOR . 'equipment_inventory.xlsx';
        $packagedArchivePath = PHP_OS_FAMILY === 'Windows'
            ? $tempRoot . DIRECTORY_SEPARATOR . 'equipment_inventory.zip'
            : $archivePath;

        if (!@mkdir($sourceDirectory, 0777, true) && !is_dir($sourceDirectory)) {
            throw new \RuntimeException('Unable to prepare the temporary Excel export directory.');
        }

        try {
            foreach ($archiveEntries as $relativePath => $content) {
                $absolutePath = $sourceDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
                $parentDirectory = dirname($absolutePath);
                if (!is_dir($parentDirectory) && !@mkdir($parentDirectory, 0777, true) && !is_dir($parentDirectory)) {
                    throw new \RuntimeException(sprintf('Unable to prepare the archive path for %s.', $relativePath));
                }

                if (file_put_contents($absolutePath, $content) === false) {
                    throw new \RuntimeException(sprintf('Unable to write the archive entry %s.', $relativePath));
                }
            }

            $this->compressDirectory($sourceDirectory, $packagedArchivePath);
            if ($packagedArchivePath !== $archivePath && is_file($packagedArchivePath)) {
                @rename($packagedArchivePath, $archivePath);
            }
            $content = file_get_contents($archivePath);
        } finally {
            $this->removeDirectory($tempRoot);
        }

        return $content;
    }

    private function compressDirectory(string $sourceDirectory, string $archivePath): void
    {
        $command = PHP_OS_FAMILY === 'Windows'
            ? sprintf(
                'powershell -NoProfile -Command "Compress-Archive -Path %s\\* -DestinationPath %s -Force"',
                escapeshellarg($sourceDirectory),
                escapeshellarg($archivePath)
            )
            : sprintf(
                'cd %s && zip -qr %s .',
                escapeshellarg($sourceDirectory),
                escapeshellarg($archivePath)
            );

        $output = [];
        $exitCode = 1;
        @exec($command, $output, $exitCode);

        if ($exitCode !== 0 || !is_file($archivePath)) {
            throw new \RuntimeException('Unable to create the Excel export archive because no zip packager is available.');
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            @unlink($path);
        }

        @rmdir($directory);
    }

    /**
     * @param array<int, array<string, mixed>> $equipmentRows
     * @return array<int, array<string, mixed>>
     */
    private function buildExportRows(array $equipmentRows): array
    {
        $deduplicatedRows = [];
        foreach ($equipmentRows as $equipmentRow) {
            $equipmentIdentifier = (int) ($equipmentRow['equipmentIdentifier'] ?? 0);
            if ($equipmentIdentifier <= 0 || isset($deduplicatedRows[$equipmentIdentifier])) {
                continue;
            }

            $units = array_values(array_filter(
                is_array($equipmentRow['units'] ?? null) ? $equipmentRow['units'] : [],
                static fn (mixed $unit): bool => is_array($unit)
            ));

            $deduplicatedRows[$equipmentIdentifier] = [
                'Equipment Name' => (string) ($equipmentRow['equipmentName'] ?? ''),
                'Category' => (string) ($equipmentRow['equipmentCategory'] ?? ''),
                'Brand' => (string) ($equipmentRow['equipmentBrand'] ?? ''),
                'Model' => (string) ($equipmentRow['equipmentModel'] ?? ''),
                'Barcode / Asset Tag' => $this->joinUniqueValues([
                    (string) ($equipmentRow['barcode'] ?? ''),
                    (string) ($equipmentRow['assetId'] ?? ''),
                ], $units, ['barcode', 'assetTag']),
                'Serial Number' => $this->joinUniqueValues([
                    (string) ($equipmentRow['serialNumber'] ?? ''),
                ], $units, ['serialNumber']),
                'Relevant Specifications' => $this->buildSpecificationsSummary(
                    is_array($equipmentRow['specifications'] ?? null) ? $equipmentRow['specifications'] : [],
                    $units
                ),
                'Total Quantity' => (int) ($equipmentRow['totalQuantity'] ?? 0),
                'Available Quantity' => (int) ($equipmentRow['availableQuantity'] ?? 0),
                'Reserved Quantity' => (int) ($equipmentRow['reservedQuantity'] ?? 0),
                'Borrowed Quantity' => (int) ($equipmentRow['borrowedQuantity'] ?? 0),
                'Under-maintenance Quantity' => (int) ($equipmentRow['underMaintenanceQuantity'] ?? 0),
                'Unavailable Quantity' => (int) ($equipmentRow['unavailableQuantity'] ?? 0),
                'Condition' => $this->joinUniqueValues([], $units, ['conditionStatus']),
                'Status' => (string) ($equipmentRow['operationalStatus'] ?? $equipmentRow['equipmentState'] ?? ''),
                'Storage Location' => $this->joinUniqueValues([], $units, ['storageLocation']),
                'Date Acquired' => $this->buildAcquisitionDateSummary($units),
                'Warranty Information' => $this->joinUniqueValues([], $units, ['warrantyDetails']),
                'Last Maintenance Date' => $this->buildLastMaintenanceDate($units),
                'Maintenance Status' => $this->joinUniqueValues([], $units, ['maintenanceState']),
                'Remarks' => $this->joinTextBlocks([
                    (string) ($equipmentRow['remarks'] ?? ''),
                ], $units, ['remarks']),
            ];
        }

        return array_values($deduplicatedRows);
    }

    /**
     * @param array<int, array<string, mixed>> $normalizedRows
     * @param array<string, mixed> $filters
     * @return array{rows: array<int, array<int, array<string, mixed>>>, headers: array<int, string>, headerRowNumber: int, columnWidths: array<int, int>}
     */
    private function buildWorksheetRows(array $normalizedRows, array $filters, string $generatedBy, \DateTimeImmutable $exportedAt): array
    {
        $headers = [
            'Equipment Name',
            'Category',
            'Brand',
            'Model',
            'Barcode / Asset Tag',
            'Serial Number',
            'Relevant Specifications',
            'Total Quantity',
            'Available Quantity',
            'Reserved Quantity',
            'Borrowed Quantity',
            'Under-maintenance Quantity',
            'Unavailable Quantity',
            'Condition',
            'Status',
            'Storage Location',
            'Date Acquired',
            'Warranty Information',
            'Last Maintenance Date',
            'Maintenance Status',
            'Remarks',
        ];

        $activeFiltersLabel = $this->buildFilterSummary($filters);

        $rows = [
            [
                ['value' => self::WORKSHEET_TITLE, 'type' => 'string', 'style' => 1],
            ],
            [
                ['value' => 'Generated By', 'type' => 'string', 'style' => 2],
                ['value' => $generatedBy, 'type' => 'string', 'style' => 3],
            ],
            [
                ['value' => 'Export Date', 'type' => 'string', 'style' => 2],
                ['value' => $exportedAt->format('Y-m-d H:i:s'), 'type' => 'string', 'style' => 3],
            ],
            [
                ['value' => 'Active Filters', 'type' => 'string', 'style' => 2],
                ['value' => $activeFiltersLabel, 'type' => 'string', 'style' => 3],
            ],
            [],
        ];

        $headerRowNumber = count($rows) + 1;
        $rows[] = array_map(
            static fn (string $header): array => ['value' => $header, 'type' => 'string', 'style' => 4],
            $headers
        );

        foreach ($normalizedRows as $normalizedRow) {
            $row = [];
            foreach ($headers as $header) {
                $value = $normalizedRow[$header] ?? '';
                $row[] = [
                    'value' => $value,
                    'type' => is_int($value) || is_float($value) ? 'number' : 'string',
                    'style' => in_array($header, ['Relevant Specifications', 'Warranty Information', 'Remarks'], true) ? 5 : 0,
                ];
            }
            $rows[] = $row;
        }

        return [
            'rows' => $rows,
            'headers' => $headers,
            'headerRowNumber' => $headerRowNumber,
            'columnWidths' => $this->calculateColumnWidths($headers, $normalizedRows),
        ];
    }

    /**
     * @param array<int, array<int, array<string, mixed>>> $rows
     * @param array<int, string> $headers
     * @param array<int, int> $columnWidths
     */
    private function buildWorksheetXml(array $rows, array $headers, array $columnWidths, int $headerRowNumber, int $lastRowNumber, int $columnCount): string
    {
        $sheetDataXml = '';
        foreach ($rows as $rowIndex => $cells) {
            $rowNumber = $rowIndex + 1;
            $sheetDataXml .= sprintf('<row r="%d">', $rowNumber);
            foreach ($cells as $columnIndex => $cell) {
                $cellReference = $this->columnIndexToLetters($columnIndex + 1) . $rowNumber;
                $sheetDataXml .= $this->buildCellXml(
                    $cellReference,
                    $cell['value'] ?? '',
                    $cell['type'] ?? 'string',
                    (int) ($cell['style'] ?? 0)
                );
            }
            $sheetDataXml .= '</row>';
        }

        $columnsXml = '';
        foreach ($columnWidths as $columnIndex => $width) {
            $columnsXml .= sprintf(
                '<col min="%1$d" max="%1$d" width="%2$d" customWidth="1"/>',
                $columnIndex + 1,
                $width
            );
        }

        $autoFilterRange = sprintf('A%d:%s%d', $headerRowNumber, $this->columnIndexToLetters($columnCount), $lastRowNumber);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . sprintf('<dimension ref="A1:%s%d"/>', $this->columnIndexToLetters($columnCount), $lastRowNumber)
            . '<sheetViews><sheetView workbookViewId="0">'
            . sprintf('<pane ySplit="%d" topLeftCell="A%d" activePane="bottomLeft" state="frozen"/>', $headerRowNumber - 1, $headerRowNumber)
            . '</sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . '<cols>' . $columnsXml . '</cols>'
            . '<sheetData>' . $sheetDataXml . '</sheetData>'
            . sprintf('<autoFilter ref="%s"/>', $autoFilterRange)
            . sprintf('<mergeCells count="1"><mergeCell ref="A1:%s1"/></mergeCells>', $this->columnIndexToLetters($columnCount))
            . '</worksheet>';
    }

    private function buildCellXml(string $cellReference, mixed $value, string $type, int $styleIndex): string
    {
        if ($type === 'number' && is_numeric($value)) {
            return sprintf(
                '<c r="%s" s="%d"><v>%s</v></c>',
                $cellReference,
                $styleIndex,
                $this->escapeNumericValue((string) $value)
            );
        }

        $stringValue = $this->normalizeCellString($value);
        return sprintf(
            '<c r="%s" s="%d" t="inlineStr"><is><t xml:space="preserve">%s</t></is></c>',
            $cellReference,
            $styleIndex,
            $this->escapeXml($stringValue)
        );
    }

    private function buildContentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '</Types>';
    }

    private function buildRootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private function buildWorkbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $this->escapeXml(self::WORKSHEET_TITLE) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function buildWorkbookRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function buildStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="3">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="14"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE8F2EC"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"/><right style="thin"/><top style="thin"/><bottom style="thin"/><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="6">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"><alignment horizontal="center"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"><alignment wrapText="1" vertical="top"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"><alignment wrapText="1" vertical="top"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function buildAppPropertiesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>TechReserve</Application>'
            . '</Properties>';
    }

    private function buildCorePropertiesXml(string $generatedBy, \DateTimeImmutable $exportedAt): string
    {
        $isoTimestamp = $exportedAt->format('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:creator>' . $this->escapeXml($generatedBy) . '</dc:creator>'
            . '<cp:lastModifiedBy>' . $this->escapeXml($generatedBy) . '</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $isoTimestamp . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $isoTimestamp . '</dcterms:modified>'
            . '<dc:title>' . $this->escapeXml(self::WORKSHEET_TITLE) . '</dc:title>'
            . '</cp:coreProperties>';
    }

    /**
     * @param array<int, array<string, mixed>> $normalizedRows
     * @return array<int, int>
     */
    private function calculateColumnWidths(array $headers, array $normalizedRows): array
    {
        $widths = [];
        foreach ($headers as $columnIndex => $header) {
            $maxLength = mb_strlen($header);
            foreach ($normalizedRows as $normalizedRow) {
                $cellValue = $this->normalizeCellString($normalizedRow[$header] ?? '');
                foreach (preg_split('/\R/u', $cellValue) ?: [''] as $line) {
                    $maxLength = max($maxLength, mb_strlen($line));
                }
            }
            $widths[$columnIndex] = max(12, min($maxLength + 3, 42));
        }

        return $widths;
    }

    private function joinUniqueValues(array $seedValues, array $units, array $keys): string
    {
        $values = [];
        foreach ($seedValues as $seedValue) {
            $normalizedValue = trim((string) $seedValue);
            if ($normalizedValue !== '') {
                $values[$normalizedValue] = true;
            }
        }

        foreach ($units as $unit) {
            foreach ($keys as $key) {
                $normalizedValue = trim((string) ($unit[$key] ?? ''));
                if ($normalizedValue !== '') {
                    $values[$normalizedValue] = true;
                }
            }
        }

        return implode("\n", array_keys($values));
    }

    private function joinTextBlocks(array $seedValues, array $units, array $keys): string
    {
        $textBlocks = [];
        foreach ($seedValues as $seedValue) {
            $normalized = trim((string) $seedValue);
            if ($normalized !== '') {
                $textBlocks[$normalized] = true;
            }
        }

        foreach ($units as $unit) {
            foreach ($keys as $key) {
                $normalized = trim((string) ($unit[$key] ?? ''));
                if ($normalized !== '') {
                    $textBlocks[$normalized] = true;
                }
            }
        }

        return implode("\n\n", array_keys($textBlocks));
    }

    private function buildSpecificationsSummary(array $parentSpecifications, array $units): string
    {
        $segments = [];

        foreach ($parentSpecifications as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $normalizedKey = trim(is_string($key) ? $key : '');
            $normalizedValue = trim((string) $value);
            if ($normalizedKey === '' && $normalizedValue === '') {
                continue;
            }

            $segments[$normalizedKey !== '' ? sprintf('%s: %s', $normalizedKey, $normalizedValue) : $normalizedValue] = true;
        }

        foreach ($units as $unit) {
            foreach ((array) ($unit['specifications'] ?? []) as $key => $value) {
                if (is_array($value)) {
                    continue;
                }

                $normalizedKey = trim(is_string($key) ? $key : '');
                $normalizedValue = trim((string) $value);
                if ($normalizedKey === '' && $normalizedValue === '') {
                    continue;
                }

                $segments[$normalizedKey !== '' ? sprintf('%s: %s', $normalizedKey, $normalizedValue) : $normalizedValue] = true;
            }
        }

        return implode("\n", array_keys($segments));
    }

    private function buildAcquisitionDateSummary(array $units): string
    {
        $dates = [];
        foreach ($units as $unit) {
            $date = trim((string) ($unit['dateAcquired'] ?? ''));
            if ($date !== '') {
                $dates[$date] = true;
            }
        }

        $uniqueDates = array_keys($dates);
        sort($uniqueDates);

        if ($uniqueDates === []) {
            return '';
        }

        if (count($uniqueDates) === 1) {
            return $uniqueDates[0];
        }

        return sprintf('%s to %s', $uniqueDates[0], $uniqueDates[count($uniqueDates) - 1]);
    }

    private function buildLastMaintenanceDate(array $units): string
    {
        $latestTimestamp = null;
        foreach ($units as $unit) {
            $maintenanceState = strtolower(trim((string) ($unit['maintenanceState'] ?? '')));
            $availabilityStatus = strtolower(trim((string) ($unit['availabilityStatus'] ?? '')));
            $isMaintenanceUnit = str_contains($maintenanceState, 'maintenance') || str_contains($availabilityStatus, 'maintenance');
            if (!$isMaintenanceUnit) {
                continue;
            }

            $updatedTimestamp = trim((string) ($unit['updatedTimestamp'] ?? ''));
            if ($updatedTimestamp === '') {
                continue;
            }

            $candidate = strtotime($updatedTimestamp);
            if ($candidate === false) {
                continue;
            }

            $latestTimestamp = $latestTimestamp === null ? $candidate : max($latestTimestamp, $candidate);
        }

        return $latestTimestamp === null ? '' : gmdate('Y-m-d', $latestTimestamp);
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function buildFilterSummary(array $filters): string
    {
        $labels = [];
        $map = [
            'search' => 'Search',
            'category' => 'Category',
            'status' => 'Status',
            'condition' => 'Condition',
            'storageLocation' => 'Storage Location',
            'acquiredStartDate' => 'Acquired From',
            'acquiredEndDate' => 'Acquired To',
            'datePreset' => 'Date Preset',
        ];

        foreach ($map as $filterKey => $label) {
            $value = trim((string) ($filters[$filterKey] ?? ''));
            if ($value !== '') {
                $labels[] = sprintf('%s: %s', $label, $value);
            }
        }

        return $labels === [] ? 'None' : implode(' | ', $labels);
    }

    private function columnIndexToLetters(int $columnIndex): string
    {
        $letters = '';
        while ($columnIndex > 0) {
            $remainder = ($columnIndex - 1) % 26;
            $letters = chr(65 + $remainder) . $letters;
            $columnIndex = intdiv($columnIndex - 1, 26);
        }

        return $letters;
    }

    private function normalizeCellString(mixed $value): string
    {
        return preg_replace("/\r\n?/", "\n", trim((string) $value)) ?? '';
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function escapeNumericValue(string $value): string
    {
        return preg_replace('/[^0-9.\-]/', '', $value) ?? '0';
    }
}
