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

namespace ApiPlatform\Core\Tests\Behat;

use Behat\Behat\Context\Context;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class HttpCacheContext implements Context
{
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @Then :iris IRIs should be purged
     */
    public function irisShouldBePurged(string $iris)
    {
        $purger = $this->kernel->getContainer()->get('behat.driver.service_container')->get('test.api_platform.http_cache.purger');

        $purgedIris = implode(',', $purger->getIris());
        $purger->clear();

        if ($iris !== $purgedIris) {
            throw new ExpectationFailedException(sprintf('IRIs "%s" does not match expected "%s".', $purgedIris, $iris));
        }
    }

    /**
     * @Then :iris IRIs should be purged with xkey
     */
    public function irisShouldBePurgedWithXKey(string $iris)
    {
        $purger = $this->kernel->getContainer()->get('behat.driver.service_container')->get('test.api_platform.http_cache.purger.xkey');

        $purgedIris = implode(' ', $purger->getIris());
        $purger->clear();

        if ($iris !== $purgedIris) {
            throw new ExpectationFailedException(sprintf('IRIs "%s" does not match expected "%s".', $purgedIris, $iris));
        }
    }
}
