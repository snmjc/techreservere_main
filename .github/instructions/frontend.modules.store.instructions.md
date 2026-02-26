---
description: "Rules for Domain Pinia Stores (Vue Frontend)"
applyTo: "frontend/src/modules/*/store/**/*"
---

# Domain Store Rules (Pinia)

This folder defines domain-level state management.

Each domain must have its own store.

Mega-store architecture is forbidden.

---

# 1. Purpose

Domain store exists to:

- Hold cross-component state
- Expose reactive domain state
- Provide controlled mutation via actions
- Provide getters for derived state

Store must NOT:

- Contain business rule branching
- Call external SDK
- Import other domain stores
- Replace backend validation logic
- Perform complex orchestration logic

Business rules belong to backend.

UI orchestration belongs to composables.

---

# 2. Naming Convention

Store name must follow:

use<Domain>Store

Examples:

useReservationStore
usePaymentStore
useAuthenticationStore

File name must match:

reservationStore.js

Minimum length:

- Store name → 6 characters
- Action name → 6 characters
- State keys → 4 characters

Forbidden:

useStore
mainStore
dataStore
tempStore

Predictability > brevity.

---

# 3. Required Structure Pattern

Each store must:

- Declare explicit state structure
- Declare actions
- Declare getters (if needed)
- Avoid dynamic state injection

Example structure:

```js
/**
 * @typedef {Object} ReservationState
 * @property {Array<Object>} reservationList
 * @property {boolean} loadingState
 * @property {string|null} errorMessage
 */

export const useReservationStore = defineStore("reservationStore", {
  state: () => ({
    reservationList: [],
    loadingState: false,
    errorMessage: null,
  }),
  actions: {
    updateReservationList(reservationList) {
      this.reservationList = reservationList;
    },
  },
});
```

State must be predictable.

No undeclared reactive keys.

---

# 4. AI Header Requirement (Mandatory)

Each store file must begin with:

```js
// ===== AI GENERATED: useReservationStore =====
// Purpose:
// State Shape:
// Actions:
// Flow:
// 1.
// 2.
// 3.
```

If modified:

```js
// ===== AI MODIFIED: useReservationStore =====
```

Maximum 8 lines.

No silent AI generation allowed.

---

# 5. API Call Discipline

Store must NOT:

- Call API directly
- Use fetch/axios directly
- Embed endpoint URL

If domain-global API required:

Composable must call service.
Composable must call store action.

Pattern:

Component → Composable → Service → Store Action

No skipping layer.

---

# 6. Mutation Discipline

All state updates must happen inside actions.

Forbidden:

store.reservationList = newValue outside action.

Forbidden:

Direct state mutation from component.

Only allowed:

store.updateReservationList(newValue)

---

# 7. Business Logic Prohibition

Forbidden inside store:

if (amountValue > 1000) applyDiscount()

if (role === 'admin') modifyState()

Business branching must live in backend.

Store only holds state.

---

# 8. Getter Discipline

Getters may:

- Compute derived UI-level state
- Filter list for presentation

Getters must NOT:

- Perform business validation
- Perform role decision
- Trigger API call

Getters must be pure.

---

# 9. Cross-Domain Prohibition

Forbidden:

- Importing other domain store
- Mutating other domain store
- Calling other domain actions

If cross-domain state required → redesign backend API.

No frontend domain coupling.

---

# 10. Error Handling Rule

Store may:

- Hold errorMessage
- Reset errorMessage
- Expose error getter

Store must NOT:

- Swallow error silently
- Perform error branching logic
- Throw raw error

Error mapping belongs to service/composable.

---

# 11. JSDoc Enforcement

All stores must:

- Define state typedef
- Document actions with JSDoc
- Document return types

Example:

```js
/**
 * @function updateReservationList
 * @param {Array<Object>} reservationList
 * @returns {void}
 */
```

No undocumented action allowed.

---

# 12. Testing Requirements

Each store must have:

frontend/tests/unit/<domain>/<store>.test.js

Must test:

- Initial state correctness
- Action mutation correctness
- Getter correctness
- Edge case mutation

No store without unit test.

---

# 13. Structural Discipline

If pattern adopted:

- Explicit state
- Named actions
- JSDoc
- AI header

All domain stores must replicate identical structure.

No dynamic store structure allowed.

No partial implementation.

---

# 14. Import Direction

Allowed:

- store → shared/utils
- store → shared/constants

Forbidden:

- store → services
- store → composables
- store → components
- store → router
- store → other domain store

Store must remain isolated.

---

# 15. Violation Protocol

If AI detects:

- API call inside store
- Business logic branching
- Cross-domain import
- Direct state mutation outside action
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
