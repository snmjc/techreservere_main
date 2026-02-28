<?php

namespace App\DataFixtures;

use App\Domain\Account\Entity\AccountEntity;
use App\Shared\Utils\RoleConstants;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class BorrowerAccountFixture extends Fixture
{
    // ===== AI GENERATED: BorrowerAccountFixture =====
    // Purpose: Seed the database with a dummy borrower account
    // Inputs: none
    // Returns: void
    // Flow:
    // 1. Check if borrower account already exists
    // 2. Create AccountEntity with borrower role and @techreserve.feu.edu.ph email
    // 3. Set hashed password for local auth fallback
    // 4. Persist and flush

    public function load(ObjectManager $manager): void
    {
        $existingBorrower = $manager->getRepository(AccountEntity::class)
            ->findOneBy(['emailAddress' => 'borrower@techreserve.feu.edu.ph']);

        if ($existingBorrower !== null) {
            return;
        }

        $borrowerAccount = new AccountEntity();
        $borrowerAccount->setLastName('Borrower');
        $borrowerAccount->setFirstName('Default');
        $borrowerAccount->setEmailAddress('borrower@techreserve.feu.edu.ph');
        $borrowerAccount->setRoleDesignation(RoleConstants::ROLE_BORROWER);
        $borrowerAccount->setContactNumber('09000000001');
        $borrowerAccount->setClerkUserId('clerk_borrower_placeholder');
        $borrowerAccount->setPasswordHash(password_hash('borrower123', PASSWORD_BCRYPT));
        $borrowerAccount->setIsActive(true);

        $manager->persist($borrowerAccount);
        $manager->flush();
    }
}
