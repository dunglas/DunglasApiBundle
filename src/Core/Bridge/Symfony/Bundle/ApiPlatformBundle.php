<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Symfony\Bundle;

use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\AnnotationFilterPass;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\AuthenticatorManagerPass;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\DataProviderPass;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\DeprecateMercurePublisherPass;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\ElasticsearchClientPass;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\FilterPass;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\GraphQlMutationResolverPass;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\GraphQlQueryResolverPass;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\GraphQlTypePass;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\MetadataAwareNameConverterPass;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\TestClientPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\Compiler\ResolveInstanceofConditionalsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The Symfony bundle.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ApiPlatformBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new DataProviderPass());
        // Run the compiler pass before the {@see ResolveInstanceofConditionalsPass} to allow autoconfiguration of generated filter definitions.
        $container->addCompilerPass(new AnnotationFilterPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 101);
        $container->addCompilerPass(new FilterPass());
        $container->addCompilerPass(new ElasticsearchClientPass());
        $container->addCompilerPass(new GraphQlTypePass());
        $container->addCompilerPass(new GraphQlQueryResolverPass());
        $container->addCompilerPass(new GraphQlMutationResolverPass());
        $container->addCompilerPass(new DeprecateMercurePublisherPass());
        $container->addCompilerPass(new MetadataAwareNameConverterPass());
        $container->addCompilerPass(new TestClientPass());
        $container->addCompilerPass(new AuthenticatorManagerPass());
    }
}
