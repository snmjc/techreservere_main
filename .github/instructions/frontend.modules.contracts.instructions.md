---
description: "Rules for Domain API Contracts (Frontend)"
applyTo: "frontend/src/modules/*/contracts/**/*"
---

# Domain Contracts Rules (Frontend)

This folder defines request and response contracts for a domain.

Contracts enforce API shape stability.

They exist to mirror backend DTO boundaries.

They must remain logic-free.

---

# 1. Purpose

Domain contracts exist to:

- Define request payload shape
- Define response payload shape
- Prevent API contract drift
- Document API expectations
- Provide JSDoc typedef consistency

They must NOT:

- Contain business logic
- Perform validation branching
- Call API
- Mutate store
- Import composables
- Import services
- Import router
- Import other domain modules

Contracts define shape only.

---

# 2. Required Structure

frontend/src/modules/<domain>/contracts/

One file per contract group.

Examples:

reservationRequest.contract.js
reservationResponse.contract.js

Or:

reservation.contract.js (if grouped logically)

File name must reflect domain.

Forbidden:

contract.js
types.js
data.js

Predictability > brevity.

---

# 3. JSDoc Typedef Enforcement (Mandatory)

All contracts must use JSDoc typedef.

Example:

```js
/**
 * @typedef {Object} ReservationCreateRequest
 * @property {string} userIdentifier
 * @property {string} itemIdentifier
 */

/**
 * @typedef {Object} ReservationCreateResponse
 * @property {string} reservationIdentifier
 * @property {string} statusLabel
 */
```

No inline anonymous object typing allowed.

No dynamic shape allowed.

---

# 4. Naming Doctrine

Must follow:

<Domain><Action>Request <Domain><Action>Response

Examples:

ReservationCreateRequest
ReservationCreateResponse

Minimum length:

- Contract name → 6 characters
- Field name → 4 characters

Forbidden field names:

- data
- item
- obj
- val
- temp
- misc

Bad:
id

Good:
reservationIdentifier

Field naming must reflect intent.

---

# 5. Contract Stability Rule

If backend DTO changes:

- Frontend contract must be updated.
- Service normalization must be updated.
- Tests must fail until aligned.

No silent contract drift allowed.

Any contract modification must include comment:

```js
// CONTRACT CHANGE: explanation
```

No undocumented change.

---

# 6. Validation Rule

Frontend contracts must NOT:

- Replicate backend business validation logic
- Perform conditional validation logic
- Contain schema enforcement logic

Validation belongs to backend.

Frontend may perform basic UI validation separately.

Contracts only define shape.

---

# 7. Service Alignment Rule

Services must:

- Use contract typedef
- Return object matching Response contract
- Accept object matching Request contract

Service must not invent fields not in contract.

If transformation required → must map explicitly.

No direct spreading of response.

---

# 8. Cross-Domain Prohibition

Forbidden:

- Importing other domain contracts
- Extending other domain contract
- Combining multiple domain contracts in one file

Each domain owns its contracts.

If aggregation required → backend must define aggregated endpoint.

No frontend contract merging.

---

# 9. Import Direction

Allowed:

- services → contracts
- composables → contracts (type reference only)
- store → contracts (type reference only)

Forbidden:

- contracts → services
- contracts → composables
- contracts → store
- contracts → router
- contracts → components

Contracts must remain isolated.

---

# 10. Testing Requirements

Each contract must be indirectly validated by:

- Service test asserting response shape
- Feature test asserting UI consumes expected shape

Optional:

frontend/tests/unit/<domain>/<contract>.test.js

If contract contains complex nested structures.

No contract without test pairing at service level.

---

# 11. AI Header Requirement

Contracts file does NOT require AI header for typedef blocks.

If AI modifies contract:

Add at top:

```js
// ===== AI MODIFIED CONTRACT: ReservationCreateResponse =====
```

No silent contract modification allowed.

---

# 12. No Structural Drift

If contract structure adopted:

Request typedef
Response typedef

All domain contracts must follow identical format.

No mixed export style.

No default export.

No runtime code allowed in contract file.

Contracts must contain typedef only.

---

# 13. Violation Protocol

If AI detects:

- Business logic inside contract
- Validation logic inside contract
- API call inside contract
- Cross-domain import
- Naming violation
- Silent contract change
- Structural inconsistency

AI must:

- STOP
- State violated rule
- Request clarification
- Not proceed

Hard stop by default.

---
