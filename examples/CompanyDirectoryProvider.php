<?php

declare(strict_types=1);

namespace App\Media;

use Neox\MediaBundle\Storage\MediaDirectoryProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Example 1 — one physical directory per company.
 *
 * Files land in public/uploads/media/<company-slug>/ instead of the flat
 * upload root. The bundle persists the key in Media::storageDir so URL
 * generation stays stable whoever generates it later.
 *
 * Adapt the "company" accessor to your domain (getCompany, getTenant,
 * getOrganization...) — the bundle does not know your entities.
 *
 * Wire it:
 *
 *     neox_media:
 *         storage:
 *             directory:
 *                 enabled: true
 *                 provider: App\Media\CompanyDirectoryProvider
 *
 *     vich_uploader:
 *         mappings:
 *             neox_media:
 *                 directory_namer:
 *                     service: Neox\MediaBundle\Storage\SubjectDirectoryNamer
 */
final class CompanyDirectoryProvider implements MediaDirectoryProviderInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function getDirectoryKey(): ?string
    {
        $user = $this->security->getUser();

        // Replace with your own accessor — e.g. $user->getCompany().
        if ($user === null || !method_exists($user, 'getCompany')) {
            return null; // stored at upload root (flat layout)
        }

        $company = $user->getCompany();
        if ($company === null) {
            return null;
        }

        // Prefer an existing slug property; fall back to the id.
        return method_exists($company, 'getSlug') && $company->getSlug() !== null
            ? (string) $company->getSlug()
            : 'company-'.$company->getId();
    }
}
