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

namespace ApiPlatform\Core\Tests\Behat;

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Doctrine\Orm\EntityManager;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\AbsoluteUrlDummy as AbsoluteUrlDummyDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\AbsoluteUrlRelationDummy as AbsoluteUrlRelationDummyDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Address as AddressDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Answer as AnswerDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Book as BookDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\CompositeItem as CompositeItemDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\CompositeLabel as CompositeLabelDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\CompositePrimitiveItem as CompositePrimitiveItemDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\CompositeRelation as CompositeRelationDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\ConvertedBoolean as ConvertedBoolDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\ConvertedDate as ConvertedDateDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\ConvertedInteger as ConvertedIntegerDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\ConvertedOwner as ConvertedOwnerDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\ConvertedRelated as ConvertedRelatedDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\ConvertedString as ConvertedStringDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Customer as CustomerDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\CustomMultipleIdentifierDummy as CustomMultipleIdentifierDummyDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Dummy as DummyDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyAggregateOffer as DummyAggregateOfferDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyCar as DummyCarDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyCarColor as DummyCarColorDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyCustomMutation as DummyCustomMutationDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyCustomQuery as DummyCustomQueryDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyDate as DummyDateDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyDifferentGraphQlSerializationGroup as DummyDifferentGraphQlSerializationGroupDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyDtoCustom as DummyDtoCustomDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyDtoNoInput as DummyDtoNoInputDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyDtoNoOutput as DummyDtoNoOutputDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyDtoOutputFallbackToSameClass as DummyDtoOutputFallbackToSameClassDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyDtoOutputSameClass as DummyDtoOutputSameClassDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyFriend as DummyFriendDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyGroup as DummyGroupDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyImmutableDate as DummyImmutableDateDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyMercure as DummyMercureDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyOffer as DummyOfferDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyPassenger as DummyPassengerDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyProduct as DummyProductDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyProperty as DummyPropertyDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyTableInheritanceNotApiResourceChild as DummyTableInheritanceNotApiResourceChildDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyTravel as DummyTravelDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\EmbeddableDummy as EmbeddableDummyDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\EmbeddedDummy as EmbeddedDummyDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\FileConfigDummy as FileConfigDummyDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Foo as FooDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\FooDummy as FooDummyDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\FourthLevel as FourthLevelDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Greeting as GreetingDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\InitializeInput as InitializeInputDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\IriOnlyDummy as IriOnlyDummyDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\MaxDepthDummy as MaxDepthDummyDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\NetworkPathDummy as NetworkPathDummyDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\NetworkPathRelationDummy as NetworkPathRelationDummyDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Order as OrderDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\PatchDummyRelation as PatchDummyRelationDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Person as PersonDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\PersonToPet as PersonToPetDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Pet as PetDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Product as ProductDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Question as QuestionDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\RelatedDummy as RelatedDummyDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\RelatedOwnedDummy as RelatedOwnedDummyDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\RelatedOwningDummy as RelatedOwningDummyDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\RelatedSecuredDummy as RelatedSecuredDummyDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\RelatedToDummyFriend as RelatedToDummyFriendDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\RelationEmbedder as RelationEmbedderDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\SecuredDummy as SecuredDummyDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\SoMany as SoManyDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Taxon as TaxonDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\ThirdLevel as ThirdLevelDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\UrlEncodedId as UrlEncodedIdDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\User as UserDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\WithJsonDummy as WithJsonDummyDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\AbsoluteUrlDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\AbsoluteUrlRelationDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Address;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Answer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Book;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeItem;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeLabel;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositePrimitiveItem;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeRelation;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ConvertedBoolean;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ConvertedDate;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ConvertedInteger;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ConvertedOwner;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ConvertedRelated;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ConvertedString;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Customer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CustomMultipleIdentifierDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyAggregateOffer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCarColor;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCarIdentifier;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCustomMutation;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCustomQuery;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyDate;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyDifferentGraphQlSerializationGroup;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyDtoCustom;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyDtoNoInput;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyDtoNoOutput;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyDtoOutputFallbackToSameClass;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyDtoOutputSameClass;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyFriend;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyGroup;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyImmutableDate;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyMercure;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyOffer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyPassenger;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyProduct;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyProperty;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyTableInheritanceNotApiResourceChild;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyTravel;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\EmbeddableDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\EmbeddedDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ExternalUser;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Foo;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FooDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FourthLevel;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Greeting;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\InitializeInput;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\InternalUser;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\IriOnlyDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\MaxDepthDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\NetworkPathDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\NetworkPathRelationDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Order;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\PatchDummyRelation;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Person;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\PersonToPet;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Pet;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Product;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Question;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RamseyUuidDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedOwnedDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedOwningDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedSecuredDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedToDummyFriend;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelationEmbedder;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\SecuredDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Site;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\SoMany;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\SymfonyUuidDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Taxon;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThirdLevel;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\UrlEncodedId;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\User;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\UuidIdentifierDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\WithJsonDummy;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

/**
 * Defines application features from the specific context.
 */
final class DoctrineContext implements Context
{
    /**
     * @var ObjectManager
     */
    private $manager;
    private $doctrine;
    private $passwordHasher;
    private $schemaTool;
    private $schemaManager;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     *
     * @param UserPasswordEncoderInterface|UserPasswordHasherInterface $passwordHasher
     */
    public function __construct(ManagerRegistry $doctrine, $passwordHasher)
    {
        $this->doctrine = $doctrine;
        $this->passwordHasher = $passwordHasher;
        $this->manager = $doctrine->getManager();
        $this->schemaTool = $this->manager instanceof EntityManagerInterface ? new SchemaTool($this->manager) : null;
        $this->schemaManager = $this->manager instanceof DocumentManager ? $this->manager->getSchemaManager() : null;
    }

    /**
     * @BeforeScenario @createSchema
     */
    public function createDatabase()
    {
        /** @var \Doctrine\ORM\Mapping\ClassMetadata[] $classes */
        $classes = $this->manager->getMetadataFactory()->getAllMetadata();

        if ($this->isOrm()) {
            $this->schemaTool->dropSchema($classes);
            $this->schemaTool->createSchema($classes);
        }

        if ($this->isOdm()) {
            $this->schemaManager->dropDatabases();
        }

        $this->doctrine->getManager()->clear();
    }

    /**
     * @Then the DQL should be equal to:
     */
    public function theDqlShouldBeEqualTo(PyStringNode $dql)
    {
        /** @var EntityManager $manager */
        $manager = $this->doctrine->getManager();

        $actualDql = $manager::$dql;

        $expectedDql = preg_replace('/\(\R */', '(', (string) $dql);
        $expectedDql = preg_replace('/\R *\)/', ')', $expectedDql);
        $expectedDql = preg_replace('/\R */', ' ', $expectedDql);

        if ($expectedDql !== $actualDql) {
            throw new \RuntimeException("The DQL:\n'$actualDql' is not equal to:\n'$expectedDql'");
        }
    }

    /**
     * @Given there are :nb dummy objects
     */
    public function thereAreDummyObjects(int $nb)
    {
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];

        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = $this->buildDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setDummy('SomeDummyTest'.$i);
            $dummy->setDescription($descriptions[($i - 1) % 2]);
            $dummy->nameConverted = 'Converted '.$i;

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb of these so many objects
     */
    public function thereAreOfTheseSoManyObjects(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = $this->isOrm() ? new SoMany() : new SoManyDocument();
            $dummy->content = 'Many #'.$i;

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @When some dummy table inheritance data but not api resource child are created
     */
    public function someDummyTableInheritanceDataButNotApiResourceChildAreCreated()
    {
        $dummy = $this->buildDummyTableInheritanceNotApiResourceChild();
        $dummy->setName('Foobarbaz inheritance');
        $this->manager->persist($dummy);
        $this->manager->flush();
    }

    /**
     * @Given there are :nb foo objects with fake names
     */
    public function thereAreFooObjectsWithFakeNames(int $nb)
    {
        $names = ['Hawsepipe', 'Sthenelus', 'Ephesian', 'Separativeness', 'Balbo'];
        $bars = ['Lorem', 'Ipsum', 'Dolor', 'Sit', 'Amet'];

        for ($i = 0; $i < $nb; ++$i) {
            $foo = $this->buildFoo();
            $foo->setName($names[$i]);
            $foo->setBar($bars[$i]);

            $this->manager->persist($foo);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb fooDummy objects with fake names
     */
    public function thereAreFooDummyObjectsWithFakeNames($nb)
    {
        $names = ['Hawsepipe', 'Ephesian', 'Sthenelus', 'Separativeness', 'Balbo'];
        $dummies = ['Lorem', 'Ipsum', 'Dolor', 'Sit', 'Amet'];

        for ($i = 0; $i < $nb; ++$i) {
            $dummy = $this->buildDummy();
            $dummy->setName($dummies[$i]);

            $foo = $this->buildFooDummy();
            $foo->setName($names[$i]);
            $foo->setDummy($dummy);

            $this->manager->persist($foo);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy group objects
     */
    public function thereAreDummyGroupObjects(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummyGroup = $this->buildDummyGroup();

            foreach (['foo', 'bar', 'baz', 'qux'] as $property) {
                $dummyGroup->{$property} = ucfirst($property).' #'.$i;
            }

            $this->manager->persist($dummyGroup);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy property objects
     */
    public function thereAreDummyPropertyObjects(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummyProperty = $this->buildDummyProperty();
            $dummyGroup = $this->buildDummyGroup();

            foreach (['foo', 'bar', 'baz'] as $property) {
                $dummyProperty->{$property} = $dummyGroup->{$property} = ucfirst($property).' #'.$i;
            }
            $dummyProperty->nameConverted = "NameConverted #$i";

            $dummyProperty->group = $dummyGroup;

            $this->manager->persist($dummyGroup);
            $this->manager->persist($dummyProperty);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy property objects with a shared group
     */
    public function thereAreDummyPropertyObjectsWithASharedGroup(int $nb)
    {
        $dummyGroup = $this->buildDummyGroup();
        foreach (['foo', 'bar', 'baz'] as $property) {
            $dummyGroup->{$property} = ucfirst($property).' #shared';
        }
        $this->manager->persist($dummyGroup);

        for ($i = 1; $i <= $nb; ++$i) {
            $dummyProperty = $this->buildDummyProperty();

            foreach (['foo', 'bar', 'baz'] as $property) {
                $dummyProperty->{$property} = ucfirst($property).' #'.$i;
            }

            $dummyProperty->group = $dummyGroup;
            $this->manager->persist($dummyProperty);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy property objects with different number of related groups
     */
    public function thereAreDummyPropertyObjectsWithADifferentNumberRelatedGroups(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummyGroup = $this->buildDummyGroup();
            $dummyProperty = $this->buildDummyProperty();

            foreach (['foo', 'bar', 'baz'] as $property) {
                $dummyProperty->{$property} = $dummyGroup->{$property} = ucfirst($property).' #'.$i;
            }

            $this->manager->persist($dummyGroup);
            $dummyGroups[$i] = $dummyGroup;

            for ($j = 1; $j <= $i; ++$j) {
                $dummyProperty->groups[] = $dummyGroups[$j];
            }

            $this->manager->persist($dummyProperty);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy property objects with :nb2 groups
     */
    public function thereAreDummyPropertyObjectsWithGroups(int $nb, int $nb2)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummyProperty = $this->buildDummyProperty();
            $dummyGroup = $this->buildDummyGroup();

            foreach (['foo', 'bar', 'baz'] as $property) {
                $dummyProperty->{$property} = $dummyGroup->{$property} = ucfirst($property).' #'.$i;
            }

            $dummyProperty->group = $dummyGroup;

            $this->manager->persist($dummyGroup);
            for ($j = 1; $j <= $nb2; ++$j) {
                $dummyGroup = $this->buildDummyGroup();

                foreach (['foo', 'bar', 'baz'] as $property) {
                    $dummyGroup->{$property} = ucfirst($property).' #'.$i.$j;
                }

                $dummyProperty->groups[] = $dummyGroup;
                $this->manager->persist($dummyGroup);
            }

            $this->manager->persist($dummyProperty);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb embedded dummy objects
     */
    public function thereAreEmbeddedDummyObjects(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = $this->buildEmbeddedDummy();
            $dummy->setName('Dummy #'.$i);

            $embeddableDummy = $this->buildEmbeddableDummy();
            $embeddableDummy->setDummyName('Dummy #'.$i);
            $dummy->setEmbeddedDummy($embeddableDummy);

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects with relatedDummy
     */
    public function thereAreDummyObjectsWithRelatedDummy(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $relatedDummy = $this->buildRelatedDummy();
            $relatedDummy->setName('RelatedDummy #'.$i);

            $dummy = $this->buildDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->nameConverted = "Converted $i";
            $dummy->setRelatedDummy($relatedDummy);

            $this->manager->persist($relatedDummy);
            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are dummies with similar properties
     */
    public function thereAreDummiesWithSimilarProperties()
    {
        $dummy1 = $this->buildDummy();
        $dummy1->setName('foo');
        $dummy1->setDescription('bar');

        $dummy2 = $this->buildDummy();
        $dummy2->setName('baz');
        $dummy2->setDescription('qux');

        $dummy3 = $this->buildDummy();
        $dummy3->setName('foo');
        $dummy3->setDescription('qux');

        $dummy4 = $this->buildDummy();
        $dummy4->setName('baz');
        $dummy4->setDescription('bar');

        $this->manager->persist($dummy1);
        $this->manager->persist($dummy2);
        $this->manager->persist($dummy3);
        $this->manager->persist($dummy4);
        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummyDtoNoInput objects
     */
    public function thereAreDummyDtoNoInputObjects(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummyDto = $this->buildDummyDtoNoInput();
            $dummyDto->lorem = 'DummyDtoNoInput foo #'.$i;
            $dummyDto->ipsum = round($i / 3, 2);

            $this->manager->persist($dummyDto);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummyDtoNoOutput objects
     */
    public function thereAreDummyDtoNoOutputObjects(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummyDto = $this->buildDummyDtoNoOutput();
            $dummyDto->lorem = 'DummyDtoNoOutput foo #'.$i;
            $dummyDto->ipsum = (string) ($i / 3);

            $this->manager->persist($dummyDto);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummyCustomQuery objects
     */
    public function thereAreDummyCustomQueryObjects(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummyCustomQuery = $this->buildDummyCustomQuery();

            $this->manager->persist($dummyCustomQuery);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummyCustomMutation objects
     */
    public function thereAreDummyCustomMutationObjects(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $customMutationDummy = $this->buildDummyCustomMutation();
            $customMutationDummy->setOperandA(3);

            $this->manager->persist($customMutationDummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects with JSON and array data
     */
    public function thereAreDummyObjectsWithJsonData(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = $this->buildDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setJsonData(['foo' => ['bar', 'baz'], 'bar' => 5]);
            $dummy->setArrayData(['foo', 'bar', 'baz']);

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy with null JSON objects
     */
    public function thereAreDummyWithNullJsonObjects(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = $this->buildWithJsonDummy();
            $dummy->json = null;

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects with relatedDummy and its thirdLevel
     * @Given there is :nb dummy object with relatedDummy and its thirdLevel
     */
    public function thereAreDummyObjectsWithRelatedDummyAndItsThirdLevel(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $thirdLevel = $this->buildThirdLevel();

            $relatedDummy = $this->buildRelatedDummy();
            $relatedDummy->setName('RelatedDummy #'.$i);
            $relatedDummy->setThirdLevel($thirdLevel);

            $dummy = $this->buildDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setRelatedDummy($relatedDummy);

            $this->manager->persist($thirdLevel);
            $this->manager->persist($relatedDummy);
            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there is a dummy object with :nb relatedDummies and their thirdLevel
     */
    public function thereIsADummyObjectWithRelatedDummiesAndTheirThirdLevel(int $nb)
    {
        $dummy = $this->buildDummy();
        $dummy->setName('Dummy with relations');

        for ($i = 1; $i <= $nb; ++$i) {
            $thirdLevel = $this->buildThirdLevel();

            $relatedDummy = $this->buildRelatedDummy();
            $relatedDummy->setName('RelatedDummy #'.$i);
            $relatedDummy->setThirdLevel($thirdLevel);

            $dummy->addRelatedDummy($relatedDummy);

            $this->manager->persist($thirdLevel);
            $this->manager->persist($relatedDummy);
        }
        $this->manager->persist($dummy);
        $this->manager->flush();
    }

    /**
     * @Given there is a dummy object with :nb relatedDummies with same thirdLevel
     */
    public function thereIsADummyObjectWithRelatedDummiesWithSameThirdLevel(int $nb)
    {
        $dummy = $this->buildDummy();
        $dummy->setName('Dummy with relations');
        $thirdLevel = $this->buildThirdLevel();

        for ($i = 1; $i <= $nb; ++$i) {
            $relatedDummy = $this->buildRelatedDummy();
            $relatedDummy->setName('RelatedDummy #'.$i);
            $relatedDummy->setThirdLevel($thirdLevel);

            $dummy->addRelatedDummy($relatedDummy);

            $this->manager->persist($relatedDummy);
        }
        $this->manager->persist($thirdLevel);
        $this->manager->persist($dummy);
        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects with embeddedDummy
     */
    public function thereAreDummyObjectsWithEmbeddedDummy(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $embeddableDummy = $this->buildEmbeddableDummy();
            $embeddableDummy->setDummyName('EmbeddedDummy #'.$i);

            $dummy = $this->buildEmbeddedDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setEmbeddedDummy($embeddableDummy);

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects having each :nbrelated relatedDummies
     */
    public function thereAreDummyObjectsWithRelatedDummies(int $nb, int $nbrelated)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = $this->buildDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));

            for ($j = 1; $j <= $nbrelated; ++$j) {
                $relatedDummy = $this->buildRelatedDummy();
                $relatedDummy->setName('RelatedDummy'.$j.$i);
                $relatedDummy->setAge((int) ($j.$i));
                $this->manager->persist($relatedDummy);

                $dummy->addRelatedDummy($relatedDummy);
            }

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects with dummyDate
     * @Given there is :nb dummy object with dummyDate
     */
    public function thereAreDummyObjectsWithDummyDate(int $nb)
    {
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];

        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTime(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));

            $dummy = $this->buildDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setDescription($descriptions[($i - 1) % 2]);

            // Last Dummy has a null date
            if ($nb !== $i) {
                $dummy->setDummyDate($date);
            }

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects with dummyDate and dummyBoolean :bool
     */
    public function thereAreDummyObjectsWithDummyDateAndDummyBoolean(int $nb, string $bool)
    {
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];

        if (\in_array($bool, ['true', '1', 1], true)) {
            $bool = true;
        } elseif (\in_array($bool, ['false', '0', 0], true)) {
            $bool = false;
        } else {
            $expected = ['true', 'false', '1', '0'];
            throw new \InvalidArgumentException(sprintf('Invalid boolean value for "%s" property, expected one of ( "%s" )', $bool, implode('" | "', $expected)));
        }

        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTime(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));

            $dummy = $this->buildDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setDescription($descriptions[($i - 1) % 2]);
            $dummy->setDummyBoolean($bool);

            // Last Dummy has a null date
            if ($nb !== $i) {
                $dummy->setDummyDate($date);
            }

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects with dummyDate and relatedDummy
     */
    public function thereAreDummyObjectsWithDummyDateAndRelatedDummy(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTime(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));

            $relatedDummy = $this->buildRelatedDummy();
            $relatedDummy->setName('RelatedDummy #'.$i);
            $relatedDummy->setDummyDate($date);

            $dummy = $this->buildDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setRelatedDummy($relatedDummy);
            // Last Dummy has a null date
            if ($nb !== $i) {
                $dummy->setDummyDate($date);
            }

            $this->manager->persist($relatedDummy);
            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb embedded dummy objects with dummyDate and embeddedDummy
     */
    public function thereAreDummyObjectsWithDummyDateAndEmbeddedDummy(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTime(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));

            $embeddableDummy = $this->buildEmbeddableDummy();
            $embeddableDummy->setDummyName('Embeddable #'.$i);
            $embeddableDummy->setDummyDate($date);

            $dummy = $this->buildEmbeddedDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setEmbeddedDummy($embeddableDummy);
            // Last Dummy has a null date
            if ($nb !== $i) {
                $dummy->setDummyDate($date);
            }

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb convertedDate objects
     */
    public function thereAreconvertedDateObjectsWith(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $convertedDate = $this->buildConvertedDate();
            $convertedDate->nameConverted = new \DateTime(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));

            $this->manager->persist($convertedDate);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb convertedString objects
     */
    public function thereAreconvertedStringObjectsWith(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $convertedString = $this->buildConvertedString();
            $convertedString->nameConverted = ($i % 2) ? "name#$i" : null;

            $this->manager->persist($convertedString);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb convertedBoolean objects
     */
    public function thereAreconvertedBooleanObjectsWith(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $convertedBoolean = $this->buildConvertedBoolean();
            $convertedBoolean->nameConverted = (bool) ($i % 2);

            $this->manager->persist($convertedBoolean);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb convertedInteger objects
     */
    public function thereAreconvertedIntegerObjectsWith(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $convertedInteger = $this->buildConvertedInteger();
            $convertedInteger->nameConverted = $i;

            $this->manager->persist($convertedInteger);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects with dummyPrice
     */
    public function thereAreDummyObjectsWithDummyPrice(int $nb)
    {
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];
        $prices = ['9.99', '12.99', '15.99', '19.99'];

        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = $this->buildDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setDescription($descriptions[($i - 1) % 2]);
            $dummy->setDummyPrice($prices[($i - 1) % 4]);

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy objects with dummyBoolean :bool
     * @Given there is :nb dummy object with dummyBoolean :bool
     */
    public function thereAreDummyObjectsWithDummyBoolean(int $nb, string $bool)
    {
        if (\in_array($bool, ['true', '1', 1], true)) {
            $bool = true;
        } elseif (\in_array($bool, ['false', '0', 0], true)) {
            $bool = false;
        } else {
            $expected = ['true', 'false', '1', '0'];
            throw new \InvalidArgumentException(sprintf('Invalid boolean value for "%s" property, expected one of ( "%s" )', $bool, implode('" | "', $expected)));
        }
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];

        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = $this->buildDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setDescription($descriptions[($i - 1) % 2]);
            $dummy->setDummyBoolean($bool);

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb embedded dummy objects with embeddedDummy.dummyBoolean :bool
     */
    public function thereAreDummyObjectsWithEmbeddedDummyBoolean(int $nb, string $bool)
    {
        if (\in_array($bool, ['true', '1', 1], true)) {
            $bool = true;
        } elseif (\in_array($bool, ['false', '0', 0], true)) {
            $bool = false;
        } else {
            $expected = ['true', 'false', '1', '0'];
            throw new \InvalidArgumentException(sprintf('Invalid boolean value for "%s" property, expected one of ( "%s" )', $bool, implode('" | "', $expected)));
        }

        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = $this->buildEmbeddedDummy();
            $dummy->setName('Embedded Dummy #'.$i);
            $embeddableDummy = $this->buildEmbeddableDummy();
            $embeddableDummy->setDummyName('Embedded Dummy #'.$i);
            $embeddableDummy->setDummyBoolean($bool);
            $dummy->setEmbeddedDummy($embeddableDummy);
            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb embedded dummy objects with relatedDummy.embeddedDummy.dummyBoolean :bool
     */
    public function thereAreDummyObjectsWithRelationEmbeddedDummyBoolean(int $nb, string $bool)
    {
        if (\in_array($bool, ['true', '1', 1], true)) {
            $bool = true;
        } elseif (\in_array($bool, ['false', '0', 0], true)) {
            $bool = false;
        } else {
            $expected = ['true', 'false', '1', '0'];
            throw new \InvalidArgumentException(sprintf('Invalid boolean value for "%s" property, expected one of ( "%s" )', $bool, implode('" | "', $expected)));
        }

        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = $this->buildEmbeddedDummy();
            $dummy->setName('Embedded Dummy #'.$i);
            $embeddableDummy = $this->buildEmbeddableDummy();
            $embeddableDummy->setDummyName('Embedded Dummy #'.$i);
            $embeddableDummy->setDummyBoolean($bool);

            $relationDummy = $this->buildRelatedDummy();
            $relationDummy->setEmbeddedDummy($embeddableDummy);

            $dummy->setRelatedDummy($relationDummy);

            $this->manager->persist($relationDummy);
            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb SecuredDummy objects
     */
    public function thereAreSecuredDummyObjects(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $securedDummy = $this->buildSecuredDummy();
            $securedDummy->setTitle("#$i");
            $securedDummy->setDescription("Hello #$i");
            $securedDummy->setOwner('notexist');

            $this->manager->persist($securedDummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb SecuredDummy objects owned by :ownedby with related dummies
     */
    public function thereAreSecuredDummyObjectsOwnedByWithRelatedDummies(int $nb, string $ownedby)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $securedDummy = $this->buildSecuredDummy();
            $securedDummy->setTitle("#$i");
            $securedDummy->setDescription("Hello #$i");
            $securedDummy->setOwner($ownedby);

            $relatedDummy = $this->buildRelatedDummy();
            $relatedDummy->setName('RelatedDummy');
            $this->manager->persist($relatedDummy);

            $relatedSecuredDummy = $this->buildRelatedSecureDummy();
            $this->manager->persist($relatedSecuredDummy);

            $publicRelatedSecuredDummy = $this->buildRelatedSecureDummy();
            $this->manager->persist($publicRelatedSecuredDummy);

            $securedDummy->addRelatedDummy($relatedDummy);
            $securedDummy->setRelatedDummy($relatedDummy);
            $securedDummy->addRelatedSecuredDummy($relatedSecuredDummy);
            $securedDummy->setRelatedSecuredDummy($relatedSecuredDummy);
            $securedDummy->addPublicRelatedSecuredDummy($publicRelatedSecuredDummy);
            $securedDummy->setPublicRelatedSecuredDummy($publicRelatedSecuredDummy);

            $this->manager->persist($securedDummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there is a RelationEmbedder object
     */
    public function thereIsARelationEmbedderObject()
    {
        $relationEmbedder = $this->buildRelationEmbedder();

        $this->manager->persist($relationEmbedder);
        $this->manager->flush();
    }

    /**
     * @Given there is a Dummy Object mapped by UUID
     */
    public function thereIsADummyObjectMappedByUUID()
    {
        $dummy = new UuidIdentifierDummy();
        $dummy->setName('My Dummy');
        $dummy->setUuid('41B29566-144B-11E6-A148-3E1D05DEFE78');

        $this->manager->persist($dummy);
        $this->manager->flush();
    }

    /**
     * @Given there are Composite identifier objects
     */
    public function thereIsACompositeIdentifierObject()
    {
        $item = $this->buildCompositeItem();
        $item->setField1('foobar');
        $this->manager->persist($item);
        $this->manager->flush();

        for ($i = 0; $i < 4; ++$i) {
            $label = $this->buildCompositeLabel();
            $label->setValue('foo-'.$i);

            $rel = $this->buildCompositeRelation();
            $rel->setCompositeLabel($label);
            $rel->setCompositeItem($item);
            $rel->setValue('somefoobardummy');

            $this->manager->persist($label);
            // since doctrine 2.6 we need existing identifiers on relations
            $this->manager->flush();
            $this->manager->persist($rel);
        }

        $this->manager->flush();
        $this->manager->clear();
    }

    /**
     * @Given there are composite primitive identifiers objects
     */
    public function thereAreCompositePrimitiveIdentifiersObjects()
    {
        $foo = $this->buildCompositePrimitiveItem('Foo', 2016);
        $foo->setDescription('This is foo.');
        $this->manager->persist($foo);

        $bar = $this->buildCompositePrimitiveItem('Bar', 2017);
        $bar->setDescription('This is bar.');
        $this->manager->persist($bar);

        $this->manager->flush();
        $this->manager->clear();
    }

    /**
     * @Given there is a FileConfigDummy object
     */
    public function thereIsAFileConfigDummyObject()
    {
        $fileConfigDummy = $this->buildFileConfigDummy();
        $fileConfigDummy->setName('ConfigDummy');
        $fileConfigDummy->setFoo('Foo');

        $this->manager->persist($fileConfigDummy);
        $this->manager->flush();
    }

    /**
     * @Given there is a DummyCar entity with related colors
     */
    public function thereIsAFooEntityWithRelatedBars()
    {
        $foo = $this->buildDummyCar();
        $foo->setName('mustli');
        $foo->setCanSell(true);
        $foo->setAvailableAt(new \DateTime());
        $this->manager->persist($foo->getId());
        $this->manager->persist($foo);
        $this->manager->flush();

        $bar1 = $this->buildDummyCarColor();
        $bar1->setProp('red');
        $bar1->setCar($foo);
        $this->manager->persist($bar1);
        $this->manager->flush();

        $bar2 = $this->buildDummyCarColor();
        $bar2->setProp('blue');
        $bar2->setCar($foo);
        $this->manager->persist($bar2);
        $this->manager->flush();

        $foo->setColors([$bar1, $bar2]);
        $this->manager->persist($foo);
        $this->manager->flush();
    }

    /**
     * @Given there is a dummy travel
     */
    public function thereIsADummyTravel()
    {
        $car = $this->buildDummyCar();
        $car->setName('model x');
        $car->setCanSell(true);
        $car->setAvailableAt(new \DateTime());
        $this->manager->persist($car);

        $passenger = $this->buildDummyPassenger();
        $passenger->nickname = 'Tom';
        $this->manager->persist($passenger);

        $travel = $this->buildDummyTravel();
        $travel->car = $car;
        $travel->passenger = $passenger;
        $travel->confirmed = true;
        $this->manager->persist($travel);

        $this->manager->flush();
    }

    /**
     * @Given there is a RelatedDummy with :nb friends
     */
    public function thereIsARelatedDummyWithFriends(int $nb)
    {
        $relatedDummy = $this->buildRelatedDummy();
        $relatedDummy->setName('RelatedDummy with friends');
        $this->manager->persist($relatedDummy);
        $this->manager->flush();

        for ($i = 1; $i <= $nb; ++$i) {
            $friend = $this->buildDummyFriend();
            $friend->setName('Friend-'.$i);

            $this->manager->persist($friend);
            // since doctrine 2.6 we need existing identifiers on relations
            // See https://github.com/doctrine/doctrine2/pull/6701
            $this->manager->flush();

            $relation = $this->buildRelatedToDummyFriend();
            $relation->setName('Relation-'.$i);
            $relation->setDummyFriend($friend);
            $relation->setRelatedDummy($relatedDummy);

            $relatedDummy->addRelatedToDummyFriend($relation);

            $this->manager->persist($relation);
        }

        $relatedDummy2 = $this->buildRelatedDummy();
        $relatedDummy2->setName('RelatedDummy without friends');
        $this->manager->persist($relatedDummy2);
        $this->manager->flush();
        $this->manager->clear();
    }

    /**
     * @Given there is an answer :answer to the question :question
     */
    public function thereIsAnAnswerToTheQuestion(string $a, string $q)
    {
        $answer = $this->buildAnswer();
        $answer->setContent($a);

        $question = $this->buildQuestion();
        $question->setContent($q);
        $question->setAnswer($answer);
        $answer->addRelatedQuestion($question);

        $this->manager->persist($answer);
        $this->manager->persist($question);

        $this->manager->flush();
        $this->manager->clear();
    }

    /**
     * @Given there is a UrlEncodedId resource
     */
    public function thereIsAUrlEncodedIdResource()
    {
        $urlEncodedIdResource = ($this->isOrm() ? new UrlEncodedId() : new UrlEncodedIdDocument());
        $this->manager->persist($urlEncodedIdResource);
        $this->manager->flush();
        $this->manager->clear();
    }

    /**
     * @Then the password :password for user :user should be hashed
     */
    public function thePasswordForUserShouldBeHashed(string $password, string $user)
    {
        $user = $this->doctrine->getRepository($this->isOrm() ? User::class : UserDocument::class)->find($user);
        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            throw new \Exception('User password mismatch');
        }
    }

    /**
     * @Given I have a product with offers
     */
    public function createProductWithOffers()
    {
        $offer = $this->buildDummyOffer();
        $offer->setValue(2);
        $aggregate = $this->buildDummyAggregateOffer();
        $aggregate->setValue(1);
        $aggregate->addOffer($offer);

        $product = $this->buildDummyProduct();
        $product->setName('Dummy product');
        $product->addOffer($aggregate);

        $relatedProduct = $this->buildDummyProduct();
        $relatedProduct->setName('Dummy related product');
        $relatedProduct->setParent($product);

        $product->addRelatedProduct($relatedProduct);

        $this->manager->persist($relatedProduct);
        $this->manager->persist($product);
        $this->manager->flush();
    }

    /**
     * @Given there are people having pets
     */
    public function createPeopleWithPets()
    {
        $personToPet = $this->buildPersonToPet();

        $person = $this->buildPerson();
        $person->name = 'foo';

        $pet = $this->buildPet();
        $pet->name = 'bar';

        $personToPet->person = $person;
        $personToPet->pet = $pet;

        $this->manager->persist($person);
        $this->manager->persist($pet);
        // since doctrine 2.6 we need existing identifiers on relations
        $this->manager->flush();
        $this->manager->persist($personToPet);

        $person->pets->add($personToPet);
        $this->manager->persist($person);

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummydate objects with dummyDate
     * @Given there is :nb dummydate object with dummyDate
     */
    public function thereAreDummyDateObjectsWithDummyDate(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTime(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));

            $dummy = $this->buildDummyDate();
            $dummy->dummyDate = $date;

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummydate objects with nullable dateIncludeNullAfter
     * @Given there is :nb dummydate object with nullable dateIncludeNullAfter
     */
    public function thereAreDummyDateObjectsWithNullableDateIncludeNullAfter(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTime(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));

            $dummy = $this->buildDummyDate();
            $dummy->dummyDate = $date;
            $dummy->dateIncludeNullAfter = 0 === $i % 3 ? null : $date;

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummydate objects with nullable dateIncludeNullBefore
     * @Given there is :nb dummydate object with nullable dateIncludeNullBefore
     */
    public function thereAreDummyDateObjectsWithNullableDateIncludeNullBefore(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTime(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));

            $dummy = $this->buildDummyDate();
            $dummy->dummyDate = $date;
            $dummy->dateIncludeNullBefore = 0 === $i % 3 ? null : $date;

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummydate objects with nullable dateIncludeNullBeforeAndAfter
     * @Given there is :nb dummydate object with nullable dateIncludeNullBeforeAndAfter
     */
    public function thereAreDummyDateObjectsWithNullableDateIncludeNullBeforeAndAfter(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTime(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));

            $dummy = $this->buildDummyDate();
            $dummy->dummyDate = $date;
            $dummy->dateIncludeNullBeforeAndAfter = 0 === $i % 3 ? null : $date;

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummyimmutabledate objects with dummyDate
     */
    public function thereAreDummyImmutableDateObjectsWithDummyDate(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTimeImmutable(sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));
            $dummy = $this->buildDummyImmutableDate();
            $dummy->dummyDate = $date;

            $this->manager->persist($dummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy with different GraphQL serialization groups objects
     */
    public function thereAreDummyWithDifferentGraphQlSerializationGroupsObjects(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $dummyDifferentGraphQlSerializationGroup = $this->buildDummyDifferentGraphQlSerializationGroup();
            $dummyDifferentGraphQlSerializationGroup->setName('Name #'.$i);
            $dummyDifferentGraphQlSerializationGroup->setTitle('Title #'.$i);
            $this->manager->persist($dummyDifferentGraphQlSerializationGroup);
        }

        $this->manager->flush();
    }

    /**
     * @Given there is a ramsey identified resource with uuid :uuid
     */
    public function thereIsARamseyIdentifiedResource(string $uuid)
    {
        $dummy = new RamseyUuidDummy(Uuid::fromString($uuid));

        $this->manager->persist($dummy);
        $this->manager->flush();
    }

    /**
     * @Given there is a Symfony dummy identified resource with uuid :uuid
     */
    public function thereIsASymfonyDummyIdentifiedResource(string $uuid)
    {
        $dummy = new SymfonyUuidDummy(SymfonyUuid::fromString($uuid));

        $this->manager->persist($dummy);
        $this->manager->flush();
    }

    /**
     * @Given there is a dummy object with a fourth level relation
     */
    public function thereIsADummyObjectWithAFourthLevelRelation()
    {
        $fourthLevel = $this->buildFourthLevel();
        $fourthLevel->setLevel(4);
        $this->manager->persist($fourthLevel);

        $thirdLevel = $this->buildThirdLevel();
        $thirdLevel->setLevel(3);
        $thirdLevel->setFourthLevel($fourthLevel);
        $this->manager->persist($thirdLevel);

        $namedRelatedDummy = $this->buildRelatedDummy();
        $namedRelatedDummy->setName('Hello');
        $namedRelatedDummy->setThirdLevel($thirdLevel);
        $this->manager->persist($namedRelatedDummy);

        $relatedDummy = $this->buildRelatedDummy();
        $relatedDummy->setThirdLevel($thirdLevel);
        $this->manager->persist($relatedDummy);

        $dummy = $this->buildDummy();
        $dummy->setName('Dummy with relations');
        $dummy->setRelatedDummy($namedRelatedDummy);
        $dummy->addRelatedDummy($namedRelatedDummy);
        $dummy->addRelatedDummy($relatedDummy);
        $this->manager->persist($dummy);

        $this->manager->flush();
    }

    /**
     * @Given there is a RelatedOwnedDummy object with OneToOne relation
     */
    public function thereIsARelatedOwnedDummy()
    {
        $relatedOwnedDummy = $this->buildRelatedOwnedDummy();
        $this->manager->persist($relatedOwnedDummy);

        $dummy = $this->buildDummy();
        $dummy->setName('plop');
        $dummy->setRelatedOwnedDummy($relatedOwnedDummy);
        $this->manager->persist($dummy);

        $this->manager->flush();
    }

    /**
     * @Given there is a RelatedOwningDummy object with OneToOne relation
     */
    public function thereIsARelatedOwningDummy()
    {
        $dummy = $this->buildDummy();
        $dummy->setName('plop');
        $this->manager->persist($dummy);

        $relatedOwningDummy = $this->buildRelatedOwningDummy();
        $relatedOwningDummy->setOwnedDummy($dummy);
        $this->manager->persist($relatedOwningDummy);

        $this->manager->flush();
    }

    /**
     * @Given there is a person named :name greeting with a :message message
     */
    public function thereIsAPersonWithAGreeting(string $name, string $message)
    {
        $person = $this->buildPerson();
        $person->name = $name;

        $greeting = $this->buildGreeting();
        $greeting->message = $message;
        $greeting->sender = $person;

        $this->manager->persist($person);
        $this->manager->persist($greeting);

        $this->manager->flush();
        $this->manager->clear();
    }

    /**
     * @Given there is a max depth dummy with :level level of descendants
     */
    public function thereIsAMaxDepthDummyWithLevelOfDescendants(int $level)
    {
        $maxDepthDummy = $this->buildMaxDepthDummy();
        $maxDepthDummy->name = "level $level";
        $this->manager->persist($maxDepthDummy);

        for ($i = 1; $i <= $level; ++$i) {
            $maxDepthDummy = $maxDepthDummy->child = $this->buildMaxDepthDummy();
            $maxDepthDummy->name = 'level '.($i + 1);
            $this->manager->persist($maxDepthDummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there is a DummyDtoCustom
     */
    public function thereIsADummyDtoCustom()
    {
        $this->thereAreNbDummyDtoCustom(1);
    }

    /**
     * @Given there are :nb DummyDtoCustom
     */
    public function thereAreNbDummyDtoCustom($nb)
    {
        for ($i = 0; $i < $nb; ++$i) {
            $dto = $this->isOrm() ? new DummyDtoCustom() : new DummyDtoCustomDocument();
            $dto->lorem = 'test';
            $dto->ipsum = (string) ($i + 1);
            $this->manager->persist($dto);
        }

        $this->manager->flush();
        $this->manager->clear();
    }

    /**
     * @Given there is a DummyDtoOutputSameClass
     */
    public function thereIsADummyDtoOutputSameClass()
    {
        $dto = $this->isOrm() ? new DummyDtoOutputSameClass() : new DummyDtoOutputSameClassDocument();
        $dto->lorem = 'test';
        $dto->ipsum = '1';
        $this->manager->persist($dto);
        $this->manager->flush();
        $this->manager->clear();
    }

    /**
     * @Given there is a DummyDtoOutputFallbackToSameClass
     */
    public function thereIsADummyDtoOutputFallbackToSameClass()
    {
        $dto = $this->isOrm() ? new DummyDtoOutputFallbackToSameClass() : new DummyDtoOutputFallbackToSameClassDocument();
        $dto->lorem = 'test';
        $dto->ipsum = '1';
        $this->manager->persist($dto);
        $this->manager->flush();
        $this->manager->clear();
    }

    /**
     * @Given there is an order with same customer and recipient
     */
    public function thereIsAnOrderWithSameCustomerAndRecipient()
    {
        $customer = $this->isOrm() ? new Customer() : new CustomerDocument();
        $customer->name = 'customer_name';

        $address1 = $this->isOrm() ? new Address() : new AddressDocument();
        $address1->name = 'foo';
        $address2 = $this->isOrm() ? new Address() : new AddressDocument();
        $address2->name = 'bar';

        $order = $this->isOrm() ? new Order() : new OrderDocument();
        $order->recipient = $customer;
        $order->customer = $customer;

        $customer->addresses->add($address1);
        $customer->addresses->add($address2);

        $this->manager->persist($address1);
        $this->manager->persist($address2);
        $this->manager->persist($customer);
        $this->manager->persist($order);

        $this->manager->flush();
        $this->manager->clear();
    }

    /**
     * @Given there are :nb sites with internal owner
     */
    public function thereAreSitesWithInternalOwner(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $internalUser = new InternalUser();
            $internalUser->setFirstname('Internal');
            $internalUser->setLastname('User');
            $internalUser->setEmail('john.doe@example.com');
            $internalUser->setInternalId('INT');
            $site = new Site();
            $site->setTitle('title');
            $site->setDescription('description');
            $site->setOwner($internalUser);
            $this->manager->persist($site);
        }
        $this->manager->flush();
    }

    /**
     * @Given there are :nb sites with external owner
     */
    public function thereAreSitesWithExternalOwner(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $externalUser = new ExternalUser();
            $externalUser->setFirstname('External');
            $externalUser->setLastname('User');
            $externalUser->setEmail('john.doe@example.com');
            $externalUser->setExternalId('EXT');
            $site = new Site();
            $site->setTitle('title');
            $site->setDescription('description');
            $site->setOwner($externalUser);
            $this->manager->persist($site);
        }
        $this->manager->flush();
    }

    /**
     * @Given there is the following taxon:
     */
    public function thereIsTheFollowingTaxon(PyStringNode $dataNode): void
    {
        $data = json_decode((string) $dataNode, true);

        $taxon = $this->isOrm() ? new Taxon() : new TaxonDocument();
        $taxon->setCode($data['code']);
        $this->manager->persist($taxon);

        $this->manager->flush();
    }

    /**
     * @Given there is the following product:
     */
    public function thereIsTheFollowingProduct(PyStringNode $dataNode): void
    {
        $data = json_decode((string) $dataNode, true);

        $product = $this->isOrm() ? new Product() : new ProductDocument();
        $product->setCode($data['code']);
        if (isset($data['mainTaxon'])) {
            $mainTaxonCode = str_replace('/taxa/', '', $data['mainTaxon']);
            $mainTaxon = $this->manager->getRepository($this->isOrm() ? Taxon::class : TaxonDocument::class)->findOneBy([
                'code' => $mainTaxonCode,
            ]);
            $product->setMainTaxon($mainTaxon);
        }
        $this->manager->persist($product);

        $this->manager->flush();
    }

    /**
     * @Given there are :nb convertedOwner objects with convertedRelated
     */
    public function thereAreConvertedOwnerObjects(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $related = $this->buildConvertedRelated();
            $related->nameConverted = 'Converted '.$i;

            $owner = $this->buildConvertedOwner();
            $owner->nameConverted = $related;

            $this->manager->persist($related);
            $this->manager->persist($owner);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb dummy mercure objects
     */
    public function thereAreDummyMercureObjects(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $relatedDummy = $this->buildRelatedDummy();
            $relatedDummy->setName('RelatedDummy #'.$i);

            $dummyMercure = $this->buildDummyMercure();
            $dummyMercure->name = "Dummy Mercure #$i";
            $dummyMercure->description = 'Description';
            $dummyMercure->relatedDummy = $relatedDummy;

            $this->manager->persist($relatedDummy);
            $this->manager->persist($dummyMercure);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb iriOnlyDummies
     */
    public function thereAreIriOnlyDummies(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $iriOnlyDummy = $this->buildIriOnlyDummy();
            $iriOnlyDummy->setFoo('bar'.$nb);
            $this->manager->persist($iriOnlyDummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb absoluteUrlDummy objects with a related absoluteUrlRelationDummy
     */
    public function thereAreAbsoluteUrlDummies(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $absoluteUrlRelationDummy = $this->buildAbsoluteUrlRelationDummy();
            $absoluteUrlDummy = $this->buildAbsoluteUrlDummy();
            $absoluteUrlDummy->absoluteUrlRelationDummy = $absoluteUrlRelationDummy;

            $this->manager->persist($absoluteUrlRelationDummy);
            $this->manager->persist($absoluteUrlDummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there are :nb networkPathDummy objects with a related networkPathRelationDummy
     */
    public function thereAreNetworkPathDummies(int $nb)
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $networkPathRelationDummy = $this->buildNetworkPathRelationDummy();
            $networkPathDummy = $this->buildNetworkPathDummy();
            $networkPathDummy->networkPathRelationDummy = $networkPathRelationDummy;

            $this->manager->persist($networkPathRelationDummy);
            $this->manager->persist($networkPathDummy);
        }

        $this->manager->flush();
    }

    /**
     * @Given there is an InitializeInput object with id :id
     */
    public function thereIsAnInitializeInput(int $id)
    {
        $initializeInput = $this->buildInitializeInput();
        $initializeInput->id = $id;
        $initializeInput->manager = 'Orwell';
        $initializeInput->name = '1984';

        $this->manager->persist($initializeInput);
        $this->manager->flush();
    }

    /**
     * @Given there is a PatchDummyRelation
     */
    public function thereIsAPatchDummyRelation()
    {
        $dummy = $this->buildPatchDummyRelation();
        $related = $this->buildRelatedDummy();
        $this->manager->persist($related);
        $this->manager->flush();
        $dummy->setRelated($related);
        $this->manager->persist($dummy);
        $this->manager->flush();
    }

    /**
     * @Given there is a book
     */
    public function thereIsABook()
    {
        $book = $this->buildBook();
        $book->name = '1984';
        $book->isbn = '9780451524935';
        $this->manager->persist($book);
        $this->manager->flush();
    }

    /**
     * @Given there is a custom multiple identifier dummy
     */
    public function thereIsACustomMultipleIdentifierDummy()
    {
        $dummy = $this->buildCustomMultipleIdentifierDummy();
        $dummy->setName('Orwell');
        $dummy->setFirstId(1);
        $dummy->setSecondId(2);

        $this->manager->persist($dummy);
        $this->manager->flush();
    }

    private function isOrm(): bool
    {
        return null !== $this->schemaTool;
    }

    private function isOdm(): bool
    {
        return null !== $this->schemaManager;
    }

    /**
     * @return Answer|AnswerDocument
     */
    private function buildAnswer()
    {
        return $this->isOrm() ? new Answer() : new AnswerDocument();
    }

    /**
     * @return CompositeItem|CompositeItemDocument
     */
    private function buildCompositeItem()
    {
        return $this->isOrm() ? new CompositeItem() : new CompositeItemDocument();
    }

    /**
     * @return CompositeLabel|CompositeLabelDocument
     */
    private function buildCompositeLabel()
    {
        return $this->isOrm() ? new CompositeLabel() : new CompositeLabelDocument();
    }

    /**
     * @return CompositePrimitiveItem|CompositePrimitiveItemDocument
     */
    private function buildCompositePrimitiveItem(string $name, int $year)
    {
        return $this->isOrm() ? new CompositePrimitiveItem($name, $year) : new CompositePrimitiveItemDocument($name, $year);
    }

    /**
     * @return CompositeRelation|CompositeRelationDocument
     */
    private function buildCompositeRelation()
    {
        return $this->isOrm() ? new CompositeRelation() : new CompositeRelationDocument();
    }

    /**
     * @return Dummy|DummyDocument
     */
    private function buildDummy()
    {
        return $this->isOrm() ? new Dummy() : new DummyDocument();
    }

    /**
     * @return DummyTableInheritanceNotApiResourceChild|DummyTableInheritanceNotApiResourceChildDocument
     */
    private function buildDummyTableInheritanceNotApiResourceChild()
    {
        return $this->isOrm() ? new DummyTableInheritanceNotApiResourceChild() : new DummyTableInheritanceNotApiResourceChildDocument();
    }

    /**
     * @return DummyAggregateOffer|DummyAggregateOfferDocument
     */
    private function buildDummyAggregateOffer()
    {
        return $this->isOrm() ? new DummyAggregateOffer() : new DummyAggregateOfferDocument();
    }

    /**
     * @return DummyCar|DummyCarDocument
     */
    private function buildDummyCar()
    {
        return $this->isOrm() ? new DummyCar() : new DummyCarDocument();
    }

    /**
     * @return DummyCarColor|DummyCarColorDocument
     */
    private function buildDummyCarColor()
    {
        return $this->isOrm() ? new DummyCarColor() : new DummyCarColorDocument();
    }

    /**
     * @return DummyPassenger|DummyPassengerDocument
     */
    private function buildDummyPassenger()
    {
        return $this->isOrm() ? new DummyPassenger() : new DummyPassengerDocument();
    }

    /**
     * @return DummyTravel|DummyTravelDocument
     */
    private function buildDummyTravel()
    {
        return $this->isOrm() ? new DummyTravel() : new DummyTravelDocument();
    }

    /**
     * @return DummyDate|DummyDateDocument
     */
    private function buildDummyDate()
    {
        return $this->isOrm() ? new DummyDate() : new DummyDateDocument();
    }

    /**
     * @return DummyImmutableDate|DummyImmutableDateDocument
     */
    private function buildDummyImmutableDate()
    {
        return $this->isOrm() ? new DummyImmutableDate() : new DummyImmutableDateDocument();
    }

    /**
     * @return DummyDifferentGraphQlSerializationGroup|DummyDifferentGraphQlSerializationGroupDocument
     */
    private function buildDummyDifferentGraphQlSerializationGroup()
    {
        return $this->isOrm() ? new DummyDifferentGraphQlSerializationGroup() : new DummyDifferentGraphQlSerializationGroupDocument();
    }

    /**
     * @return DummyDtoNoInput|DummyDtoNoInputDocument
     */
    private function buildDummyDtoNoInput()
    {
        return $this->isOrm() ? new DummyDtoNoInput() : new DummyDtoNoInputDocument();
    }

    /**
     * @return DummyDtoNoOutput|DummyDtoNoOutputDocument
     */
    private function buildDummyDtoNoOutput()
    {
        return $this->isOrm() ? new DummyDtoNoOutput() : new DummyDtoNoOutputDocument();
    }

    /**
     * @return DummyCustomQuery|DummyCustomQueryDocument
     */
    private function buildDummyCustomQuery()
    {
        return $this->isOrm() ? new DummyCustomQuery() : new DummyCustomQueryDocument();
    }

    /**
     * @return DummyCustomMutation|DummyCustomMutationDocument
     */
    private function buildDummyCustomMutation()
    {
        return $this->isOrm() ? new DummyCustomMutation() : new DummyCustomMutationDocument();
    }

    /**
     * @return DummyFriend|DummyFriendDocument
     */
    private function buildDummyFriend()
    {
        return $this->isOrm() ? new DummyFriend() : new DummyFriendDocument();
    }

    /**
     * @return DummyGroup|DummyGroupDocument
     */
    private function buildDummyGroup()
    {
        return $this->isOrm() ? new DummyGroup() : new DummyGroupDocument();
    }

    /**
     * @return DummyOffer|DummyOfferDocument
     */
    private function buildDummyOffer()
    {
        return $this->isOrm() ? new DummyOffer() : new DummyOfferDocument();
    }

    /**
     * @return DummyProduct|DummyProductDocument
     */
    private function buildDummyProduct()
    {
        return $this->isOrm() ? new DummyProduct() : new DummyProductDocument();
    }

    /**
     * @return DummyProperty|DummyPropertyDocument
     */
    private function buildDummyProperty()
    {
        return $this->isOrm() ? new DummyProperty() : new DummyPropertyDocument();
    }

    /**
     * @return EmbeddableDummy|EmbeddableDummyDocument
     */
    private function buildEmbeddableDummy()
    {
        return $this->isOrm() ? new EmbeddableDummy() : new EmbeddableDummyDocument();
    }

    /**
     * @return EmbeddedDummy|EmbeddedDummyDocument
     */
    private function buildEmbeddedDummy()
    {
        return $this->isOrm() ? new EmbeddedDummy() : new EmbeddedDummyDocument();
    }

    /**
     * @return FileConfigDummy|FileConfigDummyDocument
     */
    private function buildFileConfigDummy()
    {
        return $this->isOrm() ? new FileConfigDummy() : new FileConfigDummyDocument();
    }

    /**
     * @return Foo|FooDocument
     */
    private function buildFoo()
    {
        return $this->isOrm() ? new Foo() : new FooDocument();
    }

    /**
     * @return FooDummy|FooDummyDocument
     */
    private function buildFooDummy()
    {
        return $this->isOrm() ? new FooDummy() : new FooDummyDocument();
    }

    /**
     * @return FourthLevel|FourthLevelDocument
     */
    private function buildFourthLevel()
    {
        return $this->isOrm() ? new FourthLevel() : new FourthLevelDocument();
    }

    /**
     * @return Greeting|GreetingDocument
     */
    private function buildGreeting()
    {
        return $this->isOrm() ? new Greeting() : new GreetingDocument();
    }

    /**
     * @return IriOnlyDummy|IriOnlyDummyDocument
     */
    private function buildIriOnlyDummy()
    {
        return $this->isOrm() ? new IriOnlyDummy() : new IriOnlyDummyDocument();
    }

    /**
     * @return MaxDepthDummy|MaxDepthDummyDocument
     */
    private function buildMaxDepthDummy()
    {
        return $this->isOrm() ? new MaxDepthDummy() : new MaxDepthDummyDocument();
    }

    /**
     * @return Person|PersonDocument
     */
    private function buildPerson()
    {
        return $this->isOrm() ? new Person() : new PersonDocument();
    }

    /**
     * @return PersonToPet|PersonToPetDocument
     */
    private function buildPersonToPet()
    {
        return $this->isOrm() ? new PersonToPet() : new PersonToPetDocument();
    }

    /**
     * @return Pet|PetDocument
     */
    private function buildPet()
    {
        return $this->isOrm() ? new Pet() : new PetDocument();
    }

    /**
     * @return Question|QuestionDocument
     */
    private function buildQuestion()
    {
        return $this->isOrm() ? new Question() : new QuestionDocument();
    }

    /**
     * @return RelatedDummy|RelatedDummyDocument
     */
    private function buildRelatedDummy()
    {
        return $this->isOrm() ? new RelatedDummy() : new RelatedDummyDocument();
    }

    /**
     * @return RelatedOwnedDummy|RelatedOwnedDummyDocument
     */
    private function buildRelatedOwnedDummy()
    {
        return $this->isOrm() ? new RelatedOwnedDummy() : new RelatedOwnedDummyDocument();
    }

    /**
     * @return RelatedOwningDummy|RelatedOwningDummyDocument
     */
    private function buildRelatedOwningDummy()
    {
        return $this->isOrm() ? new RelatedOwningDummy() : new RelatedOwningDummyDocument();
    }

    /**
     * @return RelatedToDummyFriend|RelatedToDummyFriendDocument
     */
    private function buildRelatedToDummyFriend()
    {
        return $this->isOrm() ? new RelatedToDummyFriend() : new RelatedToDummyFriendDocument();
    }

    /**
     * @return RelationEmbedder|RelationEmbedderDocument
     */
    private function buildRelationEmbedder()
    {
        return $this->isOrm() ? new RelationEmbedder() : new RelationEmbedderDocument();
    }

    /**
     * @return SecuredDummy|SecuredDummyDocument
     */
    private function buildSecuredDummy()
    {
        return $this->isOrm() ? new SecuredDummy() : new SecuredDummyDocument();
    }

    /**
     * @return RelatedSecuredDummy|RelatedSecuredDummyDocument
     */
    private function buildRelatedSecureDummy()
    {
        return $this->isOrm() ? new RelatedSecuredDummy() : new RelatedSecuredDummyDocument();
    }

    /**
     * @return ThirdLevel|ThirdLevelDocument
     */
    private function buildThirdLevel()
    {
        return $this->isOrm() ? new ThirdLevel() : new ThirdLevelDocument();
    }

    /**
     * @return ConvertedDate|ConvertedDateDocument
     */
    private function buildConvertedDate()
    {
        return $this->isOrm() ? new ConvertedDate() : new ConvertedDateDocument();
    }

    /**
     * @return ConvertedBoolean|ConvertedBoolDocument
     */
    private function buildConvertedBoolean()
    {
        return $this->isOrm() ? new ConvertedBoolean() : new ConvertedBoolDocument();
    }

    /**
     * @return ConvertedInteger|ConvertedIntegerDocument
     */
    private function buildConvertedInteger()
    {
        return $this->isOrm() ? new ConvertedInteger() : new ConvertedIntegerDocument();
    }

    /**
     * @return ConvertedString|ConvertedStringDocument
     */
    private function buildConvertedString()
    {
        return $this->isOrm() ? new ConvertedString() : new ConvertedStringDocument();
    }

    /**
     * @return ConvertedOwner|ConvertedOwnerDocument
     */
    private function buildConvertedOwner()
    {
        return $this->isOrm() ? new ConvertedOwner() : new ConvertedOwnerDocument();
    }

    /**
     * @return ConvertedRelated|ConvertedRelatedDocument
     */
    private function buildConvertedRelated()
    {
        return $this->isOrm() ? new ConvertedRelated() : new ConvertedRelatedDocument();
    }

    /**
     * @return DummyMercure|DummyMercureDocument
     */
    private function buildDummyMercure()
    {
        return $this->isOrm() ? new DummyMercure() : new DummyMercureDocument();
    }

    /**
     * @return AbsoluteUrlDummyDocument|AbsoluteUrlDummy
     */
    private function buildAbsoluteUrlDummy()
    {
        return $this->isOrm() ? new AbsoluteUrlDummy() : new AbsoluteUrlDummyDocument();
    }

    /**
     * @return AbsoluteUrlRelationDummyDocument|AbsoluteUrlRelationDummy
     */
    private function buildAbsoluteUrlRelationDummy()
    {
        return $this->isOrm() ? new AbsoluteUrlRelationDummy() : new AbsoluteUrlRelationDummyDocument();
    }

    /**
     * @return NetworkPathDummyDocument|NetworkPathDummy
     */
    private function buildNetworkPathDummy()
    {
        return $this->isOrm() ? new NetworkPathDummy() : new NetworkPathDummyDocument();
    }

    /**
     * @return NetworkPathRelationDummyDocument|NetworkPathRelationDummy
     */
    private function buildNetworkPathRelationDummy()
    {
        return $this->isOrm() ? new NetworkPathRelationDummy() : new NetworkPathRelationDummyDocument();
    }

    /**
     * @return InitializeInput|InitializeInputDocument
     */
    private function buildInitializeInput()
    {
        return $this->isOrm() ? new InitializeInput() : new InitializeInputDocument();
    }

    /**
     * @return PatchDummyRelation|PatchDummyRelationDocument
     */
    private function buildPatchDummyRelation()
    {
        return $this->isOrm() ? new PatchDummyRelation() : new PatchDummyRelationDocument();
    }

    /**
     * @return BookDocument | Book
     */
    private function buildBook()
    {
        return $this->isOrm() ? new Book() : new BookDocument();
    }

    /**
     * @return CustomMultipleIdentifierDummy | CustomMultipleIdentifierDummyDocument
     */
    private function buildCustomMultipleIdentifierDummy()
    {
        return $this->isOrm() ? new CustomMultipleIdentifierDummy() : new CustomMultipleIdentifierDummyDocument();
    }

    /**
     * @return WithJsonDummy|WithJsonDummyDocument
     */
    private function buildWithJsonDummy()
    {
        return $this->isOrm() ? new WithJsonDummy() : new WithJsonDummyDocument();
    }
}
