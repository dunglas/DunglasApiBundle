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

namespace ApiPlatform\Core\Security;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authorization\ExpressionLanguage as BaseExpressionLanguage;

/**
 * Adds some function to the default Symfony Security ExpressionLanguage.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @copyright Fabien Potencier <fabien@symfony.com>
 *
 * @see https://github.com/sensiolabs/SensioFrameworkExtraBundle/blob/master/Security/ExpressionLanguage.php
 */
class ExpressionLanguage extends BaseExpressionLanguage
{
    /**
     * {@inheritdoc}
     */
    public function __construct(CacheItemPoolInterface $cache = null, array $providers = [])
    {
        @trigger_error('Using the ExpressionLanguage class directly is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Use the "api_platform.security.expression_language" service instead.', \E_USER_DEPRECATED);

        parent::__construct($cache, $providers);
    }

    protected function registerFunctions()
    {
        parent::registerFunctions();

        $this->register('is_granted', static function ($attributes, $object = 'null') {
            return sprintf('$auth_checker->isGranted(%s, %s)', $attributes, $object);
        }, static function (array $variables, $attributes, $object = null) {
            return $variables['auth_checker']->isGranted($attributes, $object);
        });
    }
}
