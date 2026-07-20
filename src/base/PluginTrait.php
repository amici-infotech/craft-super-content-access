<?php
namespace amici\SuperContentAccess\base;

use amici\SuperContentAccess\authorization\AuthorizationContextFactory;
use amici\SuperContentAccess\authorization\AuthorizationPipeline;
use amici\SuperContentAccess\authorization\ResolverRegistry;
use amici\SuperContentAccess\query\EntryQueryIntegrator;
use amici\SuperContentAccess\query\QueryProbe;
use amici\SuperContentAccess\repositories\PolicyRepository;
use amici\SuperContentAccess\services\AuthorizationService;
use amici\SuperContentAccess\services\DiagnosticsService;
use amici\SuperContentAccess\services\PolicyService;
use amici\SuperContentAccess\widgets\ElementSidebarWidget;

/**
 * Shared plugin service registration trait.
 *
 * @property-read PolicyService $policies
 * @property-read AuthorizationService $authorization
 * @property-read DiagnosticsService $diagnostics
 * @property-read PolicyRepository $policyRepository
 * @property-read AuthorizationContextFactory $contextFactory
 * @property-read AuthorizationPipeline $pipeline
 * @property-read ResolverRegistry $resolverRegistry
 * @property-read EntryQueryIntegrator $entryQueryIntegrator
 * @property-read ElementSidebarWidget $elementSidebarWidget
 * @property-read QueryProbe $queryProbe
 */
trait PluginTrait
{
    /**
     * Registers service components used by the plugin trait accessors.
     */
    private function _setPluginComponents(): void
    {
        $this->setComponents([
            'policies' => PolicyService::class,
            'authorization' => AuthorizationService::class,
            'diagnostics' => DiagnosticsService::class,
            'policyRepository' => PolicyRepository::class,
            'contextFactory' => AuthorizationContextFactory::class,
            'pipeline' => AuthorizationPipeline::class,
            'resolverRegistry' => ResolverRegistry::class,
            'entryQueryIntegrator' => EntryQueryIntegrator::class,
            'elementSidebarWidget' => ElementSidebarWidget::class,
            'queryProbe' => QueryProbe::class,
        ]);
    }

    public function getPolicies(): PolicyService
    {
        return $this->get('policies');
    }

    public function getAuthorization(): AuthorizationService
    {
        return $this->get('authorization');
    }

    public function getDiagnostics(): DiagnosticsService
    {
        return $this->get('diagnostics');
    }

    public function getPolicyRepository(): PolicyRepository
    {
        return $this->get('policyRepository');
    }

    public function getContextFactory(): AuthorizationContextFactory
    {
        return $this->get('contextFactory');
    }

    public function getPipeline(): AuthorizationPipeline
    {
        return $this->get('pipeline');
    }

    public function getResolverRegistry(): ResolverRegistry
    {
        return $this->get('resolverRegistry');
    }

    public function getEntryQueryIntegrator(): EntryQueryIntegrator
    {
        return $this->get('entryQueryIntegrator');
    }

    public function getElementSidebarWidget(): ElementSidebarWidget
    {
        return $this->get('elementSidebarWidget');
    }

    public function getQueryProbe(): QueryProbe
    {
        return $this->get('queryProbe');
    }
}
