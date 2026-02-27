<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * AI GENERATED: Initial migration for all domain entities.
 * Creates tables: accounts, equipment, reservations, venues, release_returns, tasks, audit_logs, notifications.
 */
final class Version20250101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create all domain entity tables for TechReserve RBAC backend';
    }

    public function up(Schema $schema): void
    {
        // ===== accounts =====
        $this->addSql('CREATE TABLE accounts (
            account_identifier SERIAL PRIMARY KEY,
            last_name VARCHAR(100) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            email_address VARCHAR(180) NOT NULL,
            role_designation VARCHAR(50) NOT NULL DEFAULT \'Borrower\',
            contact_number VARCHAR(30) DEFAULT NULL,
            clerk_user_id VARCHAR(255) DEFAULT NULL,
            is_active BOOLEAN NOT NULL DEFAULT TRUE,
            created_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_accounts_email ON accounts (email_address)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_accounts_clerk ON accounts (clerk_user_id)');

        // ===== equipment =====
        $this->addSql('CREATE TABLE equipment (
            equipment_identifier SERIAL PRIMARY KEY,
            equipment_name VARCHAR(150) NOT NULL,
            category_name VARCHAR(100) NOT NULL,
            total_quantity INT NOT NULL DEFAULT 0,
            available_quantity INT NOT NULL DEFAULT 0,
            operational_status VARCHAR(50) NOT NULL DEFAULT \'Active\',
            created_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');

        // ===== venues =====
        $this->addSql('CREATE TABLE venues (
            venue_identifier SERIAL PRIMARY KEY,
            venue_name VARCHAR(150) NOT NULL,
            venue_location VARCHAR(200) DEFAULT NULL,
            capacity_limit INT DEFAULT NULL,
            availability_status VARCHAR(50) NOT NULL DEFAULT \'Available\',
            created_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');

        // ===== reservations =====
        $this->addSql('CREATE TABLE reservations (
            reservation_identifier SERIAL PRIMARY KEY,
            reservation_code VARCHAR(50) NOT NULL,
            borrower_account_id INT NOT NULL,
            organization_name VARCHAR(200) NOT NULL,
            venue_identifier INT DEFAULT NULL,
            requested_equipment_list JSONB NOT NULL DEFAULT \'[]\',
            requested_quantity INT NOT NULL DEFAULT 0,
            event_date_time TIMESTAMP NOT NULL,
            purpose_description TEXT DEFAULT NULL,
            activity_type VARCHAR(100) DEFAULT NULL,
            current_status VARCHAR(50) NOT NULL DEFAULT \'Pending Review\',
            priority_level VARCHAR(50) DEFAULT NULL,
            rejection_reason TEXT DEFAULT NULL,
            supporting_documents TEXT DEFAULT NULL,
            submission_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_reservations_code ON reservations (reservation_code)');

        // ===== release_returns =====
        $this->addSql('CREATE TABLE release_returns (
            release_return_identifier SERIAL PRIMARY KEY,
            reservation_identifier INT NOT NULL,
            transaction_type VARCHAR(50) NOT NULL DEFAULT \'Release\',
            equipment_item_list JSONB NOT NULL DEFAULT \'[]\',
            quantity_processed INT NOT NULL DEFAULT 0,
            condition_status VARCHAR(50) NOT NULL DEFAULT \'Good\',
            remarks_description TEXT DEFAULT NULL,
            processed_by_account_id INT DEFAULT NULL,
            processed_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');

        // ===== tasks =====
        $this->addSql('CREATE TABLE tasks (
            task_identifier SERIAL PRIMARY KEY,
            reservation_identifier INT DEFAULT NULL,
            task_title VARCHAR(200) NOT NULL,
            task_description TEXT DEFAULT NULL,
            task_type VARCHAR(50) NOT NULL DEFAULT \'Preparation\',
            task_status VARCHAR(50) NOT NULL DEFAULT \'Pending\',
            assigned_to_account_id INT DEFAULT NULL,
            due_date_timestamp TIMESTAMP DEFAULT NULL,
            created_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');

        // ===== audit_logs =====
        $this->addSql('CREATE TABLE audit_logs (
            audit_log_identifier SERIAL PRIMARY KEY,
            performed_by_account_id INT DEFAULT NULL,
            action_performed VARCHAR(100) NOT NULL,
            target_entity_type VARCHAR(100) NOT NULL,
            target_entity_identifier INT DEFAULT NULL,
            change_details JSONB DEFAULT NULL,
            occurred_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');

        // ===== notifications =====
        $this->addSql('CREATE TABLE notifications (
            notification_identifier SERIAL PRIMARY KEY,
            recipient_account_id INT NOT NULL,
            notification_title VARCHAR(200) NOT NULL,
            notification_message TEXT DEFAULT NULL,
            notification_type VARCHAR(50) NOT NULL DEFAULT \'Info\',
            is_read BOOLEAN NOT NULL DEFAULT FALSE,
            created_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS notifications');
        $this->addSql('DROP TABLE IF EXISTS audit_logs');
        $this->addSql('DROP TABLE IF EXISTS tasks');
        $this->addSql('DROP TABLE IF EXISTS release_returns');
        $this->addSql('DROP TABLE IF EXISTS reservations');
        $this->addSql('DROP TABLE IF EXISTS venues');
        $this->addSql('DROP TABLE IF EXISTS equipment');
        $this->addSql('DROP TABLE IF EXISTS accounts');
    }
}
