<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260610000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Clerk invitation verification and approval sync fields to accounts.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE accounts ADD COLUMN IF NOT EXISTS is_verified BOOLEAN NOT NULL DEFAULT FALSE");
        $this->addSql("ALTER TABLE accounts ADD COLUMN IF NOT EXISTS verification_status VARCHAR(20) NOT NULL DEFAULT 'unverified'");
        $this->addSql("ALTER TABLE accounts ADD COLUMN IF NOT EXISTS invited_at TIMESTAMP DEFAULT NULL");
        $this->addSql("ALTER TABLE accounts ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP DEFAULT NULL");
        $this->addSql("CREATE INDEX IF NOT EXISTS IDX_ACCOUNTS_VERIFICATION_STATUS ON accounts (verification_status)");
        $this->addSql("CREATE INDEX IF NOT EXISTS IDX_ACCOUNTS_INVITED_AT ON accounts (invited_at)");
        $this->addSql("CREATE INDEX IF NOT EXISTS IDX_ACCOUNTS_APPROVED_AT ON accounts (approved_at)");

        $this->addSql("
            UPDATE accounts
            SET
                is_verified = CASE
                    WHEN LOWER(COALESCE(status, 'pending')) IN ('invited', 'approved', 'disabled') THEN TRUE
                    ELSE COALESCE(is_verified, FALSE)
                END,
                verification_status = CASE
                    WHEN LOWER(COALESCE(status, 'pending')) IN ('invited', 'approved', 'disabled') THEN 'verified'
                    ELSE COALESCE(NULLIF(BTRIM(verification_status), ''), 'unverified')
                END
        ");

        $this->addSql("
            UPDATE accounts
            SET invited_at = latest_invitation.created_at
            FROM (
                SELECT DISTINCT ON (LOWER(email))
                    LOWER(email) AS email_key,
                    created_at
                FROM invitations
                ORDER BY LOWER(email), created_at DESC
            ) latest_invitation
            WHERE LOWER(accounts.email_address) = latest_invitation.email_key
              AND accounts.invited_at IS NULL
        ");

        $this->addSql("
            UPDATE accounts
            SET approved_at = COALESCE(accounts.approved_at, latest_invitation.accepted_at, accounts.updated_timestamp)
            FROM (
                SELECT DISTINCT ON (LOWER(email))
                    LOWER(email) AS email_key,
                    accepted_at
                FROM invitations
                WHERE accepted_at IS NOT NULL
                ORDER BY LOWER(email), accepted_at DESC
            ) latest_invitation
            WHERE LOWER(accounts.email_address) = latest_invitation.email_key
              AND LOWER(COALESCE(accounts.status, 'pending')) = 'approved'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS IDX_ACCOUNTS_APPROVED_AT');
        $this->addSql('DROP INDEX IF EXISTS IDX_ACCOUNTS_INVITED_AT');
        $this->addSql('DROP INDEX IF EXISTS IDX_ACCOUNTS_VERIFICATION_STATUS');
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS approved_at');
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS invited_at');
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS verification_status');
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS is_verified');
    }
}
