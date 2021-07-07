Feature: Using custom parent identifier for subresources
  In order to use an hypermedia API
  As a client software developer
  I need to be able to use custom identifiers and query subresources

  @createSchema
  Scenario: Create a parent dummy
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/slug_parent_dummies" with body:
    """
    {
      "slug": "parent-dummy"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/SlugParentDummy",
      "@id": "/slug_parent_dummies/parent-dummy",
      "@type": "SlugParentDummy",
      "id": 1,
      "slug": "parent-dummy",
      "childDummies": []
    }
    """

  Scenario: Create a child dummy
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/slug_child_dummies" with body:
    """
    {
      "slug": "child-dummy",
      "parentDummy": "/slug_parent_dummies/parent-dummy"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/SlugChildDummy",
      "@id": "/slug_child_dummies/child-dummy",
      "@type": "SlugChildDummy",
      "id": 1,
      "slug": "child-dummy",
      "parentDummy": "/slug_parent_dummies/parent-dummy"
    }
    """

  Scenario: Get child dummies of parent dummy
    When I send a "GET" request to "/slug_parent_dummies/parent-dummy/child_dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/SlugChildDummy",
      "@id": "/slug_parent_dummies/parent-dummy/child_dummies",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/slug_child_dummies/child-dummy",
          "@type": "SlugChildDummy",
          "id": 1,
          "slug": "child-dummy",
          "parentDummy": "/slug_parent_dummies/parent-dummy"
        }
      ],
      "hydra:totalItems": 1
    }
    """

  Scenario: Get parent dummy of child dummy
    When I send a "GET" request to "/slug_child_dummies/child-dummy/parent_dummy"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/SlugParentDummy",
      "@id": "/slug_child_dummies/child-dummy/parent_dummy",
      "@type": "SlugParentDummy",
      "id": 1,
      "slug": "parent-dummy",
      "childDummies": [
          "/slug_child_dummies/child-dummy"
      ]
    }
    """
