@sqlite
Feature: Cache invalidation through HTTP Cache tags
  In order to have a fast API
  As an API software developer
  I need to store API responses in a cache

  @createSchema
  Scenario: Create some embedded resources
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/relation_embedders" with body:
    """
    {
      "anotherRelated": {
        "name": "Related",
        "thirdLevel": {}
      }
    }
    """
    Then the response status code should be 201
    And the header "Cache-Tags" should not exist
    And "/relation_embedders,/related_dummies,/third_levels" IRIs should be purged

  Scenario: Tags must be set for items
    When I send a "GET" request to "/relation_embedders/1"
    Then the response status code should be 200
    And the header "Cache-Tags" should be equal to "/relation_embedders/1,/related_dummies/1,/third_levels/1"

  Scenario: Create some more resources
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/relation_embedders" with body:
    """
    {
      "anotherRelated": {
        "name": "Another Related",
        "thirdLevel": {}
      }
    }
    """
    Then the response status code should be 201
    And the header "Cache-Tags" should not exist

  Scenario: Tags must be set for collections
    When I send a "GET" request to "/relation_embedders"
    Then the response status code should be 200
    And the header "Cache-Tags" should be equal to "/relation_embedders/1,/related_dummies/1,/third_levels/1,/relation_embedders/2,/related_dummies/2,/third_levels/2,/relation_embedders"

  Scenario: Tags must be set for Hydra API doc
    When I send a "GET" request to "/docs"
    Then the response status code should be 200
    And the header "Cache-Tags" should be equal to "_api/docs"

  Scenario: Tags must be set for Hydra context
    When I send a "GET" request to "/contexts/Foo"
    Then the response status code should be 200
    And the header "Cache-Tags" should be equal to "_api/contexts/Foo"

  Scenario: Purge item on update
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/relation_embedders/1" with body:
    """
    {
      "paris": "France"
    }
    """
    Then the response status code should be 200
    And the header "Cache-Tags" should not exist
    And "/relation_embedders,/relation_embedders/1,/related_dummies/1" IRIs should be purged

  Scenario: Purge item and the related collection on update
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "DELETE" request to "/relation_embedders/1"
    Then the response status code should be 204
    And the header "Cache-Tags" should not exist
    And "/relation_embedders,/relation_embedders/1,/related_dummies/1" IRIs should be purged

  Scenario: Create two Relation2
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/relation2s" with body:
    """
    {
    }
    """
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/relation2s" with body:
    """
    {
    }
    """
    Then the response status code should be 201

  Scenario: Embedded collection must be listed in cache tags
    When I send a "GET" request to "/relation2s/1"
    Then the header "Cache-Tags" should be equal to "/relation2s/1"

  Scenario: Create a Relation1
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/relation1s" with body:
    """
    {
      "relation2": "/relation2s/1"
    }
    """
    Then the response status code should be 201
    And "/relation1s,/relation2s/1" IRIs should be purged

  @dropSchema
  Scenario: Update a Relation1
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/relation1s/1" with body:
    """
    {
      "relation2": "/relation2s/2"
    }
    """
    Then the response status code should be 200
    And "/relation1s,/relation1s/1,/relation2s/2,/relation2s/1" IRIs should be purged
