<?php

namespace SamFreeze\SymfonyCrudBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method AbstractRouteSort|null find($id, $lockMode = null, $lockVersion = null)
 * @method AbstractRouteSort|null findOneBy(array $criteria, array $orderBy = null)
 * @method AbstractRouteSort[]    findAll()
 * @method AbstractRouteSort[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AbstractRouteSortRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry, $entity)
    {
        parent::__construct($registry, $entity);
    }

}
