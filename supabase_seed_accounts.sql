-- Seed data for TechReserve accounts
-- Insert admin and user accounts for testing

-- Admin Account
INSERT INTO accounts (
    last_name,
    first_name,
    email_address,
    role_designation,
    contact_number,
    clerk_user_id,
    last_login_timestamp,
    created_timestamp,
    updated_timestamp,
    is_active,
    failed_login_attempts,
    locked_until_timestamp,
    password_hash
) VALUES (
    'Admin',
    'Test',
    'admin@fit.edu.ph',
    'ROLE_ADMIN',
    '+63-912-345-6789',
    'user_admin_test_001',
    NOW(),
    NOW(),
    NOW(),
    true,
    0,
    NULL,
    'test_admin_password'
) ON CONFLICT (email_address) DO NOTHING;

-- Regular User Account (Borrower)
INSERT INTO accounts (
    last_name,
    first_name,
    email_address,
    role_designation,
    contact_number,
    clerk_user_id,
    last_login_timestamp,
    created_timestamp,
    updated_timestamp,
    is_active,
    failed_login_attempts,
    locked_until_timestamp,
    password_hash
) VALUES (
    'Mojica',
    'Sean',
    'sean.mojica@fit.edu.ph',
    'ROLE_BORROWER',
    '+63-912-345-6790',
    'user_borrower_test_001',
    NOW(),
    NOW(),
    NOW(),
    true,
    0,
    NULL,
    'test_user_password'
) ON CONFLICT (email_address) DO NOTHING;

-- Verify the accounts were created
SELECT account_identifier, first_name, last_name, email_address, role_designation, is_active FROM accounts ORDER BY account_identifier;
