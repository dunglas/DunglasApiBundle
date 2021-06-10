<?php

namespace ApiPlatform\Core\Tests\Bridge\Rector\Rules\ApiResourceAttributeToResourceAttributeRector\Fixture;

use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource(graphql={
 *     "sum"={
 *         "mutation"="app.graphql.mutation_resolver.dummy_custom",
 *         "normalization_context"={"groups"={"result"}},
 *         "denormalization_context"={"groups"={"sum"}}
 *     },
 *     "sumNotPersisted"={
 *         "mutation"="app.graphql.mutation_resolver.dummy_custom_not_persisted",
 *         "normalization_context"={"groups"={"result"}},
 *         "denormalization_context"={"groups"={"sum"}}
 *     },
 *     "sumNoWriteCustomResult"={
 *         "mutation"="app.graphql.mutation_resolver.dummy_custom_no_write_custom_result",
 *         "normalization_context"={"groups"={"result"}},
 *         "denormalization_context"={"groups"={"sum"}},
 *         "write"=false
 *     },
 *     "sumOnlyPersist"={
 *         "mutation"="app.graphql.mutation_resolver.dummy_custom_only_persist",
 *         "normalization_context"={"groups"={"result"}},
 *         "denormalization_context"={"groups"={"sum"}},
 *         "read"=false,
 *         "deserialize"=false,
 *         "validate"=false,
 *         "serialize"=false
 *     },
 *     "testCustomArguments"={
 *         "mutation"="app.graphql.mutation_resolver.dummy_custom",
 *         "args"={"operandC"={"type"="Int!"}}
 *     }
 * })
 */
class DummyCustomMutation
{
}

?>
-----
<?php

namespace ApiPlatform\Core\Tests\Bridge\Rector\Rules\ApiResourceAttributeToResourceAttributeRector\Fixture;

use ApiPlatform\Metadata\Resource;
use ApiPlatform\Core\Annotation\ApiResource;

#[Resource(graphql: ['sum' => ['mutation' => 'app.graphql.mutation_resolver.dummy_custom', 'normalization_context' => ['groups' => ['result']], 'denormalization_context' => ['groups' => ['sum']]], 'sumNotPersisted' => ['mutation' => 'app.graphql.mutation_resolver.dummy_custom_not_persisted', 'normalization_context' => ['groups' => ['result']], 'denormalization_context' => ['groups' => ['sum']]], 'sumNoWriteCustomResult' => ['mutation' => 'app.graphql.mutation_resolver.dummy_custom_no_write_custom_result', 'normalization_context' => ['groups' => ['result']], 'denormalization_context' => ['groups' => ['sum']], 'write' => false], 'sumOnlyPersist' => ['mutation' => 'app.graphql.mutation_resolver.dummy_custom_only_persist', 'normalization_context' => ['groups' => ['result']], 'denormalization_context' => ['groups' => ['sum']], 'read' => false, 'deserialize' => false, 'validate' => false, 'serialize' => false], 'testCustomArguments' => ['mutation' => 'app.graphql.mutation_resolver.dummy_custom', 'args' => ['operandC' => ['type' => 'Int!']]]])]
class DummyCustomMutation
{
}

?>
