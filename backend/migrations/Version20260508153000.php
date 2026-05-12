<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260508153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add seed data for admin and borrower accounts';
    }

    public function up(Schema $schema): void
    {
        // Insert admin account
        $this->addSql("INSERT INTO accounts (last_name, first_name, email_address, role_designation, contact_number, is_active, created_timestamp, updated_timestamp, password_hash, failed_login_attempts) 
            VALUES ('Admin', 'System', 'admin@techreserve.com', 'Admin', '09123456789', true, NOW(), NOW(), '\$2y\$13\$YourHashedPasswordHere', 0)");

        // Insert borrower account
        $this->addSql("INSERT INTO accounts (last_name, first_name, email_address, role_designation, contact_number, is_active, created_timestamp, updated_timestamp, password_hash, failed_login_attempts) 
            VALUES ('Doe', 'John', 'john.doe@example.com', 'Borrower', '09123456788', true, NOW(), NOW(), '\$2y\$13\$YourHashedPasswordHere', 0)");
    }

    public function down(Schema $schema): void
    {
        // Remove seed data
        $this->addSql("DELETE FROM accounts WHERE email_address IN ('admin@techreserve.com', 'john.doe@example.com')");
    }
}
