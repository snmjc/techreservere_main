---
description: "Rules for Symfony Domain Modular Architecture"
applyTo: "backend/src/Domain/**/*"
---

# Backend Domain Rules (Symfony API)

This folder defines all business domains.

Each domain must be fully isolated.

Flat architecture is forbidden.

---

# 1. Required Structure

Each domain must follow:

backend/src/Domain/<Module>/

- Controller/
- Service/
- Repository/
- DTO/
- Entity/
- Contract/

No extra random folders allowed.

If one domain adopts this structure → all domains must replicate exactly.

No structural drift allowed.

---

# 2. Layer Responsibilities

## Controller/

- Handles HTTP request only.
- No business logic.
- No database query.
- No entity exposure.
- Must call Service layer.
- Must return DTO or Contract only.
- Must not call Repository directly.

Controller is orchestration entry point only.

---

## Service/

- Contains business rule logic.
- Orchestrates Repository.
- May call Infrastructure.
- May call another domain only via Contract.
- Must not contain SQL.
- Must not contain raw Doctrine query.
- Must not return Entity directly.
- Must transform Entity → DTO/Contract.

Naming:

<Domain><Action>Service

Example:
ReservationCreateService

No generic service names allowed.

---

## Repository/

- Database access only.
- Doctrine queries only.
- No business rule branching.
- No logging.
- No Infrastructure call.
- No response formatting.
- Must not return raw arrays blindly.
- Must return Entity or defined result shape.

Naming:

<Domain>Repository

Example:
ReservationRepository

Repository must be persistence only.

---

## DTO/

- Define input and output transport objects.
- No logic allowed.
- No database access.
- No service orchestration.
- Must explicitly define public properties.

DTO must be used for API request and response boundaries.

---

## Entity/

- Doctrine entity only.
- No service logic.
- No Infrastructure calls.
- No cross-domain logic.
- Getter/Setter only.
- No hidden side effects.

---

## Contract/

- Defines response shape.
- Defines inter-domain communication shape.
- No logic.
- No Doctrine.
- No Infrastructure.

Contracts are domain communication boundary.

---

# 3. Strict Separation Rules

Prohibited:

- Controller → Repository direct
- Controller → Infrastructure direct
- Repository → Service
- Entity → Service
- Domain → Frontend logic
- Domain → Another Domain Service directly

Allowed:

- Controller → Service
- Service → Repository
- Service → Infrastructure
- Service → Contract
- Repository → Entity

No layer mixing allowed.

---

# 4. Naming Doctrine (Mandatory)

Minimum Length:

- Variables → 4 characters
- Methods → 6 characters
- Classes → 6 characters
- DTOs → 6 characters

Forbidden names:

- data
- item
- obj
- val
- temp
- misc
- handler
- manager (unless domain-qualified)

Names must reflect domain meaning.

Bad:
process()

Good:
processReservationPayment()

Loop short names allowed only with inline comment.

Predictability > brevity.

---

# 5. AI Header Enforcement

Every public method must begin with:

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

If modifying existing method:

```php
// ===== AI MODIFIED: MethodName =====
```

Rules:

- Must not exceed 8 lines.
- Must explain logic tree.
- Must exist before implementation.

No silent AI generation allowed.

---

# 6. Business Logic Enforcement

Service layer must:

- Validate input DTO.
- Enforce domain rules.
- Call repository.
- Transform entity to DTO.
- Return predictable object.

Service must not:

- Leak entity.
- Return Doctrine collection directly.
- Perform raw SQL.
- Perform cross-domain hidden chaining.

---

# 7. Infrastructure Usage Rule

External systems must be called only through:

backend/src/Infrastructure/

Examples:

- Xendit → Infrastructure/Payment/
- Resend → Infrastructure/Mail/
- Clerk verification → Infrastructure/Auth/
- PDF generator → Infrastructure/Pdf/

Domain must not import SDK directly.

Domain must depend on abstraction.

No SDK usage inside Domain layer.

---

# 8. Response Integrity Rule

All API responses must:

- Use DTO or Contract.
- Have explicit property naming.
- Never expose Entity directly.
- Never expose internal IDs without intent.
- Never expose internal database structure.

Predictable response shape required.

---

# 9. Security Enforcement

- All controllers must verify authentication.
- Role validation must be middleware-based.
- No inline string role comparison inside controller.
- Sensitive logic must live in service.

No trust in frontend role claim.

---

# 10. Error Handling Standard

Service must:

- Throw domain-specific exception.
- Not throw generic Exception blindly.
- Not swallow errors.
- Map Infrastructure errors explicitly.

Exception naming must reflect domain.

Example:
ReservationNotFoundException

No generic "ErrorException".

---

# 11. No Structural Drift

If one domain defines:

Controller/
Service/
Repository/
DTO/
Entity/
Contract/

All domains must replicate exactly.

No partial adoption.
No renaming layers.
No improvisation.

---

# 12. Violation Protocol

If AI detects:

- Repository containing business logic
- Controller calling Repository directly
- Entity leaking outside domain
- Missing AI header
- Naming violation
- SDK inside Domain
- Structural inconsistency

AI must:

- STOP
- State violated rule
- Request clarification
- Not proceed

Hard stop by default.
