---
description: "Rules for Vue Router and Centralized Access Control"
applyTo: "frontend/src/router/**/*"
---

# Frontend Router Rules (Vue 3 + Composition API)

This folder defines routing and centralized access control.

Routing is navigation only.

Access control must be centralized.

No RBAC logic allowed inside components.

---

# 1. Required Structure

frontend/src/router/

Required files:

- index.js
- routes.js
- accessGuard.js

No additional routing files unless explicitly approved.

If route complexity increases, must maintain same pattern.

No structural drift allowed.

---

# 2. Responsibilities

## routes.js

- Define route definitions only.
- No logic.
- No inline permission logic.
- No store mutation.
- No API call.

Each route must define:

- path
- name
- component
- meta

Meta must contain:

- requiresAuth (boolean)
- allowedRoles (array or null)

Example:

```js
{
  path: '/reservations',
  name: 'reservationListPage',
  component: () => import('@/pages/ReservationListPage.vue'),
  meta: {
    requiresAuth: true,
    allowedRoles: ['admin', 'staff']
  }
}
```

No role logic inside component.

---

## accessGuard.js

This is centralized RBAC enforcement.

Must:

- Validate authentication state.
- Validate allowedRoles.
- Redirect unauthorized user.
- Never trust UI state blindly.

Must not:

- Render UI.
- Mutate store directly.
- Call API.
- Contain business rules.

Naming must be explicit:

evaluateRouteAccessGuard

No generic names allowed.

---

## index.js

- Initialize router.
- Attach accessGuard.
- Export router instance.
- No business logic.

Must use:

router.beforeEach()

No inline permission logic allowed here.
Must delegate to accessGuard.

---

# 3. Authentication Enforcement

Authentication must:

- Use Clerk token from frontend.
- Validate token presence.
- Read normalized role from store.
- Not trust raw frontend value without backend validation.

Frontend role is advisory only.

Backend remains authority.

---

# 4. Access Evaluation Rules

AccessGuard must evaluate:

1. If route requiresAuth.
2. If user is authenticated.
3. If role is allowed.
4. If denied → redirect to defined fallback page.

No silent navigation failure allowed.

Return predictable boolean or redirect.

---

# 5. Naming Doctrine

Minimum length:

- Functions → 6 characters
- Variables → 4 characters
- Routes → meaningful name

Forbidden:

- data
- item
- obj
- val
- temp
- misc
- handler (unless domain-qualified)

Route names must reflect page intent.

Bad:
list

Good:
reservationListPage

Predictability > brevity.

---

# 6. AI Header Requirement (Mandatory)

Every exported function must begin with:

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

No silent generation allowed.

Maximum 8 lines in header.

---

# 7. Strict Prohibitions

Forbidden:

- Role checks inside component template
- Role checks inside page setup()
- API calls inside router
- Direct store mutation inside router
- Cross-domain service calls
- Inline string comparison like:
  if (role === 'admin')

All role evaluation must use allowedRoles meta + guard logic.

---

# 8. Error Handling Rule

If unauthorized:

- Redirect to defined fallback route.
- Optionally store last intended route.
- Must not throw uncaught error.

No infinite redirect loops allowed.

Guard must detect loop.

---

# 9. Store Interaction Rule

Router may:

- Read authentication state.
- Read user role from store.

Router must not:

- Mutate store.
- Dispatch business action.
- Fetch API.

Navigation layer must remain pure.

---

# 10. No Structural Drift

If pattern adopted:

routes.js
accessGuard.js
index.js

All routing must follow identical structure.

No splitting guards into multiple scattered files.

No ad-hoc permission logic.

---

# 11. Violation Protocol

If AI detects:

- Role logic inside component
- Inline string role comparison
- API call inside router
- Missing meta definition
- Naming violation
- Missing AI header

AI must:

- STOP
- State violated rule
- Request clarification
- Not proceed

Hard stop by default.
