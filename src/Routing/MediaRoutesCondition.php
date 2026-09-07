<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Routing;

use Symfony\Bundle\FrameworkBundle\Routing\Attribute\AsRoutingConditionService;

#[AsRoutingConditionService('neox_media')]
final readonly class MediaRoutesCondition
{
    public function __construct(
        private bool $routesEnabled,
    ) {
    }

    public function routesEnabled(): bool
    {
        return $this->routesEnabled;
    }
}
