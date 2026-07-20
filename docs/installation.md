# Installation and Setup

Super Content Access is built to be **plug-and-play**: install the plugin, add the Access Control field, set policies, and keep using your existing element queries. Front-end filtering is automatic — no special Twig variables or query params.

## Requirements

- Craft CMS 5
- PHP 8.2 or newer
- Craft Commerce (optional) — for products

## Install

```bash
composer require amici/craft-super-content-access
php craft plugin/install super-content-access
```

You can also install it from **Settings → Plugins** in the Control Panel.

## First Setup Checklist

1. (Optional) Add `config/super-content-access.php` to control the master switch from env — see [Config file](#config-file).
2. Go to **Super Content Access → Settings** and confirm **Enable authorization** (unless config already sets it).
3. Optionally rename **Plugin Name** for the CP nav label.
4. Add the **Access Control** field to entry type, category group, and/or product type layouts.
5. Edit an element: choose **Everyone** or **Members only**, then select groups/users.
6. (Optional) Set defaults under **Super Content Access → General Access** (Channels, Categories, Products).
7. (Optional) Add the **Access Overview** / **Access Breakdown** widgets on the Craft dashboard.
8. On the front end, keep using `craft.entries` / `craft.categories` / `craft.products` — unauthorized elements are filtered out automatically.

## Config file

Create `config/super-content-access.php` in your Craft project root’s `config/` folder. Any key present here overrides the matching Control Panel setting; the settings screen shows a warning under overridden fields.

```php
<?php

use craft\helpers\App;

return [
    // Optional CP nav label
    // 'pluginName' => 'Content Access',

    // Tie the master switch to Craft’s environment (or any .env value)
    'authorizationEnabled' => App::env('CRAFT_ENVIRONMENT') === 'production',
];
```

### Env-driven examples

Use Craft’s `App::env()` (or `App::parseBooleanEnv()`) so local, staging, and production can differ without changing CP settings:

```php
<?php

use craft\helpers\App;

return [
    // Only enforce access rules in production
    'authorizationEnabled' => App::env('CRAFT_ENVIRONMENT') === 'production',
];
```

```php
<?php

use craft\helpers\App;

return [
    // Dedicated .env flag — e.g. SCA_AUTHORIZATION_ENABLED=true
    'authorizationEnabled' => App::parseBooleanEnv('$SCA_AUTHORIZATION_ENABLED') ?? true,
];
```

```php
<?php

return [
    'pluginName' => 'Content Access',
    'authorizationEnabled' => true,
];
```

| Key | Type | Description |
|---|---|---|
| `pluginName` | `string` | Label shown in the Control Panel nav |
| `authorizationEnabled` | `bool` | Master switch for front-end query filtering |

When `allowAdminChanges` is `false`, prefer the config file (and env) for changes — the Settings screen stays visible but read-only.

## Permissions

Assign `super-content-access:manage-policies` to user groups that should edit **General Access** defaults.

Plugin **Settings** require a Craft admin user.

## After Install

- Plugin tables: `super_content_access_policies`, `super_content_access_principals`.
- CP section appears in the main nav.
- Front-end Entry, Category, and Product queries apply authorization when policies exist and authorization is enabled.
