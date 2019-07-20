Feature: GraphQL mutation support
  @createSchema
  Scenario: Introspect types
    When I send the following GraphQL request:
    """
    {
      __type(name: "Mutation") {
        fields {
          name
          description
          type {
            name
            kind
          }
          args {
            name
            type {
              name
              kind
              ofType {
                name
                kind
              }
            }
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.__type.fields[0].name" should contain "delete"
    And the JSON node "data.__type.fields[0].description" should match '/^Deletes a [A-z0-9]+.$/'
    And the JSON node "data.__type.fields[0].type.name" should match "/^delete[A-z0-9]+Payload$/"
    And the JSON node "data.__type.fields[0].type.kind" should be equal to "OBJECT"
    And the JSON node "data.__type.fields[0].args[0].name" should be equal to "input"
    And the JSON node "data.__type.fields[0].args[0].type.kind" should be equal to "NON_NULL"
    And the JSON node "data.__type.fields[0].args[0].type.ofType.name" should match "/^delete[A-z0-9]+Input$/"
    And the JSON node "data.__type.fields[0].args[0].type.ofType.kind" should be equal to "INPUT_OBJECT"

  Scenario: Create an item
    When I send the following GraphQL request:
    """
    mutation {
      createFoo(input: {name: "A new one", bar: "new", clientMutationId: "myId"}) {
        foo {
          id
          _id
          __typename
          name
          bar
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.createFoo.foo.id" should be equal to "/foos/1"
    And the JSON node "data.createFoo.foo._id" should be equal to 1
    And the JSON node "data.createFoo.foo.__typename" should be equal to "Foo"
    And the JSON node "data.createFoo.foo.name" should be equal to "A new one"
    And the JSON node "data.createFoo.foo.bar" should be equal to "new"
    And the JSON node "data.createFoo.clientMutationId" should be equal to "myId"

  Scenario: Create an item without a clientMutationId
    When I send the following GraphQL request:
    """
    mutation {
      createFoo(input: {name: "Created without mutation id", bar: "works"}) {
        foo {
          id
          name
          bar
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.createFoo.foo.id" should be equal to "/foos/2"
    And the JSON node "data.createFoo.foo.name" should be equal to "Created without mutation id"
    And the JSON node "data.createFoo.foo.bar" should be equal to "works"

  Scenario: Create an item with a subresource
    Given there are 1 dummy objects with relatedDummy
    When I send the following GraphQL request:
    """
    mutation {
      createDummy(input: {name: "A dummy", foo: [], relatedDummy: "/related_dummies/1", clientMutationId: "myId"}) {
        dummy {
          id
          name
          foo
          relatedDummy {
            name
            __typename
          }
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.createDummy.dummy.id" should be equal to "/dummies/2"
    And the JSON node "data.createDummy.dummy.name" should be equal to "A dummy"
    And the JSON node "data.createDummy.dummy.foo" should have 0 elements
    And the JSON node "data.createDummy.dummy.relatedDummy.name" should be equal to "RelatedDummy #1"
    And the JSON node "data.createDummy.dummy.relatedDummy.__typename" should be equal to "RelatedDummy"
    And the JSON node "data.createDummy.clientMutationId" should be equal to "myId"

  Scenario: Create an item with an iterable field
    When I send the following GraphQL request:
    """
    mutation {
      createDummy(input: {name: "A dummy", foo: [], jsonData: {bar:{baz:3,qux:[7.6,false,null]}}, arrayData: ["bar", "baz"], clientMutationId: "myId"}) {
        dummy {
          id
          name
          foo
          jsonData
          arrayData
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.createDummy.dummy.id" should be equal to "/dummies/3"
    And the JSON node "data.createDummy.dummy.name" should be equal to "A dummy"
    And the JSON node "data.createDummy.dummy.foo" should have 0 elements
    And the JSON node "data.createDummy.dummy.jsonData.bar.baz" should be equal to the number 3
    And the JSON node "data.createDummy.dummy.jsonData.bar.qux[0]" should be equal to the number 7.6
    And the JSON node "data.createDummy.dummy.jsonData.bar.qux[1]" should be false
    And the JSON node "data.createDummy.dummy.jsonData.bar.qux[2]" should be null
    And the JSON node "data.createDummy.dummy.arrayData[1]" should be equal to baz
    And the JSON node "data.createDummy.clientMutationId" should be equal to "myId"

  Scenario: Delete an item through a mutation
    When I send the following GraphQL request:
    """
    mutation {
      deleteFoo(input: {id: "/foos/1", clientMutationId: "anotherId"}) {
        foo {
          id
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.deleteFoo.foo.id" should be equal to "/foos/1"
    And the JSON node "data.deleteFoo.clientMutationId" should be equal to "anotherId"

  Scenario: Trigger an error trying to delete item of different resource
    When I send the following GraphQL request:
    """
    mutation {
      deleteFoo(input: {id: "/dummies/1", clientMutationId: "myId"}) {
        foo {
          id
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "errors[0].message" should be equal to 'Item "/dummies/1" did not match expected type "Foo".'

  @!mongodb
  Scenario: Delete an item with composite identifiers through a mutation
    Given there are Composite identifier objects
    When I send the following GraphQL request:
    """
    mutation {
      deleteCompositeRelation(input: {id: "/composite_relations/compositeItem=1;compositeLabel=1", clientMutationId: "myId"}) {
        compositeRelation {
          id
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.deleteCompositeRelation.compositeRelation.id" should be equal to "/composite_relations/compositeItem=1;compositeLabel=1"
    And the JSON node "data.deleteCompositeRelation.clientMutationId" should be equal to "myId"

  @createSchema
  Scenario: Modify an item through a mutation
    Given there are 1 dummy objects having each 2 relatedDummies
    When I send the following GraphQL request:
    """
    mutation {
      updateDummy(input: {id: "/dummies/1", description: "Modified description.", dummyDate: "2018-06-05T00:00:00+00:00", clientMutationId: "myId"}) {
        dummy {
          id
          name
          description
          dummyDate
          relatedDummies {
            edges {
              node {
                name
              }
            }
          }
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.updateDummy.dummy.id" should be equal to "/dummies/1"
    And the JSON node "data.updateDummy.dummy.name" should be equal to "Dummy #1"
    And the JSON node "data.updateDummy.dummy.description" should be equal to "Modified description."
    And the JSON node "data.updateDummy.dummy.dummyDate" should be equal to "2018-06-05"
    And the JSON node "data.updateDummy.dummy.relatedDummies.edges[0].node.name" should be equal to "RelatedDummy11"
    And the JSON node "data.updateDummy.clientMutationId" should be equal to "myId"

  @!mongodb
  Scenario: Modify an item with composite identifiers through a mutation
    Given there are Composite identifier objects
    When I send the following GraphQL request:
    """
    mutation {
      updateCompositeRelation(input: {id: "/composite_relations/compositeItem=1;compositeLabel=2", value: "Modified value.", clientMutationId: "myId"}) {
        compositeRelation {
          id
          value
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.updateCompositeRelation.compositeRelation.id" should be equal to "/composite_relations/compositeItem=1;compositeLabel=2"
    And the JSON node "data.updateCompositeRelation.compositeRelation.value" should be equal to "Modified value."
    And the JSON node "data.updateCompositeRelation.clientMutationId" should be equal to "myId"

  Scenario: Create an item with a custom UUID
    When I send the following GraphQL request:
    """
    mutation {
      createWritableId(input: {_id: "c6b722fe-0331-48c4-a214-f81f9f1ca082", name: "Foo", clientMutationId: "m"}) {
        writableId {
          id
          _id
          name
        }
        clientMutationId
      }
    }
    """
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.createWritableId.writableId.id" should be equal to "/writable_ids/c6b722fe-0331-48c4-a214-f81f9f1ca082"
    And the JSON node "data.createWritableId.writableId._id" should be equal to "c6b722fe-0331-48c4-a214-f81f9f1ca082"
    And the JSON node "data.createWritableId.writableId.name" should be equal to "Foo"
    And the JSON node "data.createWritableId.clientMutationId" should be equal to "m"

  @!mongodb
  Scenario: Update an item with a custom UUID
    When I send the following GraphQL request:
    """
    mutation {
      updateWritableId(input: {id: "/writable_ids/c6b722fe-0331-48c4-a214-f81f9f1ca082", _id: "f8a708b2-310f-416c-9aef-b1b5719dfa47", name: "Foo", clientMutationId: "m"}) {
        writableId {
          id
          _id
          name
        }
        clientMutationId
      }
    }
    """
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.updateWritableId.writableId.id" should be equal to "/writable_ids/f8a708b2-310f-416c-9aef-b1b5719dfa47"
    And the JSON node "data.updateWritableId.writableId._id" should be equal to "f8a708b2-310f-416c-9aef-b1b5719dfa47"
    And the JSON node "data.updateWritableId.writableId.name" should be equal to "Foo"
    And the JSON node "data.updateWritableId.clientMutationId" should be equal to "m"

  Scenario: Use serialization groups
    Given there are 1 dummy group objects
    When I send the following GraphQL request:
    """
    mutation {
      createDummyGroup(input: {bar: "Bar", baz: "Baz", clientMutationId: "myId"}) {
        dummyGroup {
          id
          bar
          __typename
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.createDummyGroup.dummyGroup.id" should be equal to "/dummy_groups/2"
    And the JSON node "data.createDummyGroup.dummyGroup.bar" should be equal to "Bar"
    And the JSON node "data.createDummyGroup.dummyGroup.__typename" should be equal to "createDummyGroupPayloadData"
    And the JSON node "data.createDummyGroup.clientMutationId" should be equal to "myId"

  Scenario: Trigger a validation error
    When I send the following GraphQL request:
    """
    mutation {
      createDummy(input: {name: "", foo: [], clientMutationId: "myId"}) {
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "errors[0].message" should be equal to "name: This value should not be blank."

  Scenario: Create an item using custom inputClass & disabled outputClass
    Given there are 2 dummyDtoNoOutput objects
    When I send the following GraphQL request:
    """
    mutation {
      createDummyDtoNoOutput(input: {foo: "A new one", bar: 3, clientMutationId: "myId"}) {
        dummyDtoNoOutput {
          id
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON should be equal to:
    """
    {
      "errors": [
        {
          "message": "Cannot query field \"id\" on type \"DummyDtoNoOutput\".",
          "extensions": {
            "category": "graphql"
          },
          "locations": [
            {
              "line": 4,
              "column": 7
            }
          ]
        }
      ]
    }
    """

  Scenario: Cannot create an item with input fields using disabled inputClass
    When I send the following GraphQL request:
    """
    mutation {
      createDummyDtoNoInput(input: {lorem: "A new one", ipsum: 3, clientMutationId: "myId"}) {
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON should be equal to:
    """
    {
      "errors": [
        {
          "message": "Field \"lorem\" is not defined by type createDummyDtoNoInputInput.",
          "extensions": {
            "category": "graphql"
          },
          "locations": [
            {
              "line": 2,
              "column": 33
            }
          ]
        },
        {
          "message": "Field \"ipsum\" is not defined by type createDummyDtoNoInputInput.",
          "extensions": {
            "category": "graphql"
          },
          "locations": [
            {
              "line": 2,
              "column": 53
            }
          ]
        }
      ]
    }
    """

  Scenario: Create an item with empty input fields using disabled inputClass (no persist done)
    When I send the following GraphQL request:
    """
    mutation {
      createDummyDtoNoInput(input: {clientMutationId: "myId"}) {
        dummyDtoNoInput {
          id
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON should be equal to:
    """
    {
      "data": {
        "createDummyDtoNoInput": {
          "dummyDtoNoInput": null,
          "clientMutationId": "myId"
        }
      }
    }
    """

  Scenario: Execute a custom mutation
    Given there are 1 dummyCustomMutation objects
    When I send the following GraphQL request:
    """
    mutation {
      sumDummyCustomMutation(input: {id: "/dummy_custom_mutations/1", operandB: 5}) {
        dummyCustomMutation {
          id
          result
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.sumDummyCustomMutation.dummyCustomMutation.result" should be equal to "8"

  Scenario: Execute a custom mutation without ID
    When I send the following GraphQL request:
    """
    mutation {
      sumCreateDummyCustomMutation(input: {operandA: 4, operandB: 5}) {
        dummyCustomMutation {
          id
          result
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.sumCreateDummyCustomMutation.dummyCustomMutation.id" should be equal to "/dummy_custom_mutations/2"
    And the JSON node "data.sumCreateDummyCustomMutation.dummyCustomMutation.result" should be equal to "9"

  Scenario: Execute a not persisted custom mutation
    When I send the following GraphQL request:
    """
    mutation {
      sumNotPersistedDummyCustomMutation(input: {id: "/dummy_custom_mutations/1", operandB: 5}) {
        dummyCustomMutation {
          id
          result
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.sumNotPersistedDummyCustomMutation.dummyCustomMutation" should be null

  Scenario: Execute a custom mutation with custom arguments
    When I send the following GraphQL request:
    """
    mutation {
      testCustomArgumentsDummyCustomMutation(input: {operandC: 18, clientMutationId: "myId"}) {
        dummyCustomMutation {
          result
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.testCustomArgumentsDummyCustomMutation.dummyCustomMutation.result" should be equal to "18"
    And the JSON node "data.testCustomArgumentsDummyCustomMutation.clientMutationId" should be equal to "myId"
