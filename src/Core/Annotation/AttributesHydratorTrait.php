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

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Util\Inflector;

/**
 * Hydrates attributes from annotation's parameters.
 *
 * @internal
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
trait AttributesHydratorTrait
{
    private static $configMetadata;

    /**
     * @internal
     */
    public static function getConfigMetadata(): array
    {
        if (null !== self::$configMetadata) {
            return self::$configMetadata;
        }

        $rc = new \ReflectionClass(self::class);

        $publicProperties = [];
        foreach ($rc->getProperties(\ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
            $publicProperties[$reflectionProperty->getName()] = true;
        }

        $configurableAttributes = [];
        foreach ($rc->getConstructor()->getParameters() as $param) {
            if (!isset($publicProperties[$name = $param->getName()])) {
                $configurableAttributes[$name] = true;
            }
        }

        return [$publicProperties, $configurableAttributes];
    }

    /**
     * @var array
     */
    public $attributes = null;

    /**
     * @throws InvalidArgumentException
     */
    private function hydrateAttributes(array $values): void
    {
        if (isset($values['attributes'])) {
            $this->attributes = $values['attributes'];
            unset($values['attributes']);
        }

        foreach (self::$deprecatedAttributes as $deprecatedAttribute => $options) {
            if (\array_key_exists($deprecatedAttribute, $values)) {
                $values[$options[0]] = $values[$deprecatedAttribute];
                @trigger_error(sprintf('Attribute "%s" is deprecated in annotation since API Platform %s, prefer using "%s" attribute instead', $deprecatedAttribute, $options[1], $options[0]), \E_USER_DEPRECATED);
                unset($values[$deprecatedAttribute]);
            }
        }

        [$publicProperties, $configurableAttributes] = self::getConfigMetadata();
        foreach ($values as $key => $value) {
            $key = (string) $key;
            if (!isset($publicProperties[$key]) && !isset($configurableAttributes[$key]) && !isset(self::$deprecatedAttributes[$key])) {
                throw new InvalidArgumentException(sprintf('Unknown property "%s" on annotation "%s".', $key, self::class));
            }

            if (isset($publicProperties[$key])) {
                $this->{$key} = $value;
                continue;
            }

            if (!\is_array($this->attributes)) {
                $this->attributes = [];
            }

            $this->attributes += [Inflector::tableize($key) => $value];
        }
    }
}
