# Project File Structure (Vue + Symfony Strict Modular Architecture)

Root structure is intentionally simple:

- backend/
- frontend/

No cross-layer mixing.
No shared root-level logic.
No flat architecture allowed.

---

# 1. Backend Structure (Symfony API Only)

backend/

├── src/
│   ├── Domain/
│   │   ├── <Module>/
│   │   │   ├── Controller/
│   │   │   ├── Service/
│   │   │   ├── Repository/
│   │   │   ├── DTO/
│   │   │   ├── Entity/
│   │   │   └── Contract/
│   │
│   ├── Infrastructure/
│   │   ├── Payment/
│   │   ├── Mail/
│   │   ├── Auth/
│   │   ├── Pdf/
│   │   ├── Barcode/
│   │   ├── Graph/
│   │   ├── Cache/
│   │   └── Storage/
│   │
│   ├── Middleware/
│   │   ├── AuthenticationMiddleware.php
│   │   ├── AuthorizationMiddleware.php
│   │   └── RoleResolver.php
│   │
│   └── Shared/
│       ├── Exceptions/
│       ├── Traits/
│       └── Utils/
│
├── config/
├── migrations/
├── public/
├── tests/
│   ├── Unit/
│   ├── Feature/
│   ├── Transaction/
│   └── Integration/
└── composer.json

---

## Backend Layer Responsibilities

Domain/
- Business rules
- Service orchestration
- DTO boundaries
- No SDK usage

Infrastructure/
- External integrations only
- SDK wrappers
- No business branching

Middleware/
- Auth verification
- RBAC enforcement
- No business logic

Shared/
- Generic exceptions
- Reusable traits
- No domain logic

Tests/
- Strict separation by concern
- No test dumping

---

# 2. Frontend Structure (Vue 3 SPA)

frontend/

├── src/
│   ├── modules/
│   │   ├── <domain>/
│   │   │   ├── components/
│   │   │   ├── composables/
│   │   │   ├── services/
│   │   │   ├── contracts/
│   │   │   └── store/
│   │
│   ├── pages/
│   │   ├── <PageName>.vue
│   │
│   ├── router/
│   │   ├── index.js
│   │   ├── routes.js
│   │   └── accessGuard.js
│   │
│   ├── shared/
│   │   ├── components/
│   │   ├── composables/
│   │   ├── services/
│   │   ├── utils/
│   │   ├── constants/
│   │   ├── validators/
│   │   └── graphs/
│   │
│   ├── main.js
│   └── App.vue
│
├── tests/
│   ├── unit/
│   ├── feature/
│   └── e2e/
│
├── public/
├── package.json
└── vite.config.js

---

## Frontend Layer Responsibilities

modules/
- Domain isolation
- API communication via services
- State via Pinia
- No business rule inside components

pages/
- Composition of modules
- No API calls
- No business branching

router/
- Centralized route definition
- Centralized RBAC guard
- No store mutation
- No API call

shared/
- Reusable UI
- Library wrappers
- Utility helpers
- No domain logic

tests/
- Unit: composables + store
- Feature: page behavior
- E2E: full flow validation

---

# 3. Cross-Layer Rules

Backend must NEVER import frontend.
Frontend must NEVER import backend.

No shared code outside defined shared folders.

No cross-domain service chaining without contract mapping.

No raw SDK usage outside Infrastructure or shared/services.

---

# 4. Dependency Placement Summary

Clerk:
- Frontend: token handling
- Backend: token verification (Infrastructure/Auth)

Xendit:
- Backend only (Infrastructure/Payment)

Resend:
- Backend only (Infrastructure/Mail)

Barcode:
- Frontend only (shared/services)

PDF:
- Backend official document generation
- Frontend optional client export wrapper

Graph library:
- Frontend shared/graphs wrapper only

---

# 5. Testing Enforcement Summary

Backend:
- Unit → Service
- Feature → Controller + Middleware
- Transaction → DB atomicity
- Integration → Infrastructure wrapper

Frontend:
- Unit → composables + store
- Feature → page + router
- E2E → critical flows

No feature complete without tests.

---

# 6. Structural Discipline

If one domain adopts:

Controller/
Service/
Repository/
DTO/
Entity/
Contract/

All domains must follow identical structure.

If one frontend module adopts:

components/
composables/
services/
contracts/
store/

All modules must replicate.

No partial modular adoption.

---

# 7. Naming Discipline

Minimum length:

- Variables → 4 characters
- Functions → 6 characters
- Classes → 6 characters

Forbidden generic naming.

Predictability > brevity.

---

# 8. AI Enforcement Reminder

All generated code must:

- Include AI header
- Include JSDoc (frontend)
- Respect modular layering
- Avoid structural drift

Violation = hard stop.