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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\SwaggerUi;

final class SwaggerUiContext
{
    private $swaggerUiEnabled;
    private $showWebby;
    private $reDocEnabled;
    private $graphQlEnabled;
    private $graphiQlEnabled;
    private $graphQlPlaygroundEnabled;
    private $assetPackage;
    private $extraConfiguration;

    public function __construct(bool $swaggerUiEnabled = false, bool $showWebby = true, bool $reDocEnabled = false, bool $graphQlEnabled = false, bool $graphiQlEnabled = false, bool $graphQlPlaygroundEnabled = false, $assetPackage = null, array $extraConfiguration = [])
    {
        $this->swaggerUiEnabled = $swaggerUiEnabled;
        $this->showWebby = $showWebby;
        $this->reDocEnabled = $reDocEnabled;
        $this->graphQlEnabled = $graphQlEnabled;
        $this->graphiQlEnabled = $graphiQlEnabled;
        $this->graphQlPlaygroundEnabled = $graphQlPlaygroundEnabled;
        $this->assetPackage = $assetPackage;
        $this->extraConfiguration = $extraConfiguration;
    }

    public function isSwaggerUiEnabled(): bool
    {
        return $this->swaggerUiEnabled;
    }

    public function isWebbyShown(): bool
    {
        return $this->showWebby;
    }

    public function isRedocEnabled(): bool
    {
        return $this->reDocEnabled;
    }

    public function isGraphQlEnabled(): bool
    {
        return $this->graphQlEnabled;
    }

    public function isGraphiQlEnabled(): bool
    {
        return $this->graphiQlEnabled;
    }

    public function isGraphQlPlaygroundEnabled(): bool
    {
        return $this->graphQlPlaygroundEnabled;
    }

    public function getAssetPackage(): ?string
    {
        return $this->assetPackage;
    }

    public function getExtraConfiguration(): array
    {
        return $this->extraConfiguration;
    }
}
