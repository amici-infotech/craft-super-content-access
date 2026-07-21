<?php
/**
 * Super Content Access field value object.
 *
 * Presentation/transport model for the Access Control field. The authoritative
 * data always lives in the plugin's own tables via the Policy Service; this
 * object only carries the editor's current selection for a single request.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */
namespace amici\SuperContentAccess\fields\data;

/**
 * Immutable-ish snapshot of the access rules shown in the field input.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
final class AccessControlValue
{
    /**
     * @param bool $enabled Whether the element is restricted (a policy exists).
     * @param int[] $groupIds Allowed user group IDs.
     * @param int[] $userIds Allowed user IDs.
     * @param bool $submitted True only when built from a form submission.
     */
    public function __construct(
        public bool $enabled = false,
        public array $groupIds = [],
        public array $userIds = [],
        public bool $submitted = false,
    ) {
    }

    /**
     * Whether the policy is enabled but has no members selected (fail-closed).
     *
     * @return bool True when restricted with nobody allowed.
     */
    public function isFailClosed(): bool
    {
        return $this->enabled && !$this->hasAudiences();
    }

    /**
     * Whether any member (group or user) is selected.
     *
     * @return bool True when at least one group or user is allowed.
     */
    public function hasAudiences(): bool
    {
        return $this->groupIds !== [] || $this->userIds !== [];
    }
}
