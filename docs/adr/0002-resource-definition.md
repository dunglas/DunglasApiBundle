# Resource definition

* Status: proposed
* Deciders: @dunglas @soyuka @vincentchalamon @GregoireHebert

## Context and Problem Statement

The API Platform `@ApiResource` annotation was initially created to represent a Resource as defined in [Roy Fiedling's dissertation about REST](https://www.ics.uci.edu/~fielding/pubs/dissertation/rest_arch_style.htm#sec_5_2_1_1) in corelation with [RFC 7231 about HTTP Semantics](https://httpwg.org/specs/rfc7231.html#resources). This annotation brings some confusion as it mixes concepts of resources and operations. Here we discussed how we could revamp API Platform's resource definition using PHP8 attributes, beeing as close as we can to Roy Fiedling's thesis vocabulary.

## Considered Options

* Declare multiple ApiResource on a PHP Class [see Subresources definition](./0000-subresources-definition.md)
* Declare operations in conjunction with resources using two attributes: `Resource` and `Operation`
* Use HTTP Verb to represent operations with a syntax sugar for collections (`CGET`?)

## Decision Outcome

As Roy Fiedling's thesis states:

> REST uses a resource identifier to identify the particular resource involved in an interaction between components. REST connectors provide a generic interface for accessing and manipulating the value set of a resource, regardless of how the membership function is defined or the type of software that is handling the request. 

In API Platform, this resource identifier is also named [IRI (Internationalized Resource Identifiers)](https://tools.ietf.org/html/rfc3987). Following these recommandation, applied to PHP, we came up with the following [PHP 8 attributes](https://www.php.net/manual/en/language.attributes.php):

```php
<?php

#[Resource]
class Users
{
    #[ApiProperty(iri="hydra:member")]
    public User[] $member = [];

    public float $averageRate;
}

#[Resource("/companies/{companyId}/users/{id}", normalization_context=["groups"= [....]]), operations={}]
#[Resource(normalization_context=["groups"= [....]], operations=[
 new Get(),
 new Post(),
])]
class User
{
    #[ApiProperty(identifier=true)]
    public $id;
}
```

Under the hood, API Platform would declare two routes Representing the `/users` resource:

- GET /users
- POST /users

and three routes representing the `/users/{id}` resource:

- GET /users/{id}
- PUT /users/{id}
- DELETE /users/{id}

For convenience and to ease the upgrade path, these would still be available on a single class:

```php
<?php

#[Resource]
class User {}
```

corresponding to 

```php
<?php

#[Get]
#[GetCollection]
#[PostCollection]
#[Put]
#[Delete]
class User {}
```

Verbs declared on a PHP class define API Platform operations. The `Resource` attributes would become optional and the only thing needed is to specify at least a verb and an IRI representing the Resource. Some examples:

<table>
    <tr>
        <th>
            Code
        </th>
        <th>
            Operations
        </th>
    </tr>
    <tr>
        <td>
            <pre lang="php">
#[Get]
class User {}
            </pre>
        </td>
        <td>
<ul><li>GET /users/{id}</li></ul>
        </td>
    </tr>
    <tr>
        <td>
            <pre lang="php">
#[GetCollection]
class User {}
            </pre>
        </td>
        <td>
<ul><li>GET /users</li></ul>
        </td>
    </tr>
    <tr>
        <td>
            <pre lang="php">
#[Get("/users")]
class User {}
            </pre>
        </td>
        <td>
<ul><li>GET /users</li></ul>
        </td>
    </tr>
    <tr>
        <td>
            <pre lang="php">
#[Post("/users")]
#[Patch("/users/{id}")]
class User {}
            </pre>
        </td>
        <td>
<ul><li>POST /users</li>
<li>PATCH /users/{id}</li>
        </td>
    </tr>
    <tr>
        <td>
            <pre lang="php">
// See these as aliases to the `/users/{id}` Resource:
#[Get("/companies/{companyId}/users/{id}")]
#[Delete("/companies/{companyId}/users/{id}")]
class User {}
            </pre>
        </td>
        <td>
<ul><li>GET /companies/{companyId}/users/{id}</li>
<li>DELETE /companies/{companyId}/users/{id}</li>
        </td>
    </tr>
</table>

To link these operations with identifiers, refer to [Resource Identifiers decision record](./0001-resource-identifiers), for example:

```php
<?php
use Company;

#[Get("/companies/{companyId}/users/{id}", [identifiers=["companyId" => [Company::class, "id"], "id" => [User::class, "id"]]])]
class User {
  #[ApiProperty(identifier=true)]
  public $id;
  public Company $company;
}
```

The `Resource` attribute could be used to set defaults properties on operations:

```php
<?php

#[Resource(normalization_context=["groups"= [....]])]
#[Get("/users/{id}")]
class User {}
```

These properties can also be specified directly on the verb attribute:

```php
<?php

#[Get("/users/{id}", normalizationContext=["group"])]
class User {}
```

Internally, HTTP verbs are aliases to the Resource Attribute holding a method and a default path. The Resource Attribute is a reflection of the underlying metadata:

```php
<?php
class Resource {
    public string $iri;
    public bool $mercure;
    public string $security;
    public bool $paginationEnabled;
    // etc.

    /** this was named $attributes before **/
    public array $extraProperties;
}
```

For GraphQL, `Query`, `Mutation` and `Subscription` will be added. 

## Options declined

An `Operation` attribute was proposed but we want to keep the code base small and decided verb attributes where sufficient.

The initially proposed `CGet` will be named `GetCollection`. It is only a shortcut to what **used to be called** `collectionOperation` on the `GET` verb. To remove confusion around `collectionOperations` and `itemOperations`, these terms will be deprecated in the code-base. To distant ourselves from the `CRUD` pattern, we also declined `List` and `Create` as we want to focus on Resource based architectures. `RPC` routes will be easy to add using the `Post` verb if required.
