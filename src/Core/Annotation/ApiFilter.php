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

namespace ApiPlatform\Core\Annotation;

use ApiPlatform\Core\Api\FilterInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;

/**
 * Filter annotation.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @Annotation
 * @Target({"PROPERTY", "CLASS"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class ApiFilter
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $strategy;

    /**
     * @var string|FilterInterface
     */
    public $filterClass;

    /**
     * @var array
     */
    public $properties = [];

    /**
     * @var array raw arguments for the filter
     */
    public $arguments = [];

    /**
     * @param string $filterClass
     * @param string $id
     * @param string $strategy
     */
    public function __construct(
        $filterClass,
        ?string $id = null,
        ?string $strategy = null,
        array $properties = [],
        array $arguments = []
    ) {
        if (\is_array($filterClass)) { /** @phpstan-ignore-line Doctrine annotations */
            $options = $filterClass;
            $this->filterClass = $options['value'] ?? null;
            unset($options['value']);

            foreach ($options as $key => $value) {
                if (!property_exists($this, $key)) {
                    throw new InvalidArgumentException(sprintf('Property "%s" does not exist on the ApiFilter annotation.', $key));
                }

                $this->{$key} = $value;
            }
        } else {
            // PHP attribute
            $this->filterClass = $filterClass;
            $this->id = $id;
            $this->strategy = $strategy;
            $this->properties = $properties;
            $this->arguments = $arguments;
        }

        if (!\is_string($this->filterClass)) {
            throw new InvalidArgumentException('This annotation needs a value representing the filter class.');
        }

        if (!is_a($this->filterClass, FilterInterface::class, true)) {
            throw new InvalidArgumentException(sprintf('The filter class "%s" does not implement "%s". Did you forget a use statement?', $this->filterClass, FilterInterface::class));
        }
    }
}
