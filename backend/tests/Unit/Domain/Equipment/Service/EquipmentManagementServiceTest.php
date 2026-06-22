<?php

namespace App\Tests\Unit\Domain\Equipment\Service;

use App\Domain\Equipment\DTO\EquipmentCreateRequestDTO;
use App\Domain\Equipment\Entity\EquipmentEntity;
use App\Domain\Equipment\Repository\EquipmentRepository;
use App\Domain\Equipment\Service\EquipmentAssetIdValidator;
use App\Domain\Equipment\Service\EquipmentManagementService;
use App\Domain\Reservation\Repository\ReservationRepository;
use App\Shared\Exceptions\DomainNotFoundException;
use App\Shared\Exceptions\DomainValidationException;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class EquipmentManagementServiceTest extends TestCase
{
    private EquipmentRepository|MockObject $equipmentRepository;
    private ReservationRepository|MockObject $reservationRepository;
    private Connection|MockObject $connection;
    private EquipmentManagementService $service;

    protected function setUp(): void
    {
        $this->equipmentRepository = $this->createMock(EquipmentRepository::class);
        $this->reservationRepository = $this->createMock(ReservationRepository::class);
        $this->connection = $this->createMock(Connection::class);
        $this->service = new EquipmentManagementService(
            $this->equipmentRepository,
            new EquipmentAssetIdValidator(),
            $this->reservationRepository,
            $this->connection
        );

        $schemaReadyProperty = new \ReflectionProperty($this->service, 'equipmentSchemaEnsured');
        $schemaReadyProperty->setAccessible(true);
        $schemaReadyProperty->setValue($this->service, true);
    }

    public function testCreateEquipmentPersistsEquipmentAndReturnsStoredFields(): void
    {
        $capturedEntity = null;

        $this->equipmentRepository
            ->expects($this->once())
            ->method('findOneByBarcode')
            ->with('BARCODE-001')
            ->willReturn(null);
        $this->equipmentRepository
            ->expects($this->once())
            ->method('findOneByAssetId')
            ->with('F123-456-789')
            ->willReturn(null);
        $this->equipmentRepository
            ->expects($this->once())
            ->method('persistEquipment')
            ->willReturnCallback(function (EquipmentEntity $entity) use (&$capturedEntity): void {
                $capturedEntity = $entity;
            });

        $response = $this->service->createEquipment($this->validRequest());

        $this->assertInstanceOf(EquipmentEntity::class, $capturedEntity);
        $this->assertSame('Projector', $capturedEntity->getEquipmentName());
        $this->assertSame('Presentation', $capturedEntity->getEquipmentCategory());
        $this->assertSame('Epson', $capturedEntity->getEquipmentBrand());
        $this->assertSame(5, $capturedEntity->getAvailableQuantity());
        $this->assertSame('Available', $capturedEntity->getOperationalStatus());
        $this->assertSame('BARCODE-001', $capturedEntity->getBarcode());
        $this->assertSame('F123-456-789', $capturedEntity->getAssetId());
        $this->assertSame('Projector', $response->equipmentName);
        $this->assertSame('F123-456-789', $response->assetId);
    }

    public function testCreateEquipmentGeneratesAssetIdAndBarcodeWhenMissing(): void
    {
        $capturedEntity = null;

        $this->equipmentRepository
            ->expects($this->once())
            ->method('findAllEquipment')
            ->willReturn([]);
        $this->equipmentRepository
            ->expects($this->once())
            ->method('findOneByBarcode')
            ->with('30001')
            ->willReturn(null);
        $this->equipmentRepository
            ->expects($this->once())
            ->method('findOneByAssetId')
            ->with('F300-000-001')
            ->willReturn(null);
        $this->equipmentRepository
            ->expects($this->once())
            ->method('persistEquipment')
            ->willReturnCallback(function (EquipmentEntity $entity) use (&$capturedEntity): void {
                $capturedEntity = $entity;
            });

        $response = $this->service->createEquipment($this->validRequest(barcode: '', assetId: ''));

        $this->assertInstanceOf(EquipmentEntity::class, $capturedEntity);
        $this->assertSame('F300-000-001', $capturedEntity->getAssetId());
        $this->assertSame('30001', $capturedEntity->getBarcode());
        $this->assertSame('F300-000-001', $response->assetId);
    }

    public function testCreateEquipmentGeneratesNextCategorySequenceWhenExistingRecordsMatch(): void
    {
        $capturedEntity = null;
        $existingEquipment = [
            $this->existingEquipment('Presentation', '30001', 'F300-000-001'),
            $this->existingEquipment('Presentation', '30002', 'F300-000-002'),
        ];

        $this->equipmentRepository
            ->expects($this->once())
            ->method('findAllEquipment')
            ->willReturn($existingEquipment);
        $this->equipmentRepository
            ->expects($this->once())
            ->method('findOneByBarcode')
            ->with('30003')
            ->willReturn(null);
        $this->equipmentRepository
            ->expects($this->once())
            ->method('findOneByAssetId')
            ->with('F300-000-003')
            ->willReturn(null);
        $this->equipmentRepository
            ->expects($this->once())
            ->method('persistEquipment')
            ->willReturnCallback(function (EquipmentEntity $entity) use (&$capturedEntity): void {
                $capturedEntity = $entity;
            });

        $this->service->createEquipment($this->validRequest(barcode: '', assetId: ''));

        $this->assertInstanceOf(EquipmentEntity::class, $capturedEntity);
        $this->assertSame('F300-000-003', $capturedEntity->getAssetId());
        $this->assertSame('30003', $capturedEntity->getBarcode());
    }

    public function testUpdateEquipmentPersistsChangesToExistingEquipment(): void
    {
        $existingEquipment = new EquipmentEntity();

        $this->equipmentRepository
            ->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($existingEquipment);
        $this->equipmentRepository
            ->expects($this->once())
            ->method('findOneByBarcode')
            ->willReturn(null);
        $this->equipmentRepository
            ->expects($this->once())
            ->method('findOneByAssetId')
            ->willReturn(null);
        $this->equipmentRepository
            ->expects($this->once())
            ->method('persistEquipment')
            ->with($existingEquipment);

        $response = $this->service->updateEquipment(10, $this->validRequest(
            equipmentName: 'Laptop',
            equipmentCategory: 'Computer',
            equipmentBrand: 'Lenovo',
            availableQuantity: 3,
            assetId: 'F222-333-444'
        ));

        $this->assertSame('Laptop', $existingEquipment->getEquipmentName());
        $this->assertSame('Computer', $existingEquipment->getEquipmentCategory());
        $this->assertSame('Lenovo', $existingEquipment->getEquipmentBrand());
        $this->assertSame(3, $existingEquipment->getAvailableQuantity());
        $this->assertSame('F222-333-444', $existingEquipment->getAssetId());
        $this->assertSame('Laptop', $response->equipmentName);
    }

    public function testUpdateEquipmentPreservesExistingIdentifiersWhenRequestIdentifiersAreBlank(): void
    {
        $existingEquipment = $this->existingEquipment('Presentation', '30012', 'F300-000-012');

        $this->equipmentRepository
            ->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($existingEquipment);
        $this->equipmentRepository
            ->expects($this->never())
            ->method('findAllEquipment');
        $this->equipmentRepository
            ->expects($this->once())
            ->method('findOneByBarcode')
            ->with('30012')
            ->willReturn(null);
        $this->equipmentRepository
            ->expects($this->once())
            ->method('findOneByAssetId')
            ->with('F300-000-012')
            ->willReturn(null);
        $this->equipmentRepository
            ->expects($this->once())
            ->method('persistEquipment')
            ->with($existingEquipment);

        $this->service->updateEquipment(10, $this->validRequest(barcode: '', assetId: ''));

        $this->assertSame('30012', $existingEquipment->getBarcode());
        $this->assertSame('F300-000-012', $existingEquipment->getAssetId());
    }

    public function testUpdateEquipmentGeneratesMissingIdentifiersWhenExistingIdentifiersAreBlank(): void
    {
        $existingEquipment = new EquipmentEntity();

        $this->equipmentRepository
            ->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($existingEquipment);
        $this->equipmentRepository
            ->expects($this->once())
            ->method('findAllEquipment')
            ->willReturn([]);
        $this->equipmentRepository
            ->expects($this->once())
            ->method('findOneByBarcode')
            ->with('30001')
            ->willReturn(null);
        $this->equipmentRepository
            ->expects($this->once())
            ->method('findOneByAssetId')
            ->with('F300-000-001')
            ->willReturn(null);
        $this->equipmentRepository
            ->expects($this->once())
            ->method('persistEquipment')
            ->with($existingEquipment);

        $this->service->updateEquipment(10, $this->validRequest(barcode: '', assetId: ''));

        $this->assertSame('30001', $existingEquipment->getBarcode());
        $this->assertSame('F300-000-001', $existingEquipment->getAssetId());
    }

    public function testDeleteEquipmentRemovesExistingEquipment(): void
    {
        $existingEquipment = new EquipmentEntity();

        $this->equipmentRepository
            ->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($existingEquipment);
        $this->equipmentRepository
            ->expects($this->once())
            ->method('removeEquipment')
            ->with($existingEquipment);

        $this->service->deleteEquipment(10);

        $this->addToAssertionCount(1);
    }

    public function testDeleteEquipmentFailsWhenRecordDoesNotExist(): void
    {
        $this->equipmentRepository
            ->expects($this->once())
            ->method('find')
            ->with(404)
            ->willReturn(null);
        $this->equipmentRepository
            ->expects($this->never())
            ->method('removeEquipment');

        $this->expectException(DomainNotFoundException::class);

        $this->service->deleteEquipment(404);
    }

    public function testCreateEquipmentRejectsDuplicateBarcode(): void
    {
        $duplicateEquipment = $this->createMock(EquipmentEntity::class);
        $duplicateEquipment
            ->method('getEquipmentIdentifier')
            ->willReturn(99);

        $this->equipmentRepository
            ->expects($this->once())
            ->method('findOneByBarcode')
            ->willReturn($duplicateEquipment);
        $this->equipmentRepository
            ->expects($this->never())
            ->method('persistEquipment');

        $this->expectException(DomainValidationException::class);
        $this->expectExceptionMessage('Barcode already exists.');

        $this->service->createEquipment($this->validRequest());
    }

    public function testCreateEquipmentRejectsDuplicateAssetId(): void
    {
        $duplicateEquipment = $this->createMock(EquipmentEntity::class);
        $duplicateEquipment
            ->method('getEquipmentIdentifier')
            ->willReturn(99);

        $this->equipmentRepository
            ->expects($this->once())
            ->method('findOneByBarcode')
            ->willReturn(null);
        $this->equipmentRepository
            ->expects($this->once())
            ->method('findOneByAssetId')
            ->willReturn($duplicateEquipment);
        $this->equipmentRepository
            ->expects($this->never())
            ->method('persistEquipment');

        $this->expectException(DomainValidationException::class);
        $this->expectExceptionMessage('Asset ID already exists.');

        $this->service->createEquipment($this->validRequest());
    }

    public function testCreateEquipmentRejectsInvalidAssetIdFormat(): void
    {
        $this->equipmentRepository
            ->expects($this->never())
            ->method('persistEquipment');

        $this->expectException(DomainValidationException::class);
        $this->expectExceptionMessage('Asset ID must follow the TechReserve generated format.');

        $this->service->createEquipment($this->validRequest(assetId: 'ABC-123'));
    }

    private function validRequest(
        string $equipmentName = 'Projector',
        string $equipmentCategory = 'Presentation',
        string $equipmentBrand = 'Epson',
        int $availableQuantity = 5,
        string $operationalStatus = 'Available',
        string $description = 'Classroom presentation projector.',
        string $barcode = 'BARCODE-001',
        string $assetId = 'F123-456-789'
    ): EquipmentCreateRequestDTO {
        return new EquipmentCreateRequestDTO(
            equipmentName: $equipmentName,
            equipmentCategory: $equipmentCategory,
            equipmentBrand: $equipmentBrand,
            availableQuantity: $availableQuantity,
            operationalStatus: $operationalStatus,
            description: $description,
            barcode: $barcode,
            assetId: $assetId
        );
    }

    private function existingEquipment(string $category, string $barcode, string $assetId): EquipmentEntity
    {
        return (new EquipmentEntity())
            ->setEquipmentCategory($category)
            ->setBarcode($barcode)
            ->setAssetId($assetId);
    }
}
