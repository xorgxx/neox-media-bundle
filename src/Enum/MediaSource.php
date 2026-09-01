<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Enum;

enum MediaSource: string
{
    case FILE = 'file';
    case URL = 'url';
    case EMBED = 'embed';
    case REMOTE = 'remote';
}
