<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260522002000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add mock employee account data for Wishlist and Manage Accounts pages';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO accounts
                (last_name, first_name, email_address, role_designation, id_number, department,
                 contact_number, password_hash, status, is_approved, is_active,
                 failed_login_attempts, created_timestamp, updated_timestamp)
            VALUES
                ('Zefanya', 'Nicole', 'nicolezefanya.employee.mock@techreserve.local', 'ROLE_STAFF', '2023-EMP-001', 'Maintenance Staff',
                 '09123456789', '\$2y\$04\$mockemployeehashforwishlistpending00000000000000000000000000000000', 'pending', FALSE, TRUE,
                 0, NOW() - INTERVAL '6 days', NOW() - INTERVAL '6 days'),
                ('Ramos', 'Lester', 'lesterramos.employee.mock@techreserve.local', 'ROLE_STAFF', '2025-EMP-002', 'Support Staff',
                 '09561234567', '\$2y\$04\$mockemployeehashformanageactive000000000000000000000000000000000', 'approved', TRUE, TRUE,
                 0, NOW() - INTERVAL '18 days', NOW() - INTERVAL '2 days'),
                ('Santos', 'Miguel', 'miguelsantos.employee.mock@techreserve.local', 'ROLE_STAFF', '2019-EMP-003', 'Technical Staff',
                 '09171234567', '\$2y\$04\$mockemployeehashformanagedisabled000000000000000000000000000000', 'disabled', TRUE, FALSE,
                 0, NOW() - INTERVAL '28 days', NOW() - INTERVAL '1 day')
            ON CONFLICT (email_address) DO NOTHING
        ");

        $this->addSql("
            INSERT INTO invitations
                (email, invited_by, organization, invitation_token, status, expires_at, created_at, accepted_at)
            VALUES
                ('lesterramos.employee.mock@techreserve.local', 'system@techreserve.local', 'TechReserve',
                 'mock-employee-active-lester-ramos', 'accepted', NOW() + INTERVAL '2 days', NOW() - INTERVAL '10 days', NOW() - INTERVAL '9 days'),
                ('miguelsantos.employee.mock@techreserve.local', 'system@techreserve.local', 'TechReserve',
                 'mock-employee-disabled-miguel-santos', 'accepted', NOW() + INTERVAL '1 day', NOW() - INTERVAL '12 days', NOW() - INTERVAL '11 days')
            ON CONFLICT (invitation_token) DO NOTHING
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            DELETE FROM invitations
            WHERE email IN (
                'nicolezefanya.employee.mock@techreserve.local',
                'lesterramos.employee.mock@techreserve.local',
                'miguelsantos.employee.mock@techreserve.local'
            )
        ");

        $this->addSql("
            DELETE FROM accounts
            WHERE email_address IN (
                'nicolezefanya.employee.mock@techreserve.local',
                'lesterramos.employee.mock@techreserve.local',
                'miguelsantos.employee.mock@techreserve.local'
            )
        ");
    }
}
