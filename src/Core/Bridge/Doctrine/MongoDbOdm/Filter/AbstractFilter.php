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

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Common\PropertyHelperTrait;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\PropertyHelperTrait as MongoDbOdmPropertyHelperTrait;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * {@inheritdoc}
 *
 * Abstract class for easing the implementation of a filter.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
abstract class AbstractFilter implements FilterInterface
{
    use MongoDbOdmPropertyHelperTrait;
    use PropertyHelperTrait;

    protected $managerRegistry;
    protected $logger;
    protected $properties;
    protected $nameConverter;

    public function __construct(ManagerRegistry $managerRegistry, LoggerInterface $logger = null, array $properties = null, NameConverterInterface $nameConverter = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->logger = $logger ?? new NullLogger();
        $this->properties = $properties;
        $this->nameConverter = $nameConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Builder $aggregationBuilder, string $resourceClass, string $operationName = null, array &$context = [])
    {
        foreach ($context['filters'] as $property => $value) {
            $this->filterProperty($this->denormalizePropertyName($property), $value, $aggregationBuilder, $resourceClass, $operationName, $context);
        }
    }

    /**
     * Passes a property through the filter.
     */
    abstract protected function filterProperty(string $property, $value, Builder $aggregationBuilder, string $resourceClass, string $operationName = null, array &$context = []);

    protected function getManagerRegistry(): ManagerRegistry
    {
        return $this->managerRegistry;
    }

    protected function getProperties(): ?array
    {
        return $this->properties;
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Determines whether the given property is enabled.
     */
    protected function isPropertyEnabled(string $property, string $resourceClass): bool
    {
        if (null === $this->properties) {
            // to ensure sanity, nested properties must still be explicitly enabled
            return !$this->isPropertyNested($property, $resourceClass);
        }

        return \array_key_exists($property, $this->properties);
    }

    protected function denormalizePropertyName($property)
    {
        if (!$this->nameConverter instanceof NameConverterInterface) {
            return $property;
        }

        return implode('.', array_map([$this->nameConverter, 'denormalize'], explode('.', (string) $property)));
    }

    protected function normalizePropertyName($property)
    {
        if (!$this->nameConverter instanceof NameConverterInterface) {
            return $property;
        }

        return implode('.', array_map([$this->nameConverter, 'normalize'], explode('.', (string) $property)));
    }
}
