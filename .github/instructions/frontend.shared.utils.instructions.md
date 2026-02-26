---
description: "Rules for Shared Utility Functions (Frontend)"
applyTo: "frontend/src/shared/utils/**/*"
---

# Shared Utils Rules

This folder defines pure utility functions.

Utilities must be deterministic and side-effect free.

No business logic allowed.

No API calls allowed.

No store mutation allowed.

No router interaction allowed.

---

# 1. Purpose

Shared utilities exist to:

- Transform values
- Format values
- Perform mathematical calculation
- Provide pure helpers
- Encapsulate reusable deterministic logic

They must NOT:

- Call backend
- Call services
- Mutate Pinia store
- Read from store
- Perform business rule branching
- Perform RBAC logic
- Use external SDK
- Maintain hidden state

Utilities must remain pure.

---

# 2. Required Characteristics

Every utility function must:

- Be deterministic
- Not depend on external mutable state
- Not mutate input parameters
- Not cause side effects
- Return predictable output
- Handle edge cases safely

Forbidden:

- Modifying global variables
- Logging inside utility (unless explicitly allowed)
- Throwing unhandled generic error
- Returning inconsistent structure

---

# 3. Naming Doctrine

Function names must:

- Reflect intent
- Be minimum 6 characters
- Avoid vague naming

Good:

formatCurrencyValue
calculatePercentageRatio
sanitizeInputString
generateUniqueIdentifier

Forbidden:

helper
util
process
handle
data
temp
misc

Predictability > brevity.

---

# 4. File Structure Rule

- One primary function per file.
- File name must match function name.
- No multiple unrelated helpers inside one file.
- No dumping many small helpers into a single file.

Example:

formatCurrencyValue.js
calculateDiscountRatio.js

No:

utils.js
helpers.js

---

# 5. JSDoc Enforcement (Mandatory)

Every exported function must include:

```js
/**
 * @function formatCurrencyValue
 * @description Formats numeric value into currency string.
 * @param {number} amountValue
 * @returns {string}
 */
```

All parameters must be typed.

Return type must be declared.

No undocumented function allowed.

---

# 6. AI Header Requirement (Mandatory)

Every exported utility must begin with:

```js
// ===== AI GENERATED: formatCurrencyValue =====
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
// ===== AI MODIFIED: formatCurrencyValue =====
```

Maximum 8 lines.

No silent AI generation allowed.

---

# 7. Input Mutation Prohibition

Utilities must not mutate input parameters.

Forbidden:

```js
function modifyObject(inputObject) {
  inputObject.name = "changed";
}
```

Allowed:

```js
function cloneAndModifyObject(inputObject) {
  return { ...inputObject, name: "changed" };
}
```

Immutability required.

---

# 8. Error Handling Rule

Utilities may:

- Validate input type
- Throw explicit error if misuse

Must NOT:

- Swallow errors silently
- Throw generic untyped error

Error naming must reflect intent.

Example:

InvalidCurrencyFormatException (if needed)

---

# 9. Cross-Layer Prohibition

Forbidden:

- Importing modules/
- Importing services/
- Importing store/
- Importing router/
- Importing backend logic

Allowed:

- Importing other shared/utils only if logically dependent
- Importing shared/constants

No upward dependency allowed.

Shared utils must remain lowest layer.

---

# 10. Testing Requirements

Each utility must have:

frontend/tests/unit/shared/<utility>.test.js

Must test:

- Normal case
- Edge case
- Invalid input
- Boundary case

No utility without unit test.

---

# 11. No Structural Drift

If pattern adopted:

- One function per file
- Named export
- JSDoc
- AI header

All shared utilities must follow identical structure.

No multi-function dumping allowed.

---

# 12. Strict Prohibitions

Forbidden:

- Hidden state variable outside function
- Random value generator without explicit naming
- Date-based implicit logic without input
- Console logging
- Silent try/catch

Utilities must be predictable.

---

# 13. Violation Protocol

If AI detects:

- Side effects inside utility
- Business logic inside utility
- Cross-layer import
- Naming violation
- Missing JSDoc
- Missing AI header
- Structural inconsistency

AI must:

- STOP
- State violated rule
- Request clarification
- Not proceed

Hard stop by default.
