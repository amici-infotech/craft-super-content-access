# PHP API

Use the plugin services from custom modules, plugins, queue jobs, console commands, or controllers.

```php
use amici\SuperContentAccess\Plugin;
use amici\SuperContentAccess\domain\PolicyPrincipal;
use amici\SuperContentAccess\domain\PrincipalType;
use craft\elements\Entry;
use yii\base\Event;
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

CP requests always return `true` from `canAccessElement*`. Effective access matches element queries (element policy → scope default → public), including admin/author bypass settings.

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
    isCpRequest: false,
    siteId: 1,
    isAdmin: false,
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

Register listeners from a module or plugin `init()` method. Policy events fire for element policies and for channel / category-group / product-type defaults. Query events fire only when the integrator is about to inject (or has just injected) authorization SQL.

### Policy events

Fired by `PolicyService` on every save/delete.

| Constant | When | Cancelable |
|---|---|---|
| `PolicyService::EVENT_BEFORE_SAVE_POLICY` | Before a policy is written | Yes — set `$event->isValid = false` to abort (throws on save) |
| `PolicyService::EVENT_AFTER_SAVE_POLICY` | After a policy is written | No |
| `PolicyService::EVENT_BEFORE_DELETE_POLICY` | Before a policy is removed | Yes — set `$event->isValid = false` to abort (returns `false` on delete) |
| `PolicyService::EVENT_AFTER_DELETE_POLICY` | After a policy is removed | No |

Event class: `amici\SuperContentAccess\events\PolicyEvent`

Useful properties:

- `$event->elementId` — set for per-element policies
- `$event->sectionId` — set for channel defaults
- `$event->groupId` — set for category-group defaults
- `$event->productTypeId` — set for product-type defaults
- `$event->principals` — principals being saved (save events)
- `$event->policy` — saved `AccessPolicy` (after element save)

```php
use amici\SuperContentAccess\events\PolicyEvent;
use amici\SuperContentAccess\services\PolicyService;
use Craft;
use yii\base\Event;

Event::on(
    PolicyService::class,
    PolicyService::EVENT_BEFORE_SAVE_POLICY,
    static function (PolicyEvent $event): void {
        // Block empty Members-only policies for a specific entry
        if ($event->elementId === 123 && ($event->principals ?? []) === []) {
            $event->isValid = false;
            Craft::warning('Refused empty access policy for entry 123.', __METHOD__);
        }
    }
);

Event::on(
    PolicyService::class,
    PolicyService::EVENT_AFTER_SAVE_POLICY,
    static function (PolicyEvent $event): void {
        if ($event->sectionId !== null) {
            Craft::info("Channel default saved for section {$event->sectionId}.", __METHOD__);
        }

        if ($event->policy !== null) {
            Craft::info("Element policy #{$event->policy->id} saved.", __METHOD__);
        }
    }
);

Event::on(
    PolicyService::class,
    PolicyService::EVENT_BEFORE_DELETE_POLICY,
    static function (PolicyEvent $event): void {
        // Keep a protected channel default from being cleared
        if ($event->sectionId === 5) {
            $event->isValid = false;
        }
    }
);

Event::on(
    PolicyService::class,
    PolicyService::EVENT_AFTER_DELETE_POLICY,
    static function (PolicyEvent $event): void {
        Craft::info(sprintf(
            'Policy deleted (element=%s section=%s group=%s productType=%s).',
            $event->elementId ?? '-',
            $event->sectionId ?? '-',
            $event->groupId ?? '-',
            $event->productTypeId ?? '-'
        ), __METHOD__);
    }
);
```

### Query modification events

Fired by `ElementQueryIntegrator` when authorization SQL is about to be applied to an Entry, Category, or Product query (after CP / admin / “no policies” fast paths have already decided that filtering is needed).

| Constant | When | Cancelable |
|---|---|---|
| `ElementQueryIntegrator::EVENT_BEFORE_MODIFY_QUERY` | Immediately before access SQL is injected | Yes — set `$event->isValid = false` to skip filtering for that query only |
| `ElementQueryIntegrator::EVENT_AFTER_MODIFY_QUERY` | Immediately after access SQL is injected | No |

Event class: `amici\SuperContentAccess\events\ModifyElementQueryEvent`

Useful properties:

- `$event->query` — the `ElementQuery` being prepared
- `$event->context` — current `AuthorizationContext` (user, groups, admin, CP flag, …)
- `$event->elementType` — element class string (`Entry::class`, `Category::class`, or Product class)
- `$event->scopeColumn` — default-scope column (`sectionId`, `groupId`, or `productTypeId`)

```php
use amici\SuperContentAccess\events\ModifyElementQueryEvent;
use amici\SuperContentAccess\query\ElementQueryIntegrator;
use craft\elements\Entry;
use craft\elements\db\EntryQuery;
use yii\base\Event;

Event::on(
    ElementQueryIntegrator::class,
    ElementQueryIntegrator::EVENT_BEFORE_MODIFY_QUERY,
    static function (ModifyElementQueryEvent $event): void {
        // Skip plugin filtering for a one-off admin export query
        if ($event->query instanceof EntryQuery && $event->query->sectionId === [42]) {
            $event->isValid = false;
        }
    }
);

Event::on(
    ElementQueryIntegrator::class,
    ElementQueryIntegrator::EVENT_AFTER_MODIFY_QUERY,
    static function (ModifyElementQueryEvent $event): void {
        if ($event->elementType !== Entry::class) {
            return;
        }

        // Inspect / append extra conditions after the plugin’s constraint
        // $event->query->andWhere(...);
    }
);
```

`EVENT_BEFORE_MODIFY_QUERY` is a per-query escape hatch. Prefer `$integrator->disable()` / `enable()` when you need to bypass filtering for a whole block of PHP, and prefer settings (`authorizationEnabled`, `adminAlwaysAccess`) for site-wide behaviour.
