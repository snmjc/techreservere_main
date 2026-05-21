<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260521001000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure accounts have ID numbers for wishlist verification records';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accounts ADD COLUMN IF NOT EXISTS id_number VARCHAR(50) DEFAULT NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_ACCOUNTS_ID_NUMBER ON accounts (id_number)');
        $this->addSql("UPDATE accounts SET id_number = '2024*****' WHERE email_address = 'kdelafuente@fit.edu.ph' AND id_number IS NULL");
        $this->addSql("UPDATE accounts SET id_number = '2023*****' WHERE email_address = 'avaldes@fit.edu.ph' AND id_number IS NULL");
        $this->addSql("UPDATE accounts SET id_number = '2022*****' WHERE email_address = 'msantos@fit.edu.ph' AND id_number IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS IDX_ACCOUNTS_ID_NUMBER');
        $this->addSql('ALTER TABLE accounts DROP COLUMN IF EXISTS id_number');
    }
}
