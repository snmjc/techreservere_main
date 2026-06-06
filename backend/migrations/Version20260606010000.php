<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260606010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align equipment records with admin add-equipment requirements';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE equipment RENAME COLUMN category_name TO equipment_category');
        $this->addSql('ALTER TABLE equipment RENAME COLUMN schedule_description TO description');
        $this->addSql('ALTER TABLE equipment RENAME COLUMN created_timestamp TO created_at');
        $this->addSql('ALTER TABLE equipment RENAME COLUMN updated_timestamp TO updated_at');
        $this->addSql('ALTER TABLE equipment ADD equipment_brand VARCHAR(100) DEFAULT \'Unknown\' NOT NULL');
        $this->addSql('ALTER TABLE equipment ADD image_url TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE equipment ADD barcode VARCHAR(120) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE equipment ADD asset_id VARCHAR(13) DEFAULT \'\' NOT NULL');
        $this->addSql('UPDATE equipment SET barcode = CONCAT(\'LEGACY-BARCODE-\', equipment_identifier) WHERE barcode = \'\'');
        $this->addSql('UPDATE equipment SET asset_id = CONCAT(\'F\', LPAD(CAST(equipment_identifier AS VARCHAR), 3, \'0\'), \'-000-000\') WHERE asset_id = \'\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EQUIPMENT_BARCODE ON equipment (barcode)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EQUIPMENT_ASSET_ID ON equipment (asset_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_EQUIPMENT_BARCODE');
        $this->addSql('DROP INDEX UNIQ_EQUIPMENT_ASSET_ID');
        $this->addSql('ALTER TABLE equipment DROP equipment_brand');
        $this->addSql('ALTER TABLE equipment DROP image_url');
        $this->addSql('ALTER TABLE equipment DROP barcode');
        $this->addSql('ALTER TABLE equipment DROP asset_id');
        $this->addSql('ALTER TABLE equipment RENAME COLUMN equipment_category TO category_name');
        $this->addSql('ALTER TABLE equipment RENAME COLUMN description TO schedule_description');
        $this->addSql('ALTER TABLE equipment RENAME COLUMN created_at TO created_timestamp');
        $this->addSql('ALTER TABLE equipment RENAME COLUMN updated_at TO updated_timestamp');
    }
}
