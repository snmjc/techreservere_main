<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Add Farah Kenawy user with Clerk integration
 */
final class Version20260520000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Farah Kenawy user with Clerk user ID user_3Dx2Xs0SnrU3eJsQlV5gUCKjPQi';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO accounts (last_name, first_name, email_address, role_designation, clerk_user_id, status, is_approved, is_active, created_timestamp, updated_timestamp) VALUES ('Kenawy', 'Farah', 'fekenawy@fit.edu.ph', 'ROLE_BORROWER', 'user_3Dx2Xs0SnrU3eJsQlV5gUCKjPQi', 'approved', TRUE, TRUE, NOW(), NOW())");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM accounts WHERE clerk_user_id = 'user_3Dx2Xs0SnrU3eJsQlV5gUCKjPQi'");
    }
}
