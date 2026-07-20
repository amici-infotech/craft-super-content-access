# PHP API

Use the plugin services from custom modules, plugins, queue jobs, console commands, or controllers.

```php
use amici\SuperContentAccess\Plugin;
use amici\SuperContentAccess\domain\PolicyPrincipal;
use amici\SuperContentAccess\domain\PrincipalType;
use craft\elements\Entry;
```

## Policy Service

```php
$policies = Plugin::getInstance()->getPolicies();
```

### Entry Policies

```php
$policy = $policies->getForElementId(123);

$policies->saveForElement(123, [
    new PolicyPrincipal(PrincipalType::GROUP, '2'),
    new PolicyPrincipal(PrincipalType::USER, '10'),
]);

$policies->deleteForElement(123); // back to public (unless a channel default applies)
```

### Channel Defaults

```php
$principals = $policies->getForSection($sectionId); // null = no channel default

$policies->saveForSection($sectionId, [
    new PolicyPrincipal(PrincipalType::GROUP, '2'),
]);

$policies->deleteForSection($sectionId);
```

## Authorization Service

```php
$auth = Plugin::getInstance()->getAuthorization();

$context = $auth->getContext();
$allowed = $auth->canAccessElement($entry);
$allowed = $auth->canAccessElementId(123);
```

CP requests always return `true` from `canAccessElement*`. Effective access matches Entry queries (entry policy → channel default → public).

## Twig Variable

Templates use the same check via:

```twig
{% if craft.superContentAccess.canAccess(entry) %}
{% endif %}
```

See [Twig / Front-End Behaviour](twig-usage.md).

## Bypass Query Authorization

To run an Entry query without access filtering (for admin tools, exports, etc.):

```php
$integrator = Plugin::getInstance()->getEntryQueryIntegrator();
$integrator->disable();

try {
    $entries = Entry::find()->section('news')->all();
} finally {
    $integrator->enable();
}
```

Always re-enable in a `finally` block so later queries in the same request stay protected.

There is no Twig query param for this. Prefer keeping front-end templates filtered.

## Context Factory / Pipeline / Resolvers

Advanced extension points:

```php
$plugin = Plugin::getInstance();

$context = $plugin->getContextFactory()->create();
$context = $plugin->getContextFactory()->createFromParams(
    userId: 1,
    groupIds: [2, 3],
    isGuest: false,
    siteId: 1,
    isCpRequest: false,
);

$constraint = $plugin->getPipeline()->authorize($policy, $context);
$plugin->getResolverRegistry()->register($customResolver);
```

## Diagnostics

Used by dashboard widgets and health checks:

```php
$overview = Plugin::getInstance()->getDiagnostics()->overview();
$breakdown = Plugin::getInstance()->getDiagnostics()->breakdown();
```

## Events

`PolicyService` triggers:

- `EVENT_BEFORE_SAVE_POLICY` / `EVENT_AFTER_SAVE_POLICY`
- `EVENT_BEFORE_DELETE_POLICY` / `EVENT_AFTER_DELETE_POLICY`

Cancel a save/delete by setting `$event->isValid = false` on the before events.
