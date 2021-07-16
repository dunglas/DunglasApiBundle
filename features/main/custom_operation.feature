Feature: Custom operation
  As a client software developer
  I need to be able to create custom operations

  Background:
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"

  # @createSchema
  # Scenario: Custom normalization operation
  #   When I send a "POST" request to "/custom/denormalization"
  #   Then the JSON should be equal to:
  #   """
  #   {
  #       "@context": "/contexts/CustomActionDummy",
  #       "@id": "/custom_action_dummies/1",
  #       "@type": "CustomActionDummy",
  #       "id": 1,
  #       "foo": "custom!"
  #   }
  #   """
  #
  # Scenario: Custom normalization operation
  #   When I send a "GET" request to "/custom/1/normalization"
  #   Then the JSON should be equal to:
  #   """
  #   {
  #       "id": 1,
  #       "foo": "foo"
  #   }
  #   """
  #
  # Scenario: Custom normalization operation with shorthand configuration
  #   When I send a "POST" request to "/short_custom/denormalization"
  #   Then the JSON should be equal to:
  #   """
  #   {
  #       "@context": "/contexts/CustomActionDummy",
  #       "@id": "/custom_action_dummies/2",
  #       "@type": "CustomActionDummy",
  #       "id": 2,
  #       "foo": "short declaration"
  #   }
  #   """
  #
  # Scenario: Custom normalization operation with shorthand configuration
  #   When I send a "GET" request to "/short_custom/2/normalization"
  #   Then the JSON should be equal to:
  #   """
  #   {
  #       "id": 2,
  #       "foo": "short"
  #   }
  #   """
  #
  # Scenario: Custom collection name without specific route
  #   When I send a "GET" request to "/custom_action_collection_dummies"
  #   Then the response status code should be 200
  #   Then the JSON node "hydra:member" should have 2 elements
  #
  # Scenario: Custom operation name without specific route
  #   When I send a "GET" request to "/custom_action_collection_dummies/1"
  #   Then the JSON should be equal to:
  #   """
  #   {
  #       "@context": "/contexts/CustomActionDummy",
  #       "@id": "/custom_action_dummies/1",
  #       "@type": "CustomActionDummy",
  #       "id": 1,
  #       "foo": "custom!"
  #   }
  #   """

  @createSchema
  Scenario: Create a payment
    When I send a "POST" request to "/payments" with body:
    """
    {
      "amount": "123.45"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Payment",
      "@id": "/payments/1",
      "@type": "Payment",
      "id": 1,
      "amount": "123.45",
      "voidPayment": null
    }
    """

  @createSchema
  Scenario: Void a payment
    Given There is a payment
    When I send a "POST" request to "/payments/1/void"
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/VoidPayment",
      "@id": "/void_payments/1",
      "@type": "VoidPayment",
      "id": 1,
      "payment": "/payments/1"
    }
    """

  Scenario: Get a void payment
    When I send a "GET" request to "/void_payments/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/VoidPayment",
      "@id": "/void_payments/1",
      "@type": "VoidPayment",
      "id": 1,
      "payment": "/payments/1"
    }
    """
