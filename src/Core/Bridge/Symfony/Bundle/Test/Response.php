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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\Test;

use Symfony\Component\BrowserKit\Response as BrowserKitResponse;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpClient\Exception\RedirectionException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * HTTP Response.
 *
 * @internal
 *
 * Partially copied from \Symfony\Component\HttpClient\Response\ResponseTrait
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class Response implements ResponseInterface
{
    private $httpFoundationResponse;
    private $browserKitResponse;
    private $headers;
    private $info;
    private $content;
    private $jsonData;

    public function __construct(HttpFoundationResponse $httpFoundationResponse, BrowserKitResponse $browserKitResponse, array $info)
    {
        $this->httpFoundationResponse = $httpFoundationResponse;
        $this->browserKitResponse = $browserKitResponse;

        $this->headers = $httpFoundationResponse->headers->all();

        // Compute raw headers
        $responseHeaders = [];
        foreach ($this->headers as $key => $values) {
            foreach ($values as $value) {
                $responseHeaders[] = sprintf('%s: %s', $key, $value);
            }
        }

        $this->content = (string) $httpFoundationResponse->getContent();
        $this->info = [
            'http_code' => $httpFoundationResponse->getStatusCode(),
            'error' => null,
            'response_headers' => $responseHeaders,
        ] + $info;
    }

    /**
     * {@inheritdoc}
     */
    public function getInfo(string $type = null)
    {
        if ($type) {
            return $this->info[$type] ?? null;
        }

        return $this->info;
    }

    /**
     * Checks the status, and try to extract message if appropriate.
     */
    private function checkStatusCode(): void
    {
        if (500 <= $this->info['http_code']) {
            throw new ServerException($this);
        }

        if (400 <= $this->info['http_code']) {
            throw new ClientException($this);
        }

        if (300 <= $this->info['http_code']) {
            throw new RedirectionException($this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getContent(bool $throw = true): string
    {
        if ($throw) {
            $this->checkStatusCode();
        }

        return $this->content;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode(): int
    {
        return $this->info['http_code'];
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(bool $throw = true): array
    {
        if ($throw) {
            $this->checkStatusCode();
        }

        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(bool $throw = true): array
    {
        if ('' === $content = $this->getContent($throw)) {
            throw new TransportException('Response body is empty.');
        }

        if (null !== $this->jsonData) {
            return $this->jsonData;
        }

        $contentType = $this->headers['content-type'][0] ?? 'application/json';

        if (!preg_match('/\bjson\b/i', $contentType)) {
            throw new JsonException(sprintf('Response content-type is "%s" while a JSON-compatible one was expected.', $contentType));
        }

        try {
            $content = json_decode($content, true, 512, \JSON_BIGINT_AS_STRING | (\PHP_VERSION_ID >= 70300 ? \JSON_THROW_ON_ERROR : 0));
        } catch (\JsonException $e) {
            throw new JsonException($e->getMessage(), $e->getCode());
        }

        if (\PHP_VERSION_ID < 70300 && \JSON_ERROR_NONE !== json_last_error()) {
            throw new JsonException(json_last_error_msg(), json_last_error());
        }

        if (!\is_array($content)) {
            throw new JsonException(sprintf('JSON content was expected to decode to an array, %s returned.', \gettype($content)));
        }

        return $this->jsonData = $content;
    }

    /**
     * Returns the internal HttpKernel response.
     */
    public function getKernelResponse(): HttpFoundationResponse
    {
        return $this->httpFoundationResponse;
    }

    /**
     * Returns the internal BrowserKit response.
     */
    public function getBrowserKitResponse(): BrowserKitResponse
    {
        return $this->browserKitResponse;
    }

    /**
     * {@inheritdoc}.
     */
    public function cancel(): void
    {
        $this->info['error'] = 'Response has been canceled.';
    }
}
