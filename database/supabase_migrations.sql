-- Create pending_users table
CREATE TABLE pending_users (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  email VARCHAR(255) NOT NULL UNIQUE,
  full_name VARCHAR(255) NOT NULL,
  department VARCHAR(255),
  organization VARCHAR(255),
  phone VARCHAR(20),
  status VARCHAR(50) NOT NULL DEFAULT 'pending', -- pending, approved, rejected
  rejection_reason TEXT,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  approved_at TIMESTAMP WITH TIME ZONE,
  rejected_at TIMESTAMP WITH TIME ZONE,
  invitation_token VARCHAR(255),
  CONSTRAINT status_check CHECK (status IN ('pending', 'approved', 'rejected'))
);

-- Create invitations table
CREATE TABLE invitations (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  email VARCHAR(255) NOT NULL,
  invited_by VARCHAR(255) NOT NULL,
  organization VARCHAR(255),
  invitation_token VARCHAR(255) NOT NULL UNIQUE,
  status VARCHAR(50) NOT NULL DEFAULT 'pending', -- pending, accepted, expired
  expires_at TIMESTAMP WITH TIME ZONE NOT NULL,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  accepted_at TIMESTAMP WITH TIME ZONE,
  CONSTRAINT invitation_status_check CHECK (status IN ('pending', 'accepted', 'expired'))
);

-- Create indexes for better query performance
CREATE INDEX idx_pending_users_email ON pending_users(email);
CREATE INDEX idx_pending_users_status ON pending_users(status);
CREATE INDEX idx_invitations_email ON invitations(email);
CREATE INDEX idx_invitations_token ON invitations(invitation_token);
CREATE INDEX idx_invitations_status ON invitations(status);

-- Enable Row Level Security (RLS)
ALTER TABLE pending_users ENABLE ROW LEVEL SECURITY;
ALTER TABLE invitations ENABLE ROW LEVEL SECURITY;

-- Create RLS policies for pending_users
CREATE POLICY "Anyone can insert pending users" ON pending_users
  FOR INSERT WITH CHECK (true);

CREATE POLICY "Only authenticated users can view pending users" ON pending_users
  FOR SELECT USING (auth.role() = 'authenticated');

CREATE POLICY "Only admins can update pending users" ON pending_users
  FOR UPDATE USING (auth.role() = 'authenticated');

-- Create RLS policies for invitations
CREATE POLICY "Anyone can insert invitations" ON invitations
  FOR INSERT WITH CHECK (true);

CREATE POLICY "Anyone can view invitations by token" ON invitations
  FOR SELECT USING (true);

CREATE POLICY "Only authenticated users can update invitations" ON invitations
  FOR UPDATE USING (auth.role() = 'authenticated');
