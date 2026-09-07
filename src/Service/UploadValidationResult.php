<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Service;

use Neox\MediaBundle\Enum\MediaRiskLevel;

final readonly class UploadValidationResult
{
    public function __construct(
        public MediaRiskLevel $riskLevel,
        public ?string $message = null,
    ) {
    }

    public function isBlocked(): bool
    {
        return $this->riskLevel === MediaRiskLevel::BLOCKED;
    }

    public function isSandboxed(): bool
    {
        return $this->riskLevel === MediaRiskLevel::SUSPICIOUS;
    }
}
