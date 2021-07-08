Feature: Collections filtering
  In order to retrieve subsets of collections
  As an API consumer
  I need to be able to set filters

  @createSchema
  Scenario: Retrieve a collection filtered using the boolean filter
    Given there is 1 dummy object with dummyBoolean true
    And there is 1 dummy object with dummyBoolean false
    When I send the following GraphQL request:
    """
    {
      dummies(dummyBoolean: false) {
        edges {
          node {
            id
            dummyBoolean
          }
        }
      }
    }
    """
    Then the JSON node "data.dummies.edges" should have 1 element
    And the JSON node "data.dummies.edges[0].node.dummyBoolean" should be false

  @createSchema
  Scenario: Retrieve a collection filtered using the exists filter
    Given there are 3 dummy objects
    And there are 2 dummy objects with relatedDummy
    When I send the following GraphQL request:
    """
    {
      dummies(exists: [{relatedDummy: true}]) {
        edges {
          node {
            id
            relatedDummy {
              name
            }
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the JSON node "data.dummies.edges" should have 2 elements
    And the JSON node "data.dummies.edges[0].node.relatedDummy" should have 1 element

  @createSchema
  Scenario: Retrieve a collection filtered using the date filter
    Given there are 3 dummy objects with dummyDate
    When I send the following GraphQL request:
    """
    {
      dummies(dummyDate: [{after: "2015-04-02"}]) {
        edges {
          node {
            id
            dummyDate
          }
        }
      }
    }
    """
    Then the JSON node "data.dummies.edges" should have 1 element
    And the JSON node "data.dummies.edges[0].node.dummyDate" should be equal to "2015-04-02"

  @createSchema
  Scenario: Retrieve a collection filtered using the search filter
    Given there are 10 dummy objects
    When I send the following GraphQL request:
    """
    {
      dummies(name: "#2") {
        edges {
          node {
            id
            name
          }
        }
      }
    }
    """
    Then the JSON node "data.dummies.edges" should have 1 element
    And the JSON node "data.dummies.edges[0].node.id" should be equal to "/dummies/2"

  @createSchema
  Scenario: Retrieve a collection filtered using the search filter with an int
    Given there are 4 dummy objects having each 3 relatedDummies
    When I send the following GraphQL request:
    """
    {
      dummies(name: "Dummy #1") {
        totalCount
        edges {
          node {
            name
            relatedDummies(age: 31) {
              totalCount
              edges {
                node {
                  id
                  name
                  age
                }
              }
            }
          }
        }
      }
    }
    """
    Then the JSON node "data.dummies.totalCount" should be equal to 1
    And the JSON node "data.dummies.edges[0].node.relatedDummies.totalCount" should be equal to 1
    And the JSON node "data.dummies.edges[0].node.relatedDummies.edges[0].node.age" should be equal to "31"

  @createSchema
  Scenario: Retrieve a collection filtered using the search filter and a name converter
    Given there are 10 dummy objects
    When I send the following GraphQL request:
    """
    {
      dummies(name_converted: "Converted 2") {
        edges {
          node {
            id
            name
            name_converted
          }
        }
      }
    }
    """
    Then the JSON node "data.dummies.edges" should have 1 element
    And the JSON node "data.dummies.edges[0].node.id" should be equal to "/dummies/2"
    And the JSON node "data.dummies.edges[0].node.name_converted" should be equal to "Converted 2"

  @createSchema
  Scenario: Retrieve a collection filtered using the search filter and a name converter
    Given there are 20 convertedOwner objects with convertedRelated
    When I send the following GraphQL request:
    """
    {
      convertedOwners(name_converted__name_converted: "Converted 2") {
        edges {
          node {
            id
            name_converted {
              name_converted
            }
          }
        }
      }
    }
    """
    Then the JSON node "data.convertedOwners.edges" should have 2 element
    And the JSON node "data.convertedOwners.edges[0].node.id" should be equal to "/converted_owners/2"
    And the JSON node "data.convertedOwners.edges[0].node.name_converted.name_converted" should be equal to "Converted 2"
    And the JSON node "data.convertedOwners.edges[1].node.id" should be equal to "/converted_owners/20"
    And the JSON node "data.convertedOwners.edges[1].node.name_converted.name_converted" should be equal to "Converted 20"

  @createSchema
  Scenario: Retrieve a nested collection filtered using the search filter
    Given there are 3 dummy objects having each 3 relatedDummies
    When I send the following GraphQL request:
    """
    {
      dummies {
        edges {
          node {
            id
            relatedDummies(name: "RelatedDummy13") {
              edges {
                node {
                  id
                  name
                }
              }
            }
          }
        }
      }
    }
    """
    Then the JSON node "data.dummies.edges[0].node.relatedDummies.edges" should have 0 elements
    And the JSON node "data.dummies.edges[1].node.relatedDummies.edges" should have 0 elements
    And the JSON node "data.dummies.edges[2].node.relatedDummies.edges" should have 1 element
    And the JSON node "data.dummies.edges[2].node.relatedDummies.edges[0].node.name" should be equal to "RelatedDummy13"

  @createSchema
  Scenario: Use a filter of a nested collection
    Given there is a DummyCar entity with related colors
    When I send the following GraphQL request:
    """
    {
      dummyCar(id: "/dummy_cars/1") {
        id
        colors(prop: "blue") {
          edges {
            node {
              id
              prop
            }
          }
        }
      }
    }
    """
    Then the JSON node "data.dummyCar.couleurs.edges" should have 1 element
    And the JSON node "data.dummyCar.couleurs.edges[0].node.prop" should be equal to "blue"

  @createSchema
  Scenario: Retrieve a collection filtered using the related search filter
    Given there are 1 dummy objects having each 2 relatedDummies
    And there are 1 dummy objects having each 3 relatedDummies
    When I send the following GraphQL request:
    """
    {
      dummies(relatedDummies__name: "RelatedDummy31") {
        edges {
          node {
            id
          }
        }
      }
    }
    """
    And the response status code should be 200
    And the JSON node "data.dummies.edges" should have 1 element

  @createSchema
  Scenario: Retrieve a collection ordered using nested properties
    Given there are 2 dummy objects with relatedDummy
    When I send the following GraphQL request:
    """
    {
      dummies(order: [{relatedDummy__name: "DESC"}]) {
        edges {
          node {
            name
            relatedDummy {
              id
              name
            }
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummies.edges[0].node.name" should be equal to "Dummy #2"
    And the JSON node "data.dummies.edges[1].node.name" should be equal to "Dummy #1"

  @createSchema
  Scenario: Retrieve a collection ordered correctly given the order of the argument
    Given there are dummies with similar properties
    When I send the following GraphQL request:
    """
    {
      dummies(order: [{description: "ASC"}, {name: "ASC"}]) {
        edges {
          node {
            id
            name
            description
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummies.edges[0].node.name" should be equal to "baz"
    And the JSON node "data.dummies.edges[0].node.description" should be equal to "bar"
    And the JSON node "data.dummies.edges[1].node.name" should be equal to "foo"
    And the JSON node "data.dummies.edges[1].node.description" should be equal to "bar"

  @createSchema
  Scenario: Retrieve a collection filtered using the related search filter with two values and exact strategy
    Given there are 3 dummy objects with relatedDummy
    When  I send the following GraphQL request:
    """
    {
      dummies(relatedDummy__name_list: ["RelatedDummy #1", "RelatedDummy #2"]) {
        edges {
          node {
            id
            name
            relatedDummy {
              name
            }
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummies.edges" should have 2 element
    And the JSON node "data.dummies.edges[0].node.relatedDummy.name" should be equal to "RelatedDummy #1"
    And the JSON node "data.dummies.edges[1].node.relatedDummy.name" should be equal to "RelatedDummy #2"
