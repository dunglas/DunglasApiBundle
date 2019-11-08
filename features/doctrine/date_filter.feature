Feature: Date filter on collections
  In order to retrieve large collections of resources filtered by date
  As a client software developer
  I need to retrieve collections filtered by date

  @createSchema
  Scenario: Get collection filtered by date ("after")
    Given there are 30 dummy objects with dummyDate
    When I send a "GET" request to "/dummies?dummyDate[after]=2015-04-28"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/28$"},
                  {"pattern": "^/dummies/29$"}
                ]
              }
            },
            "required": [
              "@id"
            ]
          },
          "minItems": 2,
          "maxItems": 2
        },
        "hydra:totalItems": {"type":"number", "minimum": 2, "maximum": 2},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyDate%5Bafter%5D=2015-04-28&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"},
            "hydra:first": {"pattern": "^/dummies\\?dummyDate%5Bafter%5D=2015-04-28&page=1$"},
            "hydra:last": {"pattern": "^/dummies\\?dummyDate%5Bafter%5D=2015-04-28&page=1$"}
          },
          "required": [
            "@id",
            "@type",
            "hydra:first",
            "hydra:last"
          ],
          "additionalProperties": false
        }
      },
      "required": [
        "@context",
        "@id",
        "@type",
        "hydra:member",
        "hydra:totalItems",
        "hydra:view"
      ]
    }
    """

  Scenario: Get collection filtered by date ("before")
    When I send a "GET" request to "/dummies?dummyDate[before]=2015-04-05"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/1$"},
                  {"pattern": "^/dummies/2$"},
                  {"pattern": "^/dummies/3$"}
                ]
              }
            },
            "required": [
              "@id"
            ]
          },
          "minItems": 3,
          "maxItems": 3
        },
        "hydra:totalItems": {"type":"number", "minimum": 5, "maximum": 5},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyDate%5Bbefore%5D=2015-04-05&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"},
            "hydra:first": {"pattern": "^/dummies\\?dummyDate%5Bbefore%5D=2015-04-05&page=1$"},
            "hydra:last": {"pattern": "^/dummies\\?dummyDate%5Bbefore%5D=2015-04-05&page=2$"},
            "hydra:next": {"pattern": "^/dummies\\?dummyDate%5Bbefore%5D=2015-04-05&page=2$"}
          },
          "required": [
            "@id",
            "@type",
            "hydra:first",
            "hydra:last",
            "hydra:next"
          ],
          "additionalProperties": false
        }
      },
      "required": [
        "@context",
        "@id",
        "@type",
        "hydra:member",
        "hydra:totalItems",
        "hydra:view"
      ]
    }
    """

  Scenario: Get collection filtered by date and time with timezone offset ("after")
    When I send a "GET" request to "/dummies?dummyDate[after]=2015-04-28T01:00:00%2B01:00"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/28$"},
                  {"pattern": "^/dummies/29$"}
                ]
              }
            },
            "required": [
              "@id"
            ]
          },
          "minItems": 2,
          "maxItems": 2
        },
        "hydra:totalItems": {"type":"number", "minimum": 2, "maximum": 2},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyDate%5Bafter%5D=2015-04-28T01%3A00%3A00%2B01%3A00&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"},
            "hydra:first": {"pattern": "^/dummies\\?dummyDate%5Bafter%5D=2015-04-28T01%3A00%3A00%2B01%3A00&page=1$"},
            "hydra:last": {"pattern": "^/dummies\\?dummyDate%5Bafter%5D=2015-04-28T01%3A00%3A00%2B01%3A00&page=1$"}
          },
          "required": [
            "@id",
            "@type",
            "hydra:first",
            "hydra:last"
          ],
          "additionalProperties": false
        }
      },
      "required": [
        "@context",
        "@id",
        "@type",
        "hydra:member",
        "hydra:totalItems",
        "hydra:view"
      ]
    }
    """

  Scenario: Get collection filtered by date and time with UTC timezone ("before")
    When I send a "GET" request to "/dummies?dummyDate[before]=2015-04-05T00:00:00Z"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/1$"},
                  {"pattern": "^/dummies/2$"},
                  {"pattern": "^/dummies/3$"}
                ]
              }
            },
            "required": [
              "@id"
            ]
          },
          "minItems": 3,
          "maxItems": 3
        },
        "hydra:totalItems": {"type":"number", "minimum": 5, "maximum": 5},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyDate%5Bbefore%5D=2015-04-05T00%3A00%3A00Z&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"},
            "hydra:first": {"pattern": "^/dummies\\?dummyDate%5Bbefore%5D=2015-04-05T00%3A00%3A00Z&page=1$"},
            "hydra:last": {"pattern": "^/dummies\\?dummyDate%5Bbefore%5D=2015-04-05T00%3A00%3A00Z&page=2$"},
            "hydra:next": {"pattern": "^/dummies\\?dummyDate%5Bbefore%5D=2015-04-05T00%3A00%3A00Z&page=2$"}
          },
          "required": [
            "@id",
            "@type",
            "hydra:first",
            "hydra:last",
            "hydra:next"
          ],
          "additionalProperties": false
        }
      },
      "required": [
        "@context",
        "@id",
        "@type",
        "hydra:member",
        "hydra:totalItems",
        "hydra:view"
      ]
    }
    """

  Scenario: Get collection filtered by date range ("before" followed by "after")
    When I send a "GET" request to "/dummies?dummyDate[before]=2015-04-05&dummyDate[after]=2015-04-05"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/5$"}
                ]
              }
            },
            "required": [
              "@id"
            ]
          },
          "minItems": 1,
          "maxItems": 1
        },
        "hydra:totalItems": {"type":"number", "minimum": 1, "maximum": 1},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyDate%5Bbefore%5D=2015-04-05&dummyDate%5Bafter%5D=2015-04-05&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"},
            "hydra:first": {"pattern": "^/dummies\\?dummyDate%5Bbefore%5D=2015-04-05&dummyDate%5Bafter%5D=2015-04-05&page=1$"},
            "hydra:last": {"pattern": "^/dummies\\?dummyDate%5Bbefore%5D=2015-04-05&dummyDate%5Bafter%5D=2015-04-05&page=1$"}
          },
          "required": [
            "@id",
            "@type",
            "hydra:first",
            "hydra:last"
          ],
          "additionalProperties": false
        }
      },
      "required": [
        "@context",
        "@id",
        "@type",
        "hydra:member",
        "hydra:totalItems",
        "hydra:view"
      ]
    }
    """

  Scenario: Get collection filtered by date range ("after" followed by "before")
    When I send a "GET" request to "/dummies?dummyDate[after]=2015-04-05&dummyDate[before]=2015-04-05"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/5$"}
                ]
              }
            },
            "required": [
              "@id"
            ]
          },
          "minItems": 1,
          "maxItems": 1
        },
        "hydra:totalItems": {"type":"number", "minimum": 1, "maximum": 1},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyDate%5Bafter%5D=2015-04-05&dummyDate%5Bbefore%5D=2015-04-05&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"},
            "hydra:first": {"pattern": "^/dummies\\?dummyDate%5Bafter%5D=2015-04-05&dummyDate%5Bbefore%5D=2015-04-05&page=1$"},
            "hydra:last": {"pattern": "^/dummies\\?dummyDate%5Bafter%5D=2015-04-05&dummyDate%5Bbefore%5D=2015-04-05&page=1$"}
          },
          "required": [
            "@id",
            "@type",
            "hydra:first",
            "hydra:last"
          ],
          "additionalProperties": false
        }
      },
      "required": [
        "@context",
        "@id",
        "@type",
        "hydra:member",
        "hydra:totalItems",
        "hydra:view"
      ]
    }
    """

  Scenario: Get collection filtered by impossible date range
    When I send a "GET" request to "/dummies?dummyDate[after]=2015-04-06&dummyDate[before]=2015-04-04"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 0
        },
        "hydra:totalItems": {"type":"number", "minimum": 0, "maximum": 0},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyDate%5Bafter%5D=2015-04-06&dummyDate%5Bbefore%5D=2015-04-04&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"},
            "hydra:first": {"pattern": "^/dummies\\?dummyDate%5Bafter%5D=2015-04-06&dummyDate%5Bbefore%5D=2015-04-04&page=1$"},
            "hydra:last": {"pattern": "^/dummies\\?dummyDate%5Bafter%5D=2015-04-06&dummyDate%5Bbefore%5D=2015-04-04&page=1$"}
          },
          "required": [
            "@id",
            "@type",
            "hydra:first",
            "hydra:last"
          ],
          "additionalProperties": false
        }
      },
      "required": [
        "@context",
        "@id",
        "@type",
        "hydra:member",
        "hydra:totalItems",
        "hydra:view"
      ]
    }
    """

  Scenario: Get collection filtered by date on a related entity ("after")
    Given there are 30 dummy objects with dummyDate and relatedDummy
    When I send a "GET" request to "/dummies?relatedDummy.dummyDate[after]=2015-04-28"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    Then print last JSON response
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/58$"},
                  {"pattern": "^/dummies/59$"},
                  {"pattern": "^/dummies/60$"}
                ]
              }
            },
            "required": [
              "@id"
            ]
          },
          "minItems": 3,
          "maxItems": 3
        },
        "hydra:totalItems": {"type":"number", "minimum": 3, "maximum": 3},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?relatedDummy\\.dummyDate%5Bafter%5D=2015-04-28&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"},
            "hydra:first": {"pattern": "^/dummies\\?relatedDummy\\.dummyDate%5Bafter%5D=2015-04-28&page=1$"},
            "hydra:last": {"pattern": "^/dummies\\?relatedDummy\\.dummyDate%5Bafter%5D=2015-04-28&page=1$"}
          },
          "required": [
            "@id",
            "@type",
            "hydra:first",
            "hydra:last"
          ],
          "additionalProperties": false
        }
      },
      "required": [
        "@context",
        "@id",
        "@type",
        "hydra:member",
        "hydra:totalItems",
        "hydra:view"
      ]
    }
    """

  Scenario: Get collection filtered by date on a related entity ("after" with another potentially indistinguishable query parameter)
    When I send a "GET" request to "/dummies?relatedDummy.dummyDate[after]=2015-04-28&relatedDummy_dummyDate[after]=2015-04-27"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/58$"},
                  {"pattern": "^/dummies/59$"},
                  {"pattern": "^/dummies/60$"}
                ]
              }
            },
            "required": [
              "@id"
            ]
          },
          "minItems": 3,
          "maxItems": 3
        },
        "hydra:totalItems": {"type":"number", "minimum": 3, "maximum": 3},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?relatedDummy\\.dummyDate%5Bafter%5D=2015-04-28&relatedDummy_dummyDate%5Bafter%5D=2015-04-27&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"},
            "hydra:first": {"pattern": "^/dummies\\?relatedDummy\\.dummyDate%5Bafter%5D=2015-04-28&relatedDummy_dummyDate%5Bafter%5D=2015-04-27&page=1$"},
            "hydra:last": {"pattern": "^/dummies\\?relatedDummy\\.dummyDate%5Bafter%5D=2015-04-28&relatedDummy_dummyDate%5Bafter%5D=2015-04-27&page=1$"}
          },
          "required": [
            "@id",
            "@type",
            "hydra:first",
            "hydra:last"
          ],
          "additionalProperties": false
        }
      },
      "required": [
        "@context",
        "@id",
        "@type",
        "hydra:member",
        "hydra:totalItems",
        "hydra:view"
      ]
    }
    """

  Scenario: Get collection filtered by date and time with timezone offset on a related entity ("after")
    When I send a "GET" request to "/dummies?relatedDummy.dummyDate[after]=2015-04-27T23:00:00-01:00"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/58$"},
                  {"pattern": "^/dummies/59$"},
                  {"pattern": "^/dummies/60$"}
                ]
              }
            },
            "required": [
              "@id"
            ]
          },
          "minItems": 3,
          "maxItems": 3
        },
        "hydra:totalItems": {"type":"number", "minimum": 3, "maximum": 3},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?relatedDummy\\.dummyDate%5Bafter%5D=2015-04-27T23%3A00%3A00-01%3A00&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"},
            "hydra:first": {"pattern": "^/dummies\\?relatedDummy\\.dummyDate%5Bafter%5D=2015-04-27T23%3A00%3A00-01%3A00&page=1$"},
            "hydra:last": {"pattern": "^/dummies\\?relatedDummy\\.dummyDate%5Bafter%5D=2015-04-27T23%3A00%3A00-01%3A00&page=1$"}
          },
          "required": [
            "@id",
            "@type",
            "hydra:first",
            "hydra:last"
          ],
          "additionalProperties": false
        }
      },
      "required": [
        "@context",
        "@id",
        "@type",
        "hydra:member",
        "hydra:totalItems",
        "hydra:view"
      ]
    }
    """

  @createSchema
  Scenario: Get collection filtered by date on a related entity but there are no matches ("after")
    Given there are 2 dummy objects with dummyDate and relatedDummy
    When I send a "GET" request to "/dummies?relatedDummy.dummyDate[after]=2015-04-28"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 0
        },
        "hydra:totalItems": {"type":"number", "minimum": 0, "maximum": 0},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?relatedDummy\\.dummyDate%5Bafter%5D=2015-04-28&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"},
            "hydra:first": {"pattern": "^/dummies\\?relatedDummy\\.dummyDate%5Bafter%5D=2015-04-28&page=1$"},
            "hydra:last": {"pattern": "^/dummies\\?relatedDummy\\.dummyDate%5Bafter%5D=2015-04-28&page=1$"}
          },
          "required": [
            "@id",
            "@type",
            "hydra:first",
            "hydra:last"
          ],
          "additionalProperties": false
        }
      },
      "required": [
        "@context",
        "@id",
        "@type",
        "hydra:member",
        "hydra:totalItems",
        "hydra:view"
      ]
    }
    """

  @createSchema
  Scenario: Get collection filtered by date that is not a datetime
    Given there are 30 dummydate objects with dummyDate
    When I send a "GET" request to "/dummy_dates?dummyDate[after]=2015-04-28"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:totalItems" should be equal to 3

  @createSchema
  Scenario: Get collection filtered by date that is not a datetime including null after
    Given there are 3 dummydate objects with nullable dateIncludeNullAfter
    When I send a "GET" request to "/dummy_dates?dateIncludeNullAfter[after]=2015-04-02"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:totalItems" should be equal to 2
    And the JSON node "hydra:member[0].dateIncludeNullAfter" should be equal to "2015-04-02T00:00:00+00:00"
    And the JSON node "hydra:member[1].dateIncludeNullAfter" should be null
    When I send a "GET" request to "/dummy_dates?dateIncludeNullAfter[before]=2015-04-02"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:totalItems" should be equal to 2
    And the JSON node "hydra:member[0].dateIncludeNullAfter" should be equal to "2015-04-01T00:00:00+00:00"
    And the JSON node "hydra:member[1].dateIncludeNullAfter" should be equal to "2015-04-02T00:00:00+00:00"

  @createSchema
  Scenario: Get collection filtered by date that is not a datetime including null before
    Given there are 3 dummydate objects with nullable dateIncludeNullBefore
    When I send a "GET" request to "/dummy_dates?dateIncludeNullBefore[before]=2015-04-01"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:totalItems" should be equal to 2
    And the JSON node "hydra:member[0].dateIncludeNullBefore" should be equal to "2015-04-01T00:00:00+00:00"
    And the JSON node "hydra:member[1].dateIncludeNullBefore" should be null
    When I send a "GET" request to "/dummy_dates?dateIncludeNullBefore[after]=2015-04-01"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:totalItems" should be equal to 2
    And the JSON node "hydra:member[0].dateIncludeNullBefore" should be equal to "2015-04-01T00:00:00+00:00"
    And the JSON node "hydra:member[1].dateIncludeNullBefore" should be equal to "2015-04-02T00:00:00+00:00"

  @createSchema
  Scenario: Get collection filtered by date that is not a datetime including null before and after
    Given there are 3 dummydate objects with nullable dateIncludeNullBeforeAndAfter
    When I send a "GET" request to "/dummy_dates?dateIncludeNullBeforeAndAfter[before]=2015-04-01"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:totalItems" should be equal to 2
    And the JSON node "hydra:member[0].dateIncludeNullBeforeAndAfter" should be equal to "2015-04-01T00:00:00+00:00"
    And the JSON node "hydra:member[1].dateIncludeNullBeforeAndAfter" should be null
    When I send a "GET" request to "/dummy_dates?dateIncludeNullBeforeAndAfter[after]=2015-04-02"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:totalItems" should be equal to 2
    And the JSON node "hydra:member[0].dateIncludeNullBeforeAndAfter" should be equal to "2015-04-02T00:00:00+00:00"
    And the JSON node "hydra:member[1].dateIncludeNullBeforeAndAfter" should be null

  @!mongodb
  @createSchema
  Scenario: Get collection filtered by date that is an immutable date variant
    Given there are 30 dummyimmutabledate objects with dummyDate
    When I send a "GET" request to "/dummy_immutable_dates?dummyDate[after]=2015-04-28"
    Then the response status code should be 200
    And the JSON node "hydra:totalItems" should be equal to 3
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"

  @createSchema
  Scenario: Get collection filtered by embedded date
    Given there are 2 embedded dummy objects with dummyDate and embeddedDummy
    When I send a "GET" request to "/embedded_dummies?embeddedDummy.dummyDate[after]=2015-04-01"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/EmbeddedDummy$"},
        "@id": {"pattern": "^/embedded_dummies$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/embedded_dummies/1$"},
                  {"pattern": "^/embedded_dummies/2$"}
                ]
              }
            },
            "required": [
              "@id"
            ]
          },
          "minItems": 2,
          "maxItems": 2
        },
        "hydra:totalItems": {"type":"number", "minimum": 2, "maximum": 2},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/embedded_dummies\\?embeddedDummy\\.dummyDate%5Bafter%5D=2015-04-01&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"},
            "hydra:first": {"pattern": "^/embedded_dummies\\?embeddedDummy\\.dummyDate%5Bafter%5D=2015-04-01&page=1$"},
            "hydra:last": {"pattern": "^/embedded_dummies\\?embeddedDummy\\.dummyDate%5Bafter%5D=2015-04-01&page=1$"}
          },
          "required": [
            "@id",
            "@type",
            "hydra:first",
            "hydra:last"
          ],
          "additionalProperties": false
        }
      },
      "required": [
        "@context",
        "@id",
        "@type",
        "hydra:member",
        "hydra:totalItems",
        "hydra:view"
      ]
    }
    """
