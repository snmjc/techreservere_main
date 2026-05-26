<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260526010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize default admin accounts for Clerk login and admin dashboard access';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO accounts
                (last_name, first_name, email_address, role_designation, contact_number,
                 password_hash, status, is_approved, is_active, failed_login_attempts,
                 created_timestamp, updated_timestamp)
            VALUES
                ('Admin', 'TechReserve', 'admin@techreserve.edu.ph', 'ROLE_ADMIN', 'N/A',
                 '\$2y\$04\$xUzEhHHKVmRaSQ0.eW1phOUluE69e2whGQczxIgeGb6lyUuJdvQx6',
                 'approved', TRUE, TRUE, 0, NOW(), NOW())
            ON CONFLICT (email_address) DO NOTHING
        ");

        $this->addSql("
            UPDATE accounts
            SET role_designation = 'ROLE_ADMIN',
                status = 'approved',
                is_approved = TRUE,
                is_active = TRUE,
                password_hash = CASE
                    WHEN password_hash IS NULL
                      OR password_hash = '\$2y\$04\$1lvRF7eqgD4bwgJr9iF.Dei.KxAt21NH3LcOCYRC0NGy1sJ0hC6UC'
                    THEN '\$2y\$04\$xUzEhHHKVmRaSQ0.eW1phOUluE69e2whGQczxIgeGb6lyUuJdvQx6'
                    ELSE password_hash
                END,
                updated_timestamp = NOW()
            WHERE LOWER(email_address) IN (
                'admin@techreserve.com',
                'admin@techreserve.edu.ph',
                'admin@techreserve.feu.edu.ph'
            )
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            UPDATE accounts
            SET status = 'pending',
                is_approved = FALSE,
                updated_timestamp = NOW()
            WHERE LOWER(email_address) IN (
                'admin@techreserve.com',
                'admin@techreserve.feu.edu.ph'
            )
        ");
    }
}
