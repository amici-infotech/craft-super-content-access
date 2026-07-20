# Backend Guide

The plugin adds a **Super Content Access** section to the Craft Control Panel.

## Navigation

Main sections:

- **General Access** — defaults for channels, category groups, and product types.
- **Settings** — plugin name and authorization toggle.

The plugin nav stays expanded while you are on any Super Content Access URL. Native Craft breadcrumbs appear on General Access and Settings screens.

## Access Control Field

Add the **Access Control** field to an entry type, category group, or product type field layout.

On the element edit screen you can:

- Choose **Everyone** (public — no element policy).
- Choose **Members only**, then select user groups and/or specific users.
- See a warning when Members only is enabled with no audiences (fail-closed).

Saving writes to plugin tables (`super_content_access_*`), not Craft field content. The field uses `dbType: null`.

Drafts: submitted values persist against the **canonical** element ID.

## Element Sidebar Summary

The meta sidebar shows a read-only **Access** band for entries, categories, and products:

- Badge: **Public** or **Members only**.
- Source note: set on this element, or inherited from the matching General Access default (with link).
- Audience chips for groups and users when restricted.

The sidebar does not edit policies. Use the Access Control field (or General Access for defaults).

The summary is hidden when access is fully public **and** the layout has no Access Control field.

## General Access

Sidebar scopes:

- **Channels** — Craft channel sections
- **Categories** — category groups
- **Products** — Commerce product types (shown only when Commerce is installed and enabled)

### Channels

```text
/admin/super-content-access/access/channels
```

Lists every Craft **channel** section. Click a channel to set its default policy. Choosing Everyone removes the section-scoped policy.

### Category groups

```text
/admin/super-content-access/access/categories
```

Same editor pattern for each category group.

### Product types

```text
/admin/super-content-access/access/products
```

Same editor pattern for each Commerce product type.

Requires `super-content-access:manage-policies`.

## Settings

URL:

```text
/admin/super-content-access/settings
```

- **Plugin Name**
- **Enable authorization**

Requires a Craft admin user. When `allowAdminChanges` is `false` in `config/general.php`, the settings screen is still visible but read-only (fields disabled, save blocked)—same behaviour as Super Favourite.

Config-file overrides show a warning under the field.

## Dashboard Widgets

From the Craft dashboard, add:

- **Access Overview** — authorization status and policy counts, plus a link to General Access.
- **Access Breakdown** — doughnut chart; widget settings choose policy location or audience type.
