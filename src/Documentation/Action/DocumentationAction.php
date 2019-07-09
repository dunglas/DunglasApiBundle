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

namespace ApiPlatform\Core\Documentation\Action;

use ApiPlatform\Core\Api\FormatsProviderInterface;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;

/**
 * Generates the API documentation.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
final class DocumentationAction
{
    private $resourceNameCollectionFactory;
    private $title;
    private $description;
    private $version;
    private $formats;
    private $formatsProvider;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, string $title = '', string $description = '', string $version = '', $formats = [])
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->title = $title;
        $this->description = $description;
        $this->version = $version;

        if ($formats instanceof FormatsProviderInterface) {
            @trigger_error(sprintf('Passing an instance of "%s" as 5th parameter of the constructor of "%s" is deprecated since API Platform 2.5', FormatsProviderInterface::class, __CLASS__), E_USER_DEPRECATED);
            $this->formatsProvider = $formats;

            return;
        }

        if (!\is_array($formats)) {
            throw new InvalidArgumentException('The "$formats" argument is expected to be an array.');
        }

        $this->formats = $formats;
    }

    public function __invoke(Request $request = null): Documentation
    {
        if (null !== $request) {
            $context = ['base_url' => $request->getBaseUrl(), 'spec_version' => $request->query->getInt('spec_version', 2)];
            if ($request->query->getBoolean('api_gateway')) {
                $context['api_gateway'] = true;
            }
            $request->attributes->set('_api_normalization_context', $request->attributes->get('_api_normalization_context', []) + $context);

            $attributes = RequestAttributesExtractor::extractAttributes($request);
        }
        // BC check to be removed in 3.0
        if (null !== $this->formatsProvider) {
            $this->formats = $this->formatsProvider->getFormatsFromAttributes($attributes ?? []);
        }

        return new Documentation($this->resourceNameCollectionFactory->create(), $this->title, $this->description, $this->version, $this->formats);
    }
}
