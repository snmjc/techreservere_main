<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create history_logs table linking staff, reservations, and task assignments.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS history_logs (
            id SERIAL PRIMARY KEY,
            staff_id INT NOT NULL,
            reservation_id INT NOT NULL,
            task_assignment_id INT NOT NULL
        )');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_history_logs_link ON history_logs (staff_id, reservation_id, task_assignment_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_history_logs_staff_id ON history_logs (staff_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_history_logs_reservation_id ON history_logs (reservation_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_history_logs_task_assignment_id ON history_logs (task_assignment_id)');
        $this->addSql('ALTER TABLE history_logs ADD CONSTRAINT FK_history_logs_staff_id FOREIGN KEY (staff_id) REFERENCES staff_info (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE history_logs ADD CONSTRAINT FK_history_logs_reservation_id FOREIGN KEY (reservation_id) REFERENCES reservations (reservation_identifier) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE history_logs ADD CONSTRAINT FK_history_logs_task_assignment_id FOREIGN KEY (task_assignment_id) REFERENCES tasks (task_identifier) ON DELETE CASCADE');
        $this->addSql('INSERT INTO history_logs (staff_id, reservation_id, task_assignment_id)
            SELECT staff_info.id, tasks.reservation_identifier, tasks.task_identifier
            FROM tasks
            INNER JOIN staff_info ON staff_info.account_identifier = tasks.assigned_to_account_id
            WHERE tasks.reservation_identifier IS NOT NULL
              AND tasks.assigned_to_account_id IS NOT NULL
            ON CONFLICT (staff_id, reservation_id, task_assignment_id) DO NOTHING');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS history_logs');
    }
}
