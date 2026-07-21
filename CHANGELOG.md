# Changelog

All notable changes to this project will be documented in this file.

## 5.0.0 - 2026-07-20

### Added
- Initial Craft CMS 5 release.
- Access policies and principals database schema (element-scoped plus channel, category-group, and product-type defaults).
- Query-level authorization for Entry, Category, and Commerce Product queries via `ElementQuery::EVENT_BEFORE_PREPARE` (deny-form anti-join with fast paths).
- Built-in principal resolvers: user, group, guest, public.
- Access Control field for editing element policies (stored in plugin tables, not Craft content).
- Read-only element sidebar summary with effective access (element policy, else scope default).
- General Access Control Panel screens for channel, category-group, and product-type defaults.
- Shared policy editor for the Access Control field and General Access screens.
- Plugin settings (`pluginName`, `authorizationEnabled`, `adminAlwaysAccess`, `authorAlwaysAccess`) with config-file override warnings.
- Settings remain viewable when `allowAdminChanges` is false (read-only).
- Control Panel bypass for authorization on CP requests.
- Optional front-end bypasses: admins always access; entry authors always access their own entries.
- `super-content-access:manage-policies` permission.
- Craft dashboard widgets: Access Overview and Access Breakdown.
- Console `super-content-access/query-probe` command for Entry SQL verification (`--clear` requires `--force`).
- Policy lifecycle events (`beforeSavePolicy` / `afterSavePolicy` / `beforeDeletePolicy` / `afterDeletePolicy`) via `PolicyEvent`.
- Query modification events (`beforeModifyQuery` / `afterModifyQuery`) via `ModifyElementQueryEvent`.
- Structured documentation under `docs/`.
