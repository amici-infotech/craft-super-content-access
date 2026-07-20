<?php
namespace amici\SuperContentAccess\authorization;

use amici\SuperContentAccess\authorization\resolvers\GroupResolver;
use amici\SuperContentAccess\authorization\resolvers\GuestResolver;
use amici\SuperContentAccess\authorization\resolvers\PublicResolver;
use amici\SuperContentAccess\authorization\resolvers\UserResolver;
use amici\SuperContentAccess\domain\contracts\PrincipalResolverInterface;
use craft\base\Component;

/**
 * Maps principal types to resolvers. Missing resolver = fail closed.
 */
class ResolverRegistry extends Component
{
    /** @var array<string, PrincipalResolverInterface> */
    private array $resolvers = [];

    private bool $initialized = false;

    public function init(): void
    {
        parent::init();
        $this->registerDefaults();
    }

    public function register(PrincipalResolverInterface $resolver): void
    {
        $this->resolvers[$resolver->getType()] = $resolver;
    }

    public function get(string $principalType): ?PrincipalResolverInterface
    {
        $this->ensureInitialized();

        return $this->resolvers[$principalType] ?? null;
    }

    /**
     * @return PrincipalResolverInterface[]
     */
    public function all(): array
    {
        $this->ensureInitialized();

        return array_values($this->resolvers);
    }

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

    private function ensureInitialized(): void
    {
        if (!$this->initialized) {
            $this->registerDefaults();
        }
    }
}
