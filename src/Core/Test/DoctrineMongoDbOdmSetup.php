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

namespace ApiPlatform\Core\Test;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Mapping\Driver\XmlDriver;

/**
 * Convenience class for setting up Doctrine from different installations and configurations.
 *
 * @internal
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class DoctrineMongoDbOdmSetup
{
    /**
     * Creates a configuration with an annotation metadata driver.
     */
    public static function createAnnotationMetadataConfiguration(array $paths, bool $isDevMode = false, string $proxyDir = null, string $hydratorDir = null, Cache $cache = null): Configuration
    {
        $config = self::createConfiguration($isDevMode, $proxyDir, $hydratorDir, $cache);
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver($paths));

        return $config;
    }

    /**
     * Creates a configuration with a xml metadata driver.
     */
    public static function createXMLMetadataConfiguration(array $paths, bool $isDevMode = false, string $proxyDir = null, string $hydratorDir = null, Cache $cache = null): Configuration
    {
        $config = self::createConfiguration($isDevMode, $proxyDir, $hydratorDir, $cache);
        $config->setMetadataDriverImpl(new XmlDriver($paths));

        return $config;
    }

    /**
     * Creates a configuration without a metadata driver.
     */
    public static function createConfiguration(bool $isDevMode = false, string $proxyDir = null, string $hydratorDir = null, Cache $cache = null): Configuration
    {
        $proxyDir = $proxyDir ?: sys_get_temp_dir();
        $hydratorDir = $hydratorDir ?: sys_get_temp_dir();

        $cache = self::createCacheConfiguration($isDevMode, $proxyDir, $hydratorDir, $cache);

        $config = new Configuration();
        if (method_exists($config, 'setMetadataCache')) {
            $config->setMetadataCache($cache);
        } else {
            $config->setMetadataCacheImpl($cache);
        }
        $config->setProxyDir($proxyDir);
        $config->setHydratorDir($hydratorDir);
        $config->setProxyNamespace('DoctrineProxies');
        $config->setHydratorNamespace('DoctrineHydrators');
        $config->setAutoGenerateProxyClasses($isDevMode ? Configuration::AUTOGENERATE_EVAL : Configuration::AUTOGENERATE_FILE_NOT_EXISTS);

        return $config;
    }

    private static function createCacheConfiguration(bool $isDevMode, string $proxyDir, string $hydratorDir, ?Cache $cache): Cache
    {
        $cache = self::createCacheInstance($isDevMode, $cache);

        if (!$cache instanceof CacheProvider) {
            return $cache;
        }

        $namespace = $cache->getNamespace();

        if ('' !== $namespace) {
            $namespace .= ':';
        }

        $cache->setNamespace($namespace.'dc2_'.md5($proxyDir.$hydratorDir).'_'); // to avoid collisions

        return $cache;
    }

    private static function createCacheInstance(bool $isDevMode, ?Cache $cache): Cache
    {
        if (null !== $cache) {
            return $cache;
        }

        if (true === $isDevMode) {
            return new ArrayCache();
        }

        if (\extension_loaded('apcu')) {
            return new \Doctrine\Common\Cache\ApcuCache();
        }

        return new ArrayCache();
    }
}
