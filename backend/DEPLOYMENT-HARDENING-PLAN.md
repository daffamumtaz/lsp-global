# Deployment hardening plan

## Goal

Address the confirmed frontend and backend audit findings without changing public API payloads, replacing admin content, or modifying a remote environment.

The user requested a feasible plan and immediate implementation. Work continues in the current checkout because it includes the deployment preparation under review. Existing unrelated changes remain intact. No commit, push, migration to a remote database, or paid resource creation is included.

## Feasible now

- [x] Admin security: apply a shared password policy to account creation/editing and self-service password changes. Require MFA by default in production, keep local/test enrollment optional, and expose a deliberate configuration override. Test weak-password rejection, valid password hashing, and MFA configuration.
- [x] Public API protection: configure a named rate limiter for public content routes and cache the directory response briefly. Invalidate after relevant model changes commit; preserve immediate CMS updates and the existing JSON contract. Test throttling, cache reuse, and invalidation.
- [x] Frontend resilience: bound landing requests to five seconds, distinguish an upstream outage from a missing scheme, and render testimonials and social links from the CMS. Test slow/failed API handling and empty/disabled review data.
- [x] Backend stability: lock process parents before children during reorder. Preserve empty-group and foreign-ID validation. Verify with two concurrent PostgreSQL connections and a test mutation reproducing the original deadlock.
- [x] Import recovery: add an explicit assets-only recovery option that does not rerun directory imports or overwrite populated admin fields. Test resuming a failed asset import and preserving existing directory content.
- [x] Dependency patches: update vulnerable backend development dependencies within current manifest ranges; retain package lockfiles and avoid forced major-version changes. Audit production and development dependency trees separately.
- [x] Security headers: prevent object embedding, base/form destination changes, and unauthorized framing; permit the configured backend origin to embed frontend previews. Set MIME, referrer, and browser permission headers without introducing a script policy that breaks Next.js or Filament.
- [x] Verification: run focused regression tests, the backend suite, frontend tests/build, backend asset build, Pint, and diff checks. Perform independent code review and record unresolved conditions.

## Not currently feasible / deferred

| Item | Missing prerequisite | Completion evidence |
| --- | --- | --- |
| Supabase production connectivity and migration status | Authenticated connector or configured target database connection | Server identity, read-only connectivity check, Laravel migration status, table/grant/RLS inspection |
| Supabase bucket and backup/restore validation | Target project access and a disposable restore destination | Real upload/read check and successful restore drill |
| Container build/runtime | Permission to access a Docker daemon | Successful production image build and HTTP health check |
| Frontend glob/ESLint dependency advisory | Compatible upstream patched release or separately reviewed tooling change | Passing lint/build/tests without affected dependency chain; no automatic major downgrade |
| Paid Render compute | Explicit plan/cost selection | Confirmed service configuration and live health verification |
| Pagination or separate catalog endpoints | Coordinated public API/frontend contract change | Compatible endpoints and UI pagination under realistic data volume |

## Constraints and decisions

- Use installed Laravel 13, Filament 5, Next.js 16, and the existing PHPUnit/Node test tools.
- Keep the existing public API response shapes; pagination is a later change. Use cache and rate limiting for the current capacity concern.
- MFA enforcement defaults to production only. Existing users enroll through Filament; no MFA secret is created by deployment code.
- Cache invalidation must follow committed writes; responses inside a database transaction must not populate shared cache with uncommitted state.
- Asset recovery is explicit and repeatable; it must never reimport the populated certification directory.
- Existing dependency ranges may receive security patches; no new application dependencies or forced major upgrades.
- Plans and test results describe repository readiness, not proof that Supabase production has already been migrated.

## Verification ledger

- SQLite: 219 tests; 214 passed and 5 PostgreSQL/concurrency-specific tests skipped.
- PostgreSQL 18.6, disposable local database: 219 tests passed, 2,455 assertions. Driver and server came from signed distribution packages extracted under `/tmp`; no system package installation or production migration was performed.
- Concurrency regression: two real connections passed. Temporarily removing the parent-first lock reproduced SQLSTATE `40P01`; restoring it passed again.
- Frontend: 69 tests passed; production build and focused lint passed.
- Backend asset build, Pint, and diff checks passed.
- Composer and backend npm audit: zero advisories. Frontend production npm audit: zero advisories. Full frontend development audit: five high entries in the same ESLint/glob dependency chain; compatible upstream remediation remains deferred.
- Independent reviews identified and resolved database-cache serialization and malformed-review rendering defects. Cache tests now use the production database store with class deserialization disabled; cached payloads contain plain arrays. Actual React render tests cover malformed review entries.
- Production-mode frontend HTTP check confirms the preview route serves the security headers with the backend origin permitted for framing. Apache headers still require container/platform validation.
- Supabase remote connectivity, migration history, grants, bucket operations, and backup/restore remain unverified. Earlier local migration status was SQLite with two deployment migrations pending; this work does not change that application database.
