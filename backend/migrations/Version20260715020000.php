<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add support feedback submissions and damage reports tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "CREATE TABLE IF NOT EXISTS feedback_submissions (
                feedback_identifier SERIAL PRIMARY KEY,
                account_identifier INT NOT NULL,
                category VARCHAR(80) NOT NULL DEFAULT 'General',
                subject_line VARCHAR(160) NOT NULL,
                message_body TEXT NOT NULL,
                current_status VARCHAR(40) NOT NULL DEFAULT 'Submitted',
                admin_notes TEXT DEFAULT NULL,
                created_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )"
        );
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_feedback_submissions_account ON feedback_submissions (account_identifier)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_feedback_submissions_status ON feedback_submissions (current_status)');

        $this->addSql(
            "CREATE TABLE IF NOT EXISTS damage_reports (
                damage_report_identifier SERIAL PRIMARY KEY,
                account_identifier INT NOT NULL,
                resource_type VARCHAR(40) NOT NULL,
                resource_identifier INT DEFAULT NULL,
                resource_name VARCHAR(160) NOT NULL,
                issue_type VARCHAR(80) NOT NULL DEFAULT 'Damage',
                description_text TEXT NOT NULL,
                image_data TEXT DEFAULT NULL,
                current_status VARCHAR(40) NOT NULL DEFAULT 'Submitted',
                admin_notes TEXT DEFAULT NULL,
                created_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )"
        );
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_damage_reports_account ON damage_reports (account_identifier)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_damage_reports_status ON damage_reports (current_status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS damage_reports');
        $this->addSql('DROP TABLE IF EXISTS feedback_submissions');
    }
}
