<?php
namespace amici\SuperContentAccess\authorization;

use amici\SuperContentAccess\domain\AuthorizationContext;
use Craft;
use craft\base\Component;
use craft\elements\User;

/**
 * Builds an immutable AuthorizationContext for the current request.
 */
class AuthorizationContextFactory extends Component
{
    private ?AuthorizationContext $cached = null;

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

        if ($identity instanceof User) {
            $isGuest = false;
            $userId = (int)$identity->id;
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
        );

        return $this->cached;
    }

    /**
     * Build a synthetic context (console probes / tests).
     *
     * @param int[] $groupIds
     */
    public function createFromParams(
        ?int $userId = null,
        array $groupIds = [],
        bool $isGuest = false,
        bool $isCpRequest = false,
        ?int $siteId = null,
    ): AuthorizationContext {
        if ($isGuest) {
            $userId = null;
            $groupIds = [];
        }

        return new AuthorizationContext(
            $userId,
            array_values(array_unique(array_map('intval', $groupIds))),
            $isGuest,
            $siteId,
            $isCpRequest,
        );
    }

    /**
     * Reset request cache (tests / console re-runs).
     */
    public function reset(): void
    {
        $this->cached = null;
    }
}
