<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260510165424 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE venues ADD floor_level VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE venues ADD description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE venues ADD image_url VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE venues DROP COLUMN image_url');
        $this->addSql('ALTER TABLE venues DROP COLUMN description');
        $this->addSql('ALTER TABLE venues DROP COLUMN floor_level');
    }
}
