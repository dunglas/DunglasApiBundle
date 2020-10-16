Feature: JSON-LD DTO input and output
  In order to use a hypermedia API
  As a client software developer
  I need to be able to use DTOs on my resources as Input or Output objects.

  Background:
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"

  @createSchema
  Scenario: Create a resource with a custom Input
    When I send a "POST" request to "/dummy_dto_customs" with body:
    """
    {
      "foo": "test",
      "bar": 1
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/DummyDtoCustom",
      "@id": "/dummy_dto_customs/1",
      "@type": "DummyDtoCustom",
      "lorem": "test",
      "ipsum": "1",
      "id": 1
    }
    """

  @createSchema
  Scenario: Get an item with a custom output
    Given there is a DummyDtoCustom
    When I send a "GET" request to "/dummy_dto_custom_output/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": {
        "@vocab": "http://example.com/docs.jsonld#",
        "hydra": "http://www.w3.org/ns/hydra/core#",
        "foo": "CustomOutputDto/foo",
        "bar": "CustomOutputDto/bar"
      },
      "@type": "DummyDtoCustom",
      "@id": "/dummy_dto_customs/1",
      "foo": "test",
      "bar": 1
    }
    """

  @createSchema
  Scenario: Get a collection with a custom output
    Given there are 2 DummyDtoCustom
    When I send a "GET" request to "/dummy_dto_custom_output"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/DummyDtoCustom",
      "@id": "/dummy_dto_customs",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@type": "DummyDtoCustom",
          "@id": "/dummy_dto_customs/1",
          "foo": "test",
          "bar": 1
        },
        {
          "@type": "DummyDtoCustom",
          "@id": "/dummy_dto_customs/2",
          "foo": "test",
          "bar": 2
        }
      ],
      "hydra:totalItems": 2
    }
    """

  @createSchema
  Scenario: Get an item with same class as custom output
    Given there is a DummyDtoOutputSameClass
    When I send a "GET" request to "/dummy_dto_output_same_classes/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/DummyDtoOutputSameClass",
      "@id": "/dummy_dto_output_same_classes/1",
      "@type": "DummyDtoOutputSameClass",
      "lorem": "test",
      "ipsum": "modified",
      "id": 1
    }
    """

  @createSchema
  Scenario: Get an item with a data transformer that will return the original class as a fallback
    Given there is a DummyDtoOutputFallbackToSameClass
    When I send a "GET" request to "/dummy_dto_output_fallback_to_same_classes/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/DummyDtoOutputFallbackToSameClass",
      "@id": "/dummy_dto_output_fallback_to_same_classes/1",
      "@type": "DummyDtoOutputFallbackToSameClass",
      "lorem": "test",
      "ipsum": "modified",
      "id": 1
    }
    """

  @createSchema
  Scenario: Create a DummyDtoCustom object without output
    When I send a "POST" request to "/dummy_dto_custom_post_without_output" with body:
    """
    {
      "lorem": "test",
      "ipsum": "1"
    }
    """
    Then the response status code should be 204
    And the response should be empty

  @createSchema
  Scenario: Create and update a DummyInputOutput
    When I send a "POST" request to "/dummy_dto_input_outputs" with body:
    """
    {
      "foo": "test",
      "bar": 1
    }
    """
    Then the response status code should be 201
    And the JSON should be equal to:
    """
    {
      "@context": {
        "@vocab": "http://example.com/docs.jsonld#",
        "hydra": "http://www.w3.org/ns/hydra/core#",
        "id": "OutputDto/id",
        "baz": "OutputDto/baz",
        "bat": "OutputDto/bat",
        "relatedDummies": "OutputDto/relatedDummies"
      },
      "@type": "DummyDtoInputOutput",
      "@id": "/dummy_dto_input_outputs/1",
      "id": 1,
      "baz": 1,
      "bat": "test",
      "relatedDummies": []
    }
    """
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/dummy_dto_input_outputs/1" with body:
    """
    {
      "foo": "test",
      "bar": 2
    }
    """
    Then the response status code should be 200
    And the JSON should be equal to:
    """
    {
      "@context": {
        "@vocab": "http://example.com/docs.jsonld#",
        "hydra": "http://www.w3.org/ns/hydra/core#",
        "id": "OutputDto/id",
        "baz": "OutputDto/baz",
        "bat": "OutputDto/bat",
        "relatedDummies": "OutputDto/relatedDummies"
      },
      "@type": "DummyDtoInputOutput",
      "@id": "/dummy_dto_input_outputs/1",
      "id": 1,
      "baz": 2,
      "bat": "test",
      "relatedDummies": []
    }
    """

  @!mongodb
  @createSchema
  Scenario: Use DTO with relations on User
    When I send a "POST" request to "/users" with body:
    """
    {
      "username": "soyuka",
      "plainPassword": "a real password",
      "email": "soyuka@example.com"
    }
    """
    Then the response status code should be 201
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/users/recover/1" with body:
    """
    {
      "user": "/users/1"
    }
    """
    Then the response status code should be 200
    And the JSON should be equal to:
    """
    {
      "@context": {
        "@vocab": "http://example.com/docs.jsonld#",
        "hydra": "http://www.w3.org/ns/hydra/core#",
        "dummy": "RecoverPasswordOutput/dummy"
      },
      "@type": "User",
      "@id": "/users/1",
      "dummy": "/dummies/1"
    }
    """

  @createSchema
  Scenario: Create a resource with no input
    When I send a "POST" request to "/dummy_dto_no_inputs"
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": {
        "@vocab": "http://example.com/docs.jsonld#",
        "hydra": "http://www.w3.org/ns/hydra/core#",
        "id": "OutputDto/id",
        "baz": "OutputDto/baz",
        "bat": "OutputDto/bat",
        "relatedDummies": "OutputDto/relatedDummies"
      },
      "@type": "DummyDtoNoInput",
      "@id": "/dummy_dto_no_inputs/1",
      "id": 1,
      "baz": 1,
      "bat": "test",
      "relatedDummies": []
    }
    """

  Scenario: Update a resource with no input
    When I send a "POST" request to "/dummy_dto_no_inputs/1/double_bat"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": {
        "@vocab": "http://example.com/docs.jsonld#",
        "hydra": "http://www.w3.org/ns/hydra/core#",
        "id": "OutputDto/id",
        "baz": "OutputDto/baz",
        "bat": "OutputDto/bat",
        "relatedDummies": "OutputDto/relatedDummies"
      },
      "@type": "DummyDtoNoInput",
      "@id": "/dummy_dto_no_inputs/1",
      "id": 1,
      "baz": 1,
      "bat": "testtest",
      "relatedDummies": []
    }
    """

  @!mongodb
  Scenario: Use messenger with an input where the handler gives a synchronous result
    And I send a "POST" request to "/messenger_with_inputs" with body:
    """
    {
      "var": "test"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/MessengerWithInput",
      "@id": "/messenger_with_inputs/1",
      "@type": "MessengerWithInput",
      "id": 1,
      "name": "test"
    }
    """

  @!mongodb
  Scenario: Use messenger with an input where the handler gives a synchronous Response result
    When I send a "POST" request to "/messenger_with_responses" with body:
    """
    {
      "var": "test"
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON should be equal to:
    """
    {
      "data": 123
    }
    """

  @!mongodb
  Scenario: Get an item with a custom output
    When I send a "POST" request to "/dummy_dto_post_with_output_no_persist" with body:
    """
    {
      "lorem": "test",
      "ipsum": "1"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
