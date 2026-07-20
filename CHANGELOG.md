# Changelog

All notable changes to this project will be documented in this file.

## 5.0.0 - 2026-07-20

### Added
- Initial Craft CMS 5 release.
- Access policies and principals database schema (element-scoped and channel/section-scoped).
- Query-level Entry authorization via `ElementQuery::EVENT_BEFORE_PREPARE` (deny-form anti-join with fast paths).
- Built-in principal resolvers: user, group, guest, public.
- Access Control field for editing entry policies (stored in plugin tables, not Craft content).
- Read-only entry sidebar summary with effective access (entry policy, else channel default).
- General Access Control Panel screens for channel default policies.
- Shared policy editor for the field and channel settings.
- Plugin settings (`pluginName`, `authorizationEnabled`) with config-file override warnings.
- Settings remain viewable when `allowAdminChanges` is false (read-only).
- Control Panel bypass for authorization on CP requests.
- `super-content-access:manage-policies` permission.
- Craft dashboard widgets: Access Overview and Access Breakdown.
- Console `super-content-access/query-probe` command for SQL verification.
- Structured documentation under `docs/`.
