<?php

namespace SamFreeze\SymfonyCrudBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method AbstractRouteSearchOperator|null find($id, $lockMode = null, $lockVersion = null)
 * @method AbstractRouteSearchOperator|null findOneBy(array $criteria, array $orderBy = null)
 * @method AbstractRouteSearchOperator[]    findAll()
 * @method AbstractRouteSearchOperator[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
abstract class AbstractRouteSearchOperatorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, $entity)
    {
        parent::__construct($registry, $entity);
    }
}
