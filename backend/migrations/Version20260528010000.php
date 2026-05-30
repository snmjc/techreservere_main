<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260528010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed an approved borrower account for login redirection testing';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO accounts
                (last_name, first_name, email_address, role_designation, contact_number,
                 password_hash, status, is_approved, is_active, failed_login_attempts,
                 created_timestamp, updated_timestamp)
            VALUES
                ('Borrower', 'TechReserve', 'borrower@techreserve.edu.ph', 'ROLE_BORROWER', 'N/A',
                 '\$2y\$04\$GAe8lUuUgP/aQH6W5ng4ZuufdJW0Rn0W4yUSPcvK1wu9uM4AA2CyK',
                 'approved', TRUE, TRUE, 0, NOW(), NOW())
            ON CONFLICT (email_address) DO NOTHING
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            DELETE FROM accounts
            WHERE LOWER(email_address) = 'borrower@techreserve.edu.ph'
              AND first_name = 'TechReserve'
              AND last_name = 'Borrower'
        ");
    }
}
