@!lowest
Feature: Serialization of boolean properties
  In order to use an hypermedia API
  As a client software developer
  I need to be able get boolean properties with is* methods

  @createSchema
  Scenario: Create a dummy boolean resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_booleans" with body:
    """
    {
      "isDummyBoolean": true
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the header "Content-Location" should be equal to "/dummy_booleans/1"
    And the header "Location" should be equal to "/dummy_booleans/1"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/DummyBoolean",
      "@id": "/dummy_booleans/1",
      "@type": "DummyBoolean",
      "id": 1,
      "isDummyBoolean": true,
      "dummyBoolean": true
    }
    """
