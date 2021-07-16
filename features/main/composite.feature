@!mongodb
Feature: Retrieve data with Composite identifiers
  In order to retrieve relations with composite identifiers
  As a client software developer
  I need to retrieve all collections

  @createSchema
  Scenario: Get a collection with composite identifiers
    Given there are Composite identifier objects
    When I send a "GET" request to "/composite_items"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/CompositeItem",
      "@id": "/composite_items",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/composite_items/1",
          "@type": "CompositeItem",
          "id": 1,
          "field1": "foobar",
          "compositeValues": [
            "/composite_relations/compositeItem=1;compositeLabel=1",
            "/composite_relations/compositeItem=1;compositeLabel=2",
            "/composite_relations/compositeItem=1;compositeLabel=3",
            "/composite_relations/compositeItem=1;compositeLabel=4"
          ]
        }
      ],
      "hydra:totalItems": 1
    }
    """

  @createSchema
  Scenario: Get collection with composite identifiers
    Given there are Composite identifier objects
    When I send a "GET" request to "/composite_relations"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/CompositeRelation",
      "@id": "/composite_relations",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/composite_relations/compositeItem=1;compositeLabel=1",
          "@type": "CompositeRelation",
          "value": "somefoobardummy",
          "compositeItem": "/composite_items/1",
          "compositeLabel": "/composite_labels/1"
        },
        {
          "@id": "/composite_relations/compositeItem=1;compositeLabel=2",
          "@type": "CompositeRelation",
          "value": "somefoobardummy",
          "compositeItem": "/composite_items/1",
          "compositeLabel": "/composite_labels/2"
        },
        {
          "@id": "/composite_relations/compositeItem=1;compositeLabel=3",
          "@type": "CompositeRelation",
          "value": "somefoobardummy",
          "compositeItem": "/composite_items/1",
          "compositeLabel": "/composite_labels/3"
        }
      ],
      "hydra:totalItems": 4,
      "hydra:view": {
        "@id": "/composite_relations?page=1",
        "@type": "hydra:PartialCollectionView",
        "hydra:first": "/composite_relations?page=1",
        "hydra:last": "/composite_relations?page=2",
        "hydra:next": "/composite_relations?page=2"
      }
    }
    """

  @createSchema
  Scenario: Get the first composite relation
    Given there are Composite identifier objects
    When I send a "GET" request to "/composite_relations/compositeItem=1;compositeLabel=1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/CompositeRelation",
      "@id": "/composite_relations/compositeItem=1;compositeLabel=1",
      "@type": "CompositeRelation",
      "value": "somefoobardummy",
      "compositeItem": "/composite_items/1",
      "compositeLabel": "/composite_labels/1"
    }
    """

  @createSchema
  Scenario: Get the first composite relation with a reverse identifiers order
    Given there are Composite identifier objects
    When I send a "GET" request to "/composite_relations/compositeLabel=1;compositeItem=1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/CompositeRelation",
      "@id": "/composite_relations/compositeItem=1;compositeLabel=1",
      "@type": "CompositeRelation",
      "value": "somefoobardummy",
      "compositeItem": "/composite_items/1",
      "compositeLabel": "/composite_labels/1"
    }
    """

  @createSchema
  Scenario: Get the first composite relation with a missing identifier
    Given there are Composite identifier objects
    When I send a "GET" request to "/composite_relations/compositeLabel=1;"
    Then the response status code should be 404

  Scenario: Get first composite item
    Given there are Composite identifier objects
    When I send a "GET" request to "/composite_items/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
