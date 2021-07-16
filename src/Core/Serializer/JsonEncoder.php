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

namespace ApiPlatform\Core\Serializer;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder as BaseJsonEncoder;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;

/**
 * A JSON encoder with appropriate default options to embed the generated document into HTML.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class JsonEncoder implements EncoderInterface, DecoderInterface
{
    private $format;
    private $jsonEncoder;

    public function __construct(string $format, BaseJsonEncoder $jsonEncoder = null)
    {
        $this->format = $format;
        $this->jsonEncoder = $jsonEncoder;

        if (null !== $this->jsonEncoder) {
            return;
        }

        // Encode <, >, ', &, and " characters in the JSON, making it also safe to be embedded into HTML.
        $jsonEncodeOptions = \JSON_HEX_TAG | \JSON_HEX_APOS | \JSON_HEX_AMP | \JSON_HEX_QUOT | \JSON_UNESCAPED_UNICODE;
        if (interface_exists(AdvancedNameConverterInterface::class)) {
            $jsonEncode = new JsonEncode(['json_encode_options' => $jsonEncodeOptions]);
            $jsonDecode = new JsonDecode(['json_decode_associative' => true]);
        } else {
            $jsonEncode = new JsonEncode($jsonEncodeOptions);
            $jsonDecode = new JsonDecode(true);
        }

        $this->jsonEncoder = new BaseJsonEncoder($jsonEncode, $jsonDecode);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEncoding($format)
    {
        return $this->format === $format;
    }

    /**
     * {@inheritdoc}
     */
    public function encode($data, $format, array $context = [])
    {
        return $this->jsonEncoder->encode($data, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDecoding($format)
    {
        return $this->format === $format;
    }

    /**
     * {@inheritdoc}
     */
    public function decode($data, $format, array $context = [])
    {
        return $this->jsonEncoder->decode($data, $format, $context);
    }
}
