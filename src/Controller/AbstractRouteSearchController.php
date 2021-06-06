<?php
	
	namespace SamFreeze\SymfonyCrudBundle\Controller;
	
	
	use Doctrine\ORM\EntityManagerInterface;
	use SamFreeze\SymfonyCrudBundle\Repository\AbstractRouteSearchOperatorRepository;
	use SamFreeze\SymfonyCrudBundle\Repository\Expr;
	use SamFreeze\SymfonyCrudBundle\Repository\AbstractRouteSearchRepository;
	use Symfony\Component\Translation\TranslatorInterface;
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
		
		protected $routeSearchOperatorRepository;

		protected $translator;
		
		function __construct(
			TranslatorInterface $translator,
			AbstractRouteSearchRepository $routeSearchRepository,
			AbstractRouteSearchOperatorRepository $routeSearchOperatorRepository)
		{
			$this->translator = $translator;
			$this->routeSearchRepository = $routeSearchRepository;
			$this->routeSearchOperatorRepository = $routeSearchOperatorRepository;
		}

		/**
		 * hook to translate key
		 */
		protected function trans($key, $group = 'admin', $domain)
		{
			return $this->translator->trans("$group.$key", [], $domain);
		}
		
		/**
		 * hook to generate a new search entity
		 */
		abstract function newRouteSearchEntity();
		
		/**
		 * hook to generate a new search operator entity
		 */
		abstract function newRouteSearchOperatorEntity();
		
		/**
		 * get user data from repository
		 * @param $route
		 * @return array
		 */
		private function buildFormData($route)
		{
			$data = [];
			
			// values data
			$items = $this->routeSearchRepository->findBy([
				'userId' => $this->getUser()->getId(),
				'route' => $route
			]);
			
			foreach ($items as $item) {
				$data["{$item->getField()}_value"] = $item->getValue();
			}
			
			// operator data
			$items = $this->routeSearchOperatorRepository->findBy([
				'userId' => $this->getUser()->getId(),
				'route' => $route
			]);
			
			foreach ($items as $item) {
				$data["{$item->getField()}_operator"] = $item->getValue();
			}
			
			return $data;
		}
		
		/**
		 * render search form
		 * @param $columns
		 * @param $route
		 * @return Response
		 */
		public function index($columns, $route, $domain)
		{
			$form = $this->generateSearchForm($columns, $route, $domain);
			
			return $this->render("{$domain}/_search.html.twig", [
				'columns' => $columns,
				'domain' => $domain,
				'route' => $route,
				'form' => $form->createView(),
			]);
		}
		
		/**
		 * post search form
		 */
		public function search(
			Request $request,
			EntityManagerInterface $entityManager,
			$columns,
			$route,
			$domain
		): Response
		{
			$form = $this->generateSearchForm($columns, $route, $domain);
			
			$form->handleRequest($request);
			
			if ($form->isSubmitted()) {
				$user = $this->getUser();
				$data = $form->getData();
				
				// clear route search
				$routeSearchList = $this->routeSearchRepository->findBy([
					'userId' => $this->getUser()->getId(),
					'route' => $route
				]);
				
				foreach ($routeSearchList as $routeSearch) {
					$entityManager->remove($routeSearch);
				}
				
				// clear route search operator
				$routeSearchOperatorList = $this->routeSearchOperatorRepository->findBy([
					'userId' => $this->getUser()->getId(),
					'route' => $route
				]);
				
				foreach ($routeSearchOperatorList as $routeSearchOperator) {
					$entityManager->remove($routeSearchOperator);
				}
				
				foreach ($columns as $column) {
					if (!isset($column['searchType'])) continue;
					
					$attributes = $column['attributes'];
					$searchName = join('_', $attributes);
					
					$value = isset($data["{$searchName}_value"]) ? $data["{$searchName}_value"] : null;

					$entity = $this->newRouteSearchEntity();
					$entity->setUserId($user->getId());
					$entity->setRoute($route);
					$entity->setField($searchName);
					$entity->setValue($value);
					
					$entityManager->persist($entity);
					
					$value = isset($data["{$searchName}_operator"]) ? $data["{$searchName}_operator"] : null;
						
					$entity = $this->newRouteSearchOperatorEntity();
					$entity->setUserId($user->getId());
					$entity->setRoute($route);
					$entity->setField($searchName);
					$entity->setValue($value);
						
					$entityManager->persist($entity);
				}
				
				$entityManager->flush();
			}
			
			return $this->redirectToRoute($route);
		}

		/**
		 * remove search data
		 */
		public function remove(
			Request $request,
			EntityManagerInterface $entityManager,
			$route
		): Response
		{
			// clear route search
			$routeSearchList = $this->routeSearchRepository->findBy([
				'userId' => $this->getUser()->getId(),
				'route' => $route
			]);

			foreach ($routeSearchList as $routeSearch) {
				$entityManager->remove($routeSearch);
			}
			
			// clear route search operator
			$routeSearchOperatorList = $this->routeSearchOperatorRepository->findBy([
				'userId' => $this->getUser()->getId(),
				'route' => $route
			]);
			
			foreach ($routeSearchOperatorList as $routeSearchOperator) {
				$entityManager->remove($routeSearchOperator);
			}

			$entityManager->flush();	

			return $this->redirectToRoute($route);
		}
		
		/**
		 * generateSearchForm
		 * @param $data
		 * @return \Symfony\Component\Form\FormInterface
		 */
		private function generateSearchForm($columns, $route, $domain)
		{
			$data = $this->buildFormData($route);
			$formBuilder = $this->createFormBuilder($data);
			$formBuilder->setAction($this->generateUrl("{$route}_search"));
			
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
							$this->trans('all', 'admin', $domain) => ''
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
				
				$searchOptions['label'] = $this->trans($searchLabel, 'column', $domain);
				$searchOptions['required'] = false;
				$searchOptions['attr'] = [
					'placeholder' => $this->trans($searchLabel, 'column', $domain)
				];
				
				$formBuilder->add(
					"{$searchName}_value",
					$searchType,
					$searchOptions
				);
				
				// operator options
				$operatorOptions = [
					'choices' => array_combine(
						array_map(function ($value) use ($domain) {
							return $this->trans($value, 'admin', $domain);
						}, $operatorChoices),
						$operatorChoices)
				];
				
				$formBuilder->add(
					"{$searchName}_operator",
					ChoiceType::class,
					$operatorOptions
				);
			}
			
			return $formBuilder->getForm();
		}
	}