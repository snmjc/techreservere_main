<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create staff_info table for staff account details.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS staff_info (
            id SERIAL PRIMARY KEY,
            account_identifier INT DEFAULT NULL,
            employee_id_number VARCHAR(100) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            phone_number VARCHAR(20) NOT NULL,
            role VARCHAR(100) NOT NULL,
            image_url TEXT DEFAULT NULL,
            created_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_staff_info_account_identifier ON staff_info (account_identifier) WHERE account_identifier IS NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_staff_info_employee_id_number ON staff_info (employee_id_number)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_staff_info_phone_number ON staff_info (phone_number)');
        $this->addSql("INSERT INTO staff_info
            (account_identifier, employee_id_number, first_name, last_name, phone_number, role, image_url, created_timestamp, updated_timestamp)
            SELECT account_identifier,
                   id_number,
                   first_name,
                   last_name,
                   CASE WHEN contact_number LIKE '09%' THEN SUBSTRING(contact_number FROM 2) ELSE contact_number END,
                   COALESCE(department, 'Maintenance Staff'),
                   profile_photo_data,
                   created_timestamp,
                   updated_timestamp
            FROM accounts
            WHERE role_designation = 'ROLE_STAFF'
              AND id_number IS NOT NULL
              AND contact_number IS NOT NULL
            ON CONFLICT (employee_id_number) DO NOTHING");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS staff_info');
    }
}
