---
description: "Rules for Symfony Middleware (Authentication + RBAC Enforcement)"
applyTo: "backend/src/Middleware/**/*"
---

# Backend Middleware Rules (Symfony API)

This folder defines request-level guards.

Middleware exists to enforce authentication and access control.

Middleware must NOT contain business logic.

---

# 1. Purpose

Middleware is responsible for:

- Verifying authentication token
- Extracting user identity
- Enforcing RBAC rules
- Blocking unauthorized access
- Injecting normalized identity into request context

Middleware must not:

- Query database
- Perform business rule branching
- Call repository
- Call domain service
- Perform payment logic
- Perform email logic

Middleware is gatekeeping only.

---

# 2. Required Structure

backend/src/Middleware/

Required pattern:

- AuthenticationMiddleware.php
- AuthorizationMiddleware.php
- RoleResolver.php

If more guards added → must follow same pattern.

No random middleware files allowed.

No mixing authentication and business logic.

---

# 3. AuthenticationMiddleware

Responsibilities:

- Extract Clerk token from header
- Validate token using Infrastructure/Auth layer
- Normalize user identity
- Attach identity to request context
- Reject invalid token

Must NOT:

- Assign role
- Perform permission decision
- Trust frontend role claim blindly
- Query database

Naming:

AuthenticationMiddleware

No generic names allowed.

---

# 4. AuthorizationMiddleware

Responsibilities:

- Read normalized user identity
- Read route metadata (required roles)
- Validate allowed roles
- Reject unauthorized role

Must NOT:

- Perform business rule logic
- Check database
- Modify request body
- Call domain service

RBAC enforcement only.

---

# 5. RoleResolver

Responsibilities:

- Map normalized identity to internal role
- Normalize role naming
- Provide predictable role format

Must NOT:

- Call database
- Call service layer
- Contain branching business logic

Role mapping must remain simple.

---

# 6. Naming Doctrine (Mandatory)

Minimum length:

- Classes → 6 characters
- Methods → 6 characters
- Variables → 4 characters

Forbidden names:

- data
- item
- obj
- val
- temp
- misc
- handler (unless domain-qualified)

Method names must reflect intent.

Bad:
check()

Good:
validateAccessPermission()

Predictability > brevity.

---

# 7. AI Header Requirement (Mandatory)

Every public method must start with:

```php
// ===== AI GENERATED: MethodName =====
// Purpose:
// Inputs:
// Returns:
// Flow:
// 1.
// 2.
// 3.
```

````

If modifying:

```php
// ===== AI MODIFIED: MethodName =====
```

Maximum 8 lines.

No silent AI generation allowed.

---

# 8. Token Verification Rule (Clerk)

Token validation must:

- Be delegated to Infrastructure/Auth/
- Catch token validation errors
- Return normalized identity
- Never leak raw token payload

No SDK usage inside middleware.

Middleware must call Auth adapter only.

---

# 9. Route Metadata Rule

Controllers must define required roles via attribute or configuration.

Middleware must read:

- Required roles
- Whether authentication required

No inline string comparison like:

if ($role === 'admin')

Role comparison must be centralized.

---

# 10. Security Enforcement

Middleware must:

- Reject unauthorized requests early
- Return proper HTTP status (401 / 403)
- Avoid revealing sensitive information
- Not leak internal exception trace

Security first.

---

# 11. Request Context Injection

After authentication:

- Attach normalized identity to request object
- Use consistent key
- Do not mutate request body payload

Identity must be read-only.

---

# 12. Strict Prohibitions

Forbidden:

- Middleware → Repository
- Middleware → Domain Service
- Middleware → Entity
- Middleware → Infrastructure other than Auth
- Business branching
- Cross-domain logic
- Logging domain event meaning

Middleware is guard layer only.

---

# 13. Error Handling Standard

Authentication failure → 401 Unauthorized
Authorization failure → 403 Forbidden

Must:

- Return predictable error structure
- Not expose internal exception
- Not swallow errors silently

Exception naming must reflect guard intent.

Example:
AuthenticationFailedException
AuthorizationDeniedException

No generic Exception.

---

# 14. No Structural Drift

If structure adopted:

AuthenticationMiddleware.php
AuthorizationMiddleware.php
RoleResolver.php

All middleware must replicate pattern.

No scattered RBAC checks.

No controller-level permission duplication.

---

# 15. Violation Protocol

If AI detects:

- Business rule inside middleware
- Database call inside middleware
- SDK usage inside middleware
- Naming violation
- Missing AI header
- Structural inconsistency

AI must:

- STOP
- State violated rule
- Request clarification
- Not proceed

Hard stop by default.
````
