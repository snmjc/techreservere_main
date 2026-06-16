<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616084000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align venue CRUD schema with admin modal requirements and enforce case-insensitive unique venue names';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE venues ADD COLUMN IF NOT EXISTS floor_level VARCHAR(50) DEFAULT NULL");
        $this->addSql("ALTER TABLE venues ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL");
        $this->addSql("ALTER TABLE venues ADD COLUMN IF NOT EXISTS image_url TEXT DEFAULT NULL");
        $this->addSql("ALTER TABLE venues ALTER COLUMN image_url TYPE TEXT");
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_indexes
        WHERE schemaname = 'public'
          AND indexname = 'uniq_venues_lower_venue_name'
    ) AND NOT EXISTS (
        SELECT 1
        FROM (
            SELECT LOWER(venue_name)
            FROM venues
            GROUP BY LOWER(venue_name)
            HAVING COUNT(*) > 1
        ) duplicate_venue_names
    ) THEN
        CREATE UNIQUE INDEX uniq_venues_lower_venue_name ON venues (LOWER(venue_name));
    END IF;
END
$$;
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP INDEX IF EXISTS uniq_venues_lower_venue_name");
    }
}
