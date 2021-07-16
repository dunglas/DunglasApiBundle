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

namespace ApiPlatform\Core\Bridge\Symfony\Routing;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

/**
 * Symfony router decorator.
 *
 * Kévin Dunglas <dunglas@gmail.com>
 */
final class Router implements RouterInterface, UrlGeneratorInterface
{
    public const CONST_MAP = [
        UrlGeneratorInterface::ABS_URL => RouterInterface::ABSOLUTE_URL,
        UrlGeneratorInterface::ABS_PATH => RouterInterface::ABSOLUTE_PATH,
        UrlGeneratorInterface::REL_PATH => RouterInterface::RELATIVE_PATH,
        UrlGeneratorInterface::NET_PATH => RouterInterface::NETWORK_PATH,
    ];

    private $router;
    private $urlGenerationStrategy;

    public function __construct(RouterInterface $router, int $urlGenerationStrategy = self::ABS_PATH)
    {
        $this->router = $router;
        $this->urlGenerationStrategy = $urlGenerationStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->router->setContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->router->getContext();
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection()
    {
        return $this->router->getRouteCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function match($pathInfo)
    {
        $baseContext = $this->router->getContext();
        $baseUrl = $baseContext->getBaseUrl();
        if ($baseUrl === substr($pathInfo, 0, \strlen($baseUrl))) {
            $pathInfo = substr($pathInfo, \strlen($baseUrl));
        }

        $request = Request::create($pathInfo, 'GET', [], [], [], ['HTTP_HOST' => $baseContext->getHost()]);
        try {
            $context = (new RequestContext())->fromRequest($request);
        } catch (RequestExceptionInterface $e) {
            throw new ResourceNotFoundException('Invalid request context.');
        }

        $context->setPathInfo($pathInfo);
        $context->setScheme($baseContext->getScheme());
        $context->setHost($baseContext->getHost());

        try {
            $this->router->setContext($context);

            return $this->router->match($request->getPathInfo());
        } finally {
            $this->router->setContext($baseContext);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = null)
    {
        return $this->router->generate($name, $parameters, self::CONST_MAP[$referenceType ?? $this->urlGenerationStrategy]);
    }
}
