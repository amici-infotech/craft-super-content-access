<?php
/**
 * Orchestrates principal resolution for a single Access Policy.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\authorization;

use amici\SuperContentAccess\domain\AccessPolicy;
use amici\SuperContentAccess\domain\AuthorizationContext;
use amici\SuperContentAccess\Plugin;
use craft\base\Component;
use Craft;

/**
 * Orchestrates principal resolution for a single Access Policy.
 *
 * Fail closed: missing resolver or empty principals → deny.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class AuthorizationPipeline extends Component
{
    /**
     * Whether the context is authorized for the given policy.
     *
     * No policy (null) means public content → allow.
     *
     * @param AccessPolicy|null $policy Policy to evaluate, or null for unrestricted content.
     * @param AuthorizationContext $context Current authorization context.
     *
     * @return bool True when access is allowed.
     */
    public function authorize(?AccessPolicy $policy, AuthorizationContext $context): bool
    {
        if ($policy === null) {
            return true;
        }

        if (!$policy->hasPrincipals()) {
            Craft::warning(
                'Access policy has no principals; denying access (fail closed).',
                __METHOD__
            );
            return false;
        }

        $registry = Plugin::getInstance()->getResolverRegistry();

        foreach ($policy->principals as $principal) {
            $resolver = $registry->get($principal->type);

            if ($resolver === null) {
                Craft::warning(
                    "No resolver registered for principal type “{$principal->type}”; denying.",
                    __METHOD__
                );
                return false;
            }

            $constraint = $resolver->resolve($principal, $context);
            if ($constraint->allowed) {
                return true;
            }
        }

        return false;
    }
}
