<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow verified and active invitation lifecycle statuses on accounts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE accounts
            SET status = LOWER(COALESCE(status, 'pending'))
            WHERE status IS NULL OR status <> LOWER(status)
        ");

        $this->addSql("
            ALTER TABLE accounts
            DROP CONSTRAINT IF EXISTS CHK_ACCOUNTS_STATUS_ALLOWED
        ");

        $this->addSql("
            ALTER TABLE accounts
            ADD CONSTRAINT CHK_ACCOUNTS_STATUS_ALLOWED
            CHECK (status IN ('pending', 'verified', 'active', 'approved', 'accepted', 'disabled', 'rejected', 'invited'))
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE accounts
            DROP CONSTRAINT IF EXISTS CHK_ACCOUNTS_STATUS_ALLOWED
        ");

        $this->addSql("
            ALTER TABLE accounts
            ADD CONSTRAINT CHK_ACCOUNTS_STATUS_ALLOWED
            CHECK (status IN ('pending', 'approved', 'disabled', 'rejected', 'invited'))
        ");
    }
}
