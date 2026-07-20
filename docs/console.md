# Console Probe

The query probe helps verify that Entry SQL constraints behave as expected without relying on the browser.

## Basic Usage

```bash
php craft super-content-access/query-probe
php craft super-content-access/query-probe --guest=1 --limit=10
php craft super-content-access/query-probe --userId=1 --section=home --limit=20
```

## Seed / Clear Test Principals

```bash
# Attach temporary principals for probing an entry
php craft super-content-access/query-probe --seed=1 --entryId=276 --userId=1

# Remove probe-seeded data
php craft super-content-access/query-probe --clear
```

## What It Does

- Temporarily disables the production integrator when comparing baseline vs constrained queries.
- Builds an authorization context (guest or user/groups).
- Applies the same access constraint used on the front end (including channel defaults).
- Prints counts / IDs so you can confirm filtering.

Use this when changing resolvers, indexes, or SQL shape to confirm behaviour before deploying.

## Related

- [Twig / Front-End Behaviour](twig-usage.md)
- [PHP API](php-api.md)
- [Troubleshooting](troubleshooting.md)
