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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Util\AnnotationFilterExtractorTrait;
use ApiPlatform\Core\Util\ReflectionClassRecursiveIterator;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Registers filter services from {@see ApiFilter} annotations.
 *
 * @internal
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class AnnotationFilterPass implements CompilerPassInterface
{
    use AnnotationFilterExtractorTrait;

    private const TAG_FILTER_NAME = 'api_platform.filter';

    /**
     * @var Reader|null
     */
    private $reader;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $resourceClassDirectories = $container->getParameter('api_platform.resource_class_directories');

        foreach (ReflectionClassRecursiveIterator::getReflectionClassesFromDirectories($resourceClassDirectories) as $className => $reflectionClass) {
            $this->createFilterDefinitions($reflectionClass, $container);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function createFilterDefinitions(\ReflectionClass $resourceReflectionClass, ContainerBuilder $container): void
    {
        if (null === $this->reader) {
            $this->reader = $container->has('annotation_reader') ? $container->get('annotation_reader') : null;
        }

        foreach ($this->readFilterAnnotations($resourceReflectionClass, $this->reader) as $id => [$arguments, $filterClass]) {
            if ($container->has($id)) {
                continue;
            }

            if (null === $filterReflectionClass = $container->getReflectionClass($filterClass, false)) {
                throw new InvalidArgumentException(sprintf('Class "%s" used for service "%s" cannot be found.', $filterClass, $id));
            }

            if ($container->has($filterClass) && ($parentDefinition = $container->findDefinition($filterClass))->isAbstract()) {
                $definition = new ChildDefinition($parentDefinition->getClass());
            } else {
                $definition = new Definition($filterReflectionClass->getName());
                $definition->setAutoconfigured(true);
            }

            $definition->addTag(self::TAG_FILTER_NAME);
            $definition->setAutowired(true);

            $parameterNames = [];
            if (null !== $constructorReflectionMethod = $filterReflectionClass->getConstructor()) {
                foreach ($constructorReflectionMethod->getParameters() as $reflectionParameter) {
                    $parameterNames[$reflectionParameter->name] = true;
                }
            }

            foreach ($arguments as $key => $value) {
                if (!isset($parameterNames[$key])) {
                    throw new InvalidArgumentException(sprintf('Class "%s" does not have argument "$%s".', $filterClass, $key));
                }

                $definition->setArgument("$$key", $value);
            }

            $container->setDefinition($id, $definition);
        }
    }
}
