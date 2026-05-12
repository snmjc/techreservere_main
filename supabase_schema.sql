-- TechReserve Database Schema for Supabase
-- This script creates all necessary tables for the TechReserve application

-- Create accounts table
CREATE TABLE IF NOT EXISTS accounts (
    account_identifier SERIAL PRIMARY KEY,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    email_address VARCHAR(100) NOT NULL UNIQUE,
    role_designation VARCHAR(50) NOT NULL DEFAULT 'ROLE_BORROWER',
    contact_number VARCHAR(20),
    clerk_user_id VARCHAR(255),
    last_login_timestamp TIMESTAMP,
    created_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN NOT NULL DEFAULT true,
    failed_login_attempts INTEGER NOT NULL DEFAULT 0,
    locked_until_timestamp TIMESTAMP,
    password_hash VARCHAR(255)
);

-- Create venues table
CREATE TABLE IF NOT EXISTS venues (
    venue_identifier SERIAL PRIMARY KEY,
    venue_name VARCHAR(150) NOT NULL,
    venue_location VARCHAR(200),
    floor_level VARCHAR(50),
    capacity_limit INTEGER,
    availability_status VARCHAR(50) NOT NULL DEFAULT 'Available',
    description TEXT,
    image_url VARCHAR(500),
    created_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create equipment table
CREATE TABLE IF NOT EXISTS equipment (
    equipment_identifier SERIAL PRIMARY KEY,
    equipment_name VARCHAR(150) NOT NULL,
    category_name VARCHAR(100) NOT NULL,
    total_quantity INTEGER NOT NULL,
    available_quantity INTEGER NOT NULL,
    operational_status VARCHAR(50) NOT NULL DEFAULT 'Active',
    equipment_state VARCHAR(50) NOT NULL DEFAULT 'Available',
    schedule_description TEXT,
    created_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create reservations table
CREATE TABLE IF NOT EXISTS reservations (
    reservation_identifier SERIAL PRIMARY KEY,
    reservation_code VARCHAR(20) NOT NULL UNIQUE,
    borrower_account_id INTEGER NOT NULL,
    organization_name VARCHAR(200) NOT NULL,
    venue_identifier INTEGER,
    requested_equipment_list JSONB NOT NULL DEFAULT '[]'::jsonb,
    requested_quantity INTEGER NOT NULL,
    event_date_time TIMESTAMP NOT NULL,
    purpose_description VARCHAR(200) NOT NULL,
    activity_type VARCHAR(100) NOT NULL,
    current_status VARCHAR(50) NOT NULL DEFAULT 'Pending Review',
    priority_level VARCHAR(20),
    rejection_reason TEXT,
    supporting_documents JSONB,
    submission_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (borrower_account_id) REFERENCES accounts(account_identifier),
    FOREIGN KEY (venue_identifier) REFERENCES venues(venue_identifier)
);

-- Create release_returns table
CREATE TABLE IF NOT EXISTS release_returns (
    release_return_identifier SERIAL PRIMARY KEY,
    reservation_identifier INTEGER NOT NULL,
    transaction_type VARCHAR(50) NOT NULL DEFAULT 'Release',
    equipment_item_list JSONB NOT NULL DEFAULT '[]'::jsonb,
    quantity_processed INTEGER NOT NULL,
    condition_status VARCHAR(50) NOT NULL DEFAULT 'Good',
    remarks_description TEXT,
    processed_by_account_id INTEGER,
    processed_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_identifier) REFERENCES reservations(reservation_identifier),
    FOREIGN KEY (processed_by_account_id) REFERENCES accounts(account_identifier)
);

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    notification_identifier SERIAL PRIMARY KEY,
    recipient_account_id INTEGER NOT NULL,
    notification_title VARCHAR(200) NOT NULL,
    notification_message TEXT,
    notification_type VARCHAR(50) NOT NULL DEFAULT 'Info',
    is_read BOOLEAN NOT NULL DEFAULT false,
    created_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recipient_account_id) REFERENCES accounts(account_identifier)
);

-- Create audit_logs table
CREATE TABLE IF NOT EXISTS audit_logs (
    audit_log_identifier SERIAL PRIMARY KEY,
    performed_by_account_id INTEGER,
    action_performed VARCHAR(100) NOT NULL,
    target_entity_type VARCHAR(100) NOT NULL,
    target_entity_identifier INTEGER,
    change_details JSONB,
    occurred_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (performed_by_account_id) REFERENCES accounts(account_identifier)
);

-- Create tasks table
CREATE TABLE IF NOT EXISTS tasks (
    task_identifier SERIAL PRIMARY KEY,
    reservation_identifier INTEGER,
    task_title VARCHAR(200) NOT NULL,
    task_description TEXT,
    task_type VARCHAR(50) NOT NULL DEFAULT 'Preparation',
    task_status VARCHAR(50) NOT NULL DEFAULT 'Pending',
    assigned_to_account_id INTEGER,
    due_date_timestamp TIMESTAMP,
    created_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_identifier) REFERENCES reservations(reservation_identifier),
    FOREIGN KEY (assigned_to_account_id) REFERENCES accounts(account_identifier)
);

-- Create indexes for better query performance
CREATE INDEX idx_accounts_email ON accounts(email_address);
CREATE INDEX idx_accounts_clerk_user_id ON accounts(clerk_user_id);
CREATE INDEX idx_reservations_borrower ON reservations(borrower_account_id);
CREATE INDEX idx_reservations_status ON reservations(current_status);
CREATE INDEX idx_reservations_code ON reservations(reservation_code);
CREATE INDEX idx_equipment_category ON equipment(category_name);
CREATE INDEX idx_notifications_recipient ON notifications(recipient_account_id);
CREATE INDEX idx_notifications_read ON notifications(is_read);
CREATE INDEX idx_tasks_reservation ON tasks(reservation_identifier);
CREATE INDEX idx_tasks_assigned_to ON tasks(assigned_to_account_id);
CREATE INDEX idx_audit_logs_performed_by ON audit_logs(performed_by_account_id);
