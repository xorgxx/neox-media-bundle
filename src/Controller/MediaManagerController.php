<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Neox\MediaBundle\Embed\EmbedProviderRegistry;
use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Entity\MediaCollection;
use Neox\MediaBundle\Entity\MediaCollectionItem;
use Neox\MediaBundle\Enum\MediaSource;
use Neox\MediaBundle\Enum\MediaType;
use Neox\MediaBundle\Repository\MediaCollectionRepository;
use Neox\MediaBundle\Repository\MediaRepository;
use Neox\MediaBundle\Service\MediaUrlGenerator;
use Neox\MediaBundle\Service\UrlValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('', name: 'neox_media_')]
#[IsGranted('ROLE_ADMIN')]
final class MediaManagerController extends AbstractController
{
    public function __construct(
        private readonly CsrfTokenManagerInterface $csrf,
        private readonly EmbedProviderRegistry $embedRegistry,
        private readonly UrlValidator $urlValidator,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(MediaCollectionRepository $collections): Response
    {
        return $this->render('@NeoxMedia/manager/index.html.twig', [
            'collections' => $collections->findBy([], ['name' => 'ASC']),
            'csrf_token' => $this->csrf->getToken('neox_media')->getValue(),
            'standalone' => true,
        ]);
    }

    #[Route('/picker', name: 'picker', methods: ['GET'])]
    public function picker(MediaCollectionRepository $collections): Response
    {
        return $this->render('@NeoxMedia/manager/_picker.html.twig', [
            'collections' => $collections->findBy([], ['name' => 'ASC']),
            'csrf_token' => $this->csrf->getToken('neox_media')->getValue(),
            'standalone' => false,
        ]);
    }

    #[Route('/api', name: 'api_list', methods: ['GET'])]
    public function apiList(
        Request $request,
        MediaRepository $repository,
        MediaUrlGenerator $urls,
    ): JsonResponse {
        $type = null;
        if ($request->query->get('type')) {
            $type = MediaType::tryFrom((string) $request->query->get('type'));
        }

        $limit = $request->query->getInt('limit', 60);
        $offset = $request->query->getInt('offset', 0);
        $sort = $request->query->get('sort', 'updatedAt');
        $q = $request->query->get('q');
        $collectionId = $request->query->getInt('collection') ?: null;

        $items = $repository->searchLibrary($q, $type, $limit, $offset, $sort, $collectionId);
        $total = $repository->countLibrary($q, $type, $collectionId);

        $data = [
            'items' => array_map(
                static fn (Media $m) => [
                    'id' => $m->getId(),
                    'uuid' => $m->getUuid(),
                    'title' => $m->getTitle(),
                    'alt' => $m->getAlt(),
                    'name' => $m->getOriginalName() ?? $m->getFilename(),
                    'type' => $m->getType()->value,
                    'source' => $m->getSource()->value,
                    'status' => $m->getStatus()->value,
                    'mimeType' => $m->getMimeType(),
                    'size' => $m->getSize(),
                    'url' => $urls->url($m),
                    'embedUrl' => $m->getEmbedUrl(),
                    'provider' => $m->getProvider(),
                    'thumbUrl' => $urls->filter($m, 'neox_media_thumb'),
                    'width' => $m->getWidth(),
                    'height' => $m->getHeight(),
                ],
                $items
            ),
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'hasMore' => ($offset + $limit) < $total,
        ];

        $etag = md5(serialize($data));
        $response = new JsonResponse($data);
        $response->setEtag($etag);
        $response->setCache([
            'private' => true,
            'max_age' => 10,
            's_maxage' => 0,
        ]);
        $response->isNotModified($request);

        return $response;
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'], priority: 10)]
    public function show(Media $media, MediaUrlGenerator $urls): JsonResponse
    {
        return $this->json([
            'id' => $media->getId(),
            'uuid' => $media->getUuid(),
            'title' => $media->getTitle(),
            'alt' => $media->getAlt(),
            'description' => $media->getDescription(),
            'name' => $media->getOriginalName() ?? $media->getFilename(),
            'type' => $media->getType()->value,
            'source' => $media->getSource()->value,
            'status' => $media->getStatus()->value,
            'mimeType' => $media->getMimeType(),
            'size' => $media->getSize(),
            'url' => $urls->url($media),
            'embedUrl' => $media->getEmbedUrl(),
            'provider' => $media->getProvider(),
            'width' => $media->getWidth(),
            'height' => $media->getHeight(),
            'duration' => $media->getDuration(),
            'metadata' => $media->getMetadata(),
            'createdAt' => $media->getCreatedAt()->format('c'),
            'updatedAt' => $media->getUpdatedAt()->format('c'),
        ]);
    }

    #[Route('/url', name: 'url_create', methods: ['POST'])]
    public function createUrl(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->csrf->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('neox_media', $request->headers->get('X-CSRF-Token')))) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $payload = json_decode($request->getContent(), true) ?: [];
        $url = trim((string) ($payload['url'] ?? ''));

        if ($url === '') {
            return $this->json(['error' => 'URL is required'], 422);
        }

        $error = $this->urlValidator->validateUrl($url);
        if ($error !== null) {
            return $this->json(['error' => $error], 422);
        }

        $media = new Media();
        $media->setSource(MediaSource::URL)
              ->setUrl($url)
              ->setType(MediaType::tryFrom((string) ($payload['type'] ?? 'document')) ?? MediaType::DOCUMENT)
              ->setTitle((string) ($payload['title'] ?? parse_url($url, PHP_URL_HOST) ?? 'External URL'));

        if (isset($payload['alt'])) {
            $media->setAlt($payload['alt']);
        }
        if (isset($payload['description'])) {
            $media->setDescription($payload['description']);
        }

        $em->persist($media);
        $em->flush();

        return $this->json([
            'id' => $media->getId(),
            'uuid' => $media->getUuid(),
            'url' => $media->getUrl(),
        ], 201);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function update(Media $media, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->csrf->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('neox_media', $request->headers->get('X-CSRF-Token')))) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $payload = json_decode($request->getContent(), true) ?: [];
        if (array_key_exists('title', $payload)) {
            $media->setTitle($payload['title']);
        }
        if (array_key_exists('alt', $payload)) {
            $media->setAlt($payload['alt']);
        }
        if (array_key_exists('description', $payload)) {
            $media->setDescription($payload['description']);
        }
        $media->setUpdatedAt();

        $em->flush();

        return $this->json(['ok' => true]);
    }

    #[Route('/bulk-delete', name: 'bulk_delete', methods: ['POST'])]
    public function bulkDelete(Request $request, EntityManagerInterface $em, MediaRepository $repository): JsonResponse
    {
        if (!$this->csrf->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('neox_media', $request->headers->get('X-CSRF-Token')))) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $payload = json_decode($request->getContent(), true) ?: [];
        $ids = $payload['ids'] ?? [];

        if (!is_array($ids) || $ids === []) {
            return $this->json(['error' => 'ids array is required'], 422);
        }

        $medias = $repository->findBy(['id' => $ids]);
        foreach ($medias as $media) {
            $em->remove($media);
        }
        $em->flush();

        return $this->json(['ok' => true, 'deleted' => count($medias)]);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Media $media, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->csrf->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('neox_media', $request->headers->get('X-CSRF-Token')))) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $em->remove($media);
        $em->flush();

        return $this->json(['ok' => true]);
    }

    #[Route('/collections', name: 'collection_create', methods: ['POST'])]
    public function createCollection(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->csrf->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('neox_media', $request->headers->get('X-CSRF-Token')))) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $payload = json_decode($request->getContent(), true) ?: [];
        $name = trim((string) ($payload['name'] ?? ''));

        if ($name === '') {
            return $this->json(['error' => 'Collection name is required'], 422);
        }

        $collection = (new MediaCollection())->setName($name);
        $em->persist($collection);
        $em->flush();

        return $this->json(['id' => $collection->getId(), 'name' => $collection->getName()], 201);
    }

    #[Route('/collections/{collection}/bulk-add', name: 'collection_bulk_add', methods: ['POST'])]
    public function bulkAddToCollection(
        MediaCollection $collection,
        Request $request,
        EntityManagerInterface $em,
        MediaRepository $repository,
    ): JsonResponse {
        if (!$this->csrf->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('neox_media', $request->headers->get('X-CSRF-Token')))) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $payload = json_decode($request->getContent(), true) ?: [];
        $ids = $payload['ids'] ?? [];

        if (!is_array($ids) || $ids === []) {
            return $this->json(['error' => 'ids array is required'], 422);
        }

        $medias = $repository->findBy(['id' => $ids]);
        $position = $collection->getItems()->count();
        $added = 0;
        foreach ($medias as $media) {
            $item = (new MediaCollectionItem())
                ->setCollection($collection)
                ->setMedia($media)
                ->setPosition($position++);
            $em->persist($item);
            ++$added;
        }
        $em->flush();

        return $this->json(['ok' => true, 'added' => $added], 201);
    }

    #[Route('/collections/{collection}/items/{media}', name: 'collection_add', methods: ['POST'])]
    public function addToCollection(
        MediaCollection $collection,
        Media $media,
        Request $request,
        EntityManagerInterface $em,
    ): JsonResponse {
        if (!$this->csrf->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('neox_media', $request->headers->get('X-CSRF-Token')))) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }
        $item = (new MediaCollectionItem())
            ->setCollection($collection)
            ->setMedia($media)
            ->setPosition($request->query->getInt('position', $collection->getItems()->count()));

        $em->persist($item);
        $em->flush();

        return $this->json(['ok' => true, 'itemId' => $item->getId()], 201);
    }

    #[Route('/collection-items/{id}', name: 'collection_remove', methods: ['DELETE'])]
    public function removeCollectionItem(MediaCollectionItem $item, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->csrf->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('neox_media', $request->headers->get('X-CSRF-Token')))) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $em->remove($item);
        $em->flush();

        return $this->json(['ok' => true]);
    }

    #[Route('/collections/{collection}/bulk-remove', name: 'collection_bulk_remove', methods: ['POST'])]
    public function bulkRemoveFromCollection(
        MediaCollection $collection,
        Request $request,
        EntityManagerInterface $em,
    ): JsonResponse {
        if (!$this->csrf->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('neox_media', $request->headers->get('X-CSRF-Token')))) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $payload = json_decode($request->getContent(), true) ?: [];
        $ids = $payload['ids'] ?? [];

        if (!is_array($ids) || $ids === []) {
            return $this->json(['error' => 'ids array is required'], 422);
        }

        $repo = $em->getRepository(MediaCollectionItem::class);
        $items = $repo->findBy([
            'collection' => $collection,
            'media' => $ids,
        ]);

        foreach ($items as $item) {
            $em->remove($item);
        }
        $em->flush();

        return $this->json(['ok' => true, 'removed' => count($items)]);
    }

    #[Route('/collections', name: 'collection_list', methods: ['GET'])]
    public function listCollections(MediaCollectionRepository $repo, Request $request): JsonResponse
    {
        $q = $request->query->get('q');
        $qb = $repo->createQueryBuilder('c')
            ->select('c', 'SIZE(c.items) AS itemCount')
            ->orderBy('c.name', 'ASC');

        if ($q) {
            $qb->andWhere('LOWER(c.name) LIKE :q')
               ->setParameter('q', '%'.mb_strtolower($q).'%');
        }

        $result = $qb->getQuery()->getResult();

        return $this->json([
            'items' => array_map(static fn (array $row) => [
                'id' => $row[0]->getId(),
                'name' => $row[0]->getName(),
                'itemCount' => (int) $row['itemCount'],
            ], $result),
        ]);
    }

    #[Route('/collections/{collection}', name: 'collection_show', methods: ['GET'])]
    public function showCollection(int $collection, MediaCollectionRepository $repo, MediaUrlGenerator $urls): JsonResponse
    {
        $entity = $repo->findWithItemsAndMedia($collection);
        if ($entity === null) {
            return $this->json(['error' => 'Collection not found'], 404);
        }

        $items = [];
        foreach ($entity->getItems() as $item) {
            $media = $item->getMedia();
            if ($media === null) {
                continue;
            }
            $items[] = [
                'itemId' => $item->getId(),
                'position' => $item->getPosition(),
                'id' => $media->getId(),
                'uuid' => $media->getUuid(),
                'title' => $media->getTitle(),
                'type' => $media->getType()->value,
                'source' => $media->getSource()->value,
                'status' => $media->getStatus()->value,
                'url' => $urls->url($media),
                'thumbUrl' => $urls->filter($media, 'neox_media_thumb'),
            ];
        }

        return $this->json([
            'id' => $entity->getId(),
            'name' => $entity->getName(),
            'items' => $items,
        ]);
    }

    #[Route('/collections/{collection}/reorder', name: 'collection_reorder', methods: ['POST'])]
    public function reorderCollection(MediaCollection $collection, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->csrf->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('neox_media', $request->headers->get('X-CSRF-Token')))) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $payload = json_decode($request->getContent(), true) ?: [];
        $ids = $payload['ids'] ?? [];

        if (!is_array($ids) || $ids === []) {
            return $this->json(['error' => 'ids array is required'], 422);
        }

        $itemsById = [];
        foreach ($collection->getItems() as $item) {
            $itemsById[$item->getId()] = $item;
        }

        $position = 0;
        foreach ($ids as $itemId) {
            if (isset($itemsById[$itemId])) {
                $itemsById[$itemId]->setPosition($position++);
            }
        }

        $em->flush();

        return $this->json(['ok' => true]);
    }

    #[Route('/embed', name: 'embed_create', methods: ['POST'])]
    public function createEmbed(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->csrf->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('neox_media', $request->headers->get('X-CSRF-Token')))) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $payload = json_decode($request->getContent(), true) ?: [];
        $url = trim((string) ($payload['url'] ?? ''));

        if ($url === '') {
            return $this->json(['error' => 'URL is required'], 422);
        }

        if (!$this->embedRegistry->supports($url)) {
            return $this->json(['error' => 'Unsupported embed provider'], 422);
        }

        $media = $this->embedRegistry->resolve($url);
        if ($media === null) {
            return $this->json(['error' => 'Unable to resolve embed URL'], 422);
        }

        $em->persist($media);
        $em->flush();

        return $this->json([
            'id' => $media->getId(),
            'uuid' => $media->getUuid(),
            'provider' => $media->getProvider(),
            'embedUrl' => $media->getEmbedUrl(),
        ], 201);
    }
}
