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

namespace ApiPlatform\Core\OpenApi\Model;

final class Info
{
    use ExtensionTrait;

    private $title;
    private $description;
    private $termsOfService;
    private $contact;
    private $license;
    private $version;

    public function __construct(string $title, string $version, string $description = '', string $termsOfService = null, Contact $contact = null, License $license = null)
    {
        $this->title = $title;
        $this->version = $version;
        $this->description = $description;
        $this->termsOfService = $termsOfService;
        $this->contact = $contact;
        $this->license = $license;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getTermsOfService(): ?string
    {
        return $this->termsOfService;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function getLicense(): ?License
    {
        return $this->license;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function withTitle(string $title): self
    {
        $info = clone $this;
        $info->title = $title;

        return $info;
    }

    public function withDescription(string $description): self
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    public function withTermsOfService(string $termsOfService): self
    {
        $clone = clone $this;
        $clone->termsOfService = $termsOfService;

        return $clone;
    }

    public function withContact(Contact $contact): self
    {
        $clone = clone $this;
        $clone->contact = $contact;

        return $clone;
    }

    public function withLicense(License $license): self
    {
        $clone = clone $this;
        $clone->license = $license;

        return $clone;
    }

    public function withVersion(string $version): self
    {
        $clone = clone $this;
        $clone->version = $version;

        return $clone;
    }
}
