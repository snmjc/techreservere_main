<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add staff account uniqueness constraints for Work ID and phone.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_staff_accounts_work_id ON accounts (id_number) WHERE role_designation = 'ROLE_STAFF' AND id_number IS NOT NULL");
        $this->addSql("CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_staff_accounts_phone ON accounts (contact_number) WHERE role_designation = 'ROLE_STAFF' AND contact_number IS NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS UNIQ_staff_accounts_phone');
        $this->addSql('DROP INDEX IF EXISTS UNIQ_staff_accounts_work_id');
    }
}
