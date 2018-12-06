Feature: Get the resource level context
  In order to see the resource level context
  As a client software developer
  I need to be able to create and get the JSON LD context

  @createSchema
  Scenario: Create a resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/jsonld_context_dummies" with body:
    """
    {
      "title": "My Dummy",
      "person": "My Dummy"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
          "@context": "/contexts/JsonldContextDummy",
          "@id": "/jsonld_context_dummies/1",
          "@type": "JsonldContextDummy",
          "id": 1,
          "person": "My Dummy",
          "dct:title": "My Dummy"
     }
    """