# Console Probe

The query probe helps verify that **Entry** SQL constraints behave as expected without relying on the browser. Category and product filtering use the same production integrator — verify those with normal front-end queries.

## Basic Usage

```bash
php craft super-content-access/query-probe
php craft super-content-access/query-probe --guest=1 --limit=10
php craft super-content-access/query-probe --userId=1 --section=home --limit=20
```

## Seed / Wipe Policies

```bash
# Attach a sample element policy for probing an entry
php craft super-content-access/query-probe --seed=1 --entryId=276 --userId=1

# Wipe EVERY access policy (elements + General Access defaults). Requires --force.
php craft super-content-access/query-probe --clear=1 --force=1
```

`--clear` without `--force` is refused. Prefer this only on local/dev databases.

## What It Does

- Temporarily disables the production integrator when comparing baseline vs constrained queries.
- Builds an authorization context (guest or user/groups).
- Applies the same Entry access constraint used on the front end (including section defaults, structure parent inheritance for entries/categories/products, and author bypass when enabled).
- Prints counts / IDs so you can confirm filtering.

Use this when changing resolvers, indexes, or SQL shape to confirm behaviour before deploying.

## Related

- [Twig / Front-End Behaviour](twig-usage.md)
- [PHP API](php-api.md)
- [Troubleshooting](troubleshooting.md)
