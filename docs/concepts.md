# Core Concepts

## Access Policy

An access policy describes **who** may view protected content.

A policy row is exactly one of:

- **Element policy** — `elementId` set; applies to that entry, category, or product only.
- **Channel default** — `sectionId` set; applies to every entry in that channel with no element policy.
- **Category group default** — `groupId` set; applies to every category in that group with no element policy.
- **Product type default** — `productTypeId` set; applies to every product of that type with no element policy (requires Craft Commerce).

Never combine multiple scope columns on the same row.

## Policy Principals

Principals are the audiences attached to a policy:

- **User group** — everyone in the group gets access.
- **User** — a specific Craft user gets access.

Built-in resolver types also include `guest` and `public` for the authorization engine. The current Control Panel editor focuses on groups and users; choosing **Everyone** removes the policy instead of storing a public principal.

## Everyone vs Members Only

| Mode | Storage | Front-end result |
|---|---|---|
| **Everyone** | No policy for that element/scope | Visible to all visitors |
| **Members only** | Policy with selected groups/users | Only matching logged-in members |
| **Members only** with no audiences | Policy with zero principals | Fail-closed — hidden from everyone |

## Resolution Order

For each element (after optional bypasses below):

1. If an **element policy** exists → that policy decides visibility.
2. Else if a **scope default** exists (channel / category group / product type) → that policy decides visibility.
3. Else → **public**.

### Built-in bypasses

These run before policy resolution when enabled in settings/config:

| Setting | Effect |
|---|---|
| **Admins always have access** (`adminAlwaysAccess`) | Craft admin users see all protected content on the front end |
| **Authors always have access** (`authorAlwaysAccess`) | Entry authors always see their own entries (entries only; not categories or products) |

Control Panel requests always bypass filtering regardless of these settings.

The same order is used by:

- Element query SQL filtering
- Element sidebar summary
- PHP `AuthorizationService`

## Query-Level Authorization

Front-end Entry, Category, and (when Commerce is available) Product queries are modified before SQL runs. Unauthorized elements are never hydrated.

This applies automatically to:

- Twig `craft.entries` / `craft.categories` / `craft.products`
- PHP `Entry::find()` / `Category::find()` / `Product::find()`
- GraphQL (and other consumers) that use those Craft element queries

Control Panel requests bypass filtering so editors can always manage content.

## Fail Closed

If a policy exists but no principal matches the current visitor, access is denied. Empty audience lists are valid and mean “protected, nobody allowed yet.”
