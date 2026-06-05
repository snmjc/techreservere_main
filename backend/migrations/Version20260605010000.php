<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260605010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store signup supporting document metadata outside PostgreSQL BLOB/text payloads';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accounts ADD COLUMN IF NOT EXISTS signup_supporting_document_path VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE accounts ADD COLUMN IF NOT EXISTS signup_supporting_document_size_bytes INT DEFAULT NULL');
        $this->addSql('ALTER TABLE accounts ADD COLUMN IF NOT EXISTS signup_supporting_document_uploaded_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE accounts ADD COLUMN IF NOT EXISTS signup_supporting_document_verification_status VARCHAR(30) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS signup_supporting_document_verification_status');
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS signup_supporting_document_uploaded_at');
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS signup_supporting_document_size_bytes');
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS signup_supporting_document_path');
    }
}
