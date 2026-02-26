---
description: "API Contract Enforcement (DTO + Validation + Response Stability)"
applyTo: "backend/src/Domain/**/Controller/**/*, backend/src/Domain/**/DTO/**/*, backend/src/Domain/**/Contract/**/*"
---

# API Contract Enforcement Rules

All API endpoints must strictly enforce request and response contracts.

No raw entity exposure.
No undocumented response shape.
No silent field addition or removal.

Contract stability is mandatory.

---

# 1. Request Validation Rule

All incoming API requests must:

- Be mapped to DTO.
- Validate required fields.
- Validate data type.
- Reject unknown or malformed fields.
- Reject missing required fields.

Controller must NOT accept raw request array.

Example:

Request → DTO → Service

No direct passing of request payload to Service.

---

# 2. DTO Discipline

DTO must:

- Explicitly define properties.
- Define constructor parameters.
- Not contain business logic.
- Not contain database logic.
- Not contain infrastructure calls.
- Not contain validation branching logic.

DTO represents transport only.

Naming:

<Domain><Action>RequestDTO
<Domain><Action>ResponseDTO

Minimum length rules apply.

No generic DTO name allowed.

---

# 3. Response Contract Rule

Controller must return:

- DTO
  OR
- Contract object

Controller must NOT return:

- Entity
- Doctrine Collection
- Raw array from repository
- Raw Infrastructure response

All responses must be normalized.

---

# 4. Entity Leak Prevention

Service must:

- Transform Entity → ResponseDTO
- Explicitly map properties
- Exclude internal fields

Forbidden:

return $entity;

Forbidden:

return $repositoryResult;

All entity exposure must be controlled.

---

# 5. Contract Version Stability Rule

If response shape changes:

- Test must fail.
- Change must be intentional.
- Versioning must be considered.
- Frontend must be updated.

No silent contract drift allowed.

Adding or removing field requires:

// CONTRACT CHANGE: explanation

---

# 6. Field Naming Doctrine

All API fields must:

- Use consistent naming convention.
- Avoid vendor-specific naming.
- Avoid leaking internal database naming.
- Avoid ambiguous naming.

Bad:
id

Good:
reservationIdentifier

Predictability > brevity.

Minimum length:

- Field name → 4 characters

Forbidden names:

- data
- item
- obj
- val
- temp
- misc

---

# 7. Validation Location Rule

Validation must occur:

- Before service logic.
- At Controller or dedicated validator layer.

Validation must NOT:

- Occur inside Repository.
- Occur inside Infrastructure.
- Occur inside Entity.

Service may assume validated DTO.

---

# 8. Error Response Standard

All error responses must follow consistent shape:

{
errorCode,
errorMessage,
errorDetails (optional)
}

No raw exception message leak.

No stack trace exposure.

Error naming must reflect domain meaning.

Example:
ReservationNotFound
PaymentProcessingFailed

---

# 9. External Response Normalization

Infrastructure adapters must:

- Normalize vendor response.
- Map vendor fields to internal contract.
- Avoid vendor field leakage.

Example:

Bad:
return $xenditResponse;

Good:
return [
'paymentIdentifier' => ...,
'paymentStatus' => ...,
];

Vendor naming must not leak to API.

---

# 10. API Versioning Rule (Forward Compatibility)

If future API versioning introduced:

Must follow:

/api/v1/
/api/v2/

No mixing version logic inside controller.

No silent behavior branching per version.

Version boundary must be explicit.

---

# 11. Strict Prohibitions

Forbidden:

- Returning entity directly
- Returning repository array
- Modifying response shape without contract update
- Inline response building without DTO
- Business logic inside Controller
- Infrastructure response leakage

---

# 12. AI Header Requirement (Mandatory)

All controller public methods must begin with:

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

If modified:

```php
// ===== AI MODIFIED: MethodName =====
```

Maximum 8 lines.

No silent AI generation allowed.

---

# 13. Testing Enforcement

Every API endpoint must have:

- Success test
- Validation failure test
- Unauthorized test (if protected)
- Contract shape assertion test

If DTO changes → test must fail.

No endpoint without test pairing.

---

# 14. No Structural Drift

If pattern adopted:

Controller/
Service/
Repository/
DTO/
Entity/
Contract/

All API boundaries must follow identical discipline.

No controller-level improvisation.

---

# 15. Violation Protocol

If AI detects:

- Entity returned directly
- Missing DTO
- Missing validation
- Naming violation
- Silent contract modification
- Missing AI header
- Raw Infrastructure leakage

AI must:

- STOP
- State violated rule
- Request clarification
- Not proceed

Hard stop by default.
