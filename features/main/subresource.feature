Feature: Subresource support
  In order to use a hypermedia API
  As a client software developer
  I need to be able to retrieve embedded resources only as Subresources

  @createSchema
  Scenario: Get subresource one to one relation
    Given there is an answer "42" to the question "What's the answer to the Ultimate Question of Life, the Universe and Everything?"
    When I send a "GET" request to "/questions/1/answer"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Answer",
      "@id": "/answers/1",
      "@type": "Answer",
      "id": 1,
      "content": "42",
      "question": "/questions/1",
      "relatedQuestions": [
        "/questions/1"
      ]
    }
    """

  @createSchema
  Scenario: Get a non existent subresource
    Given there is an answer "42" to the question "What's the answer to the Ultimate Question of Life, the Universe and Everything?"
    When I send a "GET" request to "/questions/999999/answer"
    Then the response status code should be 404
    And the response should be in JSON
 
  @createSchema
  Scenario: Get recursive subresource one to many relation
    Given there is an answer "42" to the question "What's the answer to the Ultimate Question of Life, the Universe and Everything?"
    When I send a "GET" request to "/questions/1/answer/related_questions"
    And the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Question",
      "@id": "/questions/1/answer/related_questions",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/questions/1",
          "@type": "Question",
          "content": "What's the answer to the Ultimate Question of Life, the Universe and Everything?",
          "id": 1,
          "answer": "/answers/1"
        }
      ],
      "hydra:totalItems": 1
    }
    """

  @createSchema
  Scenario: Get the subresource relation collection
    Given there is a dummy object with a fourth level relation
    When I send a "GET" request to "/dummies/1/related_dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/RelatedDummy",
      "@id": "/dummies/1/related_dummies",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/related_dummies/1",
          "@type": "https://schema.org/Product",
          "id": 1,
          "name": "Hello",
          "symfony": "symfony",
          "dummyDate": null,
          "thirdLevel": {
            "@id": "/third_levels/1",
            "@type": "ThirdLevel",
            "fourthLevel": "/fourth_levels/1"
          },
          "relatedToDummyFriend": [],
          "dummyBoolean": null,
          "embeddedDummy": [],
          "age": null
        },
        {
          "@id": "/related_dummies/2",
          "@type": "https://schema.org/Product",
          "id": 2,
          "name": null,
          "symfony": "symfony",
          "dummyDate": null,
          "thirdLevel": {
            "@id": "/third_levels/1",
            "@type": "ThirdLevel",
            "fourthLevel": "/fourth_levels/1"
          },
          "relatedToDummyFriend": [],
          "dummyBoolean": null,
          "embeddedDummy": [],
          "age": null
        }
      ],
      "hydra:totalItems": 2,
      "hydra:search": {
        "@type": "hydra:IriTemplate",
        "hydra:template": "/dummies/1/related_dummies{?relatedToDummyFriend.dummyFriend,relatedToDummyFriend.dummyFriend[],name,age,age[]}",
        "hydra:variableRepresentation": "BasicRepresentation",
        "hydra:mapping": [
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedToDummyFriend.dummyFriend",
            "property": "relatedToDummyFriend.dummyFriend",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedToDummyFriend.dummyFriend[]",
            "property": "relatedToDummyFriend.dummyFriend",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "name",
            "property": "name",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "age",
            "property": "age",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "age[]",
            "property": "age",
            "required": false
          }
        ]
      }
    }
    """

  @createSchema
  Scenario: Get filtered embedded relation subresource collection
    Given there is a dummy object with a fourth level relation
    When I send a "GET" request to "/dummies/1/related_dummies?name=Hello"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/RelatedDummy",
      "@id": "/dummies/1/related_dummies",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/related_dummies/1",
          "@type": "https://schema.org/Product",
          "id": 1,
          "name": "Hello",
          "symfony": "symfony",
          "dummyDate": null,
          "thirdLevel": {
            "@id": "/third_levels/1",
            "@type": "ThirdLevel",
            "fourthLevel": "/fourth_levels/1"
          },
          "relatedToDummyFriend": [],
          "dummyBoolean": null,
          "embeddedDummy": [],
          "age": null
        }
      ],
      "hydra:totalItems": 1,
      "hydra:view": {
        "@id": "/dummies/1/related_dummies?name=Hello",
        "@type": "hydra:PartialCollectionView"
      },
      "hydra:search": {
        "@type": "hydra:IriTemplate",
        "hydra:template": "/dummies/1/related_dummies{?relatedToDummyFriend.dummyFriend,relatedToDummyFriend.dummyFriend[],name,age,age[]}",
        "hydra:variableRepresentation": "BasicRepresentation",
        "hydra:mapping": [
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedToDummyFriend.dummyFriend",
            "property": "relatedToDummyFriend.dummyFriend",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedToDummyFriend.dummyFriend[]",
            "property": "relatedToDummyFriend.dummyFriend",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "name",
            "property": "name",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "age",
            "property": "age",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "age[]",
            "property": "age",
            "required": false
          }
        ]
      }
    }
    """

  Scenario: Get the subresource relation item
    When I send a "GET" request to "/dummies/1/related_dummies/2"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/RelatedDummy",
      "@id": "/related_dummies/2",
      "@type": "https://schema.org/Product",
      "id": 2,
      "name": null,
      "symfony": "symfony",
      "dummyDate": null,
      "thirdLevel": {
        "@id": "/third_levels/1",
        "@type": "ThirdLevel",
        "fourthLevel": "/fourth_levels/1"
      },
      "relatedToDummyFriend": [],
      "dummyBoolean": null,
      "embeddedDummy": [],
      "age": null
    }
    """

  Scenario: Create a dummy with a relation that is a subresource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummies" with body:
    """
    {
      "name": "Dummy with relations",
      "relatedDummy": "/dummies/1/related_dummies/2"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"

  Scenario: Get the embedded relation subresource item at the third level
    When I send a "GET" request to "/dummies/1/related_dummies/1/third_level"
    And the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/ThirdLevel",
      "@id": "/third_levels/1",
      "@type": "ThirdLevel",
      "fourthLevel": "/fourth_levels/1",
      "badFourthLevel": null,
      "id": 1,
      "level": 3,
      "test": true
    }
    """

  Scenario: Get the embedded relation subresource item at the fourth level
    When I send a "GET" request to "/dummies/1/related_dummies/1/third_level/fourth_level"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/FourthLevel",
      "@id": "/fourth_levels/1",
      "@type": "FourthLevel",
      "badThirdLevel": [],
      "id": 1,
      "level": 4
    }
    """

  Scenario: Get offers subresource from aggregate offers subresource
    Given I have a product with offers
    When I send a "GET" request to "/dummy_products/2/offers/1/offers"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/DummyOffer",
      "@id": "/dummy_products/2/offers/1/offers",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/dummy_offers/1",
          "@type": "DummyOffer",
          "id": 1,
          "value": 2,
          "aggregate": "/dummy_aggregate_offers/1"
        }
      ],
      "hydra:totalItems": 1
    }
    """

  Scenario: Get offers subresource from aggregate offers subresource
    When I send a "GET" request to "/dummy_aggregate_offers/1/offers"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/DummyOffer",
      "@id": "/dummy_aggregate_offers/1/offers",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/dummy_offers/1",
          "@type": "DummyOffer",
          "id": 1,
          "value": 2,
          "aggregate": "/dummy_aggregate_offers/1"
        }
      ],
      "hydra:totalItems": 1
    }
    """

  Scenario: The recipient of the person's greetings should be empty
    Given there is a person named "Alice" greeting with a "hello" message
    When I send a "GET" request to "/people/1/sent_greetings"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Greeting",
      "@id": "/people/1/sent_greetings",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/greetings/1",
          "@type": "Greeting",
          "message": "hello",
          "sender": "/people/1",
          "recipient": null,
          "id": 1
        }
      ],
      "hydra:totalItems": 1
    }
    """

  Scenario: Recursive resource
    When I send a "GET" request to "/dummy_products/2"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/DummyProduct",
      "@id": "/dummy_products/2",
      "@type": "DummyProduct",
      "offers": [
        "/dummy_aggregate_offers/1"
      ],
      "id": 2,
      "name": "Dummy product",
      "relatedProducts": [
        "/dummy_products/1"
      ],
      "parent": null
    }
    """

  @createSchema
  Scenario: The OneToOne subresource should be accessible from owned side
    Given there is a RelatedOwnedDummy object with OneToOne relation
    When I send a "GET" request to "/related_owned_dummies/1/owning_dummy"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies/1",
      "@type": "Dummy",
      "description": null,
      "dummy": null,
      "dummyBoolean": null,
      "dummyDate": null,
      "dummyFloat": null,
      "dummyPrice": null,
      "relatedDummy": null,
      "relatedDummies": [],
      "jsonData": [],
      "arrayData": [],
      "name_converted": null,
      "relatedOwnedDummy": "/related_owned_dummies/1",
      "relatedOwningDummy": null,
      "id": 1,
      "name": "plop",
      "alias": null,
      "foo": null
    }
    """

  @createSchema
  Scenario: The OneToOne subresource should be accessible from owning side
    Given there is a RelatedOwningDummy object with OneToOne relation
    When I send a "GET" request to "/related_owning_dummies/1/owned_dummy"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies/1",
      "@type": "Dummy",
      "description": null,
      "dummy": null,
      "dummyBoolean": null,
      "dummyDate": null,
      "dummyFloat": null,
      "dummyPrice": null,
      "relatedDummy": null,
      "relatedDummies": [],
      "jsonData": [],
      "arrayData": [],
      "name_converted": null,
      "relatedOwnedDummy": null,
      "relatedOwningDummy": "/related_owning_dummies/1",
      "id": 1,
      "name": "plop",
      "alias": null,
      "foo": null
    }
    """
