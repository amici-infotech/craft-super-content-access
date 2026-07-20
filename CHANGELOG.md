# Changelog

All notable changes to this project will be documented in this file.

## 5.0.4 - 2026-07-20

### Added
- Added covering index on access principals (`policyId`, `type`, `identifier`) for faster query authorization joins.
- Added request-scoped fast paths that skip authorization SQL when no policies apply.
- Added Craft dashboard widgets: **Access Overview** and **Access Breakdown**.
- Added structured documentation under `docs/`.

### Changed
- Rewrote Entry query authorization to a single deny-form anti-join instead of multiple correlated `EXISTS` branches.
- Replaced hot-path `COUNT(*)` policy checks with `EXISTS … LIMIT 1` presence flags.
- Settings fields now show a warning when a value is overridden by `config/super-content-access.php`.

### Fixed
- Fixed settings save calling a non-existent `Plugin::saveSettings()` method; settings now use `Plugins::savePluginSettings()`.

## 5.0.3 - 2026-07-16

### Added
- Added section-scoped (channel) default access policies.
- Added **General Access** Control Panel screens for listing and editing channel defaults.
- Added shared policy editor used by the Access Control field and channel settings.
- Entry sidebar now shows effective access (entry policy, else channel default) with inheritance notes.

### Changed
- Entry query authorization falls back to the channel default when an entry has no element policy.
- Plugin CP nav root URL set to `super-content-access` so the section stays selected across all plugin pages.
- Native Craft breadcrumbs and channel list table styling for General Access.

## 5.0.2 - 2026-07-14

### Added
- Reintroduced **Access Control** field for editing entry policies (stored in plugin tables, not Craft content).
- Entry sidebar widget switched to a read-only access summary.

### Changed
- Policy editing moves to the Access Control field; sidebar no longer submits policy changes.

### Fixed
- Fixed draft/canonical handling so submitted Access Control values persist to the canonical element.

## 5.0.0 - 2026-07-14

### Added
- Initial Craft CMS 5 release.
- Access policies and principals database schema.
- Query-level Entry authorization via `ElementQuery::EVENT_BEFORE_PREPARE`.
- Built-in principal resolvers: user, group, guest, public.
- Control Panel bypass for authorization on CP requests.
- Plugin settings (`pluginName`, `authorizationEnabled`).
- `super-content-access:manage-policies` permission.
- Console `super-content-access/query-probe` command for SQL verification.
