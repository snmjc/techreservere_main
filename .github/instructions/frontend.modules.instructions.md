---
description: "Rules for Vue modular domain architecture (Composition API + Pinia)"
applyTo: "frontend/src/modules/**/*"
---

# Frontend Modules Rules

This folder defines all domain-level frontend modules.

Each module represents one business domain.

Modular implementation is mandatory.

Flat architecture is forbidden.

---

# 1. Architecture Rules

Each module must live under:

frontend/src/modules/<domain>/

Required structure:

frontend/src/modules/<domain>/

- components/
- composables/
- services/
- contracts/
- store/

No additional random folders allowed.

If one module adopts this structure → all modules must follow it.

No partial modular implementation allowed.

---

# 2. Layer Responsibilities

## components/

- Presentation only.
- No API calls.
- No business rules.
- No direct store mutation.
- May consume composables.
- May consume store state.
- Must receive props explicitly defined.

---

## composables/

- Encapsulate local UI state logic.
- Wrap service calls.
- Manage loading and error state.
- No business rule branching.
- No cross-domain service calls.
- No direct external SDK usage.

Must follow naming:

use<Domain><Action>Composable

Example:
useReservationCreateComposable

One composable per file.

No default exports.

---

## services/

- API communication only.
- Must call backend endpoints.
- Must not contain business rule logic.
- Must not contain UI logic.
- Must normalize API response shape.
- Must not expose raw response blindly.

Naming:

<domain><Action>Service

Example:
reservationCreateService

No generic names allowed.

---

## contracts/

- Define JSDoc type contracts.
- Define response shapes.
- Define payload shapes.
- No logic allowed.
- No API calls.
- No store logic.

Must export documented objects or JSDoc typedef blocks.

---

## store/

- One Pinia store per domain.
- No mega-store.
- State must be explicitly structured.
- No business rule branching.
- No API calls unless explicitly domain-global.
- No cross-domain state mutation.

Naming:

use<Domain>Store

Example:
useReservationStore

---

# 3. Strict Separation Rules

Prohibited:

- components → services directly
- components → external SDK
- composables → external SDK
- store → other domain store directly
- service → store mutation
- composables → direct state mutation outside store
- cross-domain service chaining

Allowed:

- components → composables
- components → store
- composables → services
- composables → store
- services → fetch/axios only

No layer mixing allowed.

---

# 4. JavaScript + JSDoc Enforcement

TypeScript is not used.

Therefore:

- All exported functions must have JSDoc.
- All parameters must be typed.
- All return types must be declared.
- No implicit return.
- No untyped dynamic object larger than 2 properties.
- No undocumented objects.

Example:

```js
/**
 * @function reservationCreateService
 * @description Sends create reservation request to API.
 * @param {Object} requestPayload
 * @param {string} requestPayload.userIdentifier
 * @param {string} requestPayload.itemIdentifier
 * @returns {Promise<Object>}
 */
export async function reservationCreateService(requestPayload) {
```

Missing JSDoc = invalid output.

---

# 5. Naming Doctrine (Mandatory)

Minimum Length:

- Variables → 4 characters
- Functions → 6 characters
- Stores → 6 characters
- Services → 6 characters

Forbidden:

- data
- item
- obj
- val
- temp
- misc
- handler (unless domain-qualified)

Names must reflect domain intent.

Bad:
fetchData()

Good:
reservationFetchService()

Loop short names allowed only with inline comment.

---

# 6. AI Header Enforcement

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

If modifying:

```js
// ===== AI MODIFIED: FunctionName =====
```

No silent generation allowed.

---

# 7. State Management Rules (Pinia)

Store must:

- Declare explicit state shape.
- Avoid dynamic property injection.
- Avoid nested mutation chaos.
- Avoid hidden side effects.

No direct mutation outside defined actions.

Store actions must have JSDoc.

---

# 8. Error Handling Standard

Composables must:

- Declare loading state.
- Declare error state.
- Reset error before retry.
- Never swallow error silently.
- Return predictable structure.

Return pattern:

{
loadingState,
errorMessage,
executeAction
}

No structural deviation allowed.

---

# 9. External Dependency Wrapping

Barcode:
Must be wrapped under shared/services/barcodeScannerService.js

Graph libraries:
Must be wrapped inside shared/components/graphs/

No domain module may directly import chart library.

No direct external SDK usage in domain.

---

# 10. No Structural Drift

If one module defines:

components/
composables/
services/
contracts/
store/

All modules must replicate exactly.

No folder skipping.
No folder renaming.
No structure improvisation.

---

# 11. Violation Protocol

If AI detects:

- Missing JSDoc
- Naming violation
- Cross-layer call
- Missing AI header
- Service logic inside component
- Structural inconsistency

AI must:

- STOP
- State violated rule
- Request clarification
- Not proceed

Hard stop by default.
