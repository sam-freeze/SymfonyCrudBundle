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
    
    public function findValue($criteria) {
    	$qb = $this->createQueryBuilder('s')
			->select('s');
    	
    	$qb->where($qb->expr()->like('s.field', ':s_field'))
			->setParameter('s_field', '%_value');
    	
    	foreach ($criteria as $k => $v) {
			$qb->andWhere($qb->expr()->eq("s.$k", ":s_$k"))
				->setParameter("s_$k", $v);
		}
		
		return $qb->getQuery()
			->getResult();
	}
	
	public function findOperator($criteria) {
		$qb = $this->createQueryBuilder('s')
		->select('s');
		
		$qb->where($qb->expr()->like('s.field', ':s_field'))
			->setParameter('s_field', '%_operator');
		
		foreach ($criteria as $k => $v) {
			$qb->andWhere($qb->expr()->eq("s.$k", ":s_$k"))
				->setParameter("s_$k", $v);
		}
		
		return $qb->getQuery()
			->getResult();
	}
}
