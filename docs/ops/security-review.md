# Security review (PLAN M23)

A review of the whole app before the beta, done on 2026-11-17 against the M23 branch. What was fixed is
covered by a test; what is left is listed at the end with who decides.

## What was checked

| Area | Result | Proof |
|---|---|---|
| **Write routes**: sign-in, roles, rate limits | Every route that changes something needs a signed-in person, a rate limit, or both. Moderation routes need the `moderate` ability; `/admin` and `/horizon` need a maintainer. **Fixed:** sign-up and the password-reset mail had no rate limit (bulk accounts, reset mail sent to many addresses through SES); both now share a limit per IP address (`atlas.security.account_forms_per_hour`, 10). | `Feature/Security/WriteRoutesProtectedTest` (fails on any new unprotected write route), `Feature/Security/AccountFormThrottleTest`, `Feature/Admin/AdminAccessTest` |
| **Security headers** | **Fixed:** `nosniff`, `X-Frame-Options`, `Referrer-Policy` were only set by Nginx, so no test or other environment saw them. The app now sends them on every response (`App\Http\Middleware\SecurityHeaders`), plus `Permissions-Policy` (no camera, microphone, location, payment, USB) and `Cross-Origin-Opener-Policy`. Nginx keeps HSTS. | `Feature/Security/SecurityHeadersTest` |
| **Cookies and sessions** | HTTP-only, `SameSite=Lax`, CSRF on every web form. **Fixed:** the session cookie is now `Secure` by default when `APP_URL` is HTTPS, instead of depending on a setting someone could forget. | `config/session.php` |
| **XSS** | React escapes all text. The one `dangerouslySetInnerHTML` is the starter kit's two-factor QR code, an SVG made by the server. No `{!! !!}` in Blade. Talk posts and reports are stored and shown as plain text. | code search |
| **SQL injection** | Every raw expression is a constant or uses bindings. The one interpolated expression (`IscoGroups`) picks from four literals; the group code in the URL is limited to 1–4 digits. Search terms are normalised to letters and digits before they reach `to_tsquery`, and are bound. | code search, `Feature/Publishing/SearchPageTest` |
| **Personal data** | Public pages, the API and open data go through `Publishing` presenters; private submissions and emails never leave them. Error reports to Sentry are scrubbed (D77). The guide's answers are never stored (D73). | `Feature/Publishing/ApiNoPersonalDataTest`, `Feature/Ops/ErrorReportScrubbingTest` |
| **Mass assignment** | Every model lists its fillable attributes; requests are validated in form requests or actions before they reach a model. | code search |
| **Dependencies** | `composer audit` and `npm audit --omit=dev`: no known vulnerabilities. | run before each release |
| **Secrets** | None in the repository; `.env.example` only. Deploy keys and server secrets live in GitHub environments and on the server (`docs/ops/server.md`). | review |

## Left open

- **Content Security Policy:** not set yet. A strict policy needs nonces for the inline theme script, the
  Inertia page data and the optional Umami script. Plan: run it in report-only mode on staging first,
  then enforce. Needs a staging server (O1, H9).
- **Dependency audit in CI:** `composer audit` and `npm audit` are not CI steps yet; adding them is a
  workflow change for a maintainer to accept, because a new advisory would block unrelated PRs.
- **Penetration test by someone outside the project** before the public launch: maintainer.
