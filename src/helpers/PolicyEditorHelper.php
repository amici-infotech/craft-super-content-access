<?php
/**
 * Shared helpers for the Access Control field and General Access editors.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\helpers;

use amici\SuperContentAccess\domain\PolicyPrincipal;
use amici\SuperContentAccess\domain\PrincipalType;
use amici\SuperContentAccess\fields\data\AccessControlValue;
use Craft;
use craft\elements\User;

/**
 * Maps between editor values, posted policy data, and domain principals.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
final class PolicyEditorHelper
{
    /**
     * Prevents instantiation.
     */
    private function __construct()
    {
    }

    /**
     * Builds an editor value from stored principals.
     *
     * @param PolicyPrincipal[]|null $principals Stored principals, or null when public.
     *
     * @return AccessControlValue Value for the shared policy editor.
     */
    public static function valueFromPrincipals(?array $principals): AccessControlValue
    {
        if ($principals === null) {
            return new AccessControlValue();
        }

        $groupIds = [];
        $userIds = [];

        foreach ($principals as $principal) {
            if ($principal->type === PrincipalType::GROUP) {
                $groupIds[] = (int)$principal->identifier;
            } elseif ($principal->type === PrincipalType::USER) {
                $userIds[] = (int)$principal->identifier;
            }
        }

        return new AccessControlValue(
            enabled: true,
            groupIds: array_values(array_unique(array_filter($groupIds))),
            userIds: array_values(array_unique(array_filter($userIds))),
        );
    }

    /**
     * Converts an editor value into domain principals.
     *
     * @param AccessControlValue $value Field / editor value.
     *
     * @return PolicyPrincipal[] Principals for persistence.
     */
    public static function principalsFromValue(AccessControlValue $value): array
    {
        $principals = [];

        foreach ($value->groupIds as $groupId) {
            $principals[] = new PolicyPrincipal(PrincipalType::GROUP, (string)$groupId);
        }

        foreach ($value->userIds as $userId) {
            $principals[] = new PolicyPrincipal(PrincipalType::USER, (string)$userId);
        }

        return $principals;
    }

    /**
     * Converts posted editor data into domain principals.
     *
     * @param array $policy Posted policy data (`groupIds`, `userIds`).
     *
     * @return PolicyPrincipal[] Principals to persist.
     */
    public static function principalsFromInput(array $policy): array
    {
        $principals = [];

        foreach (self::intList($policy['groupIds'] ?? []) as $groupId) {
            $principals[] = new PolicyPrincipal(PrincipalType::GROUP, (string)$groupId);
        }

        foreach (self::intList($policy['userIds'] ?? []) as $userId) {
            $principals[] = new PolicyPrincipal(PrincipalType::USER, (string)$userId);
        }

        return $principals;
    }

    /**
     * Builds user group options for the editor.
     *
     * @return array<int, array{label: string, value: string}> Group options.
     */
    public static function groupOptions(): array
    {
        $options = [];

        foreach (Craft::$app->getUserGroups()->getAllGroups() as $group) {
            $options[] = [
                'label' => $group->name,
                'value' => (string)$group->id,
            ];
        }

        return $options;
    }

    /**
     * Loads the selected user elements for the editor.
     *
     * @param AccessControlValue $value Editor value.
     *
     * @return User[] Selected users.
     */
    public static function selectedUsers(AccessControlValue $value): array
    {
        if ($value->userIds === []) {
            return [];
        }

        return User::find()
            ->id($value->userIds)
            ->status(null)
            ->fixedOrder()
            ->all();
    }

    /**
     * Normalizes a submitted list into unique positive integers.
     *
     * @param mixed $input Raw submitted value.
     *
     * @return int[] Filtered integer list.
     */
    public static function intList(mixed $input): array
    {
        if (!is_array($input)) {
            $input = $input === '' || $input === null ? [] : [$input];
        }

        $ids = [];
        foreach ($input as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
