<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\DataProvider;

/**
 * Marks data providers able to deal with complex identifiers denormalized as an array.
 *
 * @author Anthony GRASSIOT <antograssiot@free.Fr>
 */
interface DenormalizedIdentifiersAwareItemDataProviderInterface extends ItemDataProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getItem(string $resourceClass, /* array */ $id, string $operationName = null, array $context = []);
}
