<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260522002000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Legacy placeholder migration restored to match executed migration history.';
    }

    public function up(Schema $schema): void
    {
        // This migration intentionally does nothing.
        // The version remains in migration history on deployed databases.
    }

    public function down(Schema $schema): void
    {
        // No-op rollback placeholder.
    }
}
