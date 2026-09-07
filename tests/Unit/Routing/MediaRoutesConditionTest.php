<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Tests\Unit\Routing;

use Neox\MediaBundle\Routing\MediaRoutesCondition;
use PHPUnit\Framework\TestCase;

final class MediaRoutesConditionTest extends TestCase
{
    public function testRoutesCanBeEnabled(): void
    {
        self::assertTrue((new MediaRoutesCondition(true))->routesEnabled());
    }

    public function testRoutesCanBeDisabled(): void
    {
        self::assertFalse((new MediaRoutesCondition(false))->routesEnabled());
    }
}
