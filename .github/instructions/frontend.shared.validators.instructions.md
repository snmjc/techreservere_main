---
description: "Rules for Shared UI Validators (Frontend)"
applyTo: "frontend/src/shared/validators/**/*"
---

# Shared Validators Rules

This folder defines generic frontend validation helpers.

Validators are for UI-level feedback only.

Backend remains authoritative source of validation truth.

Validators must NOT replicate business rules.

---

# 1. Purpose

Shared validators exist to:

- Validate input format (email, number, string length)
- Validate required field presence
- Provide reusable UI-level validation
- Improve user experience
- Prevent obvious malformed input

They must NOT:

- Enforce business rules
- Enforce role-based logic
- Perform database-dependent validation
- Replace backend validation
- Contain domain branching logic

Frontend validation is advisory only.

Backend validation is authoritative.

---

# 2. Allowed Validator Types

Permitted examples:

- validateRequiredField
- validateEmailFormat
- validateNumericRange
- validateStringLength
- validatePasswordStrength (format only)
- validatePositiveNumber

Forbidden examples:

- validateReservationLimit
- validateAdminAccess
- validatePaymentEligibility
- validateDiscountRule

Business-specific validation must live in backend.

---

# 3. Determinism Requirement

Validators must:

- Be pure functions
- Return predictable result
- Not mutate input
- Not depend on external state
- Not call API
- Not call store
- Not call router

No side effects allowed.

---

# 4. Return Structure Rule

All validators must return predictable structure.

Preferred structure:

{
isValid: boolean,
errorMessage: string | null
}

Example:

```js
{
  isValid: false,
  errorMessage: 'Email format is invalid'
}
```

No returning mixed shapes.

No returning raw boolean only.

Consistency required.

---

# 5. Naming Doctrine

Function name must follow:

validate<Intent>

Examples:

validateEmailFormat
validateRequiredField
validatePositiveNumber

Minimum length:

- Function name → 6 characters
- Variables → 4 characters

Forbidden names:

- check
- verify
- handler
- data
- temp
- misc

Predictability > brevity.

---

# 6. File Structure Rule

- One validator per file.
- File name must match function name.
- No dumping multiple unrelated validators into one file.

Example:

validateEmailFormat.js
validateRequiredField.js

No:

validators.js
helper.js

---

# 7. JSDoc Enforcement (Mandatory)

Each validator must include:

```js
/**
 * @function validateEmailFormat
 * @description Validates that a string matches basic email format.
 * @param {string} emailValue
 * @returns {{ isValid: boolean, errorMessage: string|null }}
 */
```

No undocumented export allowed.

No implicit return type allowed.

---

# 8. AI Header Requirement (Mandatory)

Each validator must begin with:

```js
// ===== AI GENERATED: validateEmailFormat =====
// Purpose:
// Inputs:
// Returns:
// Flow:
// 1.
// 2.
// 3.
```

If modified:

```js
// ===== AI MODIFIED: validateEmailFormat =====
```

Maximum 8 lines.

No silent AI generation allowed.

---

# 9. Backend Alignment Rule

Validators must not:

- Attempt to mirror backend business constraints
- Attempt to enforce backend-only rules
- Attempt to calculate domain logic

If backend validation changes:

Frontend validator may adjust format-level checks only.

Backend remains source of truth.

---

# 10. Cross-Layer Prohibition

Forbidden:

- Importing modules/
- Importing services/
- Importing store/
- Importing router/
- Importing backend logic

Allowed:

- Importing shared/utils
- Importing shared/constants

Shared validators must remain lowest-layer.

---

# 11. Error Message Discipline

Error messages must:

- Be clear
- Be user-facing
- Avoid technical jargon
- Avoid internal rule exposure
- Avoid leaking system detail

No stack trace.
No backend message exposure.

---

# 12. Testing Requirements

Each validator must have:

frontend/tests/unit/shared/<validator>.test.js

Must test:

- Valid input case
- Invalid input case
- Edge case
- Boundary case

No validator without unit test.

---

# 13. No Structural Drift

If pattern adopted:

- One function per file
- Named export
- JSDoc
- AI header
- Consistent return shape

All validators must replicate identical structure.

No deviation allowed.

---

# 14. Violation Protocol

If AI detects:

- Business logic inside validator
- Cross-layer import
- Missing JSDoc
- Missing AI header
- Naming violation
- Structural inconsistency
- Backend rule duplication

AI must:

- STOP
- State violated rule
- Request clarification
- Not proceed

Hard stop by default.
