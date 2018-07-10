Feature: Collections filtering
  In order to retrieve subsets of collections
  As an API consumer
  I need to be able to set filters

  @createSchema
  Scenario: Retrieve a collection ordered using the order filter
    Given there are 3 dummy objects with dummyDate
    When I send the following GraphQL request:
    """
    {
      dummies(order: {id: DESC}) {
        edges {
          node {
            id
          }
        }
      }
    }
    """
    Then the JSON node "data.dummies.edges" should have 3 elements
    And the JSON node "data.dummies.edges[0].node.id" should be equal to "/dummies/3"

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
      dummies(relatedDummy: {exists: true}) {
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
      dummies(dummyDate: {after: "2015-04-02"}) {
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
    And the JSON node "data.dummies.edges[0].node.dummyDate" should be equal to "2015-04-02T00:00:00+00:00"

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
  Scenario: Retrieve a collection filtered using the search filter
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
    And the JSON node "data.dummies.edges[0].node.relatedDummies.edges" should have 0 elements
    And the JSON node "data.dummies.edges[1].node.relatedDummies.edges" should have 0 elements
    And the JSON node "data.dummies.edges[2].node.relatedDummies.edges" should have 1 element
    And the JSON node "data.dummies.edges[2].node.relatedDummies.edges[0].node.name" should be equal to "RelatedDummy13"

  @createSchema
  Scenario: Retrieve a collection filtered using the related search filter
    Given there are 1 dummy objects having each 2 relatedDummies
    And there are 1 dummy objects having each 3 relatedDummies
    When I send the following GraphQL request:
    """
    {
      dummies(relatedDummies_name: "RelatedDummy31") {
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
      dummies(order: {relatedDummy_name: "DESC"}) {
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
