Feature: Get all translations for a resource if available
  In order to translate API resources
  As a client software developer
  The API should return fields with all their translations

  @createSchema
  Scenario: All translations of a resource can be retrieved
    Given there is a translatable dummy with its translations
    When I send a "GET" request to "/dummy_translatables?allTranslations=true"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:member[0].name.en" should be equal to "Dummy translated in English"
    And the JSON node "hydra:member[0].name.fr" should be equal to "Dummy traduit en français"
    And the JSON node "hydra:member[0].description.en" should be equal to "It's a dummy!"
    And the JSON node "hydra:member[0].description.fr" should be equal to "C'est un dummy !"
    And the JSON node "hydra:member[0].notTranslatedField" should be equal to "not translated"
