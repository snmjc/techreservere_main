<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260619091500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add workflow preparation start and end timestamps to task assignments.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tasks ADD COLUMN IF NOT EXISTS preparation_start_timestamp TIMESTAMP DEFAULT NULL');
        $this->addSql('ALTER TABLE tasks ADD COLUMN IF NOT EXISTS preparation_end_timestamp TIMESTAMP DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tasks DROP COLUMN IF EXISTS preparation_end_timestamp');
        $this->addSql('ALTER TABLE tasks DROP COLUMN IF EXISTS preparation_start_timestamp');
    }
}
