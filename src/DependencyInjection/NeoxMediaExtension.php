<?php

declare(strict_types=1);

namespace Neox\MediaBundle\DependencyInjection;

use Neox\MediaBundle\Ownership\MediaOwnerProviderInterface;
use Neox\MediaBundle\Ownership\NullMediaOwnerProvider;
use Neox\MediaBundle\Ownership\SecurityMediaOwnerProvider;
use Neox\MediaBundle\Storage\MediaDirectoryProviderInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
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
        $container->setParameter('neox_media.storage.directory.enabled', $config['storage']['directory']['enabled']);
        $container->setParameter('neox_media.ownership.enabled', $config['ownership']['enabled']);
        $container->setParameter('neox_media.ownership.default_visibility', $config['ownership']['default_visibility']);
        $container->setParameter('neox_media.ownership.admin_bypass_role', $config['ownership']['admin_bypass_role']);
        $container->setParameter('neox_media.folders.enabled', $config['folders']['enabled']);
        $container->setParameter('neox_media.folders.max_depth', $config['folders']['max_depth']);
        $container->setParameter('neox_media.folders.rules.allowed_fields', $config['folders']['rules']['allowed_fields']);
        $container->setParameter('neox_media.security.access_role', $config['security']['access_role']);
        $container->setParameter('neox_media.routes.enabled', $config['routes']['enabled']);
        $container->setParameter('neox_media.routes.prefix', $config['routes']['prefix']);

        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/config'));
        $loader->load('services.yaml');

        $this->configureOwnership($container, $config['ownership']);
        $this->configureStorageDirectory($container, $config['storage']['directory']);
    }

    /**
     * Choose the MediaOwnerProviderInterface implementation.
     *
     * Priority: explicit config provider > bundled Security provider (when
     * ownership is enabled) > null provider (global legacy mode).
     *
     * The host application may also simply register its own service
     * implementing the interface — the explicit alias still wins, so it
     * should instead point ownership.provider at its service id.
     *
     * @param array<string, mixed> $ownershipConfig
     */
    private function configureOwnership(ContainerBuilder $container, array $ownershipConfig): void
    {
        $provider = $ownershipConfig['provider'] ?? null;

        $target = $provider ?? ($ownershipConfig['enabled']
            ? SecurityMediaOwnerProvider::class
            : NullMediaOwnerProvider::class);

        $container->setAlias(MediaOwnerProviderInterface::class, $target);
    }

    /**
     * Wire the host-provided directory key resolver.
     *
     * Only registered when enabled — MediaUploadController accepts a nullable
     * provider so the bundle keeps working without the feature.
     *
     * @param array<string, mixed> $directoryConfig
     */
    private function configureStorageDirectory(ContainerBuilder $container, array $directoryConfig): void
    {
        if (!($directoryConfig['enabled'] ?? false)) {
            return;
        }

        $provider = $directoryConfig['provider'] ?? null;
        if ($provider === null) {
            throw new InvalidArgumentException(
                'neox_media.storage.directory.provider is required when storage.directory.enabled is true '
                .'(a service implementing '.MediaDirectoryProviderInterface::class.').'
            );
        }

        $container->setAlias(MediaDirectoryProviderInterface::class, $provider);
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
