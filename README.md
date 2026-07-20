# Super Content Access for Craft CMS 5

Super Content Access is an authorization plugin for Craft CMS. It lets you restrict which entries visitors can see, using query-level filtering so unauthorized content never leaves the database.

Editors manage access from an **Access Control** field and see the effective policy in the entry sidebar. Administrators can also set **channel-wide defaults** under General Access.

## Features

- Restrict entry, category, and product visibility to user groups and/or specific users.
- Query-level authorization on `craft.entries`, `craft.categories`, and `craft.products` (when Commerce is available).
- Channel, category-group, and product-type default policies with element-level overrides.
- Read-only element sidebar summary showing effective access.
- Craft dashboard widgets for access overview and breakdown charts.
- Plugin settings with `config/super-content-access.php` overrides.
- Settings remain viewable when `allowAdminChanges` is false (read-only).
- Console query probe for verifying SQL constraints.

## Requirements

- Craft CMS 5
- PHP 8.2 or newer

## Installation

```bash
composer require amici/craft-super-content-access
php craft plugin/install super-content-access
```

You can also install it from **Settings → Plugins** in the Craft Control Panel.

For the full setup flow, see the [documentation](docs/README.md).

## Documentation

- [Documentation Home](docs/README.md)
- [Installation and Setup](docs/installation.md)
- [Core Concepts](docs/concepts.md)
- [Backend Guide](docs/backend.md)
- [Twig / Front-End Behaviour](docs/twig-usage.md)
- [PHP API](docs/php-api.md)
- [Console Probe](docs/console.md)
- [Troubleshooting](docs/troubleshooting.md)

## Control Panel

The plugin adds a **Super Content Access** section with:

- **General Access** — channel, category group, and product type default policies.
- **Settings** — plugin name and authorization toggle.

On entries, categories, and products, add the **Access Control** field to a field layout to edit per-element rules. The sidebar shows a read-only summary of effective access.

## Twig Quick Start

```twig
{# Lists are filtered automatically — unauthorized elements never appear #}
{% for entry in craft.entries.section('news').all() %}
    {{ entry.title }}
{% endfor %}

{% for category in craft.categories.group('topics').all() %}
    {{ category.title }}
{% endfor %}
```

## Permissions

- `super-content-access:manage-policies` — manage General Access channel policies.

Plugin **Settings** require a Craft admin. With `allowAdminChanges` disabled, settings stay visible but read-only.

## License

Proprietary - Copyright (c) 2026 Amici Infotech
