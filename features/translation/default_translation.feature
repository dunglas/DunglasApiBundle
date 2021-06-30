Feature: Use default translation for a resource if available
  In order to translate API resources
  As a client software developer
  The API should return translated fields for a given locale

  @createSchema
  Scenario: A resource is translated for a given locale
    Given there is a translatable dummy with its translations
    When I send a "GET" request to "/dummy_translatables"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:member[0].name" should be equal to "Dummy translated in English"
    And the JSON node "hydra:member[0].description" should be equal to "It's a dummy!"
    When I send a "GET" request to "/dummy_translatables/fr"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:member[0].name" should be equal to "Dummy traduit en français"
    And the JSON node "hydra:member[0].description" should be equal to "C'est un dummy !"
    And the JSON node "hydra:member[0].notTranslatedField" should be equal to "not translated"

  Scenario: A translation can be updated for a resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/dummy_translatables/1/fr" with body:
    """
    {
      "name": "Dummy mieux traduit en français",
      "notTranslatedField": "really not translated"
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "name" should be equal to "Dummy mieux traduit en français"
    And the JSON node "description" should be equal to "C'est un dummy !"
    And the JSON node "notTranslatedField" should be equal to "really not translated"
    When I send a "GET" request to "/dummy_translatables/fr"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:member[0].name" should be equal to "Dummy mieux traduit en français"
    And the JSON node "hydra:member[0].description" should be equal to "C'est un dummy !"
    And the JSON node "hydra:member[0].notTranslatedField" should be equal to "really not translated"

  Scenario: A translation can be added to a resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/dummy_translatables/1/es" with body:
    """
    {
      "name": "Dummy traducido al español",
      "description": "¡Es un dummy!",
      "notTranslatedField": "truly not translated"
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "name" should be equal to "Dummy traducido al español"
    And the JSON node "description" should be equal to "¡Es un dummy!"
    And the JSON node "notTranslatedField" should be equal to "truly not translated"
    When I send a "GET" request to "/dummy_translatables/es"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:member[0].name" should be equal to "Dummy traducido al español"
    And the JSON node "hydra:member[0].description" should be equal to "¡Es un dummy!"
    And the JSON node "hydra:member[0].notTranslatedField" should be equal to "truly not translated"

  Scenario: A resource can be created with its translation
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_translatables/fr" with body:
    """
    {
      "name": "Autre Dummy traduit en français",
      "description": "C'est un autre dummy.",
      "notTranslatedField": "n/a"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "name" should be equal to "Autre Dummy traduit en français"
    And the JSON node "description" should be equal to "C'est un autre dummy."
    And the JSON node "notTranslatedField" should be equal to "n/a"
    When I send a "GET" request to "/dummy_translatables/fr"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:member[1].name" should be equal to "Autre Dummy traduit en français"
    And the JSON node "hydra:member[1].description" should be equal to "C'est un autre dummy."
    And the JSON node "hydra:member[1].notTranslatedField" should be equal to "n/a"
