<?php

namespace SamFreeze\SymfonyCrudBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method AbstractRoutePagination|null find($id, $lockMode = null, $lockVersion = null)
 * @method AbstractRoutePagination|null findOneBy(array $criteria, array $orderBy = null)
 * @method AbstractRoutePagination[]    findAll()
 * @method AbstractRoutePagination[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AbstractRoutePaginationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, $entity)
    {
        parent::__construct($registry, $entity);
    }
}
