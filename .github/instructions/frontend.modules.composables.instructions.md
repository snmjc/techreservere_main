---
description: "Rules for Domain-Level Vue Composables (Composition API)"
applyTo: "frontend/src/modules/*/composables/**/*"
---

# Domain Composables Rules

This folder defines domain-level composables.

Composables encapsulate UI-level orchestration.

They are not business rule engines.

They are not service layers.

They are not stores.

---

# 1. Purpose

Domain composables exist to:

- Coordinate API calls via services
- Manage loading state
- Manage error state
- Prepare data for UI consumption
- Interact with domain store safely

They must NOT:

- Contain business rule branching
- Call external SDK
- Perform cross-domain orchestration
- Mutate other domain stores
- Implement RBAC logic
- Contain complex validation logic

Business logic belongs to backend.
UI orchestration belongs here.

---

# 2. Naming Convention

Must follow:

use<Domain><Action>Composable

Examples:

useReservationCreateComposable
useReservationListComposable
usePaymentProcessComposable

Forbidden:

useData
useHandler
useManager
useThing

Minimum length:

- Function name → 6 characters
- Variables → 4 characters

Predictability > brevity.

One composable per file.
No default export.

---

# 3. Required Structure Pattern

Every composable must return predictable structure:

{
loadingState,
errorMessage,
executeAction,
(optional computed values)
}

Example return:

{
loadingState,
errorMessage,
createReservation
}

No structural drift allowed.

---

# 4. JSDoc Enforcement (Mandatory)

All composables must include JSDoc.

Example:

```js
/**
 * @function useReservationCreateComposable
 * @description Handles reservation creation UI orchestration.
 * @returns {Object}
 */
export function useReservationCreateComposable() {
```

All internal helper functions must also include JSDoc.

No undocumented export allowed.

---

# 5. AI Header Requirement (Mandatory)

Every exported composable must begin with:

```js
// ===== AI GENERATED: useReservationCreateComposable =====
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
// ===== AI MODIFIED: useReservationCreateComposable =====
```

Maximum 8 lines.

No silent AI generation allowed.

---

# 6. Service Interaction Rule

Composables may:

- Call domain services
- Await API response
- Normalize error display
- Trigger store action

Composables must NOT:

- Contain API endpoint URL
- Perform fetch/axios directly
- Contain business branching logic
- Chain multiple domain services

Pattern:

Component → Composable → Service → Backend

No skipping layer.

---

# 7. Store Interaction Rule

Composables may:

- Read from domain store
- Call store actions

Composables must NOT:

- Directly mutate store state
- Access other domain store
- Create new store dynamically

Store mutation must go through defined store actions.

---

# 8. Error Handling Standard

Each composable must:

- Declare loadingState (ref)
- Declare errorMessage (ref)
- Reset error before retry
- Catch service error
- Assign normalized error message
- Never swallow exception silently

Error must not leak raw server stack trace.

---

# 9. Business Logic Prohibition

Forbidden:

if (amountValue > 1000) { applyDiscount() }

if (role === 'admin') { ... }

Business logic must live in backend.

Composables may handle UI branching only.

---

# 10. Reactive Discipline

Allowed:

- ref()
- computed()
- watch()

Forbidden:

- Global mutable variables
- Implicit shared state
- Untracked mutation

All reactive state must be explicitly declared.

---

# 11. Cross-Domain Prohibition

Forbidden:

- Importing other domain services
- Importing other domain store
- Importing other domain composables

If cross-domain data needed → backend must provide aggregated endpoint.

No frontend domain coupling.

---

# 12. Import Direction

Allowed:

- composables → services (same domain)
- composables → store (same domain)
- composables → shared/
- composables → router (read-only navigation)

Forbidden:

- composables → components
- composables → other domain modules
- composables → external SDK
- composables → backend code

---

# 13. Testing Requirements

Each composable must have:

frontend/tests/unit/<domain>/<composable>.test.js

Must test:

- Loading state transition
- Error handling
- Successful execution path
- Returned structure shape

No composable without unit test.

---

# 14. No Structural Drift

If composable returns:

{
loadingState,
errorMessage,
executeAction
}

All composables must follow similar structure.

No custom return signature per file.

No partial pattern deviation.

---

# 15. Violation Protocol

If AI detects:

- API call inside composable
- Business rule branching
- Direct store mutation
- Cross-domain import
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
