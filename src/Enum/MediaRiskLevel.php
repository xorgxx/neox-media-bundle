<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Enum;

enum MediaRiskLevel: string
{
    case SAFE = 'safe';
    case SUSPICIOUS = 'suspicious';
    case BLOCKED = 'blocked';
}
