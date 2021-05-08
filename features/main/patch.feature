Feature: Sending PATCH requets
  As a client software developer
  I need to be able to send partial updates

  @createSchema
  Scenario: Detect accepted patch formats
    Given I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/patch_dummies" with body:
    """
    {"name": "Hello"}
    """
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/patch_dummies/1"
    Then the header "Accept-Patch" should be equal to "application/merge-patch+json, application/vnd.api+json"

  Scenario: Patch an item
    When I add "Content-Type" header equal to "application/merge-patch+json"
    And I send a "PATCH" request to "/patch_dummies/1" with body:
    """
    {"name": "Patched"}
    """
    Then the JSON node "name" should contain "Patched"

  Scenario: Remove a property according to RFC 7386
    When I add "Content-Type" header equal to "application/merge-patch+json"
    And I send a "PATCH" request to "/patch_dummies/1" with body:
    """
    {"name": null}
    """
    Then the JSON node "name" should not exist

  @createSchema
  Scenario: Patch 1:1 relation
    Given there is a PatchDummyRelation
    When I add "Content-Type" header equal to "application/merge-patch+json"
    And I send a "PATCH" request to "/patch_dummy_relations/1" with body:
    """
    {
      "related": {
        "symfony": "A new name"
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/PatchDummyRelation",
      "@id": "/patch_dummy_relations/1",
      "@type": "PatchDummyRelation",
      "related": {
        "@id": "/related_dummies/1",
        "@type": "https://schema.org/Product",
        "id": 1,
        "symfony": "A new name"
      }
    }
    """

  @createSchema
  Scenario: Patch 1:n relation
    Given there is a PatchDummyRelation
    When I add "Content-Type" header equal to "application/merge-patch+json"
    And I send a "PATCH" request to "/patch_dummy_relations/1" with body:
    """
    {
      "relatedDummies": [
        {
          "@id": "/related_dummies/2",
          "symfony": "A new name"
        }
      ]
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/PatchDummyRelation",
      "@id": "/patch_dummy_relations/1",
      "@type": "PatchDummyRelation",
      "relatedDummies": [
        {
          "@id": "/related_dummies/1",
          "@type": "https://schema.org/Product",
          "id": 1,
          "symfony": "symfony"
        },
        {
          "@id": "/related_dummies/2",
          "@type": "https://schema.org/Product",
          "id": 2,
          "symfony": "A new name"
        }
      ]
    }
    """
