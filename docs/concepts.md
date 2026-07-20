# Core Concepts

## Access Policy

An access policy describes **who** may view protected content.

A policy row is one of:

- **Entry policy** — `elementId` set; applies to that entry only.
- **Channel default** — `sectionId` set; applies to every entry in that channel that has no entry policy.

There is never both `elementId` and `sectionId` on the same row.

## Policy Principals

Principals are the audiences attached to a policy:

- **User group** — everyone in the group gets access.
- **User** — a specific Craft user gets access.

Built-in resolver types also include `guest` and `public` for the authorization engine. The current Control Panel editor focuses on groups and users; choosing **Everyone** removes the policy instead of storing a public principal.

## Everyone vs Members Only

| Mode | Storage | Front-end result |
|---|---|---|
| **Everyone** | No policy for that entry/channel | Visible to all visitors |
| **Members only** | Policy with selected groups/users | Only matching logged-in members |
| **Members only** with no audiences | Policy with zero principals | Fail-closed — hidden from everyone |

## Resolution Order

For each entry:

1. If an **entry policy** exists → that policy decides visibility.
2. Else if a **channel default** exists → that policy decides visibility.
3. Else → **public**.

The entry sidebar shows this **effective** access and notes when the rule is inherited from the channel.

## Query-Level Authorization

Front-end Entry queries are modified before SQL runs. Unauthorized entries are never hydrated.

This applies automatically to:

- Twig `craft.entries`
- PHP `Entry::find()`
- GraphQL (and other consumers) that use Craft Entry queries

Control Panel requests bypass filtering so editors can always manage content.

## Fail Closed

If a policy exists but no principal matches the current visitor, access is denied. Empty audience lists are valid and mean “protected, nobody allowed yet.”

## Settings

- `pluginName` — CP nav label.
- `authorizationEnabled` — master switch for query filtering (CP still bypasses when on).
