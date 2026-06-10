<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260521000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed wishlist account records for admin verification workflow';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accounts ADD COLUMN IF NOT EXISTS department VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE accounts ADD COLUMN IF NOT EXISTS id_number VARCHAR(50) DEFAULT NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_ACCOUNTS_ID_NUMBER ON accounts (id_number)');

        $this->addSql("
            INSERT INTO accounts
                (last_name, first_name, email_address, role_designation, id_number, department, contact_number,
                 clerk_user_id, status, is_approved, is_active, failed_login_attempts,
                 created_timestamp, updated_timestamp)
            VALUES
                ('Dela Fuente', 'Karin', 'kdelafuente@fit.edu.ph', 'ROLE_ADMIN', '2024*****', 'Administration', '09170000001',
                 'clerk_seed_wishlist_admin_karin', 'pending', FALSE, TRUE, 0,
                 '2026-05-15 08:30:00', '2026-05-15 08:30:00'),
                ('Valdes', 'Anabela', 'avaldes@fit.edu.ph', 'ROLE_BORROWER', '2023*****', 'Student', '09170000002',
                 'clerk_seed_wishlist_student_anabela', 'pending', FALSE, TRUE, 0,
                 '2026-05-16 10:15:00', '2026-05-16 10:15:00'),
                ('Santos', 'Miguel', 'msantos@fit.edu.ph', 'ROLE_BORROWER', '2022*****', 'Faculty', '09170000003',
                 'clerk_seed_wishlist_faculty_miguel', 'invited', FALSE, TRUE, 0,
                 '2026-05-14 14:05:00', '2026-05-14 14:05:00')
            ON CONFLICT (email_address) DO NOTHING
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            DELETE FROM accounts
            WHERE (email_address = 'kdelafuente@fit.edu.ph' AND clerk_user_id = 'clerk_seed_wishlist_admin_karin')
               OR (email_address = 'avaldes@fit.edu.ph' AND clerk_user_id = 'clerk_seed_wishlist_student_anabela')
               OR (email_address = 'msantos@fit.edu.ph' AND clerk_user_id = 'clerk_seed_wishlist_faculty_miguel')
        ");
        $this->addSql('DROP INDEX IF EXISTS IDX_ACCOUNTS_ID_NUMBER');
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS id_number');
    }
}
