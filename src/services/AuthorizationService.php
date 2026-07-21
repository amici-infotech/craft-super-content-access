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
use amici\SuperContentAccess\domain\PolicyPrincipal;
use amici\SuperContentAccess\helpers\CommerceHelper;
use amici\SuperContentAccess\helpers\StructurePolicyHelper;
use amici\SuperContentAccess\Plugin;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\Category;
use craft\elements\Entry;

/**
 * Authorization helpers for single-element checks and context access.
 *
 * Resolves effective access the same way element queries do:
 * element policy → nearest structure-parent element policy → section / group /
 * product-type General Access default → else public.
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

        return $this->canAccessElementId((int)$element->id, $context, $element);
    }

    /**
     * Whether the given element ID is accessible in the current context.
     *
     * @param int $elementId Element ID to evaluate.
     * @param AuthorizationContext|null $context Optional context override.
     * @param ElementInterface|null $element Optional element when already loaded.
     *
     * @return bool True when access is allowed.
     */
    public function canAccessElementId(int $elementId, ?AuthorizationContext $context = null, ?ElementInterface $element = null): bool
    {
        $context ??= $this->getContext();

        if ($context->isCpRequest) {
            return true;
        }

        $settings = Plugin::getInstance()->getSettings();
        if (!$settings->authorizationEnabled) {
            return true;
        }

        // Optional: Craft admins see everything on the front end.
        if ($settings->adminAlwaysAccess && $context->isAdmin) {
            return true;
        }

        // Optional: entry authors always see their own entries.
        if ($settings->authorAlwaysAccess && $this->isEntryAuthor($elementId, $context, $element)) {
            return true;
        }

        $policies = Plugin::getInstance()->getPolicies();
        $pipeline = Plugin::getInstance()->getPipeline();

        $policy = $policies->getForElementId($elementId);
        if ($policy instanceof AccessPolicy) {
            return $pipeline->authorize($policy, $context);
        }

        // Structured elements: inherit from the nearest parent with an element policy.
        $ancestor = StructurePolicyHelper::nearestAncestorPolicy($elementId);
        if ($ancestor !== null) {
            return $pipeline->authorize($ancestor['policy'], $context);
        }

        $principals = $this->defaultPrincipalsFor($elementId, $element);
        if ($principals === null) {
            return true;
        }

        // Synthetic policy so the pipeline can evaluate scope defaults.
        return $pipeline->authorize(new AccessPolicy(0, $principals), $context);
    }

    /**
     * Whether the current user is an author of the given entry.
     *
     * Categories and products never qualify.
     *
     * @param int $elementId Element ID.
     * @param AuthorizationContext $context Current authorization context.
     * @param ElementInterface|null $element Optional loaded element.
     *
     * @return bool True when the user is an entry author.
     */
    private function isEntryAuthor(int $elementId, AuthorizationContext $context, ?ElementInterface $element = null): bool
    {
        if ($context->userId === null) {
            return false;
        }

        if ($element !== null && !$element instanceof Entry) {
            return false;
        }

        if ($element instanceof Entry) {
            return in_array($context->userId, $element->getAuthorIds(), true);
        }

        if (!$this->isElementType($elementId, Entry::class)) {
            return false;
        }

        return (new Query())
            ->from(['{{%entries_authors}}'])
            ->where([
                'entryId' => $elementId,
                'authorId' => $context->userId,
            ])
            ->exists();
    }

    /**
     * Resolves default-scope principals for an element without an element policy.
     *
     * @param int $elementId Element ID.
     * @param ElementInterface|null $element Optional loaded element.
     *
     * @return PolicyPrincipal[]|null Principals, or null when public (no default).
     */
    private function defaultPrincipalsFor(int $elementId, ?ElementInterface $element = null): ?array
    {
        $policies = Plugin::getInstance()->getPolicies();

        if ($element instanceof Entry || ($element === null && $this->isElementType($elementId, Entry::class))) {
            $sectionId = $element instanceof Entry
                ? ($element->sectionId !== null ? (int)$element->sectionId : null)
                : $this->scalarId('{{%entries}}', 'sectionId', $elementId);

            return $sectionId === null ? null : $policies->getForSection($sectionId);
        }

        if ($element instanceof Category || ($element === null && $this->isElementType($elementId, Category::class))) {
            $groupId = $element instanceof Category
                ? ($element->groupId !== null ? (int)$element->groupId : null)
                : $this->scalarId('{{%categories}}', 'groupId', $elementId);

            return $groupId === null ? null : $policies->getForGroup($groupId);
        }

        $productClass = 'craft\\commerce\\elements\\Product';
        if (
            CommerceHelper::isAvailable()
            && class_exists($productClass)
            && ($element instanceof $productClass || ($element === null && $this->isElementType($elementId, $productClass)))
        ) {
            $typeId = null;
            if ($element !== null && isset($element->typeId)) {
                $typeId = (int)$element->typeId;
            } else {
                $typeId = $this->scalarId('{{%commerce_products}}', 'typeId', $elementId);
            }

            return $typeId === null ? null : $policies->getForProductType($typeId);
        }

        return null;
    }

    /**
     * Whether the element row matches the given type class.
     *
     * @param int $elementId Element ID.
     * @param class-string $type Element type class.
     *
     * @return bool True when the element type matches.
     */
    private function isElementType(int $elementId, string $type): bool
    {
        $found = (new Query())
            ->select(['type'])
            ->from(['{{%elements}}'])
            ->where(['id' => $elementId])
            ->scalar();

        return $found === $type;
    }

    /**
     * Reads a single ID column from an element-related table.
     *
     * @param string $table Table name.
     * @param string $column Column to select.
     * @param int $elementId Element ID (table primary key).
     *
     * @return int|null Column value, or null when missing.
     */
    private function scalarId(string $table, string $column, int $elementId): ?int
    {
        $value = (new Query())
            ->select([$column])
            ->from([$table])
            ->where(['id' => $elementId])
            ->scalar();

        if ($value === null || $value === false || $value === '') {
            return null;
        }

        return (int)$value;
    }
}
