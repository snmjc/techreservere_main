---
description: "Folder-Specific Enforcement Template"
applyTo: "path/glob/**/*"
---

# Folder Rules Template

This file defines localized enforcement rules.

Folder rules override global governance only where explicitly stated.

Do NOT restate global rules unless modifying or tightening them.

No weakening of global discipline allowed.

---

# 1. Architecture Rules

This folder handles:

- (Define purpose clearly)
- (Define responsibility boundary)
- (Define allowed interactions)

This folder must NOT:

- (Define prohibited responsibilities)
- (Define forbidden layer mixing)
- (Define prohibited imports)

Cross-layer calls allowed:

- (List explicitly)

Cross-layer calls prohibited:

- (List explicitly)

No hidden responsibilities allowed.

Purpose must be singular and explicit.

---

# 2. Structural Rules

Files must follow:

- (Define required file pattern)
- (Define naming pattern)
- (Define required subfolder pattern)

Required structure:

- (List required files/folders)
- (List co-location requirements)

Import direction:

Allowed:

- (List explicitly)

Prohibited:

- (List explicitly)

No circular dependencies allowed.

No file dumping allowed.

No structural improvisation allowed.

---

# 3. Naming Doctrine Adjustments (Optional)

If stricter than global rules:

Minimum length:

- Variables → ?
- Functions → ?
- Classes → ?

Additional forbidden names:

- (List explicitly)

If no deviation:

State:
Naming follows global doctrine without modification.

---

# 4. JSDoc / AI Header Enforcement

All exported functions must include:

```js
// ===== AI GENERATED: FunctionName =====
// Purpose:
// Inputs:
// Returns:
// Flow:
// 1.
// 2.
// 3.
```

If modifying:

```js
// ===== AI MODIFIED: FunctionName =====
```

If backend PHP:

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

If this folder has special header requirements, define them here.

Otherwise:

Header enforcement follows global governance.

---

# 5. Validation & Contract Discipline

If this folder defines API boundaries:

- Must use DTO.
- Must not leak Entity.
- Must validate input.
- Must normalize output.

If not applicable:

State:
No API boundary enforcement required.

---

# 6. Testing Requirements

Must have matching test under:

- (Define test path)

Must test:

- (Define success path)
- (Define failure path)
- (Define edge cases)

E2E required: yes / no
Unit required: yes / no
Transaction required: yes / no

If no special testing rules:

State:
Testing follows unified governance without deviation.

---

# 7. Security Enforcement (If Applicable)

If folder interacts with:

- Authentication
- Payment
- External integration
- File upload
- Sensitive data

Define:

- Required validation
- Prohibited behaviors
- Sanitization rules

If not applicable:

State:
No additional security enforcement beyond global governance.

---

# 8. Experimental Overrides (Explicit Only)

Allowed deviation:

- (Define exactly what is temporarily allowed)

Scope:

- (Define limited area)

Conditions:

- Must include comment:
  // EXPERIMENTAL: reason
- Must not violate type discipline
- Must converge to final structure after approval

No permanent experimental code allowed.

---

# 9. No Structural Drift

If this folder defines:

- A specific pattern
- A specific structure
- A specific naming rule

All files inside must replicate it exactly.

No partial implementation allowed.

No improvisation allowed.

---

# 10. Violation Protocol

If AI detects:

- Structural deviation
- Naming violation
- Missing AI header
- Layer mixing
- Contract leakage
- Missing test pairing

AI must:

- STOP
- State violated rule
- Reference section
- Request clarification
- Not proceed

Hard stop by default.
