<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Neox\MediaBundle\Embed\EmbedProviderRegistry;
use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Entity\MediaCollection;
use Neox\MediaBundle\Entity\MediaCollectionItem;
use Neox\MediaBundle\Entity\MediaShare;
use Neox\MediaBundle\Enum\MediaSource;
use Neox\MediaBundle\Enum\MediaType;
use Neox\MediaBundle\Enum\MediaVisibility;
use Neox\MediaBundle\Ownership\MediaOwnerProviderInterface;
use Neox\MediaBundle\Repository\MediaCollectionRepository;
use Neox\MediaBundle\Repository\MediaRepository;
use Neox\MediaBundle\Repository\MediaShareRepository;
use Neox\MediaBundle\Security\MediaVoter;
use Neox\MediaBundle\Service\MediaUrlGenerator;
use Neox\MediaBundle\Service\UrlValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('', name: 'neox_media_')]
final class MediaManagerController extends AbstractController
{
    public function __construct(
        private readonly CsrfTokenManagerInterface $csrf,
        private readonly EmbedProviderRegistry $embedRegistry,
        private readonly UrlValidator $urlValidator,
        private readonly MediaOwnerProviderInterface $ownerProvider,
        private readonly MediaShareRepository $shareRepository,
        private readonly string $accessRole = 'ROLE_ADMIN',
        private readonly bool $ownershipEnabled = false,
        private readonly int $foldersMaxDepth = 5,
    ) {
    }

    /**
     * Gate to the Media Manager. The role is configurable via
     * neox_media.security.access_role ('PUBLIC_ACCESS' disables the check).
     */
    private function checkAccess(): void
    {
        $this->denyAccessUnlessGranted($this->accessRole);
    }

    /**
     * Current user identity for scoping: [ownerKey, subjectKeys].
     * Empty in legacy global mode (ownership disabled).
     *
     * @return array{0: ?string, 1: array<int, string>}
     */
    private function ownerContext(): array
    {
        if (!$this->ownershipEnabled) {
            return [null, []];
        }

        return [
            $this->ownerProvider->getCurrentOwnerKey(),
            $this->ownerProvider->getCurrentSubjectKeys(),
        ];
    }

    /**
     * Assign owner + default visibility on a freshly created entity
     * (media or collection). No-op when ownership is disabled or anonymous.
     */
    private function assignOwner(Media|MediaCollection $entity): void
    {
        if (!$this->ownershipEnabled) {
            return;
        }

        $ownerKey = $this->ownerProvider->getCurrentOwnerKey();
        if ($ownerKey !== null) {
            $entity->setOwner($ownerKey);
        }
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, MediaCollectionRepository $collections, MediaRepository $mediaRepository): Response
    {
        $this->checkAccess();
        [$ownerKey, $subjectKeys] = $this->ownerContext();

        return $this->render('@NeoxMedia/manager/index.html.twig', [
            'collections' => $collections->findBy([], ['name' => 'ASC']),
            'sandbox_count' => $mediaRepository->countSandboxed($ownerKey, $subjectKeys),
            'csrf_token' => $this->csrf->getToken('neox_media')->getValue(),
            'return_url' => $this->resolveReturnUrl($request),
            'standalone' => true,
        ]);
    }

    #[Route('/picker', name: 'picker', methods: ['GET'])]
    public function picker(MediaCollectionRepository $collections, MediaRepository $mediaRepository): Response
    {
        $this->checkAccess();
        [$ownerKey, $subjectKeys] = $this->ownerContext();

        return $this->render('@NeoxMedia/manager/_picker.html.twig', [
            'collections' => $collections->findBy([], ['name' => 'ASC']),
            'sandbox_count' => $mediaRepository->countSandboxed($ownerKey, $subjectKeys),
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
        $this->checkAccess();

        $type = null;
        if ($request->query->get('type')) {
            $type = MediaType::tryFrom((string) $request->query->get('type'));
        }

        $limit = max(1, min($request->query->getInt('limit', 60), 200));
        $offset = max(0, $request->query->getInt('offset', 0));
        $sort = (string) $request->query->get('sort', 'updatedAt');
        if (!in_array($sort, ['updatedAt', 'createdAt', 'title', 'size'], true)) {
            $sort = 'updatedAt';
        }
        $q = $request->query->get('q');
        $collectionId = $request->query->getInt('collection') ?: null;
        $sandboxed = $request->query->getBoolean('sandboxed') ? true : null;

        $scope = (string) $request->query->get('scope', 'all');
        if (!in_array($scope, ['all', 'mine', 'shared'], true)) {
            $scope = 'all';
        }
        [$ownerKey, $subjectKeys] = $this->ownershipEnabled
            ? $this->ownerContext()
            : [null, []];

        $items = $repository->searchLibrary($q, $type, $limit, $offset, $sort, $collectionId, $sandboxed, $ownerKey, $subjectKeys, $scope);
        $total = $repository->countLibrary($q, $type, $collectionId, $sandboxed, $ownerKey, $subjectKeys, $scope);

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
                    'sandboxed' => $m->isSandboxed(),
                    'riskLevel' => $m->getRiskLevel()->value,
                    'riskReason' => $m->getRiskReason(),
                    'mimeType' => $m->getMimeType(),
                    'size' => $m->getSize(),
                    'url' => $urls->url($m),
                    'embedUrl' => $m->getEmbedUrl(),
                    'provider' => $m->getProvider(),
                    'thumbUrl' => $urls->filter($m, 'neox_media_thumb'),
                    'width' => $m->getWidth(),
                    'height' => $m->getHeight(),
                    'owner' => $m->getOwner(),
                    'visibility' => $m->getVisibility()->value,
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
        $this->checkAccess();
        $this->denyAccessUnlessGranted(MediaVoter::VIEW, $media);

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
            'sandboxed' => $media->isSandboxed(),
            'riskLevel' => $media->getRiskLevel()->value,
            'riskReason' => $media->getRiskReason(),
            'mimeType' => $media->getMimeType(),
            'size' => $media->getSize(),
            'url' => $urls->url($media),
            'embedUrl' => $media->getEmbedUrl(),
            'provider' => $media->getProvider(),
            'width' => $media->getWidth(),
            'height' => $media->getHeight(),
            'duration' => $media->getDuration(),
            'metadata' => $media->getMetadata(),
            'owner' => $media->getOwner(),
            'visibility' => $media->getVisibility()->value,
            'createdAt' => $media->getCreatedAt()->format('c'),
            'updatedAt' => $media->getUpdatedAt()->format('c'),
        ]);
    }

    #[Route('/url', name: 'url_create', methods: ['POST'])]
    public function createUrl(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->checkAccess();

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
        $this->assignOwner($media);

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
        $this->checkAccess();
        $this->denyAccessUnlessGranted(MediaVoter::EDIT, $media);

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
        if (array_key_exists('visibility', $payload)) {
            $visibility = MediaVisibility::tryFrom((string) $payload['visibility']);
            if ($visibility === null) {
                return $this->json(['error' => 'Invalid visibility (private|shared|public)'], 422);
            }
            $media->setVisibility($visibility);
        }
        $media->setUpdatedAt();

        $em->flush();

        return $this->json(['ok' => true]);
    }

    #[Route('/bulk-delete', name: 'bulk_delete', methods: ['POST'])]
    public function bulkDelete(Request $request, EntityManagerInterface $em, MediaRepository $repository): JsonResponse
    {
        $this->checkAccess();

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
            $this->denyAccessUnlessGranted(MediaVoter::DELETE, $media);
            $em->remove($media);
        }
        $em->flush();

        return $this->json(['ok' => true, 'deleted' => count($medias)]);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Media $media, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->checkAccess();
        $this->denyAccessUnlessGranted(MediaVoter::DELETE, $media);

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
        $this->checkAccess();

        if (!$this->csrf->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('neox_media', $request->headers->get('X-CSRF-Token')))) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $payload = json_decode($request->getContent(), true) ?: [];
        $name = trim((string) ($payload['name'] ?? ''));

        if ($name === '') {
            return $this->json(['error' => 'Collection name is required'], 422);
        }

        $collection = (new MediaCollection())->setName($name);
        $this->assignOwner($collection);
        $error = $this->applyCollectionPayload($collection, $payload, $em);
        if ($error !== null) {
            return $this->json(['error' => $error], 422);
        }

        $em->persist($collection);
        $em->flush();

        return $this->json([
            'id' => $collection->getId(),
            'name' => $collection->getName(),
            'parent' => $collection->getParent()?->getId(),
            'dynamic' => $collection->isDynamic(),
            'visibility' => $collection->getVisibility()->value,
        ], 201);
    }

    #[Route('/collections/{collection}', name: 'collection_update', methods: ['PATCH'])]
    public function updateCollection(
        MediaCollection $collection,
        Request $request,
        EntityManagerInterface $em,
    ): JsonResponse {
        $this->checkAccess();
        $this->denyAccessUnlessGranted(MediaVoter::EDIT, $collection);

        if (!$this->csrf->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('neox_media', $request->headers->get('X-CSRF-Token')))) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $payload = json_decode($request->getContent(), true) ?: [];
        if (array_key_exists('name', $payload)) {
            $name = trim((string) $payload['name']);
            if ($name === '') {
                return $this->json(['error' => 'Collection name cannot be empty'], 422);
            }
            $collection->setName($name);
        }

        $error = $this->applyCollectionPayload($collection, $payload, $em);
        if ($error !== null) {
            return $this->json(['error' => $error], 422);
        }

        $em->flush();

        return $this->json(['ok' => true]);
    }

    /**
     * Apply folder-related fields from a JSON payload: parent, dynamic,
     * rules, visibility. Returns an error message or null.
     *
     * @param array<string, mixed> $payload
     */
    private function applyCollectionPayload(MediaCollection $collection, array $payload, EntityManagerInterface $em): ?string
    {
        if (array_key_exists('parent', $payload)) {
            $parent = null;
            if ($payload['parent'] !== null) {
                $parent = $em->find(MediaCollection::class, (int) $payload['parent']);
                if ($parent === null) {
                    return 'Parent collection not found.';
                }
                if ($parent === $collection) {
                    return 'A collection cannot be its own parent.';
                }
                // Cycle guard: walk up the ancestors.
                for ($node = $parent; $node !== null; $node = $node->getParent()) {
                    if ($node === $collection) {
                        return 'Cycle detected: parent is a descendant of this collection.';
                    }
                }
            }
            $collection->setParent($parent);
        }

        if (array_key_exists('dynamic', $payload)) {
            $collection->setDynamic((bool) $payload['dynamic']);
        }

        if (array_key_exists('rules', $payload)) {
            if (!is_array($payload['rules'])) {
                return 'rules must be an object.';
            }
            $collection->setRules($payload['rules']);
        }

        if (array_key_exists('visibility', $payload)) {
            $visibility = MediaVisibility::tryFrom((string) $payload['visibility']);
            if ($visibility === null) {
                return 'Invalid visibility (private|shared|public).';
            }
            $collection->setVisibility($visibility);
        }

        return null;
    }

    #[Route('/collections/{collection}/bulk-add', name: 'collection_bulk_add', methods: ['POST'])]
    public function bulkAddToCollection(
        MediaCollection $collection,
        Request $request,
        EntityManagerInterface $em,
        MediaRepository $repository,
    ): JsonResponse {
        $this->checkAccess();
        $this->denyAccessUnlessGranted(MediaVoter::EDIT, $collection);

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
        $this->checkAccess();
        $this->denyAccessUnlessGranted(MediaVoter::EDIT, $collection);

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
        $this->checkAccess();
        if ($item->getCollection() !== null) {
            $this->denyAccessUnlessGranted(MediaVoter::EDIT, $item->getCollection());
        }

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
        $this->checkAccess();
        $this->denyAccessUnlessGranted(MediaVoter::EDIT, $collection);

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
        $this->checkAccess();
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
            'items' => array_values(array_map(
                fn (array $row) => [
                    'id' => $row[0]->getId(),
                    'name' => $row[0]->getName(),
                    'path' => $row[0]->getPath(),
                    'dynamic' => $row[0]->isDynamic(),
                    'visibility' => $row[0]->getVisibility()->value,
                    'itemCount' => (int) $row['itemCount'],
                ],
                array_filter(
                    $result,
                    fn (array $row) => $this->isGranted(MediaVoter::VIEW, $row[0])
                )
            )),
        ]);
    }

    #[Route('/collections/{collection}', name: 'collection_show', methods: ['GET'])]
    public function showCollection(int $collection, MediaCollectionRepository $repo, MediaUrlGenerator $urls): JsonResponse
    {
        $entity = $repo->findWithItemsAndMedia($collection);
        if ($entity === null) {
            return $this->json(['error' => 'Collection not found'], 404);
        }

        $this->checkAccess();
        $this->denyAccessUnlessGranted(MediaVoter::VIEW, $entity);

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
            'path' => $entity->getPath(),
            'dynamic' => $entity->isDynamic(),
            'rules' => $entity->getRules(),
            'visibility' => $entity->getVisibility()->value,
            'items' => $items,
        ]);
    }

    #[Route('/collections/{collection}/reorder', name: 'collection_reorder', methods: ['POST'])]
    public function reorderCollection(MediaCollection $collection, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->checkAccess();
        $this->denyAccessUnlessGranted(MediaVoter::EDIT, $collection);

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

    private function resolveReturnUrl(Request $request): ?string
    {
        $returnUrl = $request->query->get('return_url') ?: $request->query->get('returnUrl');
        if (!is_string($returnUrl) || $returnUrl === '') {
            return null;
        }

        if (!str_starts_with($returnUrl, '/') || str_starts_with($returnUrl, '//')) {
            return null;
        }

        return $returnUrl;
    }

    #[Route('/embed', name: 'embed_create', methods: ['POST'])]
    public function createEmbed(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->checkAccess();

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
        $this->assignOwner($media);

        $em->persist($media);
        $em->flush();

        return $this->json([
            'id' => $media->getId(),
            'uuid' => $media->getUuid(),
            'provider' => $media->getProvider(),
            'embedUrl' => $media->getEmbedUrl(),
        ], 201);
    }

    // ---------------------------------------------------------------------
    // Shares (ACL)
    // ---------------------------------------------------------------------

    #[Route('/shares', name: 'share_list', methods: ['GET'])]
    public function listShares(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->checkAccess();

        $targetType = (string) $request->query->get('target_type', '');
        $targetId = $request->query->getInt('target_id');
        $target = $this->resolveShareTarget($targetType, $targetId, $em);
        if ($target === null) {
            return $this->json(['error' => 'Target not found'], 404);
        }
        $this->denyAccessUnlessGranted(MediaVoter::VIEW, $target);

        $shares = $this->shareRepository->findBy(
            ['targetType' => $targetType, 'targetId' => $targetId],
            ['id' => 'ASC']
        );

        return $this->json([
            'items' => array_map(static fn (MediaShare $s) => [
                'id' => $s->getId(),
                'subjectType' => $s->getSubjectType(),
                'subjectKey' => $s->getSubjectKey(),
                'canEdit' => $s->canEdit(),
                'canReshare' => $s->canReshare(),
            ], $shares),
        ]);
    }

    #[Route('/shares', name: 'share_create', methods: ['POST'])]
    public function createShare(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->checkAccess();

        if (!$this->csrf->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('neox_media', $request->headers->get('X-CSRF-Token')))) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $payload = json_decode($request->getContent(), true) ?: [];
        $targetType = (string) ($payload['target_type'] ?? '');
        $targetId = (int) ($payload['target_id'] ?? 0);
        $subjectType = (string) ($payload['subject_type'] ?? '');
        $subjectKey = trim((string) ($payload['subject_key'] ?? ''));

        if (!in_array($targetType, [MediaShare::TARGET_MEDIA, MediaShare::TARGET_COLLECTION], true)) {
            return $this->json(['error' => 'Invalid target_type (media|collection)'], 422);
        }
        if (!in_array($subjectType, [
            MediaShare::SUBJECT_USER,
            MediaShare::SUBJECT_ROLE,
            MediaShare::SUBJECT_TEAM,
            MediaShare::SUBJECT_EVERYONE,
        ], true)) {
            return $this->json(['error' => 'Invalid subject_type (user|role|team|everyone)'], 422);
        }
        if ($subjectType === MediaShare::SUBJECT_EVERYONE) {
            $subjectKey = '*';
        } elseif ($subjectKey === '') {
            return $this->json(['error' => 'subject_key is required'], 422);
        }

        $target = $this->resolveShareTarget($targetType, $targetId, $em);
        if ($target === null) {
            return $this->json(['error' => 'Target not found'], 404);
        }
        $this->denyAccessUnlessGranted(MediaVoter::SHARE, $target);

        // Upsert: the unique constraint prevents duplicates — update flags instead.
        $share = $this->shareRepository->findOneBy([
            'targetType' => $targetType,
            'targetId' => $targetId,
            'subjectType' => $subjectType,
            'subjectKey' => $subjectKey,
        ]);

        if ($share === null) {
            $share = (new MediaShare())
                ->setTargetType($targetType)
                ->setTargetId($targetId)
                ->setSubjectType($subjectType)
                ->setSubjectKey($subjectKey);
            $em->persist($share);
        }

        $share->setCanEdit((bool) ($payload['can_edit'] ?? false))
              ->setCanReshare((bool) ($payload['can_reshare'] ?? false));

        $em->flush();

        return $this->json(['id' => $share->getId()], 201);
    }

    #[Route('/shares/{id}', name: 'share_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deleteShare(MediaShare $share, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->checkAccess();

        if (!$this->csrf->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('neox_media', $request->headers->get('X-CSRF-Token')))) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $target = $this->resolveShareTarget($share->getTargetType(), $share->getTargetId(), $em);
        if ($target === null) {
            return $this->json(['error' => 'Target not found'], 404);
        }
        $this->denyAccessUnlessGranted(MediaVoter::SHARE, $target);

        $em->remove($share);
        $em->flush();

        return $this->json(['ok' => true]);
    }

    private function resolveShareTarget(string $targetType, int $targetId, EntityManagerInterface $em): Media|MediaCollection|null
    {
        return match ($targetType) {
            MediaShare::TARGET_MEDIA => $em->find(Media::class, $targetId),
            MediaShare::TARGET_COLLECTION => $em->find(MediaCollection::class, $targetId),
            default => null,
        };
    }

    // ---------------------------------------------------------------------
    // Folder tree
    // ---------------------------------------------------------------------

    #[Route('/folders/tree', name: 'folders_tree', methods: ['GET'])]
    public function foldersTree(MediaCollectionRepository $repo): JsonResponse
    {
        $this->checkAccess();

        return $this->json([
            'items' => array_values(array_map(
                fn (MediaCollection $c) => $this->serializeCollectionNode($c, 0),
                array_filter(
                    $repo->findRoots(),
                    fn (MediaCollection $c) => $this->isGranted(MediaVoter::VIEW, $c)
                )
            )),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCollectionNode(MediaCollection $collection, int $depth): array
    {
        $node = [
            'id' => $collection->getId(),
            'name' => $collection->getName(),
            'path' => $collection->getPath(),
            'dynamic' => $collection->isDynamic(),
            'visibility' => $collection->getVisibility()->value,
            'owner' => $collection->getOwner(),
            'children' => [],
        ];

        if ($depth < $this->foldersMaxDepth) {
            foreach ($collection->getChildren() as $child) {
                if ($this->isGranted(MediaVoter::VIEW, $child)) {
                    $node['children'][] = $this->serializeCollectionNode($child, $depth + 1);
                }
            }
        }

        return $node;
    }
}
