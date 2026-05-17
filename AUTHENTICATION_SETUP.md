# Complete Authentication System Setup Guide

## Overview
This guide covers the complete authentication flow for TechReserve with:
- **Clerk Login** for existing users
- **Custom Sign Up** with Supabase registration and requestor approval
- **Invitation System** with email notifications
- **Requestor Approval Dashboard** for account verification

---

## 1. Supabase Database Setup

### Step 1: Run the Migration SQL
Execute the SQL script in your Supabase dashboard:

```sql
-- File: database/supabase_migrations.sql
-- Run this in Supabase SQL Editor
```

This creates:
- `pending_users` table - stores sign-up requests awaiting approval
- `invitations` table - stores invitation tokens and status

### Step 2: Verify Tables
Check Supabase dashboard under "Tables" to confirm:
- ✓ pending_users
- ✓ invitations

---

## 2. Frontend Setup

### Step 1: Install Clerk SDK
```bash
npm install @clerk/clerk-js @clerk/vue
```

### Step 2: Update Environment Variables
In `.env`:
```
VITE_CLERK_PUBLISHABLE_KEY=your_clerk_publishable_key_here
VITE_SUPABASE_URL=https://vwvoefadwrvsadrpceot.supabase.co
VITE_SUPABASE_ANON_KEY=sb_publishable_PArx9Gqcv7nB9XdpZlXAag_WIH0ivab
```

### Step 3: Created Components
- ✓ `CustomSignUp.vue` - Custom sign-up page with Supabase integration
- ✓ `ClerkLogin.vue` - Clerk login page
- ✓ `RequestorApproval.vue` - Admin dashboard for approving accounts

### Step 4: Created Services
- ✓ `supabaseService.js` - Supabase database operations
- ✓ `emailService.js` - Email sending service

### Step 5: Updated Routes
Added to `router/routes.js`:
- `/clerk-login` - Clerk login page
- `/custom-signup` - Custom sign-up page
- `/admin/requestor-approval` - Requestor approval dashboard

---

## 3. Backend Setup

### Step 1: Create Email Controller
File: `backend/app/Http/Controllers/EmailController.php`

This controller handles:
- Sending invitation emails
- Sending approval emails
- Sending rejection emails

### Step 2: Add API Routes
File: `backend/routes/api.php`

Routes:
- `POST /api/v1/emails/send-invitation`
- `POST /api/v1/emails/send-approval`
- `POST /api/v1/emails/send-rejection`

### Step 3: Configure Mail
Update your Laravel `.env`:
```
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@techreserve.com
MAIL_FROM_NAME="TechReserve"
```

---

## 4. Authentication Flow

### Login Flow (Existing Users)
```
User → /clerk-login → Clerk Authentication → /dashboard
```

### Sign Up Flow (New Users)
```
User → /custom-signup 
  → Fill form (name, email, department, organization)
  → Submit to Supabase (pending_users table)
  → Email confirmation message
  → Admin reviews at /admin/requestor-approval
  → Admin approves/rejects
  → User receives approval/rejection email
  → User can login with Clerk
```

### Invitation Flow
```
Admin → Sends invitation email
  → User clicks "Set up account" link
  → User fills sign-up form
  → Account auto-approved (invited users)
  → Confirmation email sent
  → User can login
```

---

## 5. User Roles

### ROLE_ADMIN
- Access: `/admin/*` routes
- Can: Approve/reject accounts, manage system
- Dashboard: `/admin/requestor-approval`

### ROLE_BORROWER
- Access: `/borrower/*` routes
- Can: Create reservations, view facilities
- Dashboard: `/borrower/my-reservations`

---

## 6. Testing Checklist

### Sign Up Flow
- [ ] Navigate to `/custom-signup`
- [ ] Fill in all fields
- [ ] Submit form
- [ ] Verify data in Supabase `pending_users` table
- [ ] Check email (if configured)

### Requestor Approval
- [ ] Login as admin
- [ ] Navigate to `/admin/requestor-approval`
- [ ] See pending accounts
- [ ] Approve an account
- [ ] Verify status changed to "approved"
- [ ] Check email received approval notification

### Rejection
- [ ] From approval dashboard
- [ ] Click "Reject" on pending account
- [ ] Enter rejection reason
- [ ] Verify status changed to "rejected"
- [ ] Check email received rejection notification

### Clerk Login
- [ ] Navigate to `/clerk-login`
- [ ] Sign in with Clerk credentials
- [ ] Verify redirect to dashboard

---

## 7. Email Templates

All email templates are generated in `EmailController.php`:

### Invitation Email
- Sender name and organization
- Invitation link with token
- Call-to-action button
- Support contact info

### Approval Email
- Account approved message
- Login link
- Welcome message
- Support contact info

### Rejection Email
- Rejection notification
- Reason for rejection (optional)
- Support contact info

---

## 8. Environment Variables Summary

```env
# Clerk
VITE_CLERK_PUBLISHABLE_KEY=pk_test_...
CLERK_SECRET_KEY=sk_test_...

# Supabase
VITE_SUPABASE_URL=https://vwvoefadwrvsadrpceot.supabase.co
VITE_SUPABASE_ANON_KEY=sb_publishable_...

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@techreserve.com
```

---

## 9. Troubleshooting

### Supabase Connection Issues
- Verify URL and API key in `.env`
- Check Supabase project is active
- Verify RLS policies are configured

### Email Not Sending
- Check mail configuration in Laravel
- Verify SMTP credentials
- Check spam folder
- Review Laravel logs

### Clerk Integration Issues
- Verify publishable key is correct
- Check Clerk dashboard for app setup
- Ensure redirect URLs are configured in Clerk

### Database Errors
- Run migrations in Supabase SQL editor
- Verify table names match exactly
- Check RLS policies are enabled

---

## 10. File Structure

```
frontend/
├── src/
│   ├── pages/
│   │   └── auth/
│   │       ├── ClerkLogin.vue (NEW)
│   │       └── CustomSignUp.vue (NEW)
│   │   └── admin/
│   │       └── RequestorApproval.vue (NEW)
│   ├── services/
│   │   ├── supabaseService.js (NEW)
│   │   └── emailService.js (NEW)
│   └── router/
│       └── routes.js (UPDATED)

backend/
├── app/
│   └── Http/
│       └── Controllers/
│           └── EmailController.php (NEW)
└── routes/
    └── api.php (UPDATED)

database/
└── supabase_migrations.sql (NEW)
```

---

## 11. Next Steps

1. ✓ Create Supabase tables (run SQL migration)
2. ✓ Install Clerk SDK
3. ✓ Configure environment variables
4. ✓ Set up email service
5. ✓ Test sign-up flow
6. ✓ Test approval flow
7. ✓ Test invitation system
8. ✓ Deploy to production

---

## Support

For issues or questions:
- Check Supabase documentation: https://supabase.com/docs
- Check Clerk documentation: https://clerk.com/docs
- Review Laravel Mail documentation: https://laravel.com/docs/mail
