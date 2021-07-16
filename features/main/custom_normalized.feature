Feature: Using custom normalized entity
  In order to use an hypermedia API
  As a client software developer
  I need to be able to filter correctly attribute of my entities

  @createSchema
  Scenario: Create a resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/custom_normalized_dummies" with body:
    """
    {
      "name": "My Dummy",
      "alias": "My alias"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the header "Content-Location" should be equal to "/custom_normalized_dummies/1"
    And the header "Location" should be equal to "/custom_normalized_dummies/1"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/CustomNormalizedDummy",
      "@id": "/custom_normalized_dummies/1",
      "@type": "CustomNormalizedDummy",
      "id": 1,
      "name": "My Dummy",
      "alias": "My alias"
    }
    """

  @createSchema
  Scenario: Create a resource with a custom normalized dummy
    When I add "Content-Type" header equal to "application/json"
    When I add "Accept" header equal to "application/json"
    And I send a "POST" request to "/related_normalized_dummies" with body:
    """
    {
      "name": "My Dummy"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json; charset=utf-8"
    And the header "Content-Location" should be equal to "/related_normalized_dummies/1"
    And the header "Location" should be equal to "/related_normalized_dummies/1"
    And the JSON should be equal to:
    """
    {
        "id": 1,
        "name": "My Dummy",
        "customNormalizedDummy": []
    }
    """

  @createSchema
  Scenario: Create a resource with a custom normalized dummy and an id
    When I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/custom_normalized_dummies" with body:
    """
    {
      "name": "My Dummy",
      "alias": "My alias"
    }
    """
    Then the response status code should be 201
    When I add "Content-Type" header equal to "application/json"
    When I add "Accept" header equal to "application/json"
    And I send a "POST" request to "/related_normalized_dummies" with body:
    """
    {
      "name": "My Dummy"
    }
    """
    Then the response status code should be 201
    When I add "Content-Type" header equal to "application/json"
    When I add "Accept" header equal to "application/json"
    And I send a "PUT" request to "/related_normalized_dummies/1" with body:
    """
    {
      "name": "My Dummy",
      "customNormalizedDummy":[{
        "@context": "/contexts/CustomNormalizedDummy",
        "@id": "/custom_normalized_dummies/1",
        "@type": "CustomNormalizedDummy",
        "id": 1,
        "name": "My Dummy"
    }]
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json; charset=utf-8"
    And the header "Content-Location" should be equal to "/related_normalized_dummies/1"
    And the JSON should be equal to:
    """
    {
      "id": 1,
      "name": "My Dummy",
      "customNormalizedDummy":[{
        "id": 1,
        "name": "My Dummy",
        "alias": "My alias"
         }]
    }
    """

  Scenario: Get a custom normalized dummy resource
    When I send a "GET" request to "/custom_normalized_dummies/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/CustomNormalizedDummy",
      "@id": "/custom_normalized_dummies/1",
      "@type": "CustomNormalizedDummy",
      "id": 1,
      "name": "My Dummy",
      "alias": "My alias"
    }
    """

  Scenario: Get a collection
    When I send a "GET" request to "/custom_normalized_dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/CustomNormalizedDummy",
      "@id": "/custom_normalized_dummies",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/custom_normalized_dummies/1",
          "@type": "CustomNormalizedDummy",
           "id": 1,
          "name": "My Dummy",
          "alias": "My alias"
        }
      ],
      "hydra:totalItems": 1
    }
    """

  Scenario: Update a resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/custom_normalized_dummies/1" with body:
    """
    {
      "name": "My Dummy modified"
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the header "Content-Location" should be equal to "/custom_normalized_dummies/1"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/CustomNormalizedDummy",
      "@id": "/custom_normalized_dummies/1",
      "@type": "CustomNormalizedDummy",
      "id": 1,
      "name": "My Dummy modified",
      "alias": "My alias"
    }
    """

  Scenario: API doc is correctly generated
    When I send a "GET" request to "/docs.jsonld"
    Then the response status code should be 200
    And the response should be in JSON
    And the Hydra class "CustomNormalizedDummy" exists
    And 4 operations are available for Hydra class "CustomNormalizedDummy"
    And 2 properties are available for Hydra class "CustomNormalizedDummy"
    And "name" property is readable for Hydra class "CustomNormalizedDummy"
    And "name" property is writable for Hydra class "CustomNormalizedDummy"
    And "alias" property is readable for Hydra class "CustomNormalizedDummy"
    And "alias" property is writable for Hydra class "CustomNormalizedDummy"

  Scenario: Delete a resource
    When I send a "DELETE" request to "/custom_normalized_dummies/1"
    Then the response status code should be 204
    And the response should be empty
