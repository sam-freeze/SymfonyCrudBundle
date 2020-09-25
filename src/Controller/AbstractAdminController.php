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

abstract class AbstractAdminController extends Controller {

    function __construct(
		TranslatorInterface $translator,
    	AbstractRouteSearchRepository $routeSearchRepository,
		AbstractRouteSortRepository $routeSortRepository,
		AbstractRoutePaginationRepository $routePaginationRepository,
		AbstractRepository $repository,
		$routeSearchEntity,
		$routeSortEntity,
		$routePaginationEntity,
		$entity,
		$form,
		$route,
		$columns
	) {
		$this->translator = $translator;
    	$this->routeSearchRepository = $routeSearchRepository;
		$this->routeSortRepository = $routeSortRepository;
		$this->routePaginationRepository = $routePaginationRepository;
    	$this->repository = $repository;
        $this->routeSearchEntity = $routeSearchEntity;
		$this->routeSortEntity = $routeSortEntity;
		$this->routePaginationEntity = $routePaginationEntity;
		$this->entity = $entity;
        $this->form = $form;
        $this->route = $route;
		$this->columns = $columns;
	}
	
	/**
	 * get title from entity
	 */
	private function getTitle() {
		$path = explode('\\', $this->entity);
		return strtolower(array_pop($path));
	}

    /**
     * @Route("/", name="index", methods="GET|POST")
     */
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
		$searchData = $this->getData($this->routeSearchRepository);
		$sortData = $this->getData($this->routeSortRepository);
		$paginationData = $this->getData($this->routePaginationRepository);

		if (!array_key_exists('page', $paginationData)) {
			$paginationData['page'] = 1;
		}

		if (!array_key_exists('limit', $paginationData)) {
			$paginationData['limit'] = 5;
		}
    	
        $form = $this->generateSearchForm($searchData);

        return $this->render('abstract/index.html.twig', [
            'items' => $this->repository->search($searchData, $sortData, $paginationData),
            'pagination' => $paginationData,
            'columns' => $this->columns,
            'route' => $this->route,
            'title' => $this->getTitle(),
			'form' => $form->createView(),
			'sort' => $sortData
		]);
    }
	
	/**
	 * getData
	 * @param $repository
	 * @return array
	 */
    private function getData($repo) {
		$items = $repo->findBy([
			'userId' => $this->getUser()->getId(),
			'route' => "{$this->route}index"
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
	 * resetSort
	 * @Route("/sort/reset", name="reset_sorting", methods="GET")
	 */
	public function resetSort(
		EntityManagerInterface $entityManager
	): Response
	{
		$routeSortingList = $this->routeSortRepository->findBy([
			'route' => "{$this->route}index",
			'userId' => $this->getUser()->getId()
		]);
		
		if ($routeSortingList) {
			foreach ($routeSortingList as $routeSorting) {
				$entityManager->remove($routeSorting);
			}
			
			$entityManager->flush();
		}
		
		$this->addFlash('notice', $this->translator->trans('rest.erased'));
		
		return $this->redirectToRoute("{$this->route}index");
	}

	/**
	 * sortReset
	 * @Route("/sort/{field}/reset", name="reset_field_sorting", methods="GET")
	 */
	public function resetFieldSorting(
		EntityManagerInterface $entityManager,
		$field
	): Response
	{
		$routeSort = $this->routeSortRepository->findOneBy([
			'route' => "{$this->route}index",
			'userId' => $this->getUser()->getId(),
			'field' => $field,
		]);
		
		if ($routeSort) {
			$entityManager->remove($routeSort);
			$entityManager->flush();
		}
		
		return $this->redirectToRoute("{$this->route}index");
	}
	
	/**
	 * sort
	 * @Route("/sort/{field}/{order}", name="sort_field", methods="GET")
	 */
	public function sortField(
		EntityManagerInterface $entityManager,
		$field,
		$order
	): Response
	{
		if ($order !== 'asc' && $order !== 'desc') {
			return $this->redirectToRoute("{$this->route}index");
		}

		$routeSort = $this->routeSortRepository->findOneBy([
			'route' => "{$this->route}index",
			'userId' => $this->getUser()->getId(),
			'field' => $field,
		]);
		
		if (!$routeSort) {
			$routeSort = new $this->routeSortEntity();
		}
		
		$routeSort->setRoute("{$this->route}index");
		$routeSort->setUserId($this->getUser()->getId());
		$routeSort->setField($field);
		$routeSort->setValue($order);
		
		$entityManager->persist($routeSort);
		$entityManager->flush();
		
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
		$routePagination = $this->routePaginationRepository->findOneBy([
			'route' => "{$this->route}index",
			'userId' => $this->getUser()->getId(),
			'field' => $field,
		]);
		
		if (!$routePagination) {
			$routePagination = new $this->routePaginationEntity();
		}
		
		$routePagination->setRoute("{$this->route}index");
		$routePagination->setUserId($this->getUser()->getId());
		$routePagination->setField($field);
		$routePagination->setValue($value);
		
		$entityManager->persist($routePagination);
		$entityManager->flush();
		
		return $this->redirectToRoute("{$this->route}index");
	}
	
	/**
	 * generateSearchForm
	 * @param $data
	 * @return \Symfony\Component\Form\FormInterface
	 */
    private function generateSearchForm($data) {
		$formBuilder = $this->createFormBuilder($data);
		$formBuilder->setAction($this->generateUrl("{$this->route}filter"));
	
		foreach ($this->columns as $column) {
			if (!isset($column['searchType'])) continue;
		
			$attributes = $column['attributes'];
			$searchLabel = $column['name'];
			$searchType = $column['searchType'];
			$searchName = join('_', $attributes);
			$searchOptions = isset($column['searchOptions']) ? $column['searchOptions'] : [];
		
			switch ($searchType) {
				case ChoiceType::class:
					$values = [
						$this->translator->trans('rest.all') => '',
						$this->translator->trans('rest.empty') => 'null'
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
				'placeholder' => $searchLabel
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
	 * filter
	 * @Route("/filter", name="filter", methods="POST")
	 */
	public function filter(
		Request $request,
		EntityManagerInterface $entityManager
	): Response
	{
		$searchData = $this->getData($this->routeSearchRepository);
		
		$form = $this->generateSearchForm($searchData);
		
		$form->handleRequest($request);
		
		if ($form->isSubmitted()) {
			$searchData = $form->getData();
			$user = $this->getUser();
			
			$routeSearchList = $this->routeSearchRepository->findBy([
				'userId' => $this->getUser()->getId(),
				'route' => "{$this->route}index"
			]);
			
			foreach ($routeSearchList as $routeSearch) {
				$entityManager->remove($routeSearch);
			}
			
			foreach ($searchData as $key => $value) {
				$routeSearch = new $this->routeSearchEntity();
				$routeSearch->setUserId($user->getId());
				$routeSearch->setRoute("{$this->route}index");
				$routeSearch->setField($key);
				$routeSearch->setValue($value);
				
				$entityManager->persist($routeSearch);
			}
			
			$entityManager->flush();
		}
		
		return $this->redirectToRoute("{$this->route}index");
	}

    /**
     * @Route("/filter/reset", name="filter_reset", methods="GET")
     */
    public function filterReset(
		EntityManagerInterface $entityManager
	): Response
    {
		$routeSearchList = $this->routeSearchRepository->findBy([
			'userId' => $this->getUser()->getId(),
			'route' => "{$this->route}index"
		]);
		
		if ($routeSearchList) {
			foreach ($routeSearchList as $routeSearch) {
				$entityManager->remove($routeSearch);
			}
			
			$entityManager->flush();
		}
		
		$this->addFlash('notice', $this->translator->trans('rest.erased'));
    	
		return $this->redirectToRoute("{$this->route}index");
    }

    /**
     * @Route("/new", name="new", methods="GET|POST")
     */
    public function new(Request $request): Response
    {
		return $this->generateForm($request, new $this->entity());
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
            $this->addFlash('error', $this->translator->trans('rest.notfound'));
            return $this->redirectToRoute("{$this->route}index");
        }

		return $this->generateForm($request, $item);
    }
    
    private function generateForm($request, $item) {
        $classNamespace = explode('\\', get_class($item));
        $className = array_pop($classNamespace);
        
        $form = $this->createForm($this->form, $item);

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
				
				$this->addFlash('notice', $this->translator->trans('rest.saved'));
				return $this->redirectToRoute("{$this->route}index");
			} else {
				foreach ($form->getErrors() as $error) {
					$this->addFlash('error', $error->getMessage());
				}
			}
		}
	
		return $this->render('abstract/edit.html.twig', [
			'item' => $item,
            'form' => $form->createView(),
            'route' => $this->route,
            'title' => $this->getTitle(),
		]);
	}

	/**
     * @Route("/{id}/{field}/delete", name="delete_image", methods="GET")
     */
    public function deleteImage(
		Request $request, 
		$id,
		$field
	): Response
    {
		$item = $this->repository->findOneBy(['id' => $id]);

        if (!$item) {
            $this->addFlash('error', $this->translator->trans('rest.notfound'));
            return $this->redirectToRoute("{$this->route}index");
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
	
        $this->addFlash('notice', $this->translator->trans('rest.imageDeleted'));
        
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
            $this->addFlash('error', $this->translator->trans('rest.notfound'));
            return $this->redirectToRoute("{$this->route}index");
        }

		$entityManager->remove($item);
		$entityManager->flush();
	
        $this->addFlash('notice', $this->translator->trans('rest.deleted'));
        
        return $this->redirectToRoute("{$this->route}index");
	}
}