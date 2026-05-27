<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260527010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add profile photo storage for account settings.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accounts ADD COLUMN IF NOT EXISTS profile_photo_data TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS profile_photo_data');
    }
}
