<?php

declare(strict_types=1);

namespace Neox\MediaBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class NeoxMediaExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('neox_media.upload.max_sizes', $config['upload']['max_sizes']);
        $container->setParameter('neox_media.upload.allowed_mime', $config['upload']['allowed_mime']);
        $container->setParameter('neox_media.upload.allowed_extensions', $config['upload']['allowed_extensions']);
        $container->setParameter('neox_media.upload.sandbox.enabled', $config['upload']['sandbox']['enabled']);
        $container->setParameter('neox_media.upload.sandbox.mime', $config['upload']['sandbox']['mime']);
        $container->setParameter('neox_media.upload.sandbox.extensions', $config['upload']['sandbox']['extensions']);
        $container->setParameter('neox_media.url.allowed_schemes', $config['url']['allowed_schemes']);
        $container->setParameter('neox_media.embed.allowed_providers', $config['embed']['allowed_providers']);
        $container->setParameter('neox_media.storage.adapter', $config['storage']['adapter']);
        $container->setParameter('neox_media.storage.cdn_prefix', $config['storage']['cdn_prefix']);
        $container->setParameter('neox_media.routes.enabled', $config['routes']['enabled']);
        $container->setParameter('neox_media.routes.prefix', $config['routes']['prefix']);

        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/config'));
        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('liip_imagine')) {
            return;
        }

        $container->prependExtensionConfig('liip_imagine', [
            'filter_sets' => [
                'neox_media_thumb' => [
                    'quality' => 85,
                    'filters' => [
                        'thumbnail' => ['size' => [200, 200], 'mode' => 'outbound'],
                    ],
                ],
                'neox_media_small' => [
                    'quality' => 85,
                    'filters' => [
                        'thumbnail' => ['size' => [480, 480], 'mode' => 'inset'],
                    ],
                ],
                'neox_media_medium' => [
                    'quality' => 85,
                    'filters' => [
                        'thumbnail' => ['size' => [1024, 1024], 'mode' => 'inset'],
                    ],
                ],
                'neox_media_large' => [
                    'quality' => 90,
                    'filters' => [
                        'thumbnail' => ['size' => [1920, 1920], 'mode' => 'inset'],
                    ],
                ],
            ],
        ]);
    }

    public function getAlias(): string
    {
        return 'neox_media';
    }
}
