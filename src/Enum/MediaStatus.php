<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Enum;

enum MediaStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case READY = 'ready';
    case FAILED = 'failed';
}
