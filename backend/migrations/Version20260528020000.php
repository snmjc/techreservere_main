<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260528020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add signup proof document storage to accounts.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accounts ADD COLUMN IF NOT EXISTS signup_supporting_document_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE accounts ADD COLUMN IF NOT EXISTS signup_supporting_document_mime_type VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE accounts ADD COLUMN IF NOT EXISTS signup_supporting_document_data TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS signup_supporting_document_data');
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS signup_supporting_document_mime_type');
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS signup_supporting_document_name');
    }
}
