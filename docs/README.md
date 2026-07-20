# Super Content Access Documentation

Super Content Access restricts which Craft entries visitors can see. Authorization runs at the SQL layer on Entry queries, so Twig, PHP, and GraphQL consumers of `craft.entries` only receive allowed results.

This documentation is split by task so you can find the right guide quickly.

## Start Here

- [Installation and Setup](installation.md) — install the plugin, add the field, and configure settings.
- [Core Concepts](concepts.md) — policies, principals, channel defaults, Everyone vs Members only, fail-closed behaviour.
- [Backend Guide](backend.md) — Control Panel screens, field, sidebar, General Access, dashboard widgets.

## Developer Reference

- [Twig / Front-End Behaviour](twig-usage.md) — how front-end queries behave; no special Twig helpers required.
- [PHP API](php-api.md) — services for modules, plugins, jobs, and custom code.
- [Console Probe](console.md) — verify SQL constraints with the query probe command.
- [Troubleshooting](troubleshooting.md) — common setup, visibility, and performance issues.

## Quick Mental Model

- An **Access Policy** belongs either to one entry (`elementId`) or to one channel (`sectionId`).
- **Policy Principals** are the audiences on that policy (user groups and/or users).
- **No policy** means public (everyone can see the entry).
- **Empty principals** on a policy means fail-closed (nobody can see it).
- **Resolution order:** entry policy → else channel default → else public.
- The **Access Control** field edits entry policies. The **sidebar** only displays effective access.
- **General Access** edits channel defaults for all entries in that channel without their own policy.
- Control Panel requests always bypass front-end filtering.
