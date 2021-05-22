Feature: HAL support
  In order to use the HAL hypermedia format
  As a client software developer
  I need to be able to retrieve valid HAL responses.

  @createSchema
  Scenario: Retrieve the API entrypoint
    When I add "Accept" header equal to "application/hal+json"
    And I send a "GET" request to "/"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON HAL schema
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON node "_links.self.href" should be equal to "/"
    And the JSON node "_links.dummy.href" should be equal to "/dummies"

  Scenario: Create a third level
    When I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/third_levels" with body:
    """
    {"level": 3}
    """
    Then the response status code should be 201

  Scenario: Create a related dummy
    When I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/related_dummies" with body:
    """
    {"thirdLevel": "/third_levels/1"}
    """
    Then the response status code should be 201

  Scenario: Create a dummy with relations
    When I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/dummies" with body:
    """
    {
      "name": "Dummy with relations",
      "dummyDate": "2015-03-01T10:00:00+00:00",
      "relatedDummy": "http://example.com/related_dummies/1",
      "relatedDummies": [
        "/related_dummies/1"
      ]
    }
    """
    Then the response status code should be 201

  Scenario: Get a resource with relations
    When I add "Accept" header equal to "application/hal+json"
    And I send a "GET" request to "/dummies/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON HAL schema
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "_links": {
        "self": {
          "href": "/dummies/1"
        },
        "relatedDummy": {
          "href": "/related_dummies/1"
        },
        "relatedDummies": [
          {
            "href": "/related_dummies/1"
          }
        ]
      },
      "description": null,
      "dummy": null,
      "dummyBoolean": null,
      "dummyDate": "2015-03-01T10:00:00+00:00",
      "dummyFloat": null,
      "dummyPrice": null,
      "jsonData": [],
      "arrayData": [],
      "name_converted": null,
      "id": 1,
      "name": "Dummy with relations",
      "alias": null,
      "foo": null
    }
    """

  Scenario: Update a resource
    When I add "Accept" header equal to "application/hal+json"
    And I add "Content-Type" header equal to "application/json"
    And I send a "PUT" request to "/dummies/1" with body:
    """
    {"name": "A nice dummy"}
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON HAL schema
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "_links": {
        "self": {
          "href": "/dummies/1"
        },
        "relatedDummy": {
          "href": "/related_dummies/1"
        },
        "relatedDummies": [
          {
            "href": "/related_dummies/1"
          }
        ]
      },
      "description": null,
      "dummy": null,
      "dummyBoolean": null,
      "dummyDate": "2015-03-01T10:00:00+00:00",
      "dummyFloat": null,
      "dummyPrice": null,
      "jsonData": [],
      "arrayData": [],
      "name_converted": null,
      "id": 1,
      "name": "A nice dummy",
      "alias": null,
      "foo": null
    }
    """

  Scenario: Embed a relation in a parent object
    When I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/relation_embedders" with body:
    """
    {
      "related": "/related_dummies/1"
    }
    """
    Then the response status code should be 201

  Scenario: Get the object with the embedded relation
    When I add "Accept" header equal to "application/hal+json"
    And I send a "GET" request to "/relation_embedders/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON HAL schema
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "_links": {
        "self": {
          "href": "/relation_embedders/1"
        },
        "related": {
          "href": "/related_dummies/1"
        }
      },
      "_embedded": {
        "related": {
          "_links": {
            "self": {
              "href": "/related_dummies/1"
            },
            "thirdLevel": {
              "href": "/third_levels/1"
            },
            "parent": {
               "href": "\/relation_embedders\/1"
            }
          },
          "_embedded": {
            "thirdLevel": {
              "_links": {
                "self": {
                  "href": "/third_levels/1"
                },
                "parent": {
                   "href": "\/related_dummies\/1"
                }
              },
              "level": 3
            }
          },
          "symfony": "symfony"
        }
      },
      "krondstadt": "Krondstadt"
    }
    """
