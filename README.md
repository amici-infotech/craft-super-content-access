# Super Content Access

Element-level authorization for Craft CMS 5 with query-level access control.

## Requirements

- Craft CMS 5.x
- PHP 8.2+

## Install

```bash
composer require amici/craft-super-content-access
php craft plugin/install super-content-access
```

## Setup

1. Open an entry in the Craft Control Panel
2. Use the **Super Content Access** sidebar widget
3. Choose **Restrict Access** / **Manage Policy**
4. Save the policy audiences
5. Front-end / GraphQL / Twig Entry queries are filtered automatically
6. Control Panel requests always bypass filtering

## Principals (v1)

- User
- User group
- Guest (logged-out)
- Public (everyone)

## Console probe

```bash
php craft super-content-access/query-probe --seed=1 --entryId=276 --userId=1
php craft super-content-access/query-probe --guest=1 --limit=10
php craft super-content-access/query-probe --clear
```

## Architecture

See [`README-AI.md`](README-AI.md) and [`research/`](research/).
