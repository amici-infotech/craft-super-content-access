# Troubleshooting

## Element Still Public After Restricting

1. Confirm the **Access Control** field is on the field layout and you saved with **Members only** selected.
2. Check the sidebar summary — if it says inherited from a General Access default, the element itself may still be “Everyone” while the scope default restricts it.
3. Confirm **Enable authorization** is on (and not forced off in `config/super-content-access.php`).
4. CP always bypasses filtering — test on the front end while logged out or as another user.
5. For entries, use `php craft super-content-access/query-probe` (see [Console Probe](console.md)) or PHP `AuthorizationService::canAccessElementId()` to verify a known ID.

## Scope Default Not Applying

1. Open **General Access** (Channels / Categories / Products) and confirm the scope shows **Members only**.
2. Element-level policies override scope defaults. Set the Access Control field to **Everyone** to inherit the default.
3. Only Craft sections of type **channel** appear under Channels. Category groups and Commerce product types appear under their own tabs.

## Admin / Author Bypass Surprises

1. With `adminAlwaysAccess` enabled (default), Craft admins see all protected content on the front end.
2. With `authorAlwaysAccess` enabled (default), entry authors always see their own entries — even when Members only would hide them. This does not apply to categories or products.

## PHP `canAccess` Disagrees With a Front-End Query

Both paths use the same resolution order (element → scope default → public), plus the same admin/author bypasses. If they disagree:

1. Clear Craft caches / restart long-lived PHP workers (MCP, queue, etc.).
2. Confirm you are not checking in a CP request context (CP always allows).
3. Confirm `authorizationEnabled` is on for both the query and the check.

## Settings Won’t Stick

1. If a field shows “This is being overridden by the `…` setting in the `config/super-content-access.php` file,” change or remove that key in the config file.
2. If `allowAdminChanges` is false, the settings screen is read-only by design — edit config or enable admin changes.

## Queries Feel Slow

1. With **no policies** for that element type, the plugin should skip authorization SQL (check the debug toolbar — you should not see heavy policy joins).
2. Presence checks use `EXISTS … LIMIT 1`, not `COUNT(*)`.
3. Large sites with many element policies will still pay for the authorization anti-join; that is expected. The principals covering index is created on install.

## Sidebar Missing

The sidebar summary is hidden when access is fully public **and** the layout has no Access Control field. Add the field, or set a General Access default / element policy.

## Need All Elements While Authorization Is On

There is no Twig bypass. From PHP, temporarily disable the integrator (see [PHP API — Bypass](php-api.md#bypass-query-authorization)), or turn off authorization globally.

## GraphQL / Custom Queries

Only **Entry**, **Category**, and (when Commerce is available) **Product** element queries are integrated. Other element types are not filtered yet. Custom raw SQL that bypasses those Craft element queries will also bypass the plugin.

## Probe Disagrees With Front End

The console probe is Entry-only. Ensure you are not comparing CP requests (bypass) to front-end requests. Use `query-probe --guest=1` for anonymous entry visibility checks.
