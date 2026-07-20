<?php
/**
 * Maps principal types to resolvers for authorization evaluation.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\authorization;

use amici\SuperContentAccess\authorization\resolvers\GroupResolver;
use amici\SuperContentAccess\authorization\resolvers\GuestResolver;
use amici\SuperContentAccess\authorization\resolvers\PublicResolver;
use amici\SuperContentAccess\authorization\resolvers\UserResolver;
use amici\SuperContentAccess\domain\contracts\PrincipalResolverInterface;
use craft\base\Component;

/**
 * Maps principal types to resolvers. Missing resolver = fail closed.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class ResolverRegistry extends Component
{
    /** @var array<string, PrincipalResolverInterface> */
    private array $resolvers = [];

    /**
     * @var bool Whether default resolvers have been registered.
     */
    private bool $initialized = false;

    /**
     * Registers default resolvers when the component initializes.
     *
     * @return void Nothing is returned.
     */
    public function init(): void
    {
        parent::init();
        $this->registerDefaults();
    }

    /**
     * Registers a principal resolver for its type handle.
     *
     * @param PrincipalResolverInterface $resolver Resolver to register.
     *
     * @return void Nothing is returned.
     */
    public function register(PrincipalResolverInterface $resolver): void
    {
        $this->resolvers[$resolver->getType()] = $resolver;
    }

    /**
     * Returns the resolver for a principal type, if registered.
     *
     * @param string $principalType Principal type handle.
     *
     * @return PrincipalResolverInterface|null The resolver, or null when missing.
     */
    public function get(string $principalType): ?PrincipalResolverInterface
    {
        $this->ensureInitialized();

        return $this->resolvers[$principalType] ?? null;
    }

    /**
     * Returns all registered resolvers.
     *
     * @return PrincipalResolverInterface[] Registered resolvers.
     */
    public function all(): array
    {
        $this->ensureInitialized();

        return array_values($this->resolvers);
    }

    /**
     * Registers the built-in user, group, guest, and public resolvers.
     *
     * @return void Nothing is returned.
     */
    private function registerDefaults(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->register(new UserResolver());
        $this->register(new GroupResolver());
        $this->register(new GuestResolver());
        $this->register(new PublicResolver());
        $this->initialized = true;
    }

    /**
     * Ensures default resolvers are registered before lookup.
     *
     * @return void Nothing is returned.
     */
    private function ensureInitialized(): void
    {
        if (!$this->initialized) {
            $this->registerDefaults();
        }
    }
}
