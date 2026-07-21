# Super Content Access Documentation

Super Content Access is a **plug-and-play** authorization plugin for Craft CMS. Install it, set who can see which entries, categories, and products, and keep writing normal element queries — unauthorized results are filtered at the SQL layer for Twig, PHP, and GraphQL.

No custom query API is required for day-to-day front-end work.

This documentation is split by task so you can find the right guide quickly.

## Start Here

- [Installation and Setup](installation.md) — install, optional config/env switch, field, and first policies.
- [Core Concepts](concepts.md) — policies, principals, scope defaults, Everyone vs Members only, fail-closed behaviour.
- [Backend Guide](backend.md) — Control Panel screens, field, sidebar, General Access, dashboard widgets.

## Developer Reference

- [Twig / Front-End Behaviour](twig-usage.md) — automatic filtering with the queries you already use.
- [PHP API](php-api.md) — services, events, bypass helpers, and extension hooks.
- [Console Probe](console.md) — verify SQL constraints with the query probe command.
- [Troubleshooting](troubleshooting.md) — common setup, visibility, and performance issues.

## Quick Mental Model

- Install → add the Access Control field → set policies → keep querying as usual.
- An **Access Policy** belongs to one element (`elementId`) or one default scope (`sectionId`, `groupId`, or `productTypeId`).
- **Policy Principals** are the audiences on that policy (user groups and/or users).
- **No policy** means public (everyone can see the element).
- **Empty principals** on a policy means fail-closed (nobody can see it).
- **Resolution order:** element policy → nearest structure parent (when applicable) → else scope default → else public.
- Optional `config/super-content-access.php` can drive settings from `.env` / `CRAFT_ENVIRONMENT` (`authorizationEnabled`, `adminAlwaysAccess`, `authorAlwaysAccess`).
- Control Panel requests always bypass front-end filtering.
