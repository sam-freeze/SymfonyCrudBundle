<?php
	
	namespace SamFreeze\SymfonyCrudBundle\Service;
	
	use SamFreeze\SymfonyCrudBundle\Repository\AbstractRouteColumnRepository;
	use SamFreeze\SymfonyCrudBundle\Repository\AbstractRoutePaginationRepository;
	use SamFreeze\SymfonyCrudBundle\Repository\AbstractRouteSearchOperatorRepository;
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
		
		private $routeSearchOperatorRepository;
		
		private $routeSortRepository;
		
		private $routePaginationRepository;
		
		private $routeColumnRepository;
		
		public function __construct(
			AbstractRouteSearchRepository $routeSearchRepository,
			AbstractRouteSearchOperatorRepository $routeSearchOperatorRepository,
			AbstractRouteSortRepository $routeSortRepository,
			AbstractRoutePaginationRepository $routePaginationRepository,
			AbstractRouteColumnRepository $routeColumnRepository)
		{
			$this->routeSearchRepository = $routeSearchRepository;
			$this->routeSearchOperatorRepository = $routeSearchOperatorRepository;
			$this->routePaginationRepository = $routePaginationRepository;
			$this->routeSortRepository = $routeSortRepository;
			$this->routeColumnRepository = $routeColumnRepository;
		}
		
		private function generateKeyValue($items, $extension = '')
		{	
			$data = [];
			
			foreach ($items as $item) {
				$data[$item->getField()] = $item->getValue();
			}
			
			return $data;
		}
		
		public function findRouteSearchValue($route, $user)
		{
			$items = $this->routeSearchRepository->findBy([
				'route' => $route,
				'userId' => $user
			]);

			return $this->generateKeyValue($items);
		}
		
		public function findRouteSearchOperator($route, $user)
		{
			$items = $this->routeSearchOperatorRepository->findBy([
				'route' => $route,
				'userId' => $user
			]);

			return $this->generateKeyValue($items);
		}
		
		public function findRouteSort($route, $user)
		{
			$items = $this->routeSortRepository->findBy([
				'route' => $route,
				'userId' => $user
			]);

			return $this->generateKeyValue($items);
		}
		
		public function findRoutePagination($route, $user)
		{
			$items = $this->routePaginationRepository->findBy([
				'route' => $route,
				'userId' => $user
			]);

			return $this->generateKeyValue($items);
		}
		
		public function findRouteColumn($route, $user)
		{
			$items = $this->routeColumnRepository->findBy([
				'route' => $route,
				'userId' => $user
			]);

			return $this->generateKeyValue($items);
		}
		
		
	}