# Super Content Access for Craft CMS 5

Super Content Access is a **plug-and-play** authorization plugin for Craft CMS. Install it, add the Access Control field where you need it, set who can see what — and keep using normal `craft.entries`, `craft.categories`, and `craft.products` queries. Unauthorized content is filtered at the SQL layer, so it never reaches Twig, PHP, or GraphQL.

No custom query params. No template helpers required for lists. Drop it in and it works with the queries you already write.

## Features

- Restrict entry, category, and product visibility to user groups and/or specific users.
- Automatic query-level filtering — keep using native Craft element queries.
- Channel, category-group, and product-type defaults with per-element overrides.
- Read-only element sidebar summary of effective access.
- Craft dashboard widgets for access overview and breakdown.
- Optional `config/super-content-access.php` overrides (including env-driven enable/disable).
- Settings stay viewable when `allowAdminChanges` is false (read-only).
- Console query probe for verifying SQL constraints.

## Requirements

- Craft CMS 5
- PHP 8.2 or newer
- Craft Commerce (optional) — for product-type defaults and `craft.products` filtering

## Installation

```bash
composer require amici/craft-super-content-access
php craft plugin/install super-content-access
```

You can also install it from **Settings → Plugins** in the Craft Control Panel.

Then:

1. Add the **Access Control** field to the layouts you care about.
2. Set **Everyone** or **Members only** on elements (or set defaults under **General Access**).
3. Keep querying as usual — filtering is automatic when authorization is enabled.

For the full setup flow, see the [documentation](docs/README.md).

## Config file

Create `config/super-content-access.php` to override Control Panel settings. Any key present in this file wins over the CP value; the settings screen shows a warning on overridden fields.

```php
<?php

use craft\helpers\App;

return [
    // Optional CP nav label
    // 'pluginName' => 'Content Access',

    // Enable only in production (uses Craft’s existing env helpers)
    'authorizationEnabled' => App::env('CRAFT_ENVIRONMENT') === 'production',
];
```

Other common patterns:

```php
// Dedicated flag in .env — e.g. SCA_AUTHORIZATION_ENABLED=true
'authorizationEnabled' => App::env('SCA_AUTHORIZATION_ENABLED') === 'true',

// Always on
'authorizationEnabled' => true,

// Always off (useful on local / staging)
'authorizationEnabled' => false,
```

Supported keys:

| Key | Type | Description |
|---|---|---|
| `pluginName` | `string` | Label shown in the Control Panel nav |
| `authorizationEnabled` | `bool` | Master switch for front-end query filtering |

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
- **Settings** — plugin name and authorization toggle (overridable via config).

On entries, categories, and products, add the **Access Control** field to a field layout to edit per-element rules. The sidebar shows a read-only summary of effective access.

## Twig Quick Start

```twig
{# Same queries as before — unauthorized elements never appear #}
{% for entry in craft.entries.section('news').all() %}
    {{ entry.title }}
{% endfor %}

{% for category in craft.categories.group('topics').all() %}
    {{ category.title }}
{% endfor %}
```

## Permissions

- `super-content-access:manage-policies` — manage General Access defaults.

Plugin **Settings** require a Craft admin. With `allowAdminChanges` disabled, settings stay visible but read-only (use the config file instead).

## License

Proprietary - Copyright (c) 2026 Amici Infotech
