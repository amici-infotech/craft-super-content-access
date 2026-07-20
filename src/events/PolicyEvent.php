<?php
/**
 * Event fired around access policy save and delete operations.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\events;

use amici\SuperContentAccess\domain\AccessPolicy;
use amici\SuperContentAccess\domain\PolicyPrincipal;
use craft\events\CancelableEvent;

/**
 * Cancelable event for Access Policy mutations.
 *
 * Before events: set `$event->isValid = false` to abort the operation.
 * After events: read the resulting policy or identifiers after persistence.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class PolicyEvent extends CancelableEvent
{
    /**
     * @var int|null Element ID for element-scoped policies.
     */
    public ?int $elementId = null;

    /**
     * @var int|null Section ID for channel default policies.
     */
    public ?int $sectionId = null;

    /**
     * @var int|null Category group ID for group default policies.
     */
    public ?int $groupId = null;

    /**
     * @var int|null Product type ID for Commerce product-type defaults.
     */
    public ?int $productTypeId = null;

    /**
     * @var PolicyPrincipal[]|null Principals being saved (before/after save).
     */
    public ?array $principals = null;

    /**
     * @var AccessPolicy|null Saved policy (after save for element policies).
     */
    public ?AccessPolicy $policy = null;
}
