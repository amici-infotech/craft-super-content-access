<?php
namespace amici\SuperContentAccess\services;

use amici\SuperContentAccess\domain\AccessPolicy;
use amici\SuperContentAccess\domain\contracts\PolicyServiceInterface;
use amici\SuperContentAccess\domain\PolicyPrincipal;
use amici\SuperContentAccess\domain\PrincipalType;
use amici\SuperContentAccess\Plugin;
use amici\SuperContentAccess\repositories\PolicyRepository;
use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\elements\User;
use craft\events\CancelableEvent;
use yii\base\Event;
use yii\base\InvalidArgumentException;

/**
 * Application service for Access Policy CRUD and validation.
 */
class PolicyService extends Component implements PolicyServiceInterface
{
    public const EVENT_BEFORE_SAVE_POLICY = 'beforeSavePolicy';
    public const EVENT_AFTER_SAVE_POLICY = 'afterSavePolicy';
    public const EVENT_BEFORE_DELETE_POLICY = 'beforeDeletePolicy';
    public const EVENT_AFTER_DELETE_POLICY = 'afterDeletePolicy';

    public function getForElement(ElementInterface $element): ?AccessPolicy
    {
        if ($element->id === null) {
            return null;
        }

        return $this->getForElementId((int)$element->id);
    }

    public function getForElementId(int $elementId): ?AccessPolicy
    {
        return $this->repository()->findByElementId($elementId);
    }

    /**
     * @param PolicyPrincipal[] $principals
     */
    public function saveForElement(int $elementId, array $principals): AccessPolicy
    {
        $this->validatePrincipals($principals);

        $event = new CancelableEvent(['sender' => $this]);
        $this->trigger(self::EVENT_BEFORE_SAVE_POLICY, $event);

        if (!$event->isValid) {
            throw new \RuntimeException('Access policy save was cancelled.');
        }

        $policy = $this->repository()->save($elementId, $principals);
        Plugin::getInstance()->getEntryQueryIntegrator()->resetMemo();

        $this->trigger(self::EVENT_AFTER_SAVE_POLICY, new Event(['sender' => $this]));

        return $policy;
    }

    public function deleteForElement(int $elementId): bool
    {
        $event = new CancelableEvent(['sender' => $this]);
        $this->trigger(self::EVENT_BEFORE_DELETE_POLICY, $event);

        if (!$event->isValid) {
            return false;
        }

        $deleted = $this->repository()->deleteByElementId($elementId);

        if ($deleted) {
            Plugin::getInstance()->getEntryQueryIntegrator()->resetMemo();
            $this->trigger(self::EVENT_AFTER_DELETE_POLICY, new Event(['sender' => $this]));
        }

        return $deleted;
    }

    /**
     * Loads the principals for a section (channel) default policy.
     *
     * @param int $sectionId Section ID.
     *
     * @return PolicyPrincipal[]|null Principals, or null when no policy exists.
     */
    public function getForSection(int $sectionId): ?array
    {
        return $this->repository()->findBySectionId($sectionId);
    }

    /**
     * Saves a section (channel) default policy.
     *
     * @param int $sectionId Section ID.
     * @param PolicyPrincipal[] $principals Principals to persist.
     *
     * @return void Nothing is returned.
     */
    public function saveForSection(int $sectionId, array $principals): void
    {
        $this->validatePrincipals($principals);
        $this->repository()->saveForSection($sectionId, $principals);
        Plugin::getInstance()->getEntryQueryIntegrator()->resetMemo();
    }

    /**
     * Removes a section (channel) default policy.
     *
     * @param int $sectionId Section ID.
     *
     * @return bool Whether a policy was deleted.
     */
    public function deleteForSection(int $sectionId): bool
    {
        $deleted = $this->repository()->deleteBySectionId($sectionId);
        if ($deleted) {
            Plugin::getInstance()->getEntryQueryIntegrator()->resetMemo();
        }

        return $deleted;
    }

    /**
     * Parse posted Access Control field data into principals.
     *
     * @return PolicyPrincipal[]|null null when input is absent
     */
    public function principalsFromInput(mixed $input): ?array
    {
        if (!is_array($input)) {
            return null;
        }

        $enabled = !empty($input['enabled']) && $input['enabled'] !== '0';

        if (!$enabled) {
            return [];
        }

        $principals = [];

        if (!empty($input['public']) && $input['public'] !== '0') {
            $principals[] = new PolicyPrincipal(PrincipalType::PUBLIC, PrincipalType::WILDCARD);
        }

        if (!empty($input['guest']) && $input['guest'] !== '0') {
            $principals[] = new PolicyPrincipal(PrincipalType::GUEST, PrincipalType::WILDCARD);
        }

        $groupIds = $input['groupIds'] ?? [];
        if (!is_array($groupIds)) {
            $groupIds = $groupIds !== '' && $groupIds !== null ? [$groupIds] : [];
        }

        foreach ($groupIds as $groupId) {
            $groupId = (int)$groupId;
            if ($groupId > 0) {
                $principals[] = new PolicyPrincipal(PrincipalType::GROUP, (string)$groupId);
            }
        }

        $userIds = $input['userIds'] ?? [];
        if (!is_array($userIds)) {
            $userIds = $userIds !== '' && $userIds !== null ? [$userIds] : [];
        }

        foreach ($userIds as $userId) {
            $userId = (int)$userId;
            if ($userId > 0) {
                $principals[] = new PolicyPrincipal(PrincipalType::USER, (string)$userId);
            }
        }

        return $principals;
    }

    /**
     * @param PolicyPrincipal[] $principals
     */
    public function validatePrincipals(array $principals): void
    {
        foreach ($principals as $principal) {
            if (!$principal instanceof PolicyPrincipal) {
                throw new InvalidArgumentException('Invalid policy principal.');
            }

            if (!in_array($principal->type, PrincipalType::ALL, true)) {
                throw new InvalidArgumentException(
                    Craft::t('super-content-access', 'Unsupported principal type “{type}”.', [
                        'type' => $principal->type,
                    ])
                );
            }

            if ($principal->type === PrincipalType::USER) {
                $user = User::find()->id((int)$principal->identifier)->status(null)->one();
                if (!$user) {
                    throw new InvalidArgumentException(
                        Craft::t('super-content-access', 'User {id} does not exist.', [
                            'id' => $principal->identifier,
                        ])
                    );
                }
            }

            if ($principal->type === PrincipalType::GROUP) {
                $group = Craft::$app->getUserGroups()->getGroupById((int)$principal->identifier);
                if (!$group) {
                    throw new InvalidArgumentException(
                        Craft::t('super-content-access', 'User group {id} does not exist.', [
                            'id' => $principal->identifier,
                        ])
                    );
                }
            }
        }
    }

    private function repository(): PolicyRepository
    {
        return Plugin::getInstance()->getPolicyRepository();
    }
}
