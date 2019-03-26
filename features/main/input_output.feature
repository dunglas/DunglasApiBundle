Feature: DTO input and output
  In order to use a hypermedia API
  As a client software developer
  I need to be able to use DTOs on my resources as Input or Output objects.

  @createSchema
  Scenario: Create a resource with a custom Input
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_dto_customs" with body:
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
          "@id": "/dummy_dto_customs/1",
          "foo": "test",
          "bar": 1
        },
        {
          "@id": "/dummy_dto_customs/2",
          "foo": "test",
          "bar": 2
        }
      ],
      "hydra:totalItems": 2
    }
    """

  @createSchema
  Scenario: Create a DummyDtoCustom object without output
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_dto_custom_post_without_output" with body:
    """
    {
      "lorem": "test",
      "ipsum": "1"
    }
    """
    Then the response status code should be 201
    And the response should be empty

  @createSchema
  Scenario: Create and update a DummyInputOutput
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_dto_input_outputs" with body:
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
        "bat": "OutputDto/bat"
      },
      "@type": "DummyDtoInputOutput",
      "@id": "/dummy_dto_input_outputs/1",
      "id": 1,
      "baz": 1,
      "bat": "test"
    }
    """
    When I add "Content-Type" header equal to "application/ld+json"
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
        "bat": "OutputDto/bat"
      },
      "@type": "DummyDtoInputOutput",
      "@id": "/dummy_dto_input_outputs/1",
      "id": 1,
      "baz": 2,
      "bat": "test"
    }
    """

  @createSchema
  Scenario: Create a resource with custom input with nested dto
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_dto_customs" with body:
    """
    {
      "foo": "test",
      "bar": 1,
      "nested": {
        "baz": "baz-nested"
      }
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

  @!mongodb
  @createSchema
  Scenario: Use DTO with relations on User
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/users" with body:
    """
    {
      "username": "soyuka",
      "plainPassword": "a real password",
      "email": "soyuka@example.com"
    }
    """
    Then the response status code should be 201
    When I add "Content-Type" header equal to "application/ld+json"
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
  Scenario: Retrieve an Output with GraphQl
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_dto_input_outputs" with body:
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
        "bat": "OutputDto/bat"
      },
      "@type": "DummyDtoInputOutput",
      "@id": "/dummy_dto_input_outputs/1",
      "id": 1,
      "baz": 1,
      "bat": "test"
    }
    """
    When I send the following GraphQL request:
    """
    {
      dummyDtoInputOutput(id: "/dummy_dto_input_outputs/1") {
        _id, id, baz
      }
    }
    """
    Then the response status code should be 200
    And the JSON should be equal to:
    """
    {
      "data": {
        "dummyDtoInputOutput": {
          "_id": 1,
          "id": "/dummy_dto_input_outputs/1",
          "baz": 1
        }
      }
    }
    """

  @createSchema
  Scenario: Create a resource with no input
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_dto_no_inputs" with body:
    """
    {
      "foo": "test",
      "bar": 1
    }
    """
    Then the response status code should be 201
    And the response should be empty

  @!mongodb
  Scenario: Use messenger with an input where the handler gives a synchronous result
    When I add "Content-Type" header equal to "application/ld+json"
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
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/messenger_with_responses" with body:
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
  Scenario: Use messenger with graphql and an input where the handler gives a synchronous result
    When I send the following GraphQL request:
    """
    mutation {
      createMessengerWithInput(input: {var: "test"}) {
        messengerWithInput { id, name }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "data": {
        "createMessengerWithInput": {
          "messengerWithInput": {
              "id": "/messenger_with_inputs/1",
              "name": "test"
          }
        }
      }
    }
    """
