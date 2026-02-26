---
description: "Rules for Domain-Scoped Vue Components"
applyTo: "frontend/src/modules/*/components/**/*"
---

# Domain Components Rules

This folder defines domain-specific UI components.

These components belong to one domain only.

They are not globally reusable.

They must remain presentational.

---

# 1. Purpose

Domain components exist to:

- Render UI for a specific domain
- Display props passed from composables or store
- Emit events upward
- Remain predictable and testable

They must NOT:

- Call API directly
- Import services directly
- Contain business rule branching
- Perform role logic
- Mutate store directly
- Import other domain modules
- Use external SDK

Components must remain thin.

---

# 2. Structural Rules

Each component must:

- Live in its own file
- File name must match component name
- Use PascalCase
- Be domain-qualified if needed

Example:

ReservationCardComponent.vue
ReservationFormComponent.vue

Forbidden names:

Card.vue
Form.vue
Component.vue
Thing.vue

Predictability > brevity.

---

# 3. Composition API Enforcement

Components must:

- Use `<script setup>`
  OR
- Use Composition API explicitly

Options API is forbidden.

No `data() {}` usage allowed.

All reactive logic must use:

- ref()
- computed()
- defineProps()
- defineEmits()

No legacy syntax allowed.

---

# 4. Prop Discipline

All props must be explicitly defined.

No implicit prop usage.

Example:

```js
/**
 * @typedef {Object} ReservationCardProps
 * @property {string} reservationIdentifier
 * @property {string} statusLabel
 */

const props = defineProps({
  reservationIdentifier: {
    type: String,
    required: true,
  },
  statusLabel: {
    type: String,
    required: true,
  },
});
```

No untyped prop usage allowed.

No spreading unknown object into component.

---

# 5. Event Emission Rules

Components may emit events.

Must:

- Explicitly define emits
- Document emitted events
- Use meaningful event name

Example:

emit('submitReservation')

Forbidden:

emit('submit')
emit('done')

Events must reflect domain meaning.

---

# 6. State Discipline

Components may:

- Use local ref state
- Use computed values
- Use watch()

Components must NOT:

- Mutate Pinia store directly
- Call store action implicitly without composable
- Modify cross-domain state

If state needed → use composable or store.

---

# 7. Business Logic Prohibition

Forbidden:

if (role === 'admin')
if (amount > 1000) { applyBusinessRule() }

Business branching must exist inside:

- Backend Service
  OR
- Domain composable

Component must only render state passed.

---

# 8. Naming Doctrine

Minimum length:

- Component name → 6 characters
- Variables → 4 characters
- Methods → 6 characters

Forbidden names:

- data
- item
- obj
- val
- temp
- misc
- handler (unless domain-qualified)

Method naming must reflect UI intent.

Bad:
handle()

Good:
handleReservationSubmit()

---

# 9. AI Header Enforcement

If exporting helper functions inside component:

```js
// ===== AI GENERATED: functionName =====
// Purpose:
// Inputs:
// Returns:
// Flow:
// 1.
// 2.
// 3.
```

Component itself does not require header at top,
but any exported utility must include header.

If AI modifies file:

Add at top:

// ===== AI MODIFIED: ComponentName =====

No silent AI modification allowed.

---

# 10. Styling Rules

- Tailwind classes allowed.
- No inline style blocks.
- No dynamic style injection.
- No hard-coded theme values.
- Reuse shared components when possible.

If styling becomes reusable → move to shared/components/.

---

# 11. Import Direction

Allowed:

- components → composables (same domain)
- components → store (same domain)
- components → shared/
- components → router (read-only navigation)

Forbidden:

- components → services directly
- components → other domain modules
- components → external SDK
- components → backend logic

---

# 12. Testing Requirements

Each logic-heavy component must have:

frontend/tests/feature/<domain>/<component>.test.js

Must test:

- Rendering correctness
- Event emission
- Conditional rendering
- Error display state

Pure visual-only component may skip test.

---

# 13. No Structural Drift

If structure adopted:

frontend/src/modules/<domain>/components/

All domain UI components must live here.

No dumping inside pages/.

No nested deep subfolders without approval.

---

# 14. Violation Protocol

If AI detects:

- API call inside component
- Business rule branching
- Role string comparison
- Direct store mutation
- Naming violation
- Missing prop definition
- Structural inconsistency

AI must:

- STOP
- State violated rule
- Request clarification
- Not proceed

Hard stop by default.
