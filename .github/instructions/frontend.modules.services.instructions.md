---
description: "Rules for Domain API Services (Vue Frontend)"
applyTo: "frontend/src/modules/*/services/**/*"
---

# Domain Services Rules

This folder defines API communication logic for a specific domain.

Services are transport adapters.

They must remain thin and predictable.

They must not contain business rules.

---

# 1. Purpose

Domain services exist to:

- Call backend API endpoints
- Send validated payloads
- Normalize backend response
- Map error response to predictable shape
- Return clean object to composables

They must NOT:

- Contain business rule branching
- Contain UI logic
- Mutate Pinia store
- Import components
- Import composables
- Call other domain services
- Use external SDK directly

Services are API-only layer.

---

# 2. Naming Convention

Must follow:

<domain><Action>Service

Examples:

reservationCreateService
reservationFetchListService
paymentProcessService

Forbidden:

fetchData
sendRequest
callApi
handleService

Minimum length:

- Function name → 6 characters
- Variables → 4 characters

Predictability > brevity.

One service per file.
No default export.

---

# 3. Required Structure Pattern

Each service must:

- Accept explicit input object
- Call backend endpoint
- Normalize response
- Throw normalized error
- Return predictable object shape

Return must not be raw axios/fetch response.

Example:

```js
/**
 * @function reservationCreateService
 * @description Sends reservation creation request.
 * @param {Object} requestPayload
 * @param {string} requestPayload.userIdentifier
 * @param {string} requestPayload.itemIdentifier
 * @returns {Promise<Object>}
 */
export async function reservationCreateService(requestPayload) {
```

No implicit return allowed.

---

# 4. AI Header Requirement (Mandatory)

Each exported service must begin with:

```js
// ===== AI GENERATED: reservationCreateService =====
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
// ===== AI MODIFIED: reservationCreateService =====
```

Maximum 8 lines.

No silent AI generation allowed.

---

# 5. API Call Discipline

Services must:

- Use centralized HTTP client (axios or fetch wrapper)
- Not embed base URL inline
- Not hardcode environment logic
- Not duplicate endpoint path across files
- Use consistent error mapping

Forbidden:

fetch('/api/reservation')

Allowed:

httpClient.post('/reservation', requestPayload)

Endpoint prefix must be centralized.

---

# 6. Response Normalization Rule

Service must:

- Extract meaningful data
- Map backend field names if necessary
- Avoid leaking raw backend structure

Bad:

return response;

Good:

return {
reservationIdentifier: response.data.reservationIdentifier,
statusLabel: response.data.statusLabel
};

Frontend must depend on stable shape.

---

# 7. Error Handling Rule

Service must:

- Catch HTTP error
- Normalize error message
- Throw predictable error object
- Avoid leaking stack trace

Example:

throw {
errorCode: 'ReservationCreateFailed',
errorMessage: 'Unable to create reservation'
};

Never throw raw axios error.

---

# 8. No Business Logic Rule

Forbidden:

if (amountValue > 1000) applyDiscount()

if (role === 'admin') changeFlow()

Business logic belongs in backend.

Service must remain transport only.

---

# 9. Cross-Domain Prohibition

Forbidden:

- Importing other domain services
- Importing store
- Importing composables
- Importing router
- Importing components

Service must remain isolated.

If cross-domain needed → backend must aggregate.

---

# 10. Import Direction

Allowed:

- services → shared/httpClient
- services → shared/utils
- services → domain/contracts

Forbidden:

- services → components
- services → composables
- services → store
- services → other domain modules
- services → external SDK directly

External SDK must be wrapped in shared/services.

---

# 11. JSDoc Enforcement (Mandatory)

All exported services must include:

- @function
- @description
- @param
- @returns

No undocumented service allowed.

No implicit Promise return.

---

# 12. Testing Requirements

Each service must have:

frontend/tests/unit/<domain>/<service>.test.js

Must test:

- Successful response mapping
- Error normalization
- Return structure shape
- Edge case handling

Mock HTTP client.

No real API calls in unit test.

---

# 13. Structural Discipline

If service pattern adopted:

- One function per file
- Explicit naming
- Normalized return
- JSDoc
- AI header

All domain services must replicate identical structure.

No improvisation.

---

# 14. Violation Protocol

If AI detects:

- Business logic inside service
- Store mutation
- Cross-domain import
- Missing JSDoc
- Missing AI header
- Naming violation
- Raw response leakage
- Structural inconsistency

AI must:

- STOP
- State violated rule
- Request clarification
- Not proceed

Hard stop by default.
