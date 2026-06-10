<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260610000200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill account profile and invitation sync columns required by account and wishlist APIs.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE accounts ADD COLUMN IF NOT EXISTS invitation_status VARCHAR(20) NOT NULL DEFAULT 'not_sent'");
        $this->addSql("ALTER TABLE accounts ADD COLUMN IF NOT EXISTS username VARCHAR(100) DEFAULT NULL");
        $this->addSql("ALTER TABLE accounts ADD COLUMN IF NOT EXISTS id_number VARCHAR(50) DEFAULT NULL");
        $this->addSql("ALTER TABLE accounts ADD COLUMN IF NOT EXISTS profile_photo_data TEXT DEFAULT NULL");
        $this->addSql("ALTER TABLE accounts ADD COLUMN IF NOT EXISTS signup_supporting_document_name VARCHAR(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE accounts ADD COLUMN IF NOT EXISTS signup_supporting_document_mime_type VARCHAR(120) DEFAULT NULL");
        $this->addSql("ALTER TABLE accounts ADD COLUMN IF NOT EXISTS signup_supporting_document_data TEXT DEFAULT NULL");
        $this->addSql("ALTER TABLE accounts ADD COLUMN IF NOT EXISTS signup_supporting_document_path VARCHAR(500) DEFAULT NULL");
        $this->addSql("ALTER TABLE accounts ADD COLUMN IF NOT EXISTS signup_supporting_document_size_bytes INT DEFAULT NULL");
        $this->addSql("ALTER TABLE accounts ADD COLUMN IF NOT EXISTS signup_supporting_document_uploaded_at TIMESTAMP DEFAULT NULL");
        $this->addSql("ALTER TABLE accounts ADD COLUMN IF NOT EXISTS signup_supporting_document_verification_status VARCHAR(30) DEFAULT NULL");

        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_ACCOUNTS_INVITATION_STATUS ON accounts (invitation_status)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UQ_ACCOUNTS_CLERK_USER_ID_NOT_NULL ON accounts (clerk_user_id) WHERE clerk_user_id IS NOT NULL');

        $this->addSql("
            UPDATE accounts
            SET invitation_status = CASE
                WHEN LOWER(COALESCE(status, 'pending')) = 'approved' THEN 'accepted'
                WHEN LOWER(COALESCE(status, 'pending')) = 'invited' THEN 'sent'
                ELSE COALESCE(NULLIF(BTRIM(invitation_status), ''), 'not_sent')
            END
            WHERE COALESCE(NULLIF(BTRIM(invitation_status), ''), '') = ''
               OR invitation_status = 'not_sent'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS UQ_ACCOUNTS_CLERK_USER_ID_NOT_NULL');
        $this->addSql('DROP INDEX IF EXISTS IDX_ACCOUNTS_INVITATION_STATUS');
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS signup_supporting_document_verification_status');
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS signup_supporting_document_uploaded_at');
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS signup_supporting_document_size_bytes');
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS signup_supporting_document_path');
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS signup_supporting_document_data');
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS signup_supporting_document_mime_type');
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS signup_supporting_document_name');
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS profile_photo_data');
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS id_number');
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS username');
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS invitation_status');
    }
}
