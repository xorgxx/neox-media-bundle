<?php

declare(strict_types=1);

namespace Neox\MediaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('neox_media');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('upload')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('max_sizes')
                            ->info('Max file size in bytes per media type group. "default" is the fallback.')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->integerNode('image')->defaultValue(4194304)->end()
                                ->integerNode('video')->defaultValue(209715200)->end()
                                ->integerNode('audio')->defaultValue(52428800)->end()
                                ->integerNode('document')->defaultValue(2097152)->end()
                                ->integerNode('archive')->defaultValue(10485760)->end()
                                ->integerNode('default')->defaultValue(10485760)->end()
                            ->end()
                        ->end()
                        ->arrayNode('allowed_mime')
                            ->info('Whitelist of allowed MIME types (real MIME, not client-provided).')
                            ->scalarPrototype()->end()
                            ->defaultValue([
                                'image/jpeg',
                                'image/png',
                                'image/gif',
                                'image/webp',
                                'image/svg+xml',
                                'image/avif',
                                'video/mp4',
                                'video/webm',
                                'video/ogg',
                                'audio/mpeg',
                                'audio/ogg',
                                'audio/wav',
                                'audio/webm',
                                'application/pdf',
                                'application/zip',
                                'text/plain',
                                'text/csv',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                            ])
                        ->end()
                        ->arrayNode('allowed_extensions')
                            ->info('Whitelist of allowed file extensions.')
                            ->scalarPrototype()->end()
                            ->defaultValue([
                                'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif',
                                'mp4', 'webm', 'mov', 'ogv',
                                'mp3', 'ogg', 'wav', 'weba',
                                'pdf', 'zip',
                                'txt', 'csv',
                                'docx', 'xlsx', 'pptx',
                            ])
                        ->end()
                        ->arrayNode('sandbox')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')
                                    ->info('Whether suspicious-but-allowed uploads are marked as sandboxed and withheld from public URLs.')
                                    ->defaultTrue()
                                ->end()
                                ->arrayNode('mime')
                                    ->info('Allowed MIME types that require sandbox review before public use.')
                                    ->scalarPrototype()->end()
                                    ->defaultValue(['image/svg+xml', 'application/pdf', 'application/zip'])
                                ->end()
                                ->arrayNode('extensions')
                                    ->info('Allowed file extensions that require sandbox review before public use.')
                                    ->scalarPrototype()->end()
                                    ->defaultValue(['svg', 'pdf', 'zip'])
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('url')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('allowed_schemes')
                            ->info('Allowed URL schemes for source URL and EMBED.')
                            ->scalarPrototype()->end()
                            ->defaultValue(['http', 'https'])
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('embed')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('allowed_providers')
                            ->info('Whitelist of allowed embed provider hosts.')
                            ->scalarPrototype()->end()
                            ->defaultValue([
                                'youtube.com',
                                'youtu.be',
                                'www.youtube.com',
                                'vimeo.com',
                                'player.vimeo.com',
                                'www.dailymotion.com',
                                'dailymotion.com',
                                'dai.ly',
                                'soundcloud.com',
                                'w.soundcloud.com',
                                'www.slideshare.net',
                            ])
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('storage')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('adapter')
                            ->info('Storage adapter for REMOTE source. "filesystem" uses the bundled FilesystemStorageAdapter. Set to null to disable (fallback to Media.url).')
                            ->defaultValue('filesystem')
                        ->end()
                        ->scalarNode('cdn_prefix')
                            ->info('CDN prefix for the filesystem adapter (e.g. "https://cdn.example.com/media"). Empty = local /uploads/media/.')
                            ->defaultValue('')
                        ->end()
                        ->arrayNode('directory')
                            ->info('Physical subdirectory per uploader context (e.g. one directory per company). Disabled = flat layout.')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')
                                    ->defaultFalse()
                                ->end()
                                ->scalarNode('provider')
                                    ->info('Service id implementing Neox\MediaBundle\Storage\MediaDirectoryProviderInterface. Required when enabled.')
                                    ->defaultNull()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('ownership')
                    ->info('Multi-user ownership: owner key + visibility + MediaShare ACL. Disabled = global media (legacy behaviour).')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultFalse()
                        ->end()
                        ->enumNode('default_visibility')
                            ->info('Visibility assigned to newly created media.')
                            ->values(['private', 'shared', 'public'])
                            ->defaultValue('private')
                        ->end()
                        ->scalarNode('admin_bypass_role')
                            ->info('Role allowed to view/edit/delete every media. Empty string disables the bypass.')
                            ->defaultValue('ROLE_ADMIN')
                        ->end()
                        ->scalarNode('provider')
                            ->info('Service id implementing MediaOwnerProviderInterface. Null = bundled Security-based provider.')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('folders')
                    ->info('Collection tree and dynamic (rule-based) smart folders.')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultFalse()
                        ->end()
                        ->integerNode('max_depth')
                            ->info('Maximum depth of the collection tree.')
                            ->defaultValue(5)
                        ->end()
                        ->arrayNode('rules')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('allowed_fields')
                                    ->info('Whitelist of rule fields FolderRuleEvaluator will translate to DQL.')
                                    ->scalarPrototype()->end()
                                    ->defaultValue([
                                        'type', 'source', 'mime_prefix', 'owner',
                                        'storage_dir', 'filename_prefix',
                                        'title_contains', 'created_after',
                                        'created_before', 'size_min', 'size_max',
                                        'metadata',
                                    ])
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('security')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('access_role')
                            ->info('Role required to open the Media Manager (attribute checked per action). Use "PUBLIC_ACCESS" to disable.')
                            ->defaultValue('ROLE_ADMIN')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('routes')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->info('Whether the bundle routes (Media Manager, upload, API) are loaded. Set to false to use only the services and wire your own routes.')
                            ->defaultValue(true)
                        ->end()
                        ->scalarNode('prefix')
                            ->info('Route prefix for all bundle routes. Default: /admin/media')
                            ->defaultValue('/admin/media')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
