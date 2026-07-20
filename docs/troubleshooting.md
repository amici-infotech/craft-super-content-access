# Troubleshooting

## Entry Still Public After Restricting

1. Confirm the **Access Control** field is on the entry type layout and you saved the entry with **Members only** selected.
2. Check the sidebar summary — if it says inherited from the channel, the entry itself may still be “Everyone” while the channel default restricts it.
3. Confirm **Enable authorization** is on (and not forced off in `config/super-content-access.php`).
4. CP always bypasses filtering — test on the front end while logged out or as another user.
5. For a single known entry, verify with `{% if craft.superContentAccess.canAccess(entry) %}`.

## Channel Default Not Applying

1. Open **General Access → Channels** and confirm the channel shows **Members only**.
2. Entry-level policies override channel defaults. Clear the entry’s Access Control field (Everyone) to inherit the channel rule.
3. Only Craft sections of type **channel** appear in General Access.

## `canAccess` Says Allowed but Query Hides It (or the Reverse)

Both paths use the same resolution order (entry → channel → public). If they disagree:

1. Clear Craft caches / restart long-lived PHP workers (MCP, queue, etc.).
2. Confirm you are not checking in a CP request context (CP always allows).
3. Confirm `authorizationEnabled` is on for both the query and the check.

## Settings Won’t Stick

1. If a field shows “This is being overridden by the `…` setting in the `config/super-content-access.php` file,” change or remove that key in the config file.
2. If `allowAdminChanges` is false, the settings screen is read-only by design — edit config or enable admin changes.

## Queries Feel Slow

1. With **no policies**, the plugin should skip authorization SQL (check the debug toolbar — you should not see heavy policy joins).
2. Presence checks use `EXISTS … LIMIT 1`, not `COUNT(*)`.
3. Large sites with many entry policies will still pay for the authorization anti-join; that is expected. The principals covering index is created on install.

## Sidebar Missing

The sidebar summary is hidden when access is fully public **and** the entry layout has no Access Control field. Add the field, or set a channel default / entry policy.

## Need All Entries While Authorization Is On

There is no Twig bypass. From PHP, temporarily disable the integrator (see [PHP API — Bypass](php-api.md#bypass-query-authorization)), or turn off authorization globally.

## GraphQL / Custom Queries

Only **Entry** element queries are integrated in v1. Other element types are not filtered yet. Custom raw SQL that bypasses `Entry::find()` will also bypass the plugin.

## Probe Disagrees With Front End

Ensure you are not comparing CP requests (bypass) to front-end requests. Use `query-probe --guest=1` for anonymous visibility checks.
