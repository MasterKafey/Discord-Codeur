<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class ChannelRepository extends EntityRepository
{
    public function findByIds($ids): array
    {
        $queryBuilder = $this->createQueryBuilder('channel');

        $queryBuilder
            ->where($queryBuilder->expr()->in('channel.channelId', ':ids'))
            ->setParameter('ids', $ids)
        ;

        return $queryBuilder->getQuery()->getResult();
    }
}