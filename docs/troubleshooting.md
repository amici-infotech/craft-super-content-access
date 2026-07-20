# Troubleshooting

## Entry Still Public After Restricting

1. Confirm the **Access Control** field is on the entry type layout and you saved the entry with **Members only** selected.
2. Check the sidebar summary — if it says inherited from the channel, the entry itself may still be “Everyone” while the channel default restricts it.
3. Confirm **Enable authorization** is on (and not forced off in `config/super-content-access.php`).
4. CP always bypasses filtering — test on the front end while logged out or as another user.

## Channel Default Not Applying

1. Open **General Access → Channels** and confirm the channel shows **Members only**.
2. Entry-level policies override channel defaults. Clear the entry’s Access Control field (Everyone) to inherit the channel rule.
3. Only Craft sections of type **channel** appear in General Access.

## Settings Won’t Stick

If a field shows “This is being overridden by the `…` setting in the `config/super-content-access.php` file,” change or remove that key in the config file.

## Queries Feel Slow

1. With **no policies**, the plugin should skip authorization SQL (check the debug toolbar — you should not see heavy policy joins).
2. Presence checks use `EXISTS … LIMIT 1`, not `COUNT(*)`.
3. Large sites with many entry policies will still pay for the authorization anti-join; that is expected. The principals covering index is created on install.

## Sidebar Missing

The sidebar summary is hidden when access is fully public **and** the entry layout has no Access Control field. Add the field, or set a channel default / entry policy.

## GraphQL / Custom Queries

Only **Entry** element queries are integrated in v1. Other element types are not filtered yet. Custom raw SQL that bypasses `Entry::find()` will also bypass the plugin.

## Probe Disagrees With Front End

Ensure you are not comparing CP requests (bypass) to front-end requests. Use `query-probe --guest=1` for anonymous visibility checks.
