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

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;

/**
 * Pagination configuration.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class Pagination
{
    private $options;
    private $graphQlOptions;
    private $resourceMetadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, array $options = [], array $graphQlOptions = [])
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->options = array_merge([
            'enabled' => true,
            'client_enabled' => false,
            'client_items_per_page' => false,
            'items_per_page' => 30,
            'page_default' => 1,
            'page_parameter_name' => 'page',
            'enabled_parameter_name' => 'pagination',
            'items_per_page_parameter_name' => 'itemsPerPage',
            'maximum_items_per_page' => null,
            'partial' => false,
            'client_partial' => false,
            'partial_parameter_name' => 'partial',
        ], $options);
        $this->graphQlOptions = array_merge([
            'enabled' => true,
        ], $graphQlOptions);
    }

    /**
     * Gets the current page.
     *
     * @throws InvalidArgumentException
     */
    public function getPage(array $context = []): int
    {
        $page = (int) $this->getParameterFromContext(
            $context,
            $this->options['page_parameter_name'],
            $this->options['page_default']
        );

        if (1 > $page) {
            throw new InvalidArgumentException('Page should not be less than 1');
        }

        return $page;
    }

    /**
     * Gets the current offset.
     */
    public function getOffset(string $resourceClass = null, string $operationName = null, array $context = []): int
    {
        $graphql = (bool) ($context['graphql_operation_name'] ?? false);

        $limit = $this->getLimit($resourceClass, $operationName, $context);

        if ($graphql && null !== ($after = $this->getParameterFromContext($context, 'after'))) {
            return false === ($after = base64_decode($after, true)) ? 0 : (int) $after + 1;
        }

        if ($graphql && null !== ($before = $this->getParameterFromContext($context, 'before'))) {
            return ($offset = (false === ($before = base64_decode($before, true)) ? 0 : (int) $before - $limit)) < 0 ? 0 : $offset;
        }

        if ($graphql && null !== ($last = $this->getParameterFromContext($context, 'last'))) {
            return ($offset = ($context['count'] ?? 0) - $last) < 0 ? 0 : $offset;
        }

        $offset = ($this->getPage($context) - 1) * $limit;

        if (!\is_int($offset)) {
            throw new InvalidArgumentException('Page parameter is too large.');
        }

        return $offset;
    }

    /**
     * Gets the current limit.
     *
     * @throws InvalidArgumentException
     */
    public function getLimit(string $resourceClass = null, string $operationName = null, array $context = []): int
    {
        $graphql = (bool) ($context['graphql_operation_name'] ?? false);

        $limit = $this->options['items_per_page'];
        $clientLimit = $this->options['client_items_per_page'];

        if (null !== $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $limit = $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_items_per_page', $limit, true);
            $clientLimit = $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_client_items_per_page', $clientLimit, true);
        }

        if ($graphql && null !== ($first = $this->getParameterFromContext($context, 'first'))) {
            $limit = $first;
        }

        if ($graphql && null !== ($last = $this->getParameterFromContext($context, 'last'))) {
            $limit = $last;
        }

        if ($graphql && null !== ($before = $this->getParameterFromContext($context, 'before'))
            && (false === ($before = base64_decode($before, true)) ? 0 : (int) $before - $limit) < 0) {
            $limit = (int) $before;
        }

        if ($clientLimit) {
            $limit = (int) $this->getParameterFromContext($context, $this->options['items_per_page_parameter_name'], $limit);
            $maxItemsPerPage = null;

            if (null !== $resourceClass) {
                $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
                $maxItemsPerPage = $resourceMetadata->getCollectionOperationAttribute($operationName, 'maximum_items_per_page', null, true);
                if (null !== $maxItemsPerPage) {
                    @trigger_error('The "maximum_items_per_page" option has been deprecated since API Platform 2.5 in favor of "pagination_maximum_items_per_page" and will be removed in API Platform 3.', \E_USER_DEPRECATED);
                }
                $maxItemsPerPage = $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_maximum_items_per_page', $maxItemsPerPage ?? $this->options['maximum_items_per_page'], true);
            }

            if (null !== $maxItemsPerPage && $limit > $maxItemsPerPage) {
                $limit = $maxItemsPerPage;
            }
        }

        if (0 > $limit) {
            throw new InvalidArgumentException('Limit should not be less than 0');
        }

        return $limit;
    }

    /**
     * Gets info about the pagination.
     *
     * Returns an array with the following info as values:
     *   - the page {@see Pagination::getPage()}
     *   - the offset {@see Pagination::getOffset()}
     *   - the limit {@see Pagination::getLimit()}
     *
     * @throws InvalidArgumentException
     */
    public function getPagination(string $resourceClass = null, string $operationName = null, array $context = []): array
    {
        $page = $this->getPage($context);
        $limit = $this->getLimit($resourceClass, $operationName, $context);

        if (0 === $limit && 1 < $page) {
            throw new InvalidArgumentException('Page should not be greater than 1 if limit is equal to 0');
        }

        return [$page, $this->getOffset($resourceClass, $operationName, $context), $limit];
    }

    /**
     * Is the pagination enabled?
     */
    public function isEnabled(string $resourceClass = null, string $operationName = null, array $context = []): bool
    {
        return $this->getEnabled($context, $resourceClass, $operationName);
    }

    /**
     * Is the pagination enabled for GraphQL?
     */
    public function isGraphQlEnabled(?string $resourceClass = null, ?string $operationName = null, array $context = []): bool
    {
        return $this->getGraphQlEnabled($resourceClass, $operationName);
    }

    /**
     * Is the partial pagination enabled?
     */
    public function isPartialEnabled(string $resourceClass = null, string $operationName = null, array $context = []): bool
    {
        return $this->getEnabled($context, $resourceClass, $operationName, true);
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getGraphQlPaginationType(string $resourceClass, string $operationName): string
    {
        try {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        } catch (ResourceClassNotFoundException $e) {
            return 'cursor';
        }

        return (string) $resourceMetadata->getGraphqlAttribute($operationName, 'pagination_type', 'cursor', true);
    }

    /**
     * Is the classic or partial pagination enabled?
     */
    private function getEnabled(array $context, string $resourceClass = null, string $operationName = null, bool $partial = false): bool
    {
        $enabled = $this->options[$partial ? 'partial' : 'enabled'];
        $clientEnabled = $this->options[$partial ? 'client_partial' : 'client_enabled'];

        if (null !== $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $enabled = $resourceMetadata->getCollectionOperationAttribute($operationName, $partial ? 'pagination_partial' : 'pagination_enabled', $enabled, true);

            $clientEnabled = $resourceMetadata->getCollectionOperationAttribute($operationName, $partial ? 'pagination_client_partial' : 'pagination_client_enabled', $clientEnabled, true);
        }

        if ($clientEnabled) {
            return filter_var($this->getParameterFromContext($context, $this->options[$partial ? 'partial_parameter_name' : 'enabled_parameter_name'], $enabled), \FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) $enabled;
    }

    private function getGraphQlEnabled(?string $resourceClass, ?string $operationName): bool
    {
        $enabled = $this->graphQlOptions['enabled'];

        if (null !== $resourceClass) {
            try {
                $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            } catch (ResourceClassNotFoundException $e) {
                return $enabled;
            }

            return (bool) $resourceMetadata->getGraphqlAttribute($operationName, 'pagination_enabled', $enabled, true);
        }

        return $enabled;
    }

    /**
     * Gets the given pagination parameter name from the given context.
     *
     * @param mixed|null $default
     */
    private function getParameterFromContext(array $context, string $parameterName, $default = null)
    {
        $filters = $context['filters'] ?? [];

        return \array_key_exists($parameterName, $filters) ? $filters[$parameterName] : $default;
    }
}
