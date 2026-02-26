---
description: "Rules for Symfony Infrastructure Layer (External Systems & Adapters)"
applyTo: "backend/src/Infrastructure/**/*"
---

# Backend Infrastructure Rules

This folder defines all external integrations and technical adapters.

Infrastructure provides capability.

Infrastructure does NOT define business rules.

---

# 1. Purpose

Infrastructure exists to:

- Wrap external SDKs
- Normalize external responses
- Provide technical adapters
- Encapsulate transport logic
- Encapsulate third-party services

It must never contain domain logic.

---

# 2. Required Structure

backend/src/Infrastructure/

Subfolders must reflect integration concern:

- Payment/
- Mail/
- Auth/
- Pdf/
- Barcode/
- Graph/
- Cache/
- Storage/ (if needed)

No dumping ground folder allowed.

Each external integration must have its own subfolder.

Example:

Payment/

- XenditClient.php
- XenditWebhookVerifier.php

No mixing multiple vendors in one file.

---

# 3. Responsibilities

Infrastructure MAY:

- Call external SDK
- Perform HTTP client calls
- Map external response to internal shape
- Normalize data
- Throw integration-specific exception
- Log transport-level errors

Infrastructure MUST NOT:

- Enforce business rules
- Perform domain branching logic
- Perform role-based decisions
- Perform cross-domain orchestration
- Return Doctrine Entity
- Know about frontend logic

Infrastructure is a capability provider only.

---

# 4. Naming Doctrine

Minimum Length:

- Classes → 6 characters
- Methods → 6 characters
- Variables → 4 characters

Naming must reflect vendor and capability.

Bad:
Client.php

Good:
XenditPaymentClient.php

Bad:
send()

Good:
sendTransactionalEmail()

Forbidden names:

- data
- item
- obj
- val
- temp
- misc
- helper (unless vendor-qualified)

Predictability > brevity.

---

# 5. AI Header Requirement (Mandatory)

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

If modifying:

```php
// ===== AI MODIFIED: MethodName =====
```

No silent AI generation allowed.

Maximum 8 lines in header.

---

# 6. Integration Wrapping Rule

External SDK must NEVER be used directly inside:

- Domain Service
- Controller
- Repository
- Entity

Instead:

Domain Service → Infrastructure Adapter → SDK

Example:

ReservationPaymentService
↓
XenditPaymentClient
↓
Xendit SDK

Strict layering required.

---

# 7. Response Normalization Rule

Infrastructure must never leak raw SDK response.

Example:

Bad:
return $xenditResponse;

Good:
return [
'paymentIdentifier' => $xenditResponse['id'],
'paymentStatus' => $xenditResponse['status'],
'amountValue' => $xenditResponse['amount'],
];

All outputs must be normalized.

No vendor-specific naming leaks.

---

# 8. Exception Handling Rule

Infrastructure must:

- Catch SDK exception
- Map to Integration-specific exception
- Never throw raw SDK exception upward

Example:

XenditPaymentFailedException

No generic Exception.

---

# 9. Authentication Integration (Clerk)

Auth layer must:

- Verify token
- Decode token
- Return normalized user identity object

Must not:

- Assign roles
- Perform permission logic
- Trust frontend claims

Role mapping belongs to Domain layer.

---

# 10. Payment Rule (Xendit)

Must live inside:

Infrastructure/Payment/

Must:

- Create payment
- Verify webhook signature
- Normalize response
- Throw integration-specific exception

Must not:

- Mark order as paid (Domain responsibility)
- Modify database
- Decide business rule

---

# 11. Email Rule (Resend)

Must live inside:

Infrastructure/Mail/

Must:

- Send email
- Normalize response
- Handle API failure
- Return success flag

Must not:

- Decide when email is sent
- Decide template logic

Template decision belongs to Domain.

---

# 12. PDF Rule

If official document:

- Generate PDF server-side.
- Return binary stream or file response.
- No business decision inside PDF builder.

If dynamic report:

- Domain provides structured data.
- Infrastructure renders PDF.

Infrastructure does not decide report logic.

---

# 13. Barcode Rule

If barcode generation:

- Infrastructure may generate server barcode.
- Must return normalized format.

If scanning:

- Frontend only.
- Backend must not perform scanning.

---

# 14. Graph Rule

Graph data transformation must:

- Accept structured domain data.
- Return normalized graph dataset.
- Not perform aggregation decisions.

Aggregation belongs to Domain Service.

---

# 15. Logging Rule

Infrastructure may:

- Log transport errors
- Log integration failure

Must not:

- Log domain event meaningfully
- Log business rule evaluation

Domain decides business logging.

---

# 16. No Structural Drift

If one integration defines:

VendorClient.php
VendorException.php
VendorVerifier.php

All integrations must replicate consistent structure.

No ad-hoc file naming.

---

# 17. Violation Protocol

If AI detects:

- Business rule inside Infrastructure
- SDK usage inside Domain
- Raw response leakage
- Naming violation
- Missing AI header
- Structural inconsistency

AI must:

- STOP
- State violated rule
- Request clarification
- Not proceed

Hard stop by default.
