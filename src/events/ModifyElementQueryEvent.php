<?php
/**
 * Event fired around query-level authorization SQL injection.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\events;

use amici\SuperContentAccess\domain\AuthorizationContext;
use craft\elements\db\ElementQuery;
use craft\events\CancelableEvent;

/**
 * Cancelable event for ElementQuery authorization modification.
 *
 * Fired by {@see \amici\SuperContentAccess\query\ElementQueryIntegrator}
 * immediately before and after access SQL is applied to a query.
 *
 * Before: set `$event->isValid = false` to skip injecting authorization
 * constraints for that query only.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 *
 * @property ElementQuery $sender Unused — prefer {@see $query}.
 */
class ModifyElementQueryEvent extends CancelableEvent
{
    /**
     * @var ElementQuery The element query about to be (or just) constrained.
     */
    public ElementQuery $query;

    /**
     * @var AuthorizationContext Authorization context used for the constraint.
     */
    public AuthorizationContext $context;

    /**
     * @var class-string Element class being queried (Entry, Category, Product).
     */
    public string $elementType;

    /**
     * @var string Default-scope policy column (sectionId, groupId, productTypeId).
     */
    public string $scopeColumn;
}
