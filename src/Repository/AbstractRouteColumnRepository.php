<?php

namespace SamFreeze\SymfonyCrudBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method AbstractRouteColumn|null find($id, $lockMode = null, $lockVersion = null)
 * @method AbstractRouteColumn|null findOneBy(array $criteria, array $orderBy = null)
 * @method AbstractRouteColumn[]    findAll()
 * @method AbstractRouteColumn[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
abstract class AbstractRouteColumnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, $entity)
    {
        parent::__construct($registry, $entity);
    }
}
