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

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behatch\Context\RestContext;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class HydraContext implements Context
{
    /**
     * @var RestContext
     */
    private $restContext;
    private $propertyAccessor;

    public function __construct()
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * Gives access to the Behatch context.
     *
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        /** @var InitializedContextEnvironment $environment */
        $environment = $scope->getEnvironment();
        $this->restContext = $environment->getContext(RestContext::class);
    }

    /**
     * @Then the Hydra class :class exists
     */
    public function assertTheHydraClassExist(string $className)
    {
        try {
            $this->getClassInfo($className);
        } catch (\InvalidArgumentException $e) {
            throw new ExpectationFailedException(sprintf('The class "%s" doesn\'t exist.', $className), null, $e);
        }
    }

    /**
     * @Then the Hydra class :class doesn't exist
     */
    public function assertTheHydraClassNotExist(string $className)
    {
        try {
            $this->getClassInfo($className);
        } catch (\InvalidArgumentException $exception) {
            return;
        }

        throw new ExpectationFailedException(sprintf('The class "%s" exists.', $className));
    }

    /**
     * @Then the boolean value of the node :node of the Hydra class :class is true
     */
    public function assertBooleanNodeValueIs(string $nodeName, string $className)
    {
        Assert::assertTrue($this->propertyAccessor->getValue($this->getClassInfo($className), $nodeName));
    }

    /**
     * @Then the value of the node :node of the Hydra class :class is :value
     */
    public function assertNodeValueIs(string $nodeName, string $className, string $value)
    {
        Assert::assertEquals(
            $this->propertyAccessor->getValue($this->getClassInfo($className), $nodeName),
            $value
        );
    }

    /**
     * @Then the boolean value of the node :node of the property :prop of the Hydra class :class is true
     */
    public function assertPropertyNodeValueIsTrue(string $nodeName, string $propertyName, string $className)
    {
        Assert::assertTrue($this->propertyAccessor->getValue($this->getPropertyInfo($propertyName, $className), $nodeName));
    }

    /**
     * @Then the value of the node :node of the property :prop of the Hydra class :class is :value
     */
    public function assertPropertyNodeValueIs(string $nodeName, string $propertyName, string $className, string $value)
    {
        Assert::assertEquals(
            $this->propertyAccessor->getValue($this->getPropertyInfo($propertyName, $className), $nodeName),
            $value
        );
    }

    /**
     * @Then the boolean value of the node :node of the operation :operation of the Hydra class :class is true
     */
    public function assertOperationNodeBooleanValueIs(string $nodeName, string $operationMethod, string $className)
    {
        Assert::assertTrue($this->propertyAccessor->getValue($this->getOperation($operationMethod, $className), $nodeName));
    }

    /**
     * @Then the value of the node :node of the operation :operation of the Hydra class :class is :value
     */
    public function assertOperationNodeValueIs(string $nodeName, string $operationMethod, string $className, string $value)
    {
        Assert::assertEquals(
            $this->propertyAccessor->getValue($this->getOperation($operationMethod, $className), $nodeName),
            $value
        );
    }

    /**
     * @Then the value of the node :node of the operation :operation of the Hydra class :class contains :value
     */
    public function assertOperationNodeValueContains(string $nodeName, string $operationMethod, string $className, string $value)
    {
        $property = $this->getOperation($operationMethod, $className);

        Assert::assertContains($value, $this->propertyAccessor->getValue($property, $nodeName));
    }

    /**
     * @Then :nb operations are available for Hydra class :class
     */
    public function assertNbOperationsExist(int $nb, string $className)
    {
        Assert::assertEquals($nb, count($this->getOperations($className)));
    }

    /**
     * @Then :nb properties are available for Hydra class :class
     */
    public function assertNbPropertiesExist(int $nb, string $className)
    {
        Assert::assertEquals($nb, count($this->getProperties($className)));
    }

    /**
     * @Then :prop property doesn't exist for Hydra class :class
     */
    public function assertPropertyNotExist(string $propertyName, string $className)
    {
        try {
            $this->getPropertyInfo($propertyName, $className);
        } catch (\InvalidArgumentException $exception) {
            return;
        }

        throw new ExpectationFailedException(sprintf('Property "%s" of class "%s" exists.', $propertyName, $className));
    }

    /**
     * @Then :prop property is readable for Hydra class :class
     */
    public function assertPropertyIsReadable(string $propertyName, string $className)
    {
        if (!$this->getPropertyInfo($propertyName, $className)->{'hydra:readable'}) {
            throw new ExpectationFailedException(sprintf('Property "%s" of class "%s" is not readable', $propertyName, $className));
        }
    }

    /**
     * @Then :prop property is not readable for Hydra class :class
     */
    public function assertPropertyIsNotReadable(string $propertyName, string $className)
    {
        if ($this->getPropertyInfo($propertyName, $className)->{'hydra:readable'}) {
            throw new ExpectationFailedException(sprintf('Property "%s" of class "%s" is readable', $propertyName, $className));
        }
    }

    /**
     * @Then :prop property is writable for Hydra class :class
     */
    public function assertPropertyIsWritable(string $propertyName, string $className)
    {
        if (!$this->getPropertyInfo($propertyName, $className)->{'hydra:writable'}) {
            throw new ExpectationFailedException(sprintf('Property "%s" of class "%s" is not writable', $propertyName, $className));
        }
    }

    /**
     * @Then :prop property is required for Hydra class :class
     */
    public function assertPropertyIsRequired(string $propertyName, string $className)
    {
        if (!$this->getPropertyInfo($propertyName, $className)->{'hydra:required'}) {
            throw new ExpectationFailedException(sprintf('Property "%s" of class "%s" is not required', $propertyName, $className));
        }
    }

    /**
     * @Then :prop property is not required for Hydra class :class
     */
    public function assertPropertyIsNotRequired(string $propertyName, string $className)
    {
        if ($this->getPropertyInfo($propertyName, $className)->{'hydra:required'}) {
            throw new ExpectationFailedException(sprintf('Property "%s" of class "%s" is required', $propertyName, $className));
        }
    }

    /**
     * Gets information about a property.
     *
     * @throws \InvalidArgumentException
     */
    private function getPropertyInfo(string $propertyName, string $className): stdClass
    {
        foreach ($this->getProperties($className) as $property) {
            if ($property->{'hydra:title'} === $propertyName) {
                return $property;
            }
        }

        throw new \InvalidArgumentException(sprintf('Property "%s" of class "%s" doesn\'t exist', $propertyName, $className));
    }

    /**
     * Gets an operation by its method name.
     *
     * @throws \InvalidArgumentException
     */
    private function getOperation(string $method, string $className): stdClass
    {
        foreach ($this->getOperations($className) as $operation) {
            if ($operation->{'hydra:method'} === $method) {
                return $operation;
            }
        }

        throw new \InvalidArgumentException(sprintf('Operation "%s" of class "%s" doesn\'t exist.', $method, $className));
    }

    /**
     * Gets all operations of a given class.
     */
    private function getOperations(string $className): array
    {
        return $this->getClassInfo($className)->{'hydra:supportedOperation'} ?? [];
    }

    /**
     * Gets all properties of a given class.
     */
    private function getProperties(string $className): array
    {
        return $this->getClassInfo($className)->{'hydra:supportedProperty'} ?? [];
    }

    /**
     * Gets information about a class.
     *
     * @throws \InvalidArgumentException
     */
    private function getClassInfo(string $className): stdClass
    {
        $json = $this->getLastJsonResponse();

        if (isset($json->{'hydra:supportedClass'})) {
            foreach ($json->{'hydra:supportedClass'} as $classData) {
                if ($classData->{'hydra:title'} === $className) {
                    return $classData;
                }
            }
        }

        throw new \InvalidArgumentException(sprintf('Class %s cannot be found in the vocabulary', $className));
    }

    /**
     * Gets the last JSON response.
     *
     * @throws \RuntimeException
     */
    private function getLastJsonResponse(): stdClass
    {
        if (null === $decoded = json_decode($this->restContext->getMink()->getSession()->getDriver()->getContent())) {
            throw new \RuntimeException('JSON response seems to be invalid');
        }

        return $decoded;
    }
}
