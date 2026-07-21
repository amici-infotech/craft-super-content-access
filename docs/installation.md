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
6. (Optional) Set defaults under **Super Content Access → General Access** (Sections, Categories, Products).
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

    // Craft admins see all protected content on the front end
    'adminAlwaysAccess' => true,

    // Entry authors always see their own entries (entries only)
    'authorAlwaysAccess' => true,
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
    'adminAlwaysAccess' => true,
    'authorAlwaysAccess' => true,
];
```

```php
<?php

use craft\helpers\App;

return [
    // Dedicated .env flags
    'authorizationEnabled' => App::parseBooleanEnv('$SCA_AUTHORIZATION_ENABLED') ?? true,
    'adminAlwaysAccess' => App::parseBooleanEnv('$SCA_ADMIN_ALWAYS_ACCESS') ?? true,
    'authorAlwaysAccess' => App::parseBooleanEnv('$SCA_AUTHOR_ALWAYS_ACCESS') ?? true,
];
```

```php
<?php

return [
    'pluginName' => 'Content Access',
    'authorizationEnabled' => true,
    'adminAlwaysAccess' => true,
    'authorAlwaysAccess' => true,
];
```

| Key | Type | Default | Description |
|---|---|---|---|
| `pluginName` | `string` | `Super Content Access` | Label shown in the Control Panel nav |
| `authorizationEnabled` | `bool` | `true` | Master switch for front-end query filtering |
| `adminAlwaysAccess` | `bool` | `true` | Craft admins always see protected content on the front end |
| `authorAlwaysAccess` | `bool` | `true` | Entry authors always see their own entries (not categories or products) |

When `allowAdminChanges` is `false`, prefer the config file (and env) for changes — the Settings screen stays visible but read-only.

## Permissions

Assign `super-content-access:manage-policies` to user groups that should edit **General Access** defaults.

Plugin **Settings** require a Craft admin user.

## After Install

- Plugin tables: `super_content_access_policies`, `super_content_access_principals`.
- CP section appears in the main nav.
- Front-end Entry, Category, and Product queries apply authorization when policies exist and authorization is enabled.
