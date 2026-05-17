<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Add approval system fields to accounts table for Clerk integration
 */
final class Version20260518000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add status, is_approved, and invited_by fields to accounts table for Clerk approval workflow';
    }

    public function up(Schema $schema): void
    {
        // Add approval system fields to accounts table
        $this->addSql('ALTER TABLE accounts ADD status VARCHAR(20) DEFAULT \'pending\' NOT NULL');
        $this->addSql('ALTER TABLE accounts ADD is_approved BOOLEAN DEFAULT FALSE NOT NULL');
        $this->addSql('ALTER TABLE accounts ADD invited_by INT DEFAULT NULL');
        $this->addSql('ALTER TABLE accounts ADD CONSTRAINT FK_ACCOUNTS_INVITED_BY FOREIGN KEY (invited_by) REFERENCES accounts (account_identifier) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_ACCOUNTS_INVITED_BY ON accounts (invited_by)');
        $this->addSql('CREATE INDEX IDX_ACCOUNTS_STATUS ON accounts (status)');
        $this->addSql('CREATE INDEX IDX_ACCOUNTS_IS_APPROVED ON accounts (is_approved)');
    }

    public function down(Schema $schema): void
    {
        // Remove approval system fields from accounts table
        $this->addSql('DROP INDEX IDX_ACCOUNTS_IS_APPROVED');
        $this->addSql('DROP INDEX IDX_ACCOUNTS_STATUS');
        $this->addSql('DROP INDEX IDX_ACCOUNTS_INVITED_BY');
        $this->addSql('ALTER TABLE accounts DROP CONSTRAINT FK_ACCOUNTS_INVITED_BY');
        $this->addSql('ALTER TABLE accounts DROP invited_by');
        $this->addSql('ALTER TABLE accounts DROP is_approved');
        $this->addSql('ALTER TABLE accounts DROP status');
    }
}
