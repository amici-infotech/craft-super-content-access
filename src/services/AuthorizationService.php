<?php
/**
 * Authorization helpers for single-element access checks.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\services;

use amici\SuperContentAccess\domain\AccessPolicy;
use amici\SuperContentAccess\domain\AuthorizationContext;
use amici\SuperContentAccess\domain\contracts\AuthorizationServiceInterface;
use amici\SuperContentAccess\Plugin;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\Entry;

/**
 * Authorization helpers for single-element checks and context access.
 *
 * Resolves effective access the same way Entry queries do:
 * entry policy → else channel default → else public.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class AuthorizationService extends Component implements AuthorizationServiceInterface
{
    /**
     * Returns the authorization context for the current request.
     *
     * @return AuthorizationContext The current authorization context.
     */
    public function getContext(): AuthorizationContext
    {
        return Plugin::getInstance()->getContextFactory()->create();
    }

    /**
     * Whether the given element is accessible in the current context.
     *
     * @param ElementInterface $element Element to evaluate.
     * @param AuthorizationContext|null $context Optional context override.
     *
     * @return bool True when access is allowed.
     */
    public function canAccessElement(ElementInterface $element, ?AuthorizationContext $context = null): bool
    {
        if ($element->id === null) {
            return true;
        }

        $sectionId = $element instanceof Entry ? $element->sectionId : null;

        return $this->canAccessElementId((int)$element->id, $context, $sectionId !== null ? (int)$sectionId : null);
    }

    /**
     * Whether the given element ID is accessible in the current context.
     *
     * @param int $elementId Element ID to evaluate.
     * @param AuthorizationContext|null $context Optional context override.
     * @param int|null $sectionId Optional section ID when already known (avoids a lookup).
     *
     * @return bool True when access is allowed.
     */
    public function canAccessElementId(int $elementId, ?AuthorizationContext $context = null, ?int $sectionId = null): bool
    {
        $context ??= $this->getContext();

        if ($context->isCpRequest) {
            return true;
        }

        $settings = Plugin::getInstance()->getSettings();
        if (!$settings->authorizationEnabled) {
            return true;
        }

        $policies = Plugin::getInstance()->getPolicies();
        $pipeline = Plugin::getInstance()->getPipeline();

        $policy = $policies->getForElementId($elementId);
        if ($policy instanceof AccessPolicy) {
            return $pipeline->authorize($policy, $context);
        }

        $sectionId ??= $this->sectionIdForElement($elementId);
        if ($sectionId === null) {
            return true;
        }

        $principals = $policies->getForSection($sectionId);
        if ($principals === null) {
            return true;
        }

        // Synthetic policy so the pipeline can evaluate channel defaults.
        return $pipeline->authorize(new AccessPolicy(0, $principals), $context);
    }

    /**
     * Looks up the section ID for an entry element without running Entry queries.
     *
     * @param int $elementId Element ID.
     *
     * @return int|null Section ID, or null when the element is not an entry.
     */
    private function sectionIdForElement(int $elementId): ?int
    {
        $sectionId = (new Query())
            ->select(['sectionId'])
            ->from(['{{%entries}}'])
            ->where(['id' => $elementId])
            ->scalar();

        if ($sectionId === null || $sectionId === false || $sectionId === '') {
            return null;
        }

        return (int)$sectionId;
    }
}
