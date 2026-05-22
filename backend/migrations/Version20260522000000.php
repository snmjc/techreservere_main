<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260522000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create invitations table for wishlist send-invite tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS pgcrypto');
        $this->addSql("
            CREATE TABLE IF NOT EXISTS invitations (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                email VARCHAR(255) NOT NULL,
                invited_by VARCHAR(255) NOT NULL,
                organization VARCHAR(255) DEFAULT NULL,
                invitation_token VARCHAR(255) NOT NULL UNIQUE,
                status VARCHAR(50) NOT NULL DEFAULT 'pending',
                expires_at TIMESTAMP WITH TIME ZONE NOT NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                accepted_at TIMESTAMP WITH TIME ZONE DEFAULT NULL
            )
        ");
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_INVITATIONS_EMAIL ON invitations (email)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_INVITATIONS_TOKEN ON invitations (invitation_token)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_INVITATIONS_STATUS ON invitations (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS invitations');
    }
}
