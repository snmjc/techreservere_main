<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260607010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Extend equipment records for admin edit fields and optional JPG photo storage.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE equipment ADD COLUMN IF NOT EXISTS equipment_brand VARCHAR(100) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE equipment ADD COLUMN IF NOT EXISTS barcode VARCHAR(120) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE equipment ADD COLUMN IF NOT EXISTS serial_number VARCHAR(120) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE equipment ADD COLUMN IF NOT EXISTS photo_data TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE equipment DROP COLUMN IF EXISTS equipment_brand');
        $this->addSql('ALTER TABLE equipment DROP COLUMN IF EXISTS barcode');
        $this->addSql('ALTER TABLE equipment DROP COLUMN IF EXISTS serial_number');
        $this->addSql('ALTER TABLE equipment DROP COLUMN IF EXISTS photo_data');
    }
}
