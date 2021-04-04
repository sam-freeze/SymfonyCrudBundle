<?php
	
	namespace SamFreeze\SymfonyCrudBundle\Controller;
	
	use Doctrine\ORM\EntityManagerInterface;
	use SamFreeze\SymfonyCrudBundle\Repository\AbstractRouteColumnRepository;
	use Symfony\Bundle\FrameworkBundle\Controller\Controller;
	use Symfony\Component\Routing\Annotation\Route;
	use Symfony\Component\HttpFoundation\Response;
	
	/**
	 * Route column controller
	 * Manage RouteColumn entity
	 */
	abstract class AbstractRouteColumnController extends Controller
	{
		
		protected $routeColumnRepository;
		
		function __construct(AbstractRouteColumnRepository $routeColumnRepository)
		{
			$this->routeColumnRepository = $routeColumnRepository;
		}
		
		/**
		 * hook to generate a new search entity
		 */
		abstract function newRouteColumnEntity();
		
		/**
		 * display column
		 * @Route("/column/{route}/{field}/{value}", name="column", methods="GET")
		 */
		public function column(
			EntityManagerInterface $entityManager,
			$route,
			$field,
			$value
		): Response
		{
			$routeColumn = $this->routeColumnRepository->findOneBy([
				'route' => $route,
				'userId' => $this->getUser()->getId(),
				'field' => $field,
			]);
			
			if (!$routeColumn) {
				$routeColumn = $this->newRouteColumnEntity();
			}
			
			$routeColumn->setRoute($route);
			$routeColumn->setUserId($this->getUser()->getId());
			$routeColumn->setField($field);
			$routeColumn->setValue($value);
			
			$entityManager->persist($routeColumn);
			$entityManager->flush();
			
			return $this->redirectToRoute($route);
		}
	}