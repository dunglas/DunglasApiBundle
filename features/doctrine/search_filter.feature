Feature: Search filter on collections
  In order to get specific result from a large collections of resources
  As a client software developer
  I need to search for collections properties

  @createSchema
  Scenario: Test ManyToMany with filter on join table
    Given there is a RelatedDummy with 4 friends
    When I add "Accept" header equal to "application/hal+json"
    And I send a "GET" request to "/related_dummies?relatedToDummyFriend.dummyFriend=/dummy_friends/4"
    Then the response status code should be 200
    And the JSON node "_embedded.item" should have 1 element
    And the JSON node "_embedded.item[0].id" should be equal to the number 1
    And the JSON node "_embedded.item[0]._links.relatedToDummyFriend" should have 4 elements
    And the JSON node "_embedded.item[0]._embedded.relatedToDummyFriend" should have 4 elements

  @createSchema
  Scenario: Test #944
    Given there is a DummyCar entity with related colors
    When I send a "GET" request to "/dummy_cars?couleurs.prop=red"
    Then the response status code should be 200
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/DummyCar",
      "@id": "/dummy_cars",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/dummy_cars/1",
          "@type": "DummyCar",
          "couleurs": [
            {
              "@id": "/dummy_car_colors/1",
              "@type": "DummyCarColor",
              "prop": "red"
            },
            {
              "@id": "/dummy_car_colors/2",
              "@type": "DummyCarColor",
              "prop": "blue"
            }
          ],
          "secondColors": [
            {
              "@id": "/dummy_car_colors/1",
              "@type": "DummyCarColor",
              "prop": "red"
            },
            {
              "@id": "/dummy_car_colors/2",
              "@type": "DummyCarColor",
              "prop": "blue"
            }
          ],
          "thirdColors": [
            {
              "@id": "/dummy_car_colors/1",
              "@type": "DummyCarColor",
              "prop": "red"
            },
            {
              "@id": "/dummy_car_colors/2",
              "@type": "DummyCarColor",
              "prop": "blue"
            }
          ],
          "uuid": [],
          "carBrand": "DummyBrand"
        }
      ],
      "hydra:totalItems": 1,
      "hydra:view": {
        "@id": "/dummy_cars?couleurs.prop=red",
        "@type": "hydra:PartialCollectionView"
      },
      "hydra:search": {
        "@type": "hydra:IriTemplate",
        "hydra:template": "/dummy_cars{?availableAt[before],availableAt[strictly_before],availableAt[after],availableAt[strictly_after],canSell,foobar[],foobargroups[],foobargroups_override[],couleurs.prop,couleurs,couleurs[],secondColors,secondColors[],thirdColors,thirdColors[],uuid,uuid[],name}",
        "hydra:variableRepresentation": "BasicRepresentation",
        "hydra:mapping": [
          {
            "@type": "IriTemplateMapping",
            "variable": "availableAt[before]",
            "property": "availableAt",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "availableAt[strictly_before]",
            "property": "availableAt",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "availableAt[after]",
            "property": "availableAt",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "availableAt[strictly_after]",
            "property": "availableAt",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "canSell",
            "property": "canSell",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "foobar[]",
            "property": null,
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "foobargroups[]",
            "property": null,
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "foobargroups_override[]",
            "property": null,
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "couleurs.prop",
            "property": "couleurs.prop",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "couleurs",
            "property": "couleurs",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "couleurs[]",
            "property": "couleurs",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "secondColors",
            "property": "secondColors",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "secondColors[]",
            "property": "secondColors",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "thirdColors",
            "property": "thirdColors",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "thirdColors[]",
            "property": "thirdColors",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "uuid",
            "property": "uuid",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "uuid[]",
            "property": "uuid",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "name",
            "property": "name",
            "required": false
          }
        ]
      }
    }
    """

  Scenario: Search collection by name (partial)
    Given there are 30 dummy objects
    When I send a "GET" request to "/dummies?name=my"
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
                  {"pattern": "^/dummies/1$"},
                  {"pattern": "^/dummies/2$"},
                  {"pattern": "^/dummies/3$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?name=my"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Search collection by name (partial)
    Given there are 30 embedded dummy objects
    When I send a "GET" request to "/embedded_dummies?embeddedDummy.dummyName=my"
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
                  {"pattern": "^/embedded_dummies/2$"},
                  {"pattern": "^/embedded_dummies/3$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/embedded_dummies\\?embeddedDummy\\.dummyName=my"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Search collection by name (partial multiple values)
    Given there are 30 dummy objects
    When I send a "GET" request to "/dummies?name[]=2&name[]=3"
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
                  {"pattern": "^/dummies/2$"},
                  {"pattern": "^/dummies/3$"},
                  {"pattern": "^/dummies/12$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?name%5B%5D=2&name%5B%5D=3"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Search collection by name (partial case insensitive)
    When I send a "GET" request to "/dummies?dummy=somedummytest1"
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
              "dummy": {
                "pattern": "^SomeDummyTest\\d{1,2}$"
              }
            }
          }
        }
      }
    }
    """

  Scenario: Search collection by alias (start)
    When I send a "GET" request to "/dummies?alias=Ali"
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
                  {"pattern": "^/dummies/1$"},
                  {"pattern": "^/dummies/2$"},
                  {"pattern": "^/dummies/3$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?alias=Ali"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Search collection by alias (start multiple values)
    When I send a "GET" request to "/dummies?description[]=Sma&description[]=Not"
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
                  {"pattern": "^/dummies/1$"},
                  {"pattern": "^/dummies/2$"},
                  {"pattern": "^/dummies/3$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?description%5B%5D=Sma&description%5B%5D=Not"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  @sqlite
  Scenario: Search collection by description (word_start)
    When I send a "GET" request to "/dummies?description=smart"
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
                  {"pattern": "^/dummies/1$"},
                  {"pattern": "^/dummies/2$"},
                  {"pattern": "^/dummies/3$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?description=smart"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  @sqlite
  Scenario: Search collection by description (word_start multiple values)
    When I send a "GET" request to "/dummies?description[]=smart&description[]=so"
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
                  {"pattern": "^/dummies/1$"},
                  {"pattern": "^/dummies/2$"},
                  {"pattern": "^/dummies/3$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?description%5B%5D=smart&description%5B%5D=so"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  # note on Postgres compared to sqlite the LIKE clause is case sensitive
  @postgres
  Scenario: Search collection by description (word_start)
    When I send a "GET" request to "/dummies?description=smart"
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
                  {"pattern": "^/dummies/2$"},
                  {"pattern": "^/dummies/4$"},
                  {"pattern": "^/dummies/6$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?description=smart"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Search for entities within an impossible range
    When I send a "GET" request to "/dummies?name=MuYm"
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
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?name=MuYm$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  @sqlite
  Scenario: Search for entities with an existing collection route name
    When I send a "GET" request to "/dummies?relatedDummies=dummy_cars"
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
          "type": "array"
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?relatedDummies=dummy_cars"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  @createSchema
  Scenario: Search related collection by name
    Given there are 3 dummy objects having each 3 relatedDummies
    When I add "Accept" header equal to "application/hal+json"
    And I send a "GET" request to "/dummies?relatedDummies.name=RelatedDummy1"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON node "_embedded.item" should have 3 elements
    And the JSON node "_embedded.item[0]._links.relatedDummies" should have 3 elements
    And the JSON node "_embedded.item[1]._links.relatedDummies" should have 3 elements
    And the JSON node "_embedded.item[2]._links.relatedDummies" should have 3 elements

  @createSchema
  Scenario: Search by related collection id
    Given there are 2 dummy objects having each 2 relatedDummies
    When I add "Accept" header equal to "application/hal+json"
    And I send a "GET" request to "/dummies?relatedDummies=3"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON node "totalItems" should be equal to "1"
    And the JSON node "_links.item" should have 1 element
    And the JSON node "_links.item[0].href" should be equal to "/dummies/2"

  @createSchema
  Scenario: Get collection by id equals 9.99 which is not possible
    Given there are 30 dummy objects
    When I send a "GET" request to "/dummies?id=9.99"
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
                  {"pattern": "^/dummies/1$"},
                  {"pattern": "^/dummies/2$"},
                  {"pattern": "^/dummies/3$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?id=9.99"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get collection by id 10
    When I send a "GET" request to "/dummies?id=10"
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
                  {"pattern": "^/dummies/10$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?id=10"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get collection ordered by a non valid properties
    When I send a "GET" request to "/dummies?unknown=0"
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
                  {"pattern": "^/dummies/1$"},
                  {"pattern": "^/dummies/2$"},
                  {"pattern": "^/dummies/3$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?unknown=0"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

    When I send a "GET" request to "/dummies?unknown=1"
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
                  {"pattern": "^/dummies/1$"},
                  {"pattern": "^/dummies/2$"},
                  {"pattern": "^/dummies/3$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?unknown=1"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Search at third level
    Given there is a dummy object with a fourth level relation
    When I send a "GET" request to "/dummies?relatedDummy.thirdLevel.level=3"
    Then the response status code should be 200
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
                  {"pattern": "^/dummies/31$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?relatedDummy.thirdLevel.level=3"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Search at fourth level
    When I send a "GET" request to "/dummies?relatedDummy.thirdLevel.fourthLevel.level=4"
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
                  {"pattern": "^/dummies/31$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?relatedDummy.thirdLevel.fourthLevel.level=4"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  @createSchema
  Scenario: Search collection on a property using a name converted
    Given there are 30 dummy objects
    When I send a "GET" request to "/dummies?name_converted=Converted 3"
    Then the response status code should be 200
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
                  {"pattern": "^/dummies/3$"},
                  {"pattern": "^/dummies/30$"}
                ]
              },
              "required": ["@id"]
            }
          },
          "minItems": 2,
          "maxItems": 2
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?name_converted=Converted%203"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        },
        "hydra:search": {
          "type": "object",
          "properties": {
            "@type": {"pattern": "^hydra:IriTemplate$"},
            "hydra:template": {"pattern": "^/dummies\\{\\?.*name_converted.*}$"},
            "hydra:variableRepresentation": {"pattern": "^BasicRepresentation$"},
            "hydra:mapping": {
              "type": "array",
              "items": {
                "type": "object",
                "properties": {
                  "@type": {"pattern": "^IriTemplateMapping$"},
                  "variable": {"pattern": "^name_converted$"},
                  "property": {"pattern": "^name_converted$"},
                  "required": {"type": "boolean"}
                },
                "required": ["@type", "variable", "property", "required"],
                "additionalProperties": false
              },
              "additionalItems": true,
              "uniqueItems": true
            }
          },
          "additionalProperties": false,
          "required": ["@type", "hydra:template", "hydra:variableRepresentation", "hydra:mapping"]
        },
        "additionalProperties": false,
        "required": ["@context", "@id", "@type", "hydra:member", "hydra:totalItems", "hydra:view", "hydra:search"]
      }
    }
    """


  @createSchema
  Scenario: Search collection on a property using a nested name converted
    Given there are 30 convertedOwner objects with convertedRelated
    When I send a "GET" request to "/converted_owners?name_converted.name_converted=Converted 3"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/ConvertedOwner$"},
        "@id": {"pattern": "^/converted_owners$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/converted_owners/3$"},
                  {"pattern": "^/converted_owners/30$"}
                ]
              },
              "name_converted": {
                "oneOf": [
                  {"pattern": "^/converted_relateds/3$"},
                  {"pattern": "^/converted_relateds/30$"}
                ]
              },
              "required": ["@id", "name_converted"]
            }
          },
          "minItems": 2,
          "maxItems": 2
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/converted_owners\\?name_converted.name_converted=Converted%203"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        },
        "hydra:search": {
          "type": "object",
          "properties": {
            "@type": {"pattern": "^hydra:IriTemplate$"},
            "hydra:template": {"pattern": "^/converted_owners\\{\\?.*name_converted\\.name_converted.*\\}$"},
            "hydra:variableRepresentation": {"pattern": "^BasicRepresentation$"},
            "hydra:mapping": {
              "type": "array",
              "items": {
                "type": "object",
                "properties": {
                  "@type": {"pattern": "^IriTemplateMapping$"},
                  "variable": {"pattern": "^name_converted\\.name_converted"},
                  "property": {"pattern": "^name_converted\\.name_converted$"},
                  "required": {"type": "boolean"}
                },
                "required": ["@type", "variable", "property", "required"],
                "additionalProperties": false
              },
              "additionalItems": true,
              "uniqueItems": true
            }
          },
          "additionalProperties": false,
          "required": ["@type", "hydra:template", "hydra:variableRepresentation", "hydra:mapping"]
        },
        "additionalProperties": false,
        "required": ["@context", "@id", "@type", "hydra:member", "hydra:totalItems", "hydra:view", "hydra:search"]
      }
    }
    """

  @createSchema
  Scenario: Search by date (#4128)
    Given there are 3 dummydate objects with dummyDate
    When I send a "GET" request to "/dummy_dates?dummyDate=2015-04-01"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON node "hydra:totalItems" should be equal to 1
