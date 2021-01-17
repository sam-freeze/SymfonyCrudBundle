<?php

namespace SamFreeze\SymfonyCrudBundle\Controller;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use SamFreeze\SymfonyCrudBundle\Repository\AbstractRepository;
use SamFreeze\SymfonyCrudBundle\Repository\AbstractRouteSearchRepository;
use SamFreeze\SymfonyCrudBundle\Repository\AbstractRouteSortRepository;
use SamFreeze\SymfonyCrudBundle\Repository\AbstractRoutePaginationRepository;
use SamFreeze\SymfonyCrudBundle\Service\FileUploader;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractCrudController extends Controller {

	protected $translator;
	
	protected $routePaginationRepository;

	protected $routeSortRepository;

	protected $routeSearchRepository;
	
	protected $repository;
	
	protected $route;
	
	protected $name;
	
	function __construct(
		TranslatorInterface $translator,
		AbstractRouteSearchRepository $routeSearchRepository,
		AbstractRouteSortRepository $routeSortRepository,
		AbstractRoutePaginationRepository $routePaginationRepository,
		AbstractRepository $repository,
		$route,
		$name
	) {
		$this->translator = $translator;
		$this->routePaginationRepository = $routePaginationRepository;
		$this->routeSortRepository = $routeSortRepository;
		$this->routeSearchRepository = $routeSearchRepository;
		$this->repository = $repository;
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
	 * hook to generate route pagination entity
	 */
	abstract function newRoutePaginationEntity();

	/**
	 * hook to generate a new search entity
	 */
	abstract function newRouteSearchEntity();

	/**
	 * hook to generate route sort entity
	 */
	abstract function newRouteSortEntity();
	
	/**
	 * get name
	 * @return mixed
	 */
	protected function getName() {
		return $this->name;
	}

	/**
	 * get route
	 * @return mixed
	 */
	protected function getRoute() {
		return $this->route;
	}

	/**
	 * trans key
	 */
	protected function trans($key, $group = 'admin') {
		$name = $this->getName();
		return $this->translator->trans("$group.$key", [], $name);
	}

	/**
	 * hook to search data
	 */
	protected function searchData($searchData, $sortData, $paginationData) {
		return $this->repository->search($searchData, $sortData, $paginationData);
	}

	/**
	 * get user data from repository
	 * @param $repo
	 * @param $route
	 * @return array
	 */
	private function getUserData($repo) {
		$route = $this->getRoute();
		
		$items = $repo->findBy([
			'userId' => $this->getUser()->getId(),
			'route' => "{$route}index"
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
	 * generateSearchForm
	 * @param $data
	 * @return \Symfony\Component\Form\FormInterface
	 */
    private function generateSearchForm($data) {
    	$route = $this->getRoute();
		$formBuilder = $this->createFormBuilder($data);
		$formBuilder->setAction($this->generateUrl("{$route}search"));
	
		foreach ($this->getColumns() as $column) {
			if (!isset($column['searchType'])) continue;
		
			$attributes = $column['attributes'];
			$searchLabel = $column['name'];
			$searchType = $column['searchType'];
			$searchName = join('_', $attributes);
			$searchOptions = isset($column['searchOptions']) ? $column['searchOptions'] : [];
		
			switch ($searchType) {
				case ChoiceType::class:
					$values = [
						$this->trans('all') => '',
						$this->trans('empty') => 'null'
					];
					
					foreach ($this->repository->findDistinctValues($attributes) as $value) {
						$values[$value] = $value;
					}
					
					$searchOptions['choices'] = $values;
					break;
				default:
					break;
			}
			
			$searchOptions['required'] = false;
			$searchOptions['attr'] = [
				'placeholder' => $this->trans($searchLabel, 'column')
			];
		
			$formBuilder->add(
				$searchName,
				$searchType,
				$searchOptions
			);
		}
	
		return $formBuilder->getForm();
	}

	/**
     * @Route("", name="index", methods="GET")
     */
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
		$searchData = $this->getUserData($this->routeSearchRepository);
		$sortData = $this->getUserData($this->routeSortRepository);
		$paginationData = $this->getUserData($this->routePaginationRepository);

		if (!array_key_exists('page', $paginationData)) {
			$paginationData['page'] = 1;
		}

		if (!array_key_exists('limit', $paginationData)) {
			$paginationData['limit'] = 5;
		}
	
		$name = $this->getName();
		$route = $this->getRoute();
		$form = $this->generateSearchForm($searchData);

        return $this->render("{$name}/index.html.twig", [
            'items' => $this->searchData($searchData, $sortData, $paginationData),
            'pagination' => $paginationData,
            'columns' => $this->getColumns(),
            'route' => $route,
            'name' => $name,
			'form' => $form->createView(),
			'sort' => $sortData
		]);
    }
	
	/**
	 * search
	 * @Route("", name="search", methods="POST")
	 */
	public function search(
		Request $request,
		EntityManagerInterface $entityManager
	): Response
	{
		$searchData = $this->getUserData($this->routeSearchRepository);
		
		$route = $this->getRoute();
		$form = $this->generateSearchForm($searchData);
		
		$form->handleRequest($request);
		
		if ($form->isSubmitted()) {
			$searchData = $form->getData();
			$user = $this->getUser();
			
			$routeSearchList = $this->routeSearchRepository->findBy([
				'userId' => $this->getUser()->getId(),
				'route' => "{$route}index"
			]);
			
			foreach ($routeSearchList as $routeSearch) {
				$entityManager->remove($routeSearch);
			}
			
			foreach ($searchData as $key => $value) {
				$routeSearch = $this->newRouteSearchEntity();
				$routeSearch->setUserId($user->getId());
				$routeSearch->setRoute("{$route}index");
				$routeSearch->setField($key);
				$routeSearch->setValue($value);
				
				$entityManager->persist($routeSearch);
			}
			
			$entityManager->flush();
		}
		
		return $this->redirectToRoute("{$route}index");
	}

	/**
     * @Route("/filter/reset", name="filter_reset", methods="GET")
     */
    public function filterReset(
		EntityManagerInterface $entityManager
	): Response
    {
		$route = $this->getRoute();
		$routeSearchList = $this->routeSearchRepository->findBy([
			'userId' => $this->getUser()->getId(),
			'route' => "{$route}index"
		]);
		
		if ($routeSearchList) {
			foreach ($routeSearchList as $routeSearch) {
				$entityManager->remove($routeSearch);
			}
			
			$entityManager->flush();
		}
		
		$this->addFlash('notice', $this->trans('erased'));
    	
		return $this->redirectToRoute("{$route}index");
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
    public function edit(
		Request $request,
		$id
	): Response
    {
		$item = $this->repository->findOneBy(['id' => $id]);

        if (!$item) {
			$this->addFlash('error', $this->trans('notfound'));
			$route = $this->getRoute();
            return $this->redirectToRoute("{$route}index");
        }

		return $this->generateForm($request, $formType, $item);
    }
    
    protected function generateForm($request, $formType, $item) {
        $classNamespace = explode('\\', get_class($item));
        $className = array_pop($classNamespace);
		$title = $this->getName();
		$route = $this->getRoute();
        $form = $this->createForm($formType, $item);

        $oldData = [];
        foreach($form->all() as $field) {
			$name = $field->getName();
            $getFunc = "get$name";
            $oldData[$name] = $item->$getFunc();
        }
		
        $form->handleRequest($request);
        
		if ($form->isSubmitted()) {
			if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $fileUploader = new FileUploader();
                
                foreach($form->all() as $field) {
                    $name = $field->getName();
                    $data = $field->getData();
                    $setFunc="set$name";

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
		
		$getFunc="get$field";
		$setFunc="set$field";
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

	/**
	 * sort
	 * @Route("/paginate/{field}/{value}", name="paginate", methods="GET")
	 */
	public function paginate(
		EntityManagerInterface $entityManager,
		$field,
		$value
	): Response
	{
		$route = $this->getRoute();
		$routePagination = $this->routePaginationRepository->findOneBy([
			'route' => "{$route}index",
			'userId' => $this->getUser()->getId(),
			'field' => $field,
		]);
		
		if (!$routePagination) {
			$routePagination = $this->newRoutePaginationEntity();
		}
		
		$routePagination->setRoute("{$route}index");
		$routePagination->setUserId($this->getUser()->getId());
		$routePagination->setField($field);
		$routePagination->setValue($value);
		
		$entityManager->persist($routePagination);
		$entityManager->flush();
		
		return $this->redirectToRoute("{$route}index");
	}

	/**
	 * reset sorting
	 * @Route("/sort/reset", name="reset_sorting", methods="GET")
	 */
	public function resetSort(
		EntityManagerInterface $entityManager
	): Response
	{
		$route = $this->getRoute();
		$routeSortingList = $this->routeSortRepository->findBy([
			'route' => "{$route}index",
			'userId' => $this->getUser()->getId()
		]);
		
		if ($routeSortingList) {
			foreach ($routeSortingList as $routeSorting) {
				$entityManager->remove($routeSorting);
			}
			
			$entityManager->flush();
		}
		
		$this->addFlash('notice', $this->trans('erased'));
		
		return $this->redirectToRoute("{$route}index");
	}

	/**
	 * reset sorting field
	 * @Route("/sort/{field}/reset", name="reset_field_sorting", methods="GET")
	 */
	public function resetFieldSorting(
		EntityManagerInterface $entityManager,
		$field
	): Response
	{
		$route = $this->getRoute();
		$routeSort = $this->routeSortRepository->findOneBy([
			'route' => "{$route}index",
			'userId' => $this->getUser()->getId(),
			'field' => $field,
		]);
		
		if ($routeSort) {
			$entityManager->remove($routeSort);
			$entityManager->flush();
		}
		
		return $this->redirectToRoute("{$route}index");
	}
	
	/**
	 * set sorting field
	 * @Route("/sort/{field}/{order}", name="sort_field", methods="GET")
	 */
	public function sortField(
		EntityManagerInterface $entityManager,
		$field,
		$order
	): Response
	{
		$route = "{$this->getRoute()}index";

		if ($order !== 'asc' && $order !== 'desc') {
			return $this->redirectToRoute($route);
		}

		$routeSort = $this->routeSortRepository->findOneBy([
			'route' => $route,
			'userId' => $this->getUser()->getId(),
			'field' => $field,
		]);
		
		if (!$routeSort) {
			$routeSort = $this->newRouteSortEntity();
		}
		
		$routeSort->setRoute($route);
		$routeSort->setUserId($this->getUser()->getId());
		$routeSort->setField($field);
		$routeSort->setValue($order);
		
		$entityManager->persist($routeSort);
		$entityManager->flush();
		
		return $this->redirectToRoute($route);
	}
}