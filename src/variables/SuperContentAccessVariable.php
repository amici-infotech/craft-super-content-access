<?php
/**
 * Twig variable for Super Content Access template helpers.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\variables;

use amici\SuperContentAccess\Plugin;
use craft\base\ElementInterface;

/**
 * Twig variable registered as `craft.superContentAccess`.
 *
 * Usage:
 * ```twig
 * {% if craft.superContentAccess.canAccess(entry) %}
 * {% if craft.superContentAccess.canAccess(entry.id) %}
 * ```
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class SuperContentAccessVariable
{
    /**
     * Whether the current visitor may access the given entry/element.
     *
     * Accepts an element or a numeric element ID. Prefer `craft.entries`
     * filtering for lists; use this for ad-hoc checks on a known entry.
     *
     * @param ElementInterface|int $element Element instance or element ID.
     *
     * @return bool True when access is allowed.
     */
    public function canAccess(ElementInterface|int $element): bool
    {
        $auth = Plugin::getInstance()->getAuthorization();

        if ($element instanceof ElementInterface) {
            return $auth->canAccessElement($element);
        }

        return $auth->canAccessElementId((int)$element);
    }
}
