<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Message\ProcessMediaMessage;
use Neox\MediaBundle\Service\UploadValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class MediaUploadController extends AbstractController
{
    public function __construct(
        private readonly CsrfTokenManagerInterface $csrf,
        private readonly UploadValidator $validator,
    ) {
    }

    #[Route('/upload', name: 'neox_media_upload', methods: ['POST'])]
    public function __invoke(
        Request $request,
        EntityManagerInterface $em,
        MessageBusInterface $bus,
    ): JsonResponse {
        if (!$this->csrf->isTokenValid(new CsrfToken('neox_media', $request->headers->get('X-CSRF-Token')))) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $files = $request->files->all('files');
        if (!$files) {
            $single = $request->files->get('file');
            $files = $single instanceof UploadedFile ? [$single] : [];
        }

        if (!$files) {
            $hint = 'No files received. Check upload_max_filesize and post_max_size in php.ini.';
            if ($request->headers->get('Content-Type') && str_contains($request->headers->get('Content-Type'), 'multipart/form-data')) {
                $hint = 'File too large: rejected by PHP (check upload_max_filesize / post_max_size).';
            }

            return $this->json(['error' => 'Missing files', 'details' => [$hint]], 400);
        }

        $created = [];
        $errors = [];

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $violation = $this->validator->validate($file);
            if ($violation !== null) {
                $errors[] = $violation;
                continue;
            }

            $media = new Media();
            $media->setFile($file);
            $media->setTitle(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $media->setType($this->validator->detectMediaType($file));

            $em->persist($media);
            $created[] = $media;
        }

        if ($created === [] && $errors !== []) {
            return $this->json(['error' => 'Upload rejected', 'details' => $errors], 422);
        }

        if ($created === []) {
            return $this->json(['error' => 'Missing files'], 400);
        }

        $em->flush();

        foreach ($created as $media) {
            $bus->dispatch(new ProcessMediaMessage($media->getId() ?? throw new \LogicException('Media ID is null after flush')));
        }

        $response = [
            'items' => array_map(
                static fn (Media $m) => ['id' => $m->getId(), 'uuid' => $m->getUuid()],
                $created
            ),
        ];
        if ($errors !== []) {
            $response['warnings'] = $errors;
        }

        return $this->json($response, 201);
    }
}
