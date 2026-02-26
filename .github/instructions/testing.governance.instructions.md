---
description: "Unified Testing Governance (Symfony + Vue Strict Modular Architecture)"
applyTo: "backend/tests/**/*, frontend/tests/**/*"
---

# Unified Testing Governance

Testing enforces architecture integrity.

No feature is considered complete without required test pairing.

Testing must validate:

- Business logic correctness
- Layer separation integrity
- RBAC enforcement
- Transaction safety
- API contract stability
- Frontend state behavior
- Composable correctness

---

# 1. Testing Philosophy

Tests must:

- Validate behavior
- Validate boundaries
- Validate failure paths
- Validate predictable outputs
- Fail if contract changes

Tests must NOT:

- Duplicate business logic
- Replace architecture enforcement
- Depend on uncontrolled state
- Rely on external live systems

---

# 2. Backend Testing Structure (Symfony)

backend/tests/

Required structure:

- Unit/
- Feature/
- Transaction/
- Integration/

No flat test dumping.

---

## 2.1 Unit Tests

Location:
backend/tests/Unit/

Test:

- Domain Service logic
- Pure transformation logic
- Exception handling behavior

Must NOT:

- Touch database
- Call Infrastructure SDK
- Call HTTP layer

Mock repository and infrastructure.

---

## 2.2 Feature Tests

Location:
backend/tests/Feature/

Test:

- Controller behavior
- Middleware behavior
- RBAC enforcement
- Response structure

Must validate:

- 200 success case
- 401 unauthorized
- 403 forbidden
- Validation failure
- Edge case handling

Feature tests must use test database.

---

## 2.3 Transaction Tests

Location:
backend/tests/Transaction/

Test:

- Multi-step write operations
- Atomicity
- Rollback behavior
- Idempotency

Each transactional test must validate:

1. Success commit
2. Forced failure
3. Rollback integrity
4. No partial write remains

No silent write allowed.

---

## 2.4 Integration Tests

Location:
backend/tests/Integration/

Test:

- Infrastructure adapters
- Payment wrapper
- Mail wrapper
- Token validation adapter

Must mock external SDK.
Must not call live Xendit or Resend.

Response normalization must be validated.

---

# 3. Frontend Testing Structure (Vue)

frontend/tests/

Required structure:

- unit/
- feature/
- e2e/

No flat test dumping.

---

## 3.1 Unit Tests (Vitest)

Location:
frontend/tests/unit/

Test:

- Composables
- Utility functions
- Store logic
- Shared services

Must validate:

- Loading state
- Error state
- Return structure predictability

Must NOT:

- Call real API
- Depend on router
- Mutate global state implicitly

---

## 3.2 Feature Tests

Location:
frontend/tests/feature/

Test:

- Page-level behavior
- Store integration
- Route guard logic
- Component interaction

Must validate:

- Authorized flow
- Unauthorized flow
- Error rendering
- Conditional rendering correctness

No shallow test allowed for logic-heavy components.

---

## 3.3 E2E Tests (Playwright)

Location:
frontend/tests/e2e/

Test:

- Full user flow
- Authentication flow
- RBAC enforcement
- Payment flow (mocked)
- Critical domain workflow

Must validate:

- Page load
- Form submission
- Redirect behavior
- Error fallback
- Access denial

Must not test trivial UI spacing.

E2E validates integration, not micro logic.

---

# 4. API Contract Stability Rule

Every backend endpoint must have:

- Response structure test
- Error structure test

If DTO changes → test must fail.

Frontend must assume contract stability.

No undocumented API shape allowed.

---

# 5. Naming Doctrine (Testing)

Minimum length:

- Test functions → 6 characters
- Variables → 4 characters

Test description must reflect scenario intent.

Bad:
it("works")

Good:
it("shouldRejectUnauthorizedReservationCreation")

No vague test descriptions allowed.

---

# 6. AI Header Enforcement (Test Code)

Every test file must include top header:

```js
// ===== AI GENERATED TEST: <Domain/Feature> =====
// Purpose:
// Scope:
// Covers:
// 1.
// 2.
// 3.
```

No silent AI-generated tests allowed.

If modifying:

```js
// ===== AI MODIFIED TEST: <Domain/Feature> =====
```

---

# 7. Coverage Expectations

Mandatory coverage:

Backend:

- All Domain Services
- All Middleware branches
- All Transaction paths
- All Infrastructure adapters

Frontend:

- All composables
- All store actions
- All route guards
- All critical flows

Untested business logic = incomplete feature.

---

# 8. Strict Prohibitions

Forbidden:

- Test depending on real external API
- Test relying on production DB
- Test without isolation
- Test without failure case
- Silent exception swallowing

All negative paths must be tested.

---

# 9. Layer Enforcement via Testing

Tests must detect:

- Entity leaking outside domain
- Raw SDK response exposure
- Missing DTO transformation
- Role logic inside component
- Business logic inside infrastructure

If architectural violation exists → test must fail.

Testing enforces discipline.

---

# 10. CI Enforcement

No merge allowed if:

- Backend tests fail
- Frontend tests fail
- Coverage below agreed threshold
- Build fails
- Lint fails

Minimum expected coverage:

- 85% business logic coverage
- 100% RBAC branch coverage
- 100% transaction branch coverage

---

# 11. No Structural Drift

If structure adopted:

backend/tests/
frontend/tests/

All future tests must replicate same grouping.

No ad-hoc test placement.

No scattered structure.

---

# 12. Violation Protocol

If AI detects:

- Missing negative test case
- Missing AI header
- Naming violation
- Architectural leak
- Untested branch
- Direct SDK call in test without mocking

AI must:

- STOP
- State violated rule
- Request clarification
- Not proceed

Hard stop by default.

---
