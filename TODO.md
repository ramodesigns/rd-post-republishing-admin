# TODO — Central License & Calculation Server

Companion repo: `rd-post-republishing` (the customer-facing plugin that consumes this server). See its `TODO.md` for the client-side half of each phase below.

## Phase 0 — Deploy
- [ ] Install this plugin on a real, stable WordPress site (your own domain) — the client plugin will be hardcoded/configured to call its URL. Nothing downstream works until this exists.
- [ ] Run activation on that install so `Init_Setup` creates the license table (`rd_admin_li_log`) and action log table (`rd_admin_act_log`).

## Phase 1 — HMAC verification + license status

- [ ] Add a `status` column (`active` / `revoked`) to the license table. `create_license()` already generates a secure per-domain `activation_key` (`hash('sha256', ...)` in `src/helpers/License_Helper.php`) — that key becomes the HMAC shared secret; `status` is what's currently missing, since a row's mere existence is the only signal today.
  - Update `setup/Init_Setup.php`'s `create_license_table()` (add column) and follow the client repo's existing "version-based update check" pattern so the schema change applies on existing installs too.
- [ ] Add signature verification to `src/helpers/License_Helper.php` (pure function, no DB): given `domain_name`, `timestamp`, `body`, and the stored `activation_key` for that domain, recompute `HMAC-SHA256(domain_name + timestamp + body, activation_key)` and compare against the request's `X-Signature` header. Reject if it doesn't match, the timestamp is missing, or it drifts beyond ~5 minutes (replay protection).
- [ ] Add a license-status endpoint to `src/controllers/License_Controller.php` (e.g. `GET /postrepublishing/v1/license/status`), HMAC-verified via the helper above, returning `{status: active|revoked}` for the requesting domain. Used by the client for both its daily heartbeat cron and its Settings-page display.
- [ ] Extend the existing "Licensed Domains" admin page (`admin/partials/rd-post-republishing-admin-licensed-domains-display.php` + JS) so you can flip a license's `status` between active/revoked — currently it only supports create/delete via `License_Service`.

## Phase 2 — Server-side calculation, gated on license

- [ ] Port the calculation algorithm from the client repo (`Calculation_Helper`/`Calculation_Service`: `get_post_times`, `generate_post_times`, `validate_date`) into this repo as a new `src/helpers/Calculation_Helper.php` + `src/services/Calculation_Service.php`, following the Controller/Helper/Service pattern documented in `CLAUDE.md`.
- [ ] Add `src/controllers/Calculation_Controller.php`. Its `permission_callback` must:
  1. Verify the HMAC signature (reusing the Phase 1 `License_Helper` method).
  2. Check the domain's license `status === 'active'`.
  3. Return 403 on either failure — this is the real-time enforcement the client relies on for every calculation request (no caching, checked live on each call since this is the same server as validation).
- [ ] Register the controller in `src/controllers/index.php` per the existing wiring convention (inject the shared `Authorisation_Helper`/HMAC helper).
