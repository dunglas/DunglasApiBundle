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

namespace ApiPlatform\Core\OpenApi;

final class Options
{
    private $title;
    private $description;
    private $version;
    private $oAuthEnabled;
    private $oAuthType;
    private $oAuthFlow;
    private $oAuthTokenUrl;
    private $oAuthAuthorizationUrl;
    private $oAuthRefreshUrl;
    private $oAuthScopes;
    private $apiKeys;
    private $contactName;
    private $contactUrl;
    private $contactEmail;
    private $termsOfService;
    private $licenseName;
    private $licenseUrl;

    public function __construct(string $title, string $description = '', string $version = '', bool $oAuthEnabled = false, ?string $oAuthType = null, ?string $oAuthFlow = null, ?string $oAuthTokenUrl = null, ?string $oAuthAuthorizationUrl = null, ?string $oAuthRefreshUrl = null, array $oAuthScopes = [], array $apiKeys = [], string $contactName = null, string $contactUrl = null, string $contactEmail = null, string $termsOfService = null, string $licenseName = null, string $licenseUrl = null)
    {
        $this->title = $title;
        $this->description = $description;
        $this->version = $version;
        $this->oAuthEnabled = $oAuthEnabled;
        $this->oAuthType = $oAuthType;
        $this->oAuthFlow = $oAuthFlow;
        $this->oAuthTokenUrl = $oAuthTokenUrl ?: null;
        $this->oAuthAuthorizationUrl = $oAuthAuthorizationUrl ?: null;
        $this->oAuthRefreshUrl = $oAuthRefreshUrl ?: null;
        $this->oAuthScopes = $oAuthScopes;
        $this->apiKeys = $apiKeys;
        $this->contactName = $contactName;
        $this->contactUrl = $contactUrl;
        $this->contactEmail = $contactEmail;
        $this->termsOfService = $termsOfService;
        $this->licenseName = $licenseName;
        $this->licenseUrl = $licenseUrl;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getOAuthEnabled(): bool
    {
        return $this->oAuthEnabled;
    }

    public function getOAuthType(): ?string
    {
        return $this->oAuthType;
    }

    public function getOAuthFlow(): ?string
    {
        return $this->oAuthFlow;
    }

    public function getOAuthTokenUrl(): ?string
    {
        return $this->oAuthTokenUrl;
    }

    public function getOAuthAuthorizationUrl(): ?string
    {
        return $this->oAuthAuthorizationUrl;
    }

    public function getOAuthRefreshUrl(): ?string
    {
        return $this->oAuthRefreshUrl;
    }

    public function getOAuthScopes(): array
    {
        return $this->oAuthScopes;
    }

    public function getApiKeys(): array
    {
        return $this->apiKeys;
    }

    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    public function getContactUrl(): ?string
    {
        return $this->contactUrl;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function getTermsOfService(): ?string
    {
        return $this->termsOfService;
    }

    public function getLicenseName(): ?string
    {
        return $this->licenseName;
    }

    public function getLicenseUrl(): ?string
    {
        return $this->licenseUrl;
    }
}
