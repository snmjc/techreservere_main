<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260627070500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing borrower remarks column to reservations.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservations ADD COLUMN IF NOT EXISTS borrower_remarks TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservations DROP COLUMN IF EXISTS borrower_remarks');
    }
}
