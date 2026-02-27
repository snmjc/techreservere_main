<?php

namespace App\DataFixtures;

use App\Domain\Account\Entity\AccountEntity;
use App\Shared\Utils\RoleConstants;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AdminAccountFixture extends Fixture
{
    // ===== AI GENERATED: AdminAccountFixture =====
    // Purpose: Seed the database with a dummy admin account
    // Inputs: none
    // Returns: void
    // Flow:
    // 1. Check if admin account already exists
    // 2. Create AccountEntity with admin role and @techreserve.edu.ph email
    // 3. Set hashed password for local auth fallback
    // 4. Persist and flush

    public function load(ObjectManager $manager): void
    {
        $existingAdmin = $manager->getRepository(AccountEntity::class)
            ->findOneBy(['emailAddress' => 'admin@techreserve.edu.ph']);

        if ($existingAdmin !== null) {
            return;
        }

        $adminAccount = new AccountEntity();
        $adminAccount->setLastName('Administrator');
        $adminAccount->setFirstName('System');
        $adminAccount->setEmailAddress('admin@techreserve.edu.ph');
        $adminAccount->setRoleDesignation(RoleConstants::ROLE_ADMIN);
        $adminAccount->setContactNumber('09000000000');
        $adminAccount->setClerkUserId('clerk_admin_placeholder');
        $adminAccount->setPasswordHash(password_hash('admin123', PASSWORD_BCRYPT));
        $adminAccount->setIsActive(true);

        $manager->persist($adminAccount);
        $manager->flush();
    }
}
