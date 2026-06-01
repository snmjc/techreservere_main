# TechReserve CodeScene Health Roadmap

Date: 2026-06-01

## 1. Executive Summary

TechReserve's highest health risks are concentrated in account registration, account management, wishlist verification, authentication, and large Vue pages. The system is still workable, but several files combine UI, validation, API calls, persistence, Clerk synchronization, and authorization decisions in one place. That creates low cohesion, complex methods, duplicated logic, and bumpy-road flows that CodeScene will continue to flag until those responsibilities are separated.

Completed health work:

- `UserRegistrationController.php`: wishlist read SQL and row mapping moved to `WishlistAccountReadService`.
- `AdminWishlist.vue`: pure wishlist normalization, validation, formatting, and sanitizing moved to `adminWishlistHelpers.js`.
- `adminWishlistApi.js`: repeated request blocks consolidated into one `sendWishlistRequest` helper.
- `accessGuard.js`: route decision logic moved to `routeAccessDecision.js`.
- `authenticationService.js`: duplicated response parsing and login error construction moved to helpers.
- `authenticationStore.js`: auth localStorage access moved to `authStorage.js`.
- Logout redirect logic centralized in `logoutRedirect.js`.
- Reservation, venue, equipment, and dashboard API token/header construction centralized in `authToken.js`.
- Route-name strings centralized in `routeNames.js` to reduce hidden coupling between `routes.js`, auth pages, `App.vue`, and route guards.
- Auth localStorage key usage centralized through `AUTH_STORAGE_KEYS`, including remembered login email and Clerk account cache keys.
- `PendingUserController.php`: repeated pending-user lookup SQL replaced with explicit columns and a shared lookup helper.
- `UserRegistrationController.php`: duplicate account conflict lookup/formatting moved to `AccountConflictLookupService`; person-name/admin-email validation moved to `AccountInputValidationService`.
- `ManageAccounts.vue`: account normalization, formatting, sorting, validation, and permission helpers moved to `manageAccountsHelpers.js`.
- `AuthenticationController.php` and `AccountController.php`: duplicated password-strength policy moved to `PasswordPolicyService`.
- `AuthenticationController.php`: Clerk password lookup/update moved to `AuthenticationClerkService`; reset-code email rendering/sending moved to `PasswordResetEmailService`.
- `AccountController.php`: settings/profile validation moved to `AccountSettingsValidationService`; account response mapping moved to `AccountResponseMapperService`; account status/action rules moved to `AccountLifecyclePolicyService`; Clerk delete delegated to `AuthenticationClerkService`.
- `TaskManagementService.php`: repeated primitive task arguments moved into `TaskMutationRequestDTO`; task validation and entity-to-DTO mapping moved into focused services.
- `TaskController.php`: task read/query mapping, linked-record validation, and history-log syncing moved into dedicated task services.
- `UserRegistrationController.php`: branded accepted-account email construction and mail sending moved to `AccountAcceptanceEmailService`.
- `UserRegistrationController.php`: Clerk invitation/signup provisioning moved to `AccountClerkProvisioningService`.

Dashboard safety rule:

- Do not change dashboard UI, layout, data mapping, computed output, or behavior during CodeScene refactors.
- Dashboard-related work must be limited to internal extraction unless separately requested.

## 2. CodeScene Health Interpretation

CodeScene is showing three main patterns:

- Hotspots: files that change often and contain high complexity. These are most likely to create regressions.
- Health decline: files getting harder to understand, test, and safely change.
- Low cohesion: files mixing unrelated reasons to change, such as form rendering, API orchestration, validation, SQL, Clerk calls, and permission decisions.

The most important rule for this project is: controllers and pages should coordinate work, not own all the work.

## 3. Priority Refactoring Roadmap

| Priority | Scope | Goal | Status |
|---|---|---|---|
| Critical | `UserRegistrationController.php` | Move registration, invitation, duplicate checks, and Clerk sync into services/validators/repositories | Started |
| Critical | Auth/authorization | Separate login, Clerk account loading, route decisions, token handling, role decisions | Started |
| High | `AdminWishlist.vue` and `adminWishlistApi.js` | Split helper logic, modal state, create-account flows, and API wrappers | Started |
| High | `ManageAccounts.vue` | Extract create/update/delete/work-log modals and account services | Pending |
| High | `AccountController.php` | Move account profile/update/delete logic into services | Pending |
| Medium | Large signup pages | Extract form validation, file upload logic, Clerk/backend sync logic | Pending |
| Medium | Reservation/admin pages | Extract API/data logic and guard borrower-owned records | Pending |
| Low | Layout/components | Remove page-specific logic from shared layouts | Pending |

## 4. File-by-File Issue Table

| File | Risk | Why Unhealthy | Recommended Fix |
|---|---:|---|---|
| `backend/src/Domain/Account/Controller/UserRegistrationController.php` | Critical | Still large, but Clerk API calls, branded email content, wishlist reads, duplicate checks, and validation have started moving out | Continue extracting registration workflow, invitation persistence, and admin confirmation services |
| `frontend/src/pages/admin/AdminWishlist.vue` | Critical | 1400+ lines after first split, still owns table UI, modal state, create flows, action orchestration | Extract modal components and `useAdminWishlist` composables |
| `frontend/src/services/adminWishlistApi.js` | Medium | Request duplication was present; now reduced | Keep thin API facade and add tests around response parsing |
| `backend/src/Domain/Account/Controller/AccountController.php` | High | Still owns several account workflows, but validation, mapping, lifecycle rules, and Clerk deletion have been extracted | Continue splitting account settings, access updates, deletion, and work-log queries into services |
| `frontend/src/pages/admin/ManageAccounts.vue` | High | 1100+ lines, 67 functions, many modals and workflows | Extract account table, create/update/delete modals, work logs composable |
| `frontend/src/pages/auth/CustomSignUp.vue` | High | 1100+ lines, form UI, validation, file handling, backend calls | Extract signup form, document upload, validation composable |
| `frontend/src/pages/auth/ClerkLogin.vue` | High | 1000 lines, custom Clerk reset/login flows and local backend auth handling | Extract Clerk password reset composable and backend login service |
| `backend/src/Domain/Account/Controller/AuthenticationController.php` | High | Still owns endpoint orchestration, but Clerk password API and reset email rendering are now extracted | Continue extracting local login and password reset persistence into services |
| `frontend/src/pages/auth/SignUp.vue` | Medium | Large page, duplicated signup validation with `CustomSignUp.vue` | Share signup validation/service |
| `backend/src/Command/CreateAdminCommand.php` | Medium | Command creates Clerk user and DB account directly | Move account creation into reusable admin account service |
| `frontend/src/pages/auth/PostLogin.vue` | Medium | Clerk/backend linking and redirect decisions in page | Move post-login sync into auth composable/service |
| `frontend/src/pages/auth/RequestPending.vue` | Medium | Poll/status logic in page | Extract approval status composable |
| `frontend/src/modules/authentication/store/authenticationStore.js` | Medium | Store owns persistence, Clerk fetch, fallback, role normalization | Extract storage adapter and Clerk account service |
| `frontend/src/router/accessGuard.js` | Low | Previously mixed logging and route decision logic | Refactored into `routeAccessDecision.js` |
| `backend/src/Infrastructure/Auth/ClerkTokenVerifier.php` | Medium | Clerk verification and identity enrichment are security sensitive | Keep focused; add tests and move logging/config parsing out if needed |
| `backend/src/Middleware/AuthenticationMiddleware.php` | Medium | Token parsing, request identity setting, auth exceptions | Keep middleware thin; delegate token extraction and identity creation |
| `backend/src/Middleware/AuthorizationMiddleware.php` | Medium | Role checks must be stable and auditable | Keep role resolver/service tests strong |
| `frontend/src/shared/components/AdminSidebarLayoutComponent.vue` | Low | Shared layout can accumulate unrelated header/user logic | Keep layout presentational; move user menu state outward if it grows |
| `frontend/src/pages/admin/PastRecords.vue` | Medium | Records pages risk user data leakage if filtering is frontend-only | Verify backend owner filtering and extract records service |
| `frontend/src/pages/admin/ManageFacilities.vue` | Medium | Facility CRUD and UI state in one page | Extract facility API/composable and modal components |
| `backend/src/Domain/Dashboard/Service/DashboardAggregationService.php` | Medium | Aggregation services can grow broad queries | Split per dashboard metric when complexity grows |
| `backend/src/Domain/Venue/Service/VenueManagementService.php` | Low | Small now, but venue rules should stay service-owned | Keep repository/service boundary clean |
| `backend/src/Domain/Task/Service/TaskManagementService.php` | Medium | Declining from complex methods, repeated primitive argument lists, status strings, and entity mapping in one service | Keep orchestration in service; move validation/DTO mapping into focused services |
| `backend/src/Domain/Task/Controller/TaskController.php` | Medium | Declining from duplicate controller paths, inline SQL reads, history-log sync, security confirmation, and response mapping | Keep routes thin; delegate reads, history sync, linked-record validation, and task mutation DTO creation |
| `frontend/src/pages/borrower/CreateReservationDocuments.vue` | Medium | File validation/upload flow mixed with page rendering | Extract document upload validation composable |

## 5. Root Causes

- Controllers are doing service and repository work.
- Vue pages are doing component, composable, validation, API, and state-management work.
- Auth flows have duplicated role/status/token decisions.
- API services repeatedly build headers, parse responses, and handle errors.
- Primitive strings such as roles, statuses, account types, and invitation states appear across files.
- Hidden route-name and auth-storage key dependencies made unrelated route/auth files change together.
- Some backend security rules are difficult to audit because they are embedded inside long methods.

## 6. Before and After Architecture

Before:

```text
Vue page -> inline validation -> inline fetch -> backend controller -> inline SQL/business rules -> database/Clerk/mail
```

After:

```text
Vue page -> component/composable -> API service -> thin controller -> application service -> validator/repository/integration service -> database/Clerk/mail
```

## 7. Suggested Folder Structure

Backend:

```text
backend/src/Domain/Account/
  Controller/
  DTO/
  Repository/
  Service/
    Registration/
    Invitation/
    Staff/
    Wishlist/
  Validator/

backend/src/Domain/Authentication/
  Controller/
  Service/
    Clerk/
    PasswordReset/
    Session/
```

Frontend:

```text
frontend/src/pages/admin/
  ManageAccounts.vue
  AdminWishlist.vue
  wishlist/
    components/
    composables/
    adminWishlistHelpers.js

frontend/src/modules/authentication/
  composables/
  services/
  store/
  utils/

frontend/src/shared/api/
  apiClient.js
  authHeaders.js
```

## 8. Step-by-Step Implementation Plan

1. Lock safety net: keep `php bin/phpunit` and `npm run build` passing after every phase.
2. Continue `UserRegistrationController.php` extraction:
   - `RegistrationRequestValidator`
   - `AccountRegistrationService`
   - `InvitationAcceptanceService`
   - `AccountDuplicateLookupService`
3. Split wishlist frontend:
   - `WishlistAccountTable.vue`
   - `WishlistApprovalModal.vue`
   - `WishlistDenyModal.vue`
   - `WishlistDeleteModal.vue`
   - `useWishlistActions.js`
4. Split `ManageAccounts.vue`:
   - account table component
   - create/update/delete modal components
   - work-log sheet component
   - `manageAccountsApi.js`
5. Refactor authentication:
   - move Clerk account loading out of Pinia store
   - centralize token storage/header creation
   - keep route decisions in pure helpers
   - keep route names and logout destinations centralized constants
6. Refactor backend account APIs:
   - split `AccountController.php` into thin endpoints backed by services
   - move account deletion credential verification into a service
7. Add security-focused tests:
   - borrower cannot read other borrowers' reservations/logs/records
   - admin can read all relevant admin datasets
   - pending/unverified users cannot enter protected system pages
8. Re-run CodeScene after each phase and compare hotspot movement.

## 9. Testing Plan

Backend:

- Run `docker compose exec -T backend php -l <changed-file>` for changed PHP files.
- Run `docker compose exec -T backend php bin/phpunit`.
- Add unit tests for validators and services extracted from controllers.
- Add feature tests for account registration, wishlist approval/denial/delete, login, and role authorization.

Frontend:

- Run `npm run build`.
- Add focused tests for pure helpers/composables where the project test setup supports it.
- Manually test:
  - Clerk login
  - signup pending flow
  - wishlist view/approve/deny/delete
  - manage accounts create/update/delete
  - borrower reservations and records
  - admin reservations and records

Security tests:

- Borrower account A cannot access borrower account B data.
- Pending account cannot access borrower/admin routes.
- Disabled/rejected account cannot access protected routes.
- Admin-only endpoints reject borrower tokens.

## 10. Rollback Plan

- Keep each refactor small and independently buildable.
- Commit each passing phase separately.
- If a phase fails, revert only the files changed in that phase.
- Preserve API contracts while moving code behind the same route names and payload shapes.
- Avoid database migrations during pure code-health phases unless explicitly required.

## 11. Expected CodeScene Improvement

Expected impact after completed work:

- `adminWishlistApi.js`: meaningful duplication reduction.
- `AdminWishlist.vue`: moderate improvement from reduced size and better cohesion.
- `accessGuard.js`: meaningful improvement from reduced branching and focused responsibility.
- Auth/router coupling: moderate improvement from central route-name and storage-key ownership.
- `AuthenticationController.php`: moderate improvement from removing direct Clerk HTTP and mail-template responsibilities.
- `AccountController.php`: moderate improvement from reducing private helpers and moving mapping/validation/lifecycle rules into cohesive services.
- Missed-goal follow-up: `AuthenticationController.php` is now a thin response wrapper around login, Clerk preflight, password reset, and registration services.
- Missed-goal follow-up: `AccountController.php` now delegates account reads, staff writes, admin security confirmation, account deletion, and authenticated account resolution to focused services.
- Missed-goal follow-up: `ClerkTokenVerifier.php` now delegates local token resolution, Clerk token resolution, JWT decoding, primary email lookup, and account identity building to small infrastructure services.
- Task module: moderate improvement from removing repeated primitive arguments, inline task reads/mapping, and history-log persistence from controller/service hotspots.
- `UserRegistrationController.php`: major improvement from extracting Clerk registration, public signup requests, wishlist user/staff creation, and wishlist approval orchestration into focused services. The controller is now a route/response layer instead of the owner of those workflows.
- Auth Vue hotspots: meaningful improvement from moving `ClerkLogin.vue`, `CustomSignUp.vue`, `PostLogin.vue`, `SignUp.vue`, and `SettingsPage.vue` script logic into composables while preserving templates and styling.

Expected impact after the next two phases:

- `UserRegistrationController.php`: should move from critical hotspot toward high/medium risk once registration/invitation logic is extracted.
- `ManageAccounts.vue`: should reduce health decline once modals and account actions are split.
- Authentication files: should become easier to audit when token/session/role decisions are separated.

CodeScene will only show these improvements after a fresh analysis run.
