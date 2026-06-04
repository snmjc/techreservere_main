<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add derived account username from email local part.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accounts ADD COLUMN IF NOT EXISTS username VARCHAR(100) DEFAULT NULL');
        $this->addSql("
            WITH derived AS (
                SELECT
                    account_identifier,
                    NULLIF(REGEXP_REPLACE(LOWER(SPLIT_PART(email_address, '@', 1)), '[^a-z0-9._-]', '', 'g'), '') AS base_username
                FROM accounts
            ),
            numbered AS (
                SELECT
                    account_identifier,
                    COALESCE(base_username, 'user') AS base_username,
                    ROW_NUMBER() OVER (PARTITION BY COALESCE(base_username, 'user') ORDER BY account_identifier) AS duplicate_index
                FROM derived
            )
            UPDATE accounts
            SET username = CASE
                WHEN numbered.duplicate_index = 1 THEN numbered.base_username
                ELSE numbered.base_username || '-' || accounts.account_identifier::text
            END
            FROM numbered
            WHERE accounts.account_identifier = numbered.account_identifier
              AND (accounts.username IS NULL OR BTRIM(accounts.username) = '')
        ");
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_ACCOUNTS_USERNAME_LOWER ON accounts (LOWER(username)) WHERE username IS NOT NULL AND BTRIM(username) <> \'\'');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_ACCOUNTS_USERNAME ON accounts (username)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS IDX_ACCOUNTS_USERNAME');
        $this->addSql('DROP INDEX IF EXISTS UNIQ_ACCOUNTS_USERNAME_LOWER');
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS username');
    }
}
