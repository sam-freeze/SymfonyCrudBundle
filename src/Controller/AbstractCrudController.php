<?php
	
	namespace SamFreeze\SymfonyCrudBundle\Controller;
	
	
	use Doctrine\ORM\EntityManager;
	use Doctrine\ORM\EntityManagerInterface;
	use SamFreeze\SymfonyCrudBundle\Service\AbstractCrudService;
	use Symfony\Component\Translation\TranslatorInterface;
	use SamFreeze\SymfonyCrudBundle\Repository\AbstractRepository;
	use SamFreeze\SymfonyCrudBundle\Service\FileUploader;
	use Symfony\Bundle\FrameworkBundle\Controller\Controller;
	use Symfony\Component\Form\Extension\Core\Type\FileType;
	use Symfony\Component\HttpFoundation\File\UploadedFile;
	use Symfony\Component\Routing\Annotation\Route;
	use Symfony\Component\HttpFoundation\Response;
	use Symfony\Component\HttpFoundation\Request;
	
	/**
	 * Crud controller
	 * create, read, update and delete a entity
	 */
	abstract class AbstractCrudController extends Controller
	{
		
		protected $translator;
		
		protected $repository;
		
		protected $service;
		
		protected $route;
		
		protected $name;
		
		function __construct(
			TranslatorInterface $translator,
			AbstractCrudService $service,
			AbstractRepository $repository,
			$route,
			$name
		)
		{
			$this->translator = $translator;
			$this->repository = $repository;
			$this->service = $service;
			$this->route = $route;
			$this->name = $name;
		}
		
		/**
		 * hook to get form
		 */
		abstract function getForm();
		
		/**
		 * hook to generate a new entity
		 */
		abstract function getNewEntity();
		
		/**
		 * hook to generate columns
		 * @return mixed
		 */
		abstract function getColumns();
		
		/**
		 * get name
		 * @return mixed
		 */
		protected function getName()
		{
			return $this->name;
		}
		
		/**
		 * get route
		 * @return mixed
		 */
		protected function getRoute()
		{
			return $this->route;
		}
		
		/**
		 * hook to translate key
		 */
		protected function trans($key, $group = 'admin')
		{
			$name = $this->getName();
			return $this->translator->trans("$group.$key", [], $name);
		}
		
		/**
		 * Hook to search data in repository
		 * @param $searchData
		 * @param $operatorData
		 * @param $sortData
		 * @param $paginationData
		 * @return \Doctrine\ORM\Tools\Pagination\Paginator
		 */
		protected function searchData($searchData, $operatorData, $sortData, $paginationData)
		{
			return $this->repository->search($searchData, $operatorData, $sortData, $paginationData);
		}
		
		/**
		 * @Route("", name="index", methods="GET")
		 */
		public function index(Request $request, EntityManagerInterface $entityManager): Response
		{
			$name = $this->getName();
			$route = $this->getRoute();
			
			$columns = $this->getColumns();
			
			$user = $this->getUser()->getId();
			$routeName = $request->attributes->get('_route');
			$paginationData = $this->service->findRoutePagination($routeName, $user);
			$searchData = $this->service->findRouteSearchValue($routeName, $user);
			$operatorData= $this->service->findRouteSearchOperator($routeName, $user);
			$sortData = $this->service->findRouteSort($routeName, $user);
			$columnData = $this->service->findRouteColumn($routeName, $user);
			
			if (!array_key_exists('page', $paginationData)) {
				$paginationData['page'] = 1;
			}
	
			if (!array_key_exists('limit', $paginationData)) {
				$paginationData['limit'] = 5;
			}
			
			return $this->render("{$name}/index.html.twig", [
				'items' => $this->searchData($searchData, $operatorData, $sortData, $paginationData),
				'columns' => $columns,
				'dColumns' => array_filter($columns, function($column) use ($columnData) {
					$name = $column['name'];
					return isset($columnData[$name]) && $columnData[$name] > 0;
				}),
				'sortData' => $sortData,
				'columnData' => $columnData,
				'searchData' => $searchData,
				'searchOperatorData' => $operatorData,
				'paginationData' => $paginationData,
				'route' => $route,
				'name' => $name,
			]);
		}
		
		/**
		 * @Route("/new", name="new", methods="GET|POST")
		 */
		public function new(Request $request): Response
		{
			$entity = $this->getNewEntity();
			$formType = $this->getForm();
			return $this->generateForm($request, $formType, $entity);
		}
		
		/**
		 * @Route("/{id}/edit", name="edit", methods="GET|POST")
		 */
		public function edit(Request $request, $id): Response
		{
			$item = $this->repository->findOneBy(['id' => $id]);
			
			if (!$item) {
				$this->addFlash('error', $this->trans('notfound'));
				$route = $this->getRoute();
				return $this->redirectToRoute("{$route}index");
			}
			
			$formType = $this->getForm();
			return $this->generateForm($request, $formType, $item);
		}
		
		/**
		 * generate form
		 * @param $request
		 * @param $formType
		 * @param $item
		 * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
		 */
		protected function generateForm($request, $formType, $item)
		{
			$classNamespace = explode('\\', get_class($item));
			$className = array_pop($classNamespace);
			$title = $this->getName();
			$route = $this->getRoute();
			$form = $this->createForm($formType, $item);
			
			$oldData = [];
			foreach ($form->all() as $field) {
				$name = $field->getName();
				$getFunc = "get$name";
				$oldData[$name] = $item->$getFunc();
			}
			
			$form->handleRequest($request);
			
			if ($form->isSubmitted()) {
				if ($form->isValid()) {
					$em = $this->getDoctrine()->getManager();
					$fileUploader = new FileUploader();
					
					foreach ($form->all() as $field) {
						$name = $field->getName();
						$data = $field->getData();
						$setFunc = "set$name";
						
						$config = $field->getConfig();
						if (!$config) continue;
						
						$type = $config->getType();
						if (!$type) continue;
						
						$innerType = $type->getInnerType();
						if (!$innerType) continue;
						
						if ($innerType instanceof FileType) {
							if ($data instanceof UploadedFile) {
								$path = strtolower("uploads/$className/$name");
								$fileName = $fileUploader->uploadFile($data, $path);
								$item->$setFunc($fileName);
							} else {
								$item->$setFunc($oldData[$name]);
							}
						}
					}
					
					$em->persist($item);
					$em->flush();
					
					$this->addFlash('notice', $this->trans('saved'));
					return $this->redirectToRoute("{$route}index");
				} else {
					foreach ($form->getErrors() as $error) {
						$this->addFlash('error', $error->getMessage());
					}
				}
			}
			
			return $this->render("{$title}/edit.html.twig", [
				'item' => $item,
				'form' => $form->createView(),
				'route' => $route,
				'name' => $title,
			]);
		}
		
		/**
		 * @Route("/{id}/{field}/delete", name="delete_image", methods="GET")
		 */
		public function deleteImage(
			Request $request,
			EntityManagerInterface $entityManager,
			$id,
			$field
		): Response
		{
			$item = $this->repository->findOneBy(['id' => $id]);
			$route = $this->getRoute();
			
			if (!$item) {
				$this->addFlash('error', $this->trans('notfound'));
				return $this->redirectToRoute("{$route}index");
			}
			
			$getFunc = "get$field";
			$setFunc = "set$field";
			$oldImage = $item->$getFunc();
			$item->$setFunc(null);
			
			$entityManager->persist($item);
			$entityManager->flush();
			
			if ($oldImage && file_exists($oldImage)) {
				unlink($oldImage);
			}
			
			$this->addFlash('notice', $this->trans('imageDeleted'));
			
			return $this->redirectToRoute("{$this->route}index");
		}
		
		/**
		 * @Route("/{id}/delete", name="delete", methods="GET")
		 */
		public function delete(
			Request $request,
			EntityManagerInterface $entityManager,
			$id
		): Response
		{
			$item = $this->repository->findOneBy(['id' => $id]);
			
			if (!$item) {
				$this->addFlash('error', $this->trans('notfound'));
				return $this->redirectToRoute("{$this->route}index");
			}
			
			$entityManager->remove($item);
			$entityManager->flush();
			
			$this->addFlash('notice', $this->trans('deleted'));
			
			return $this->redirectToRoute("{$this->route}index");
		}
	}