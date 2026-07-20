<?php
/**
 * Shared plugin service registration trait.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\base;

use amici\SuperContentAccess\authorization\AuthorizationContextFactory;
use amici\SuperContentAccess\authorization\AuthorizationPipeline;
use amici\SuperContentAccess\authorization\ResolverRegistry;
use amici\SuperContentAccess\query\ElementQueryIntegrator;
use amici\SuperContentAccess\query\QueryProbe;
use amici\SuperContentAccess\repositories\PolicyRepository;
use amici\SuperContentAccess\services\AuthorizationService;
use amici\SuperContentAccess\services\DiagnosticsService;
use amici\SuperContentAccess\services\PolicyService;
use amici\SuperContentAccess\widgets\ElementSidebarWidget;

/**
 * Plugin Trait
 *
 * Registers plugin services and exposes typed accessors.
 *
 * @author    Amici Infotech
 * @package   SuperContentAccess
 * @since     5.0.0
 *
 * @property-read PolicyService $policies
 * @property-read AuthorizationService $authorization
 * @property-read DiagnosticsService $diagnostics
 * @property-read PolicyRepository $policyRepository
 * @property-read AuthorizationContextFactory $contextFactory
 * @property-read AuthorizationPipeline $pipeline
 * @property-read ResolverRegistry $resolverRegistry
 * @property-read ElementQueryIntegrator $elementQueryIntegrator
 * @property-read ElementSidebarWidget $elementSidebarWidget
 * @property-read QueryProbe $queryProbe
 */
trait PluginTrait
{
    /**
     * Registers service components used by the plugin trait accessors.
     *
     * @return void Nothing is returned.
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
            'elementQueryIntegrator' => ElementQueryIntegrator::class,
            'elementSidebarWidget' => ElementSidebarWidget::class,
            'queryProbe' => QueryProbe::class,
        ]);
    }

    /**
     * Returns the policy service used for Access Policy CRUD.
     *
     * @return PolicyService The policy service instance.
     */
    public function getPolicies(): PolicyService
    {
        return $this->get('policies');
    }

    /**
     * Returns the authorization service used for element access checks.
     *
     * @return AuthorizationService The authorization service instance.
     */
    public function getAuthorization(): AuthorizationService
    {
        return $this->get('authorization');
    }

    /**
     * Returns the diagnostics service used for dashboard stats.
     *
     * @return DiagnosticsService The diagnostics service instance.
     */
    public function getDiagnostics(): DiagnosticsService
    {
        return $this->get('diagnostics');
    }

    /**
     * Returns the policy repository used for persistence.
     *
     * @return PolicyRepository The policy repository instance.
     */
    public function getPolicyRepository(): PolicyRepository
    {
        return $this->get('policyRepository');
    }

    /**
     * Returns the authorization context factory for the current request.
     *
     * @return AuthorizationContextFactory The context factory instance.
     */
    public function getContextFactory(): AuthorizationContextFactory
    {
        return $this->get('contextFactory');
    }

    /**
     * Returns the authorization pipeline that evaluates policies.
     *
     * @return AuthorizationPipeline The authorization pipeline instance.
     */
    public function getPipeline(): AuthorizationPipeline
    {
        return $this->get('pipeline');
    }

    /**
     * Returns the principal resolver registry.
     *
     * @return ResolverRegistry The resolver registry instance.
     */
    public function getResolverRegistry(): ResolverRegistry
    {
        return $this->get('resolverRegistry');
    }

    /**
     * Returns the element query integrator that applies access constraints.
     *
     * @return ElementQueryIntegrator The element query integrator instance.
     */
    public function getElementQueryIntegrator(): ElementQueryIntegrator
    {
        return $this->get('elementQueryIntegrator');
    }

    /**
     * @deprecated Use getElementQueryIntegrator().
     *
     * @return ElementQueryIntegrator The element query integrator instance.
     */
    public function getEntryQueryIntegrator(): ElementQueryIntegrator
    {
        return $this->getElementQueryIntegrator();
    }

    /**
     * Returns the element sidebar widget renderer.
     *
     * @return ElementSidebarWidget The sidebar widget instance.
     */
    public function getElementSidebarWidget(): ElementSidebarWidget
    {
        return $this->get('elementSidebarWidget');
    }

    /**
     * Returns the query probe helper for console verification.
     *
     * @return QueryProbe The query probe instance.
     */
    public function getQueryProbe(): QueryProbe
    {
        return $this->get('queryProbe');
    }
}
