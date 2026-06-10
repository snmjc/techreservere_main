<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260608010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Expand venue records with availability date, operational status, and text photo storage';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE venues ADD COLUMN IF NOT EXISTS availability_date DATE DEFAULT CURRENT_DATE");
        $this->addSql("ALTER TABLE venues ADD COLUMN IF NOT EXISTS operational_status VARCHAR(50) DEFAULT 'Active' NOT NULL");
        $this->addSql("ALTER TABLE venues ALTER COLUMN image_url TYPE TEXT");
        $this->addSql("UPDATE venues SET availability_date = CURRENT_DATE WHERE availability_date IS NULL");
        $this->addSql("UPDATE venues SET operational_status = 'Active' WHERE operational_status IS NULL OR operational_status = ''");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE venues DROP COLUMN IF EXISTS availability_date");
        $this->addSql("ALTER TABLE venues DROP COLUMN IF EXISTS operational_status");
        $this->addSql("ALTER TABLE venues ALTER COLUMN image_url TYPE VARCHAR(500)");
    }
}
