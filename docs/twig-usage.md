# Twig / Front-End Behaviour

Entry queries are filtered automatically. Use the Twig variable for ad-hoc checks on a known entry.

## Automatic Filtering

```twig
{% for entry in craft.entries.section('news').all() %}
    {{ entry.title }}
{% endfor %}
```

Only entries the current visitor is allowed to see are returned. The same applies to `.one()`, `.ids()`, `.count()`, and related Entry query methods.

No special query param is required — keep using native Craft Entry queries.

## Twig Variable

Registered as `craft.superContentAccess`.

### Check access to an entry

```twig
{% if craft.superContentAccess.canAccess(entry) %}
    {# visitor may view this entry #}
{% endif %}

{# or by ID #}
{% if craft.superContentAccess.canAccess(entry.id) %}
{% endif %}
```

`canAccess` uses the same effective rules as Entry queries:

1. Entry policy  
2. Else channel default  
3. Else public  

Prefer `craft.entries` for lists so authorization stays at the SQL layer. Use `canAccess` when you already have an entry (for example from a route, relation, or eager-loaded set that was not filtered).

### Example: gate a related entry

```twig
{% set related = entry.relatedArticle.one() %}

{% if related and craft.superContentAccess.canAccess(related) %}
    <a href="{{ related.url }}">{{ related.title }}</a>
{% endif %}
```

## Guests vs Members

- Guests see public entries (no policy) and any policy that explicitly allows guest/public principals if present.
- With the current CP UI, **Members only** stores group/user principals, so guests typically cannot see those entries.
- Logged-in members see entries whose effective policy includes their user ID or one of their groups.

## Getting All Entries (Bypass)

Twig has no per-query bypass flag. To load unfiltered entries from PHP (modules, controllers, custom variables), temporarily disable the integrator — see [PHP API](php-api.md#bypass-query-authorization).

Turning off **Enable authorization** (or `authorizationEnabled` in config) disables filtering site-wide.

## Control Panel

CP requests never apply front-end filtering. Editors always see all entries in the Control Panel.
