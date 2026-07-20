# Twig / Front-End Behaviour

Super Content Access does not require special Twig helpers. Continue using native Craft queries.

## Automatic Filtering

```twig
{% for entry in craft.entries.section('news').all() %}
    {{ entry.title }}
{% endfor %}
```

Only entries the current visitor is allowed to see are returned. The same applies to `.one()`, `.ids()`, `.count()`, and related Entry query methods.

## Guests vs Members

- Guests see public entries (no policy) and any policy that explicitly allows the guest/public principal types if present.
- With the current CP UI, **Members only** stores group/user principals, so guests typically cannot see those entries.
- Logged-in members see entries whose effective policy includes their user ID or one of their groups.

## Checking a Single Entry

If you already have an entry (for example from a route), use the authorization service:

```twig
{# Prefer relying on element queries. For ad-hoc checks: #}
{% set allowed = craft.app.plugins.getPlugin('super-content-access')
    .authorization
    .canAccessElement(entry) %}
```

Prefer filtering via `craft.entries` whenever possible so authorization stays at the SQL layer.

## Control Panel

CP requests never apply front-end filtering. Editors always see all entries in the Control Panel.
