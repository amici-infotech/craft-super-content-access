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

### Channel / Group / Product Type Defaults

```php
$principals = $policies->getForSection($sectionId); // null = no channel default
$policies->saveForSection($sectionId, [
    new PolicyPrincipal(PrincipalType::GROUP, '2'),
]);
$policies->deleteForSection($sectionId);

$policies->getForGroup($groupId);
$policies->saveForGroup($groupId, [/* … */]);
$policies->deleteForGroup($groupId);

$policies->getForProductType($productTypeId); // requires Commerce
$policies->saveForProductType($productTypeId, [/* … */]);
$policies->deleteForProductType($productTypeId);
```

## Authorization Service

```php
$auth = Plugin::getInstance()->getAuthorization();

$context = $auth->getContext();
$allowed = $auth->canAccessElement($entry);
$allowed = $auth->canAccessElementId(123);
```

CP requests always return `true` from `canAccessElement*`. Effective access matches element queries (element policy → scope default → public).

Use this from PHP when you already hold an element ID outside a filtered query (for example custom controllers or modules). Front-end Twig does not need a parallel helper — filtered queries already omit unauthorized elements.

## Bypass Query Authorization

To run element queries without access filtering (for admin tools, exports, etc.):

```php
$integrator = Plugin::getInstance()->getElementQueryIntegrator();
$integrator->disable();

try {
    $entries = Entry::find()->section('news')->all();
    $categories = \craft\elements\Category::find()->all();
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
