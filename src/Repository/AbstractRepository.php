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

	/**
	 * is valid value for operator
	 */
	private function isValidValue($operator, $value) {
		switch ($operator) {
			case Expr::isNull:
			case Expr::isNotNull:
				return true;
			case Expr::in:
			case Expr::notIn:
				return !empty($value);
			case Expr::like:
			case Expr::notLike:
			case Expr::eq:
			case Expr::neq:
			case Expr::lt:
			case Expr::lte:
			case Expr::gt:
			case Expr::gte:
			default:
				return !empty($value);
		}
	}
	
	/**
	 * search values with orperator
	 * order by and sort result
	 */
	public function search($values, $operators, $orderBy, $pagination) {
		$qb = $this->createQueryBuilder('p');
		
		$join = [];
		
		foreach ($values as $key => $value) {
			$operatorKey = "{$key}_operator";
			$attributes = explode('_', $key);
			$count = count($attributes);

			if (!isset($operators[$operatorKey])) continue;
			if (!$this->isValidValue($operators[$operatorKey], $value)) continue;
			
			$parent = 'p';
			
			for ($i = 0; $i < $count; $i++) {
				$child = $attributes[$i];

				if ($i == $count - 1) {
					switch ($operators[$operatorKey]) {
						case Expr::eq:
							$qb->andWhere($qb->expr()->eq("$parent.$child", ":$parent$child"))
								->setParameter("$parent$child", $value);
							break;
						case Expr::neq:
							$qb->andWhere($qb->expr()->neq("$parent.$child", ":$parent$child"))
								->setParameter("$parent$child", $value);
							break;
						case Expr::lt:
							$qb->andWhere($qb->expr()->lt("$parent.$child", ":$parent$child"))
								->setParameter("$parent$child", $value);
							break;
						case Expr::lte:
							$qb->andWhere($qb->expr()->lte("$parent.$child", ":$parent$child"))
								->setParameter("$parent$child", $value);
							break;
						case Expr::gt:
							$qb->andWhere($qb->expr()->gt("$parent.$child", ":$parent$child"))
								->setParameter("$parent$child", $value);
							break;
						case Expr::gte:
							$qb->andWhere($qb->expr()->gte("$parent.$child", ":$parent$child"))
								->setParameter("$parent$child", $value);
							break;
						case Expr::isNull:
							$qb->andWhere($qb->expr()->isNull("$parent.$child"));
							break;
						case Expr::isNotNull:
							$qb->andWhere($qb->expr()->isNotNull("$parent.$child"));
							break;
						case Expr::in:
							$qb->andWhere($qb->expr()->in("$parent.$child", $value));
							break;
						case Expr::notIn:
							$qb->andWhere($qb->expr()->notIn("$parent.$child", $value));
							break;
						case Expr::like:
							$qb->andWhere($qb->expr()->like("$parent.$child", ":$parent$child"))
								->setParameter("$parent$child", "%$value%");
							break;
						case Expr::notLike:
							$qb->andWhere($qb->expr()->notLike("$parent.$child", ":$parent$child"))
								->setParameter("$parent$child", "%$value%");
							break;
						default:
							break;
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