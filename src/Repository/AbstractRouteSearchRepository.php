<?php

namespace SamFreeze\SymfonyCrudBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method AbstractRouteSearch|null find($id, $lockMode = null, $lockVersion = null)
 * @method AbstractRouteSearch|null findOneBy(array $criteria, array $orderBy = null)
 * @method AbstractRouteSearch[]    findAll()
 * @method AbstractRouteSearch[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
abstract class AbstractRouteSearchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, $entity)
    {
        parent::__construct($registry, $entity);
    }
}
