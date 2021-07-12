Feature: JSON-LD non-resource handling
  In order to use non-resource types
  As a developer
  I should be able to serialize types not mapped to an API resource.

  Background:
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"

  Scenario: Get a resource containing a raw object
    When I send a "GET" request to "/contain_non_resources/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/ContainNonResource",
      "@id": "/contain_non_resources/1",
      "@type": "ContainNonResource",
      "id": 1,
      "nested": {
        "@id": "/contain_non_resources/1-nested",
        "@type": "ContainNonResource",
        "id": "1-nested",
        "nested": null,
        "notAResource": {
          "@type": "NotAResource",
          "@id": "_:7e075feb68a66b8e398bd5ec1ac0bf90",
          "foo": "f2",
          "bar": "b2"
        }
      },
      "notAResource": {
        "@type": "NotAResource",
        "@id": "_:82a2e6df651246c369ba0176220d8102",
        "foo": "f1",
        "bar": "b1"
      }
    }
    """

  @!mongodb
  @createSchema
  Scenario: Create a resource that has a non-resource relation.
    When I send a "POST" request to "/non_relation_resources" with body:
    """
    {
      "relation": {
        "foo": "test"
      }
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be a superset of:
    """
    {
      "@context": "/contexts/NonRelationResource",
      "@id": "/non_relation_resources/1",
      "@type": "NonRelationResource",
      "relation": {
        "@type": "NonResourceClass",
        "foo": "test"
      },
      "id": 1
    }
    """

  @!mongodb
  @createSchema
  Scenario: Create a resource that contains a stdClass object.
    When I send a "POST" request to "/plain_object_dummies" with body:
    """
    {
      "content": "{\"fields\":{\"title\":{\"value\":\"\"},\"images\":[{\"id\":0,\"categoryId\":0,\"uri\":\"/api/pictures\",\"resource\":\"{}\",\"description\":\"\",\"alt\":\"\",\"type\":\"picture\",\"text\":\"\",\"src\":\"\"}],\"alternativeAudio\":{},\"caption\":\"\"},\"showCaption\":false,\"alternativeContent\":false,\"alternativeAudioContent\":false,\"blockLayout\":\"default\"}"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be a superset of:
    """
    {
      "@context": "/contexts/PlainObjectDummy",
      "@id": "/plain_object_dummies/1",
      "@type": "PlainObjectDummy",
      "data": {
        "fields": [],
        "showCaption": false,
        "alternativeContent": false,
        "alternativeAudioContent": false,
        "blockLayout": "default"
      },
      "id": 1
    }
    """
