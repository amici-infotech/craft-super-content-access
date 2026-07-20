<?php
/**
 * Access-policy stats for dashboard widgets and health checks.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */
namespace amici\SuperContentAccess\services;

use amici\SuperContentAccess\domain\PrincipalType;
use amici\SuperContentAccess\Plugin;
use Craft;
use craft\base\Component;
use craft\models\Section;

/**
 * Aggregates access-control stats for Craft dashboard widgets.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class DiagnosticsService extends Component
{
    /**
     * Compact summary used by older call sites.
     *
     * @return array{authorizationEnabled: bool, policyCount: int, resolverTypes: string[]}
     */
    public function summary(): array
    {
        $overview = $this->overview();

        return [
            'authorizationEnabled' => $overview['authorizationEnabled'],
            'policyCount' => $overview['totalPolicyCount'],
            'resolverTypes' => $overview['resolverTypes'],
        ];
    }

    /**
     * Headline numbers for the Access Overview widget.
     *
     * @return array{
     *     authorizationEnabled: bool,
     *     totalPolicyCount: int,
     *     elementPolicyCount: int,
     *     sectionPolicyCount: int,
     *     principalCount: int,
     *     channelCount: int,
     *     restrictedChannelCount: int,
     *     resolverTypes: string[]
     * }
     */
    public function overview(): array
    {
        $plugin = Plugin::getInstance();
        $repository = $plugin->getPolicyRepository();
        $principals = $repository->countPrincipals();

        $channelCount = 0;
        foreach (Craft::$app->getEntries()->getAllSections() as $section) {
            if ($section->type === Section::TYPE_CHANNEL) {
                $channelCount++;
            }
        }

        return [
            'authorizationEnabled' => $plugin->getSettings()->authorizationEnabled,
            'totalPolicyCount' => $repository->countPolicies(),
            'elementPolicyCount' => $repository->countElementPolicies(),
            'sectionPolicyCount' => $repository->countSectionPolicies(),
            'groupPolicyCount' => $repository->countGroupPolicies(),
            'productTypePolicyCount' => $repository->countProductTypePolicies(),
            'principalCount' => $principals['total'],
            'channelCount' => $channelCount,
            'restrictedChannelCount' => $repository->countSectionPolicies(),
            'resolverTypes' => array_map(
                static fn($resolver): string => $resolver->getType(),
                $plugin->getResolverRegistry()->all()
            ),
        ];
    }

    /**
     * Chart series for the Access Breakdown widget.
     *
     * @return array{
     *     byScope: list<array{key: string, label: string, value: int, color: string}>,
     *     byPrincipalType: list<array{key: string, label: string, value: int, color: string}>
     * }
     */
    public function breakdown(): array
    {
        $repository = Plugin::getInstance()->getPolicyRepository();
        $principals = $repository->countPrincipals();

        return [
            'byScope' => [
                [
                    'key' => 'element',
                    'label' => Craft::t('super-content-access', 'Element policies'),
                    'value' => $repository->countElementPolicies(),
                    'color' => '#2e70e6',
                ],
                [
                    'key' => 'section',
                    'label' => Craft::t('super-content-access', 'Channel defaults'),
                    'value' => $repository->countSectionPolicies(),
                    'color' => '#cf7118',
                ],
                [
                    'key' => 'group',
                    'label' => Craft::t('super-content-access', 'Category group defaults'),
                    'value' => $repository->countGroupPolicies(),
                    'color' => '#14884a',
                ],
                [
                    'key' => 'productType',
                    'label' => Craft::t('super-content-access', 'Product type defaults'),
                    'value' => $repository->countProductTypePolicies(),
                    'color' => '#805ad5',
                ],
            ],
            'byPrincipalType' => [
                [
                    'key' => PrincipalType::GROUP,
                    'label' => Craft::t('super-content-access', 'User groups'),
                    'value' => (int)($principals['byType'][PrincipalType::GROUP] ?? 0),
                    'color' => '#14884a',
                ],
                [
                    'key' => PrincipalType::USER,
                    'label' => Craft::t('super-content-access', 'Specific users'),
                    'value' => (int)($principals['byType'][PrincipalType::USER] ?? 0),
                    'color' => '#805ad5',
                ],
            ],
        ];
    }
}
