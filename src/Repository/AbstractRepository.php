<?php

namespace SamFreeze\SymfonyCrudBundle\Repository;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Entity|null find($id, $lockMode = null, $lockVersion = null)
 * @method Entity|null findOneBy(array $criteria, array $orderBy = null)
 * @method Entity[]    findAll()
 * @method Entity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
abstract class AbstractRepository extends ServiceEntityRepository
{

	public function findDistinctValues($attributes) {
		$count = count($attributes);
		
		if ($count == 0) return [];
		
		$qb = $this->createQueryBuilder('p');
		
		$parent = 'p';
		$child = '';
		
		for ($i = 0; $i < $count; $i++) {
			$child = $attributes[$i];
			
			if ($i == $count - 1) {
				$qb
					->select("$parent.$child")
					->distinct(true)
					->where($qb->expr()->isNotNull("$parent.$child"))
					->addOrderBy("$parent.$child", 'ASC');
			} else {
				$qb->leftJoin("$parent.$child", $child);
			}
			
			$parent = $child;
		}
		
		return array_map(
			function($item) use ($child) { return $item[$child]; },
			$qb->getQuery()->getArrayResult()
		);
	}
	
	public function search($values, $orderBy, $pagination) {
		$qb = $this->createQueryBuilder('p');
		
		$join = [];
		
		foreach ($values as $key => $value) {
			if (is_null($value)) continue;
			$attributes = explode('_', $key);
			$count = count($attributes);
			
			$parent = 'p';
			
			for ($i = 0; $i < $count; $i++) {
				$child = $attributes[$i];
				
				if ($i == $count - 1) {
					if ($value == 'null') {
						$qb->andWhere($qb->expr()->isNull("$parent.$child"));
					} elseif($value != '') {
						$qb->andWhere($qb->expr()->like("$parent.$child", ":$parent$child"))
							->setParameter("$parent$child", "%$value%");
					}
				} else if (!in_array("$parent.$child=$child", $join)) {
					$qb->leftJoin("$parent.$child", $child);
					array_push($join, "$parent.$child=$child");
				}
				
				$parent = $child;
			}
		}
		
		foreach ($orderBy as $key => $sort) {
			if ($sort != 'asc' && $sort != 'desc') continue;
			
			$attributes = explode('_', $key);
			$count = count($attributes);
			
			$parent = 'p';
			
			for ($i = 0; $i < $count; $i++) {
				$child = $attributes[$i];
				
				if ($i == $count - 1) {
					$qb->addOrderBy("$parent.$child", $sort);
				} else if (!in_array("$parent.$child=$child", $join)) {
					$qb->leftJoin("$parent.$child", $child);
					array_push($join, "$parent.$child=$child");
				}
				
				$parent = $child;
			}
			
		}
		
		$paginator = new Paginator($qb);
		$paginator
			->getQuery()
			->setFirstResult(($pagination['page'] - 1) * $pagination['limit'])
			->setMaxResults($pagination['limit']);
		
		return $paginator;
	}
}