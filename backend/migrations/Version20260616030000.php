<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reservation end time support for time-slot availability checks.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservations ADD end_date_time TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql("UPDATE reservations SET end_date_time = event_date_time + INTERVAL '30 minutes' WHERE end_date_time IS NULL");
        $this->addSql('ALTER TABLE reservations ALTER COLUMN end_date_time SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservations DROP COLUMN end_date_time');
    }
}
