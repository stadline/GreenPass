<?php

declare(strict_types=1);

namespace Stadline\Resamania2Bundle\Lib\GreenPass\Doctrine\Repository;

use Doctrine\ORM\EntityRepository;
use Stadline\Resamania2Bundle\Lib\Date\Service\DateManipulator;
use Stadline\Resamania2Bundle\Lib\GreenPass\Doctrine\Entity\GreenPass;

class GreenPassRepository extends EntityRepository
{
    public function removeAllExpired(\DateTime $date = null): int
    {
        return $this->createQueryBuilder('gp')
            ->delete()
            ->andWhere('gp.validThrough <= :date')
            ->setParameter('date', $date ?? DateManipulator::createNow())
            ->getQuery()
            ->execute()
        ;
    }

    public function findContactGreenPass(string $contactId): ?GreenPass
    {
        $queryBuilder = $this->createQueryBuilder('gp');

        $queryBuilder
            ->andWhere('gp.validThrough >= :now')
            ->andWhere('gp.contactId = :contactId')
            ->setParameter('now', DateManipulator::createNow())
            ->setParameter('contactId', $contactId)
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
