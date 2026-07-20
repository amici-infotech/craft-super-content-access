# Installation and Setup

## Requirements

- Craft CMS 5
- PHP 8.2 or newer

## Install

```bash
composer require amici/craft-super-content-access
php craft plugin/install super-content-access
```

You can also install it from **Settings → Plugins** in the Control Panel.

## First Setup Checklist

1. Go to **Super Content Access → Settings**.
2. Confirm **Enable authorization** is on (or override it in config — see below).
3. Optionally rename **Plugin Name** for the CP nav label.
4. Open an entry type’s field layout and add the **Access Control** field.
5. Edit an entry: choose **Everyone** or **Members only**, then select groups/users.
6. (Optional) Go to **Super Content Access → General Access → Channels** and set channel defaults.
7. (Optional) Add the **Access Overview** / **Access Breakdown** widgets on the Craft dashboard.
8. On the front end, rely on `craft.entries` for filtered lists, or use `craft.superContentAccess.canAccess(entry)` for a single-entry check.

## Config File Overrides

Create `config/super-content-access.php` in your Craft project:

```php
<?php

return [
    'pluginName' => 'Super Content Access',
    'authorizationEnabled' => true,
];
```

Any key present in this file overrides the Control Panel setting. The settings screen shows a warning under overridden fields.

## Permissions

Assign `super-content-access:manage-policies` to user groups that should edit **General Access** channel policies.

Plugin **Settings** require a Craft admin user. With `allowAdminChanges` disabled, settings remain viewable but not editable.

## After Install

- Plugin tables: `super_content_access_policies`, `super_content_access_principals`.
- CP section appears in the main nav.
- Entry queries on the front end begin applying authorization when policies exist and authorization is enabled.
- Twig variable `craft.superContentAccess` is available in templates.
