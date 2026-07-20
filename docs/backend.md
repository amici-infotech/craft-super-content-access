# Backend Guide

The plugin adds a **Super Content Access** section to the Craft Control Panel.

## Navigation

Main sections:

- **General Access** — channel default policies.
- **Settings** — plugin name and authorization toggle.

The plugin nav stays expanded while you are on any Super Content Access URL.

## Access Control Field

Add the **Access Control** field to an entry type field layout.

On the entry edit screen you can:

- Choose **Everyone** (public — no entry policy).
- Choose **Members only**, then select user groups and/or specific users.
- See a warning when Members only is enabled with no audiences (fail-closed).

Saving the entry writes to plugin tables (`super_content_access_*`), not Craft field content. The field uses `dbType: null`.

Drafts: submitted values persist against the **canonical** entry ID.

## Entry Sidebar Summary

The entry meta sidebar shows a read-only **Access** band:

- Badge: **Public** or **Members only**.
- Source note: set on this entry, or inherited from the channel default (with link to General Access).
- Audience chips for groups and users when restricted.

The sidebar does not edit policies. Use the Access Control field (or General Access for channel defaults).

## General Access — Channels

URL:

```text
/admin/super-content-access/access/channels
```

Lists every Craft **channel** section with:

- Name and handle
- Default access status (Public / Members only / No one)
- Link to edit

Click a channel to set its default policy using the same Everyone / Members only editor. Saving updates the section-scoped policy; choosing Everyone removes it.

Requires `super-content-access:manage-policies`.

## Settings

URL:

```text
/admin/super-content-access/settings
```

- **Plugin Name**
- **Enable authorization**

Requires a Craft admin. Config-file overrides show a warning under the field.

## Dashboard Widgets

From the Craft dashboard, add:

- **Access Overview** — authorization status and policy counts.
- **Access Breakdown** — doughnut chart; settings choose policy location (entries vs channels) or audience type (groups vs users).
