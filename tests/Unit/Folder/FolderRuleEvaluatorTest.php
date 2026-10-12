<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Tests\Unit\Folder;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Enum\MediaType;
use Neox\MediaBundle\Folder\FolderRuleEvaluator;
use PHPUnit\Framework\TestCase;

final class FolderRuleEvaluatorTest extends TestCase
{
    private const ALLOWED = [
        'type', 'source', 'mime_prefix', 'owner', 'storage_dir',
        'filename_prefix', 'title_contains', 'created_after',
        'created_before', 'size_min', 'size_max', 'metadata',
    ];

    public function testTypeRule(): void
    {
        [$qb, $evaluator] = $this->makeQueryBuilder();
        $evaluator->apply($qb, ['type' => 'image']);

        self::assertStringContainsString('m.type =', $qb->getDQL());
        self::assertSame(MediaType::IMAGE, $qb->getParameters()->first()->getValue());
    }

    public function testMimePrefixRule(): void
    {
        [$qb, $evaluator] = $this->makeQueryBuilder();
        $evaluator->apply($qb, ['mime_prefix' => 'image/']);

        self::assertStringContainsString('m.mimeType LIKE', $qb->getDQL());
        self::assertSame('image/%', $qb->getParameters()->first()->getValue());
    }

    public function testStorageDirRule(): void
    {
        [$qb, $evaluator] = $this->makeQueryBuilder();
        $evaluator->apply($qb, ['storage_dir' => 'acme-corp']);

        self::assertStringContainsString('m.storageDir =', $qb->getDQL());
        self::assertSame('acme-corp', $qb->getParameters()->first()->getValue());
    }

    public function testFilenamePrefixRule(): void
    {
        [$qb, $evaluator] = $this->makeQueryBuilder();
        $evaluator->apply($qb, ['filename_prefix' => 'repertoire/produits/']);

        self::assertStringContainsString('m.filename LIKE', $qb->getDQL());
        self::assertSame('repertoire/produits/%', $qb->getParameters()->first()->getValue());
    }

    public function testUnknownRuleKeysAreIgnored(): void
    {
        [$qb, $evaluator] = $this->makeQueryBuilder();
        $evaluator->apply($qb, [
            'type' => 'image',
            'not_a_field' => 'm.id = 1 OR 1 = 1 --',
            'DROP TABLE neox_media' => 'x',
        ]);

        $dql = $qb->getDQL();
        self::assertStringContainsString('m.type =', $dql);
        self::assertStringNotContainsString('not_a_field', $dql);
        self::assertStringNotContainsString('DROP', $dql);
        self::assertCount(1, $qb->getParameters());
    }

    public function testNonWhitelistedFieldIsIgnored(): void
    {
        $evaluator = new FolderRuleEvaluator(['type']); // only 'type' allowed
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));
        $qb->select('m')->from(Media::class, 'm');

        $evaluator->apply($qb, ['storage_dir' => 'acme-corp']);

        self::assertStringNotContainsString('storageDir', $qb->getDQL());
    }

    public function testMetadataRuleProducesPortableLike(): void
    {
        [$qb, $evaluator] = $this->makeQueryBuilder();
        $evaluator->apply($qb, ['metadata' => ['role' => 'cover']]);

        self::assertStringContainsString('m.metadata LIKE', $qb->getDQL());
        self::assertSame('%"role":"cover"%', $qb->getParameters()->first()->getValue());
    }

    /** @return array{0: QueryBuilder, 1: FolderRuleEvaluator} */
    private function makeQueryBuilder(): array
    {
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));
        $qb->select('m')->from(Media::class, 'm');

        return [$qb, new FolderRuleEvaluator(self::ALLOWED)];
    }
}
