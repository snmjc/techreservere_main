<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260522001000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add account status guardrails for Manage Accounts action rules';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE accounts
            SET status = LOWER(COALESCE(status, 'pending'))
            WHERE status IS NULL OR status <> LOWER(status)
        ");
        $this->addSql("
            UPDATE accounts
            SET status = 'pending'
            WHERE status NOT IN ('pending', 'approved', 'disabled', 'rejected', 'invited')
        ");
        $this->addSql("
            ALTER TABLE accounts
            DROP CONSTRAINT IF EXISTS CHK_ACCOUNTS_STATUS_ALLOWED
        ");
        $this->addSql("
            ALTER TABLE accounts
            ADD CONSTRAINT CHK_ACCOUNTS_STATUS_ALLOWED
            CHECK (status IN ('pending', 'approved', 'disabled', 'rejected', 'invited'))
        ");
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_ACCOUNTS_MANAGE_STATUS ON accounts (status, is_approved, is_active)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS IDX_ACCOUNTS_MANAGE_STATUS');
        $this->addSql('ALTER TABLE accounts DROP CONSTRAINT IF EXISTS CHK_ACCOUNTS_STATUS_ALLOWED');
    }
}
