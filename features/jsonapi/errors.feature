Feature: JSON API error handling
  In order to be able to handle error client side
  As a client software developer
  I need to retrieve an JSON API serialization of errors

  @createSchema
  Scenario: Get a validation error on an attribute
    When I add "Content-Type" header equal to "application/vnd.api+json"
    And I add "Accept" header equal to "application/vnd.api+json"
    And I send a "POST" request to "/dummies" with body:
    """
    {
      "data": {
        "type": "dummy",
        "attributes": {}
      }
    }
    """
    Then the response status code should be 400
    And print last JSON response
    And I validate it with jsonapi-validator
    And the JSON should be equal to:
    """
    {
      "errors": [
        {
          "detail": "This value should not be blank.",
          "source": {
            "pointer": "data\/attributes\/name"
          }
        }
      ]
    }
    """

  @dropSchema
  Scenario: Get a validation error on an relationship
    Given there is a RelatedDummy
    And there is a DummyFriend
    When I add "Content-Type" header equal to "application/vnd.api+json"
    And I add "Accept" header equal to "application/vnd.api+json"
    And I send a "POST" request to "/related_to_dummy_friends" with body:
    """
    {
      "data": {
        "type": "RelatedToDummyFriend",
        "attributes": {
          "name": "Related to dummy friend"
        }
      }
    }
    """
    And print last JSON response
    Then the response status code should be 400
    And I validate it with jsonapi-validator
    And the JSON should be equal to:
    """
    {
      "errors": [
        {
          "detail": "This value should not be null.",
          "source": {
            "pointer": "data\/relationships\/dummyFriend"
          }
        },
        {
          "detail": "This value should not be null.",
          "source": {
            "pointer": "data\/relationships\/relatedDummy"
          }
        }
      ]
    }
    """
