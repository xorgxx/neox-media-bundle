<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Message;

final readonly class ProcessMediaMessage
{
    public function __construct(public int $mediaId)
    {
    }
}
