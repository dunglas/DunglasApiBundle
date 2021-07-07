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

namespace ApiPlatform\Core\Bridge\Symfony\Validator;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;

/**
 * Validates an item using the Symfony validator component.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @final
 */
class Validator implements ValidatorInterface
{
    private $validator;
    private $container;

    public function __construct(SymfonyValidatorInterface $validator, ContainerInterface $container = null)
    {
        $this->validator = $validator;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($data, array $context = [])
    {
        if (null !== $validationGroups = $context['groups'] ?? null) {
            if (
                $this->container &&
                \is_string($validationGroups) &&
                $this->container->has($validationGroups) &&
                ($service = $this->container->get($validationGroups)) &&
                \is_callable($service)
            ) {
                if (!$service instanceof ValidationGroupsGeneratorInterface) {
                    @trigger_error(sprintf('Using a public validation groups generator service not implementing "%s" is deprecated since 2.6 and will be removed in 3.0.', ValidationGroupsGeneratorInterface::class), \E_USER_DEPRECATED);
                }

                $validationGroups = $service($data);
            } elseif (\is_callable($validationGroups)) {
                $validationGroups = $validationGroups($data);
            }

            if (!$validationGroups instanceof GroupSequence) {
                $validationGroups = (array) $validationGroups;
            }
        }

        $violations = $this->validator->validate($data, null, $validationGroups);
        if (0 !== \count($violations)) {
            throw new ValidationException($violations);
        }
    }
}
