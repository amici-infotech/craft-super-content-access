<?php
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
 */
class AuthorizationPipeline extends Component
{
    /**
     * Whether the context is authorized for the given policy.
     *
     * No policy (null) means public content → allow.
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
