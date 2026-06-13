<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize duplicate account emails and enforce case-insensitive uniqueness for accounts.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            WITH ranked_accounts AS (
                SELECT
                    account_identifier,
                    email_address,
                    LOWER(TRIM(email_address)) AS normalized_email,
                    ROW_NUMBER() OVER (
                        PARTITION BY LOWER(TRIM(email_address))
                        ORDER BY
                            CASE
                                WHEN COALESCE(NULLIF(clerk_user_id, ''), '') <> '' THEN 0
                                WHEN LOWER(COALESCE(invitation_status, 'not_sent')) = 'accepted' THEN 1
                                WHEN LOWER(COALESCE(status, 'pending')) IN ('active', 'approved', 'accepted') THEN 2
                                WHEN LOWER(COALESCE(invitation_status, 'not_sent')) = 'sent' THEN 3
                                WHEN LOWER(COALESCE(status, 'pending')) = 'invited' THEN 4
                                ELSE 5
                            END,
                            updated_timestamp DESC NULLS LAST,
                            created_timestamp DESC NULLS LAST,
                            account_identifier DESC
                    ) AS row_rank
                FROM accounts
                WHERE email_address IS NOT NULL
                  AND TRIM(email_address) <> ''
            )
            UPDATE accounts AS duplicate_account
            SET email_address = CONCAT('dedup+', duplicate_account.account_identifier, '__', ranked_accounts.normalized_email),
                status = CASE
                    WHEN LOWER(COALESCE(duplicate_account.status, 'pending')) IN ('active', 'approved', 'accepted') THEN duplicate_account.status
                    ELSE 'disabled'
                END,
                is_active = CASE
                    WHEN LOWER(COALESCE(duplicate_account.status, 'pending')) IN ('active', 'approved', 'accepted') THEN duplicate_account.is_active
                    ELSE FALSE
                END,
                updated_timestamp = NOW()
            FROM ranked_accounts
            WHERE duplicate_account.account_identifier = ranked_accounts.account_identifier
              AND ranked_accounts.row_rank > 1
        ");

        $this->addSql('DROP INDEX IF EXISTS UNIQ_accounts_email');
        $this->addSql('DROP INDEX IF EXISTS idx_accounts_email');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UQ_ACCOUNTS_EMAIL_ADDRESS_LOWER ON accounts (LOWER(TRIM(email_address)))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS UQ_ACCOUNTS_EMAIL_ADDRESS_LOWER');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_accounts_email ON accounts (email_address)');
    }
}
