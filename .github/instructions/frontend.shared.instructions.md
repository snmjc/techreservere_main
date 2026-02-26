---
description: "Rules for Shared Frontend Layer (Vue 3 + Composition API)"
applyTo: "frontend/src/shared/**/*"
---

# Frontend Shared Rules

This folder defines cross-domain reusable logic.

Shared layer provides capability.

Shared layer must NOT contain domain business rules.

---

# 1. Required Structure

frontend/src/shared/

Allowed subfolders:

- components/
- composables/
- services/
- utils/
- constants/
- validators/
- graphs/

No dumping ground folder allowed.

No random file placement at root level.

Each concern must live in proper subfolder.

---

# 2. Purpose of Shared Layer

Shared exists to provide:

- Reusable UI primitives
- Reusable technical services
- External library wrappers
- Generic utility functions
- Graph rendering wrapper
- Barcode scanner wrapper
- Global constants
- Generic validators

Shared must NOT:

- Contain domain-specific logic
- Contain API endpoint logic
- Contain store logic
- Contain business branching
- Import domain modules
- Mutate domain store

Shared must remain domain-agnostic.

---

# 3. components/

- Pure presentational components.
- No API call.
- No store mutation.
- No business branching.
- May receive props.
- Must be reusable across domains.

Naming must reflect UI role.

Example:
BaseCardComponent
PrimaryButtonComponent

No vague names like:
Box
Wrapper
Thing

---

# 4. composables/

- Reusable UI-level logic.
- No API calls.
- No domain-specific behavior.
- No store mutation unless truly global.

Example:
useDebounceComposable
usePaginationComposable

Must follow naming:
use<Intent>Composable

One composable per file.
No default export.

---

# 5. services/

Shared services must wrap external libraries.

Examples:

- barcodeScannerService.js
- graphRendererService.js
- pdfClientExportService.js

Rules:

- Must not contain business logic.
- Must not call backend endpoints.
- Must normalize third-party response.
- Must hide raw SDK usage.

No direct SDK import inside domain modules.

External library must be wrapped here.

---

# 6. graphs/

Graph components must:

- Wrap chart library.
- Accept normalized data only.
- Not aggregate raw domain data.
- Not decide business meaning.

Domain Service must prepare dataset.

Shared graph must render only.

---

# 7. utils/

- Pure functions only.
- No side effects.
- No store mutation.
- No API call.
- No external SDK usage.
- No hidden state.

Functions must be deterministic.

Example:
formatCurrencyValue
calculatePercentageRatio

No generic name allowed.

---

# 8. constants/

- Define reusable constant values.
- No business rules.
- No environment secrets.
- No dynamic values.

Must export named constants.

No default export.

---

# 9. validators/

- Generic validation only.
- No domain schema logic.
- No API validation duplication.
- No backend rule replication.

Domain validation belongs to backend.

---

# 10. Naming Doctrine (Mandatory)

Minimum Length:

- Variables → 4 characters
- Functions → 6 characters
- Components → 6 characters

Forbidden names:

- data
- item
- obj
- val
- temp
- misc
- helper (unless qualified)

Predictability > brevity.

Names must reflect intent.

Bad:
helper.js

Good:
barcodeScannerService.js

---

# 11. JavaScript + JSDoc Enforcement

All exported functions must include JSDoc.

Example:

```js
/**
 * @function formatCurrencyValue
 * @description Formats numeric value to currency string.
 * @param {number} amountValue
 * @returns {string}
 */
export function formatCurrencyValue(amountValue) {
```

No undocumented export allowed.

No implicit return type allowed.

---

# 12. AI Header Enforcement

Every exported function must start with:

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

If modified:

```js
// ===== AI MODIFIED: FunctionName =====
```

No silent AI generation allowed.

Maximum 8 lines.

---

# 13. Strict Prohibitions

Forbidden:

- shared → modules import
- shared → store import
- shared → router import
- shared → business branching
- shared → API call
- shared → secret key exposure

Shared must remain pure and reusable.

---

# 14. No Structural Drift

If structure adopted:

components/
composables/
services/
utils/
constants/
validators/
graphs/

All shared logic must follow this structure.

No extra folders without approval.

No file dumping.

---

# 15. Violation Protocol

If AI detects:

- Domain logic inside shared
- API call inside shared
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
