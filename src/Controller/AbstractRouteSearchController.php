<?php
	
	namespace SamFreeze\SymfonyCrudBundle\Controller;
	
	
	use Doctrine\ORM\EntityManagerInterface;
	use SamFreeze\SymfonyCrudBundle\Repository\Expr;
	use SamFreeze\SymfonyCrudBundle\Repository\AbstractRouteSearchRepository;
	use Symfony\Bundle\FrameworkBundle\Controller\Controller;
	use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
	use Symfony\Component\Form\Extension\Core\Type\TextType;
	use Symfony\Component\Form\Extension\Core\Type\NumberType;
	use Symfony\Component\HttpFoundation\Response;
	use Symfony\Component\HttpFoundation\Request;
	
	/**
	 * Crud controller
	 * create, read, update and delete a entity
	 */
	abstract class AbstractRouteSearchController extends Controller
	{
		
		protected $routeSearchRepository;
		
		function __construct(AbstractRouteSearchRepository $routeSearchRepository)
		{
			$this->routeSearchRepository = $routeSearchRepository;
		}
		
		/**
		 * hook to generate a new search entity
		 */
		abstract function newRouteSearchEntity();
		
		/**
		 * get user data from repository
		 * @param $route
		 * @return array
		 */
		private function buildFormData($route)
		{
			$items = $this->routeSearchRepository->findBy([
				'userId' => $this->getUser()->getId(),
				'route' => $route
			], [
				'id' => 'asc'
			]);
			
			$data = [];
			
			foreach ($items as $item) {
				$data[$item->getField()] = $item->getValue();
			}
			
			return $data;
		}
		
		/**
		 * render search form
		 * @param $columns
		 * @param $route
		 * @return Response
		 */
		public function search($columns, $route, $name)
		{
			$data = $this->buildFormData($route);
			
			$form = $this->generateSearchForm($columns, $data);
			
			return $this->render("{$name}/search.html.twig", [
				'columns' => $columns,
				'form' => $form->createView(),
			]);
		}
		
		/**
		 * post search form
		 */
		public function post(
			Request $request,
			EntityManagerInterface $entityManager,
			$columns,
			$route
		): Response
		{
			$form = $this->generateSearchForm($columns, $route);
			
			$form->handleRequest($request);
			
			if ($form->isSubmitted()) {
				$searchData = $form->getData();
				$user = $this->getUser();
				
				$routeSearchList = $this->routeSearchRepository->findBy([
					'userId' => $this->getUser()->getId(),
					'route' => $route
				]);
				
				foreach ($routeSearchList as $routeSearch) {
					$entityManager->remove($routeSearch);
				}
				
				foreach ($searchData as $key => $value) {
					$entity = $this->newRouteSearchEntity();
					$entity->setUserId($user->getId());
					$entity->setRoute($route);
					$entity->setField($key);
					$entity->setValue($value);
					
					$entityManager->persist($entity);
				}
				
				$entityManager->flush();
			}
			
			return $this->redirectToRoute($route);
		}
		
		/**
		 * generateSearchForm
		 * @param $data
		 * @return \Symfony\Component\Form\FormInterface
		 */
		private function generateSearchForm($columns, $route)
		{
			$data = $this->buildFormData($route);
			$formBuilder = $this->createFormBuilder($data);
			$formBuilder->setAction($this->generateUrl($route));
			
			foreach ($columns as $column) {
				if (!isset($column['searchType'])) continue;
				
				$attributes = $column['attributes'];
				$searchLabel = $column['name'];
				$searchType = $column['searchType'];
				$searchName = join('_', $attributes);
				$searchOptions = isset($column['searchOptions']) ? $column['searchOptions'] : [];
				
				switch ($searchType) {
					case ChoiceType::class:
						$values = [
							$this->trans('all') => ''
						];
						
						foreach ($this->repository->findDistinctValues($attributes) as $value) {
							$values[$value] = $value;
						}
						
						$searchOptions['choices'] = $values;
						$operatorChoices = [Expr::eq, Expr::neq, Expr::isNull, Expr::isNotNull];
						break;
					case NumberType::class:
						$operatorChoices = [Expr::eq, Expr::neq, Expr::lt, Expr::lte, Expr::gt, Expr::gte, Expr::isNull, Expr::isNotNull];
						break;
					case TextType::class:
						$operatorChoices = [Expr::eq, Expr::neq, Expr::like, Expr::notLike, Expr::isNull, Expr::isNotNull];
						break;
					default:
						$operatorChoices = [Expr::isNull, Expr::isNotNull];
						break;
				}
				
				$searchOptions['label'] = $this->trans($searchLabel, 'column');
				$searchOptions['required'] = false;
				$searchOptions['attr'] = [
					'placeholder' => $this->trans($searchLabel, 'column')
				];
				
				$formBuilder->add(
					$searchName,
					$searchType,
					$searchOptions
				);
				
				// operator options
				$operatorOptions = [
					'choices' => array_combine(
						array_map(function ($value) {
							return $this->trans($value);
						}, $operatorChoices),
						$operatorChoices)
				];
				
				$operatorName = "{$searchName}_operator";
				$operatorType = ChoiceType::class;
				
				$formBuilder->add(
					$operatorName,
					$operatorType,
					$operatorOptions
				);
			}
			
			return $formBuilder->getForm();
		}
	}