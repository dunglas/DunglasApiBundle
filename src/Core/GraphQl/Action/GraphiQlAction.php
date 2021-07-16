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

namespace ApiPlatform\Core\GraphQl\Action;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment as TwigEnvironment;

/**
 * GraphiQL entrypoint.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class GraphiQlAction
{
    private $twig;
    private $router;
    private $graphiqlEnabled;
    private $title;

    public function __construct(TwigEnvironment $twig, RouterInterface $router, bool $graphiqlEnabled = false, string $title = '')
    {
        $this->twig = $twig;
        $this->router = $router;
        $this->graphiqlEnabled = $graphiqlEnabled;
        $this->title = $title;
    }

    public function __invoke(Request $request): Response
    {
        if ($this->graphiqlEnabled) {
            return new Response($this->twig->render('@ApiPlatform/Graphiql/index.html.twig', [
                'title' => $this->title,
                'graphiql_data' => ['entrypoint' => $this->router->generate('api_graphql_entrypoint')],
            ]));
        }

        throw new BadRequestHttpException('GraphiQL is not enabled.');
    }
}
