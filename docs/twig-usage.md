# Twig / Front-End Behaviour

Entry, category, and product queries are filtered automatically. Unauthorized elements never reach Twig.

## Automatic Filtering

```twig
{% for entry in craft.entries.section('news').all() %}
    {{ entry.title }}
{% endfor %}

{% for category in craft.categories.group('topics').all() %}
    {{ category.title }}
{% endfor %}

{# Requires Craft Commerce #}
{% for product in craft.products.type('clothing').all() %}
    {{ product.title }}
{% endfor %}
```

Only elements the current visitor is allowed to see are returned. The same applies to `.one()`, `.ids()`, `.count()`, related queries of those types, and routes that load via the matching Craft element queries.

No special query param is required. There is no Twig `canAccess` helper: if an element was returned by a filtered query (or loaded via a filtered route), the visitor already has access.

## Guests vs Members

- Guests see public elements (no policy) and any policy that explicitly allows guest/public principals if present.
- With the current CP UI, **Members only** stores group/user principals, so guests typically cannot see those elements.
- Logged-in members see elements whose effective policy includes their user ID or one of their groups.

## Getting All Elements (Bypass)

Twig has no per-query bypass flag. To load unfiltered elements from PHP (modules, controllers), temporarily disable the integrator — see [PHP API](php-api.md#bypass-query-authorization).

Turning off **Enable authorization** (or `authorizationEnabled` in config) disables filtering site-wide.

## Control Panel

CP requests never apply front-end filtering. Editors always see all elements in the Control Panel.
