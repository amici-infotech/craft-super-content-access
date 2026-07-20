# Super Content Access Documentation

Super Content Access restricts which Craft entries visitors can see. Authorization runs at the SQL layer on Entry queries, so Twig, PHP, and GraphQL consumers of `craft.entries` only receive allowed results.

This documentation is split by task so you can find the right guide quickly.

## Start Here

- [Installation and Setup](installation.md) — install the plugin, add the field, and configure settings.
- [Core Concepts](concepts.md) — policies, principals, channel defaults, Everyone vs Members only, fail-closed behaviour.
- [Backend Guide](backend.md) — Control Panel screens, field, sidebar, General Access, dashboard widgets.

## Developer Reference

- [Twig / Front-End Behaviour](twig-usage.md) — automatic Entry query filtering on the front end.
- [PHP API](php-api.md) — services, events, bypass helpers, and extension hooks.
- [Console Probe](console.md) — verify SQL constraints with the query probe command.
- [Troubleshooting](troubleshooting.md) — common setup, visibility, and performance issues.

## Quick Mental Model

- An **Access Policy** belongs to one element (`elementId`) or one default scope (`sectionId`, `groupId`, or `productTypeId`).
- **Policy Principals** are the audiences on that policy (user groups and/or users).
- **No policy** means public (everyone can see the element).
- **Empty principals** on a policy means fail-closed (nobody can see it).
- **Resolution order:** element policy → else scope default → else public.
- The **Access Control** field edits element policies. The **sidebar** only displays effective access.
- **General Access** edits defaults for channels, category groups, and product types.
- Prefer filtered element queries on the front end; unauthorized elements are omitted at the SQL layer.
- Control Panel requests always bypass front-end filtering.
