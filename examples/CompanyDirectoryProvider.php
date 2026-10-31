<?php

declare(strict_types=1);

namespace App\Media;

use Neox\MediaBundle\Storage\MediaDirectoryProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class HomeDirectoryProvider implements MediaDirectoryProviderInterface
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function getDirectoryKey(): ?string
    {
        $user = $this->security->getUser();

        if ($user === null) {
            return 'home';
        }

        if (method_exists($user, 'getCompany')) {
            $company = $user->getCompany();

            if ($company !== null) {
                $slug = method_exists($company, 'getSlug')
                    ? $company->getSlug()
                    : null;

                return is_string($slug) && $slug !== ''
                    ? 'home/'.$slug
                    : 'home/company-'.$company->getId();
            }
        }

        return 'home';
    }
}
