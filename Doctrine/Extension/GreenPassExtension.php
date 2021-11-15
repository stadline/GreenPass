<?php

declare(strict_types=1);

namespace Stadline\Resamania2Bundle\Lib\GreenPass\Doctrine\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Stadline\Resamania2Bundle\Lib\Date\Service\DateManipulator;
use Stadline\Resamania2Bundle\Lib\GreenPass\Doctrine\Entity\GreenPass;

class GreenPassExtension implements QueryCollectionExtensionInterface
{
    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null,
        array $context = []
    ): void {
        $this->addWhere($queryBuilder, $queryNameGenerator, $resourceClass);
    }

    private function addWhere(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass
    ): void {
        if (!$this->isSupported($resourceClass)) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->andWhere(sprintf('%s.validThrough > :now', $rootAlias))
            ->setParameter('now', DateManipulator::createNow())
        ;
    }

    private function isSupported(string $resourceClass): bool
    {
        return GreenPass::class === $resourceClass;
    }
}
