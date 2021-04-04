<?php
	
	namespace SamFreeze\SymfonyCrudBundle\Service;
	
	use SamFreeze\SymfonyCrudBundle\Repository\AbstractRouteColumnRepository;
	use SamFreeze\SymfonyCrudBundle\Repository\AbstractRoutePaginationRepository;
	use SamFreeze\SymfonyCrudBundle\Repository\AbstractRouteSearchRepository;
	use SamFreeze\SymfonyCrudBundle\Repository\AbstractRouteSortRepository;
	
	/**
	 * Class AbstractCrudService
	 *
	 * @package SamFreeze\SymfonyCrudBundle\Service
	 */
	abstract class AbstractCrudService
	{
		
		private $routeSearchRepository;
		
		private $routeSortRepository;
		
		private $routePaginationRepository;
		
		private $routeColumnRepository;
		
		public function __construct(
			AbstractRouteSearchRepository $routeSearchRepository,
			AbstractRouteSortRepository $routeSortRepository,
			AbstractRoutePaginationRepository $routePaginationRepository,
			AbstractRouteColumnRepository $routeColumnRepository)
		{
			$this->routeSearchRepository = $routeSearchRepository;
			$this->routePaginationRepository = $routePaginationRepository;
			$this->routeSortRepository = $routeSortRepository;
			$this->routeColumnRepository = $routeColumnRepository;
		}
		
		private function findBy($repository, $criteria)
		{
			$items = $repository->findBy($criteria, [
				'id' => 'asc'
			]);
			
			$data = [];
			
			foreach ($items as $item) {
				$data[$item->getField()] = $item->getValue();
			}
			
			return $data;
		}
		
		public function findRouteSearch($route, $user)
		{
			return $this->findBy($this->routeSearchRepository, ['route' => $route, 'userId' => $user]);
		}
		
		public function findRouteSearchValue($route, $user)
		{
			return $this->routeSearchRepository->findValue(['route' => $route, 'userId' => $user]);
		}
		
		public function findRouteSearchOperator($route, $user)
		{
			return $this->routeSearchRepository->findOperator(['route' => $route, 'userId' => $user]);
		}
		
		public function findRouteSort($route, $user)
		{
			return $this->findBy($this->routeSortRepository, ['route' => $route, 'userId' => $user]);
		}
		
		public function findRoutePagination($route, $user)
		{
			return $this->findBy($this->routePaginationRepository, ['route' => $route, 'userId' => $user]);
		}
		
		public function findRouteColumn($route, $user)
		{
			return $this->findBy($this->routeColumnRepository, ['route' => $route, 'userId' => $user]);
		}
		
		
	}