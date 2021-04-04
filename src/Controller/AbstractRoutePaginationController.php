<?php
	
	namespace SamFreeze\SymfonyCrudBundle\Controller;
	
	
	use Doctrine\ORM\EntityManagerInterface;
	use SamFreeze\SymfonyCrudBundle\Repository\AbstractRoutePaginationRepository;
	use Symfony\Bundle\FrameworkBundle\Controller\Controller;
	use Symfony\Component\Routing\Annotation\Route;
	use Symfony\Component\HttpFoundation\Response;
	
	/**
	 * Route pagination
	 * Manage RoutePagination entity
	 */
	abstract class AbstractRoutePaginationController extends Controller
	{
		
		protected $repository;
		
		function __construct(AbstractRoutePaginationRepository $repository)
		{
			$this->repository = $repository;
		}
		
		/**
		 * hook to generate route pagination entity
		 */
		abstract function newRoutePaginationEntity();
		
		/**
		 * sort
		 * @Route("/paginate/{route}/{field}/{value}", name="paginate", methods="GET")
		 */
		public function paginate(
			EntityManagerInterface $entityManager,
			$route,
			$field,
			$value
		): Response
		{
			$routePagination = $this->repository->findOneBy([
				'route' => $route,
				'userId' => $this->getUser()->getId(),
				'field' => $field,
			]);
			
			if (!$routePagination) {
				$routePagination = $this->newRoutePaginationEntity();
			}
			
			$routePagination->setRoute($route);
			$routePagination->setUserId($this->getUser()->getId());
			$routePagination->setField($field);
			$routePagination->setValue($value);
			
			$entityManager->persist($routePagination);
			$entityManager->flush();
			
			return $this->redirectToRoute($route);
		}
	}