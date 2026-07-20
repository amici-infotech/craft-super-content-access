<?php
/**
 * Builds immutable AuthorizationContext snapshots for the current request.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\authorization;

use amici\SuperContentAccess\domain\AuthorizationContext;
use Craft;
use craft\base\Component;
use craft\elements\User;

/**
 * Builds an immutable AuthorizationContext for the current request.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class AuthorizationContextFactory extends Component
{
    /**
     * @var AuthorizationContext|null Request-scoped cached context.
     */
    private ?AuthorizationContext $cached = null;

    /**
     * Creates or returns the cached authorization context for the current request.
     *
     * @return AuthorizationContext The request authorization context.
     */
    public function create(): AuthorizationContext
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        $request = Craft::$app->getRequest();
        $identity = Craft::$app->getUser()->getIdentity();

        $userId = null;
        $groupIds = [];
        $isGuest = true;
        $isAdmin = false;

        if ($identity instanceof User) {
            $isGuest = false;
            $userId = (int)$identity->id;
            $isAdmin = (bool)$identity->admin;
            $groupIds = array_map(
                static fn($group): int => (int)$group->id,
                $identity->getGroups()
            );
        }

        $siteId = null;
        try {
            $siteId = (int)Craft::$app->getSites()->getCurrentSite()->id;
        } catch (\Throwable) {
            $siteId = null;
        }

        $isCpRequest = false;
        try {
            $isCpRequest = $request->getIsCpRequest();
        } catch (\Throwable) {
            $isCpRequest = false;
        }

        $this->cached = new AuthorizationContext(
            $userId,
            $groupIds,
            $isGuest,
            $siteId,
            $isCpRequest,
            $isAdmin,
        );

        return $this->cached;
    }

    /**
     * Builds a synthetic context for console probes and tests.
     *
     * @param int|null $userId User ID, or null for guests.
     * @param int[] $groupIds Group IDs the user belongs to.
     * @param bool $isGuest Whether the visitor is unauthenticated.
     * @param bool $isCpRequest Whether the context represents a CP request.
     * @param int|null $siteId Optional site ID.
     * @param bool $isAdmin Whether the synthetic user is a Craft admin.
     *
     * @return AuthorizationContext The constructed context.
     */
    public function createFromParams(
        ?int $userId = null,
        array $groupIds = [],
        bool $isGuest = false,
        bool $isCpRequest = false,
        ?int $siteId = null,
        bool $isAdmin = false,
    ): AuthorizationContext {
        if ($isGuest) {
            $userId = null;
            $groupIds = [];
            $isAdmin = false;
        }

        return new AuthorizationContext(
            $userId,
            array_values(array_unique(array_map('intval', $groupIds))),
            $isGuest,
            $siteId,
            $isCpRequest,
            $isAdmin,
        );
    }

    /**
     * Clears the request-scoped context cache.
     *
     * @return void Nothing is returned.
     */
    public function reset(): void
    {
        $this->cached = null;
    }
}
