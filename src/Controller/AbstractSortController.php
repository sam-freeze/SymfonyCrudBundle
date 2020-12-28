<?php

namespace SamFreeze\SymfonyCrudBundle\Controller;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use SamFreeze\SymfonyCrudBundle\Repository\AbstractRouteSortRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractSortController extends Controller {
	
	protected $translator;
	
	protected $repository;
	
	function __construct(
		TranslatorInterface $translator,
		AbstractRouteSortRepository $repository
	) {
		$this->translator = $translator;
		$this->repository = $repository;
	}

	/**
	 * reset sorting
	 * @Route("/{route}/reset", name="reset_sorting", methods="GET")
	 */
	public function resetSort(
		EntityManagerInterface $entityManager,
		$route
	): Response
	{
		$routeSortingList = $this->repository->findBy([
			'route' => $route,
			'userId' => $this->getUser()->getId()
		]);
		
		if ($routeSortingList) {
			foreach ($routeSortingList as $routeSorting) {
				$entityManager->remove($routeSorting);
			}
			
			$entityManager->flush();
		}
		
		$this->addFlash('notice', $this->trans('erased'));
		
		return $this->redirectToRoute($route);
	}

	/**
	 * reset sorting field
	 * @Route("/{route}/{field}/reset", name="reset_field_sorting", methods="GET")
	 */
	public function resetFieldSorting(
		EntityManagerInterface $entityManager,
		$route,
		$field
	): Response
	{
		$routeSort = $this->repository->findOneBy([
			'route' => $route,
			'userId' => $this->getUser()->getId(),
			'field' => $field,
		]);
		
		if ($routeSort) {
			$entityManager->remove($routeSort);
			$entityManager->flush();
		}
		
		return $this->redirectToRoute($route);
	}
	
	/**
	 * set sorting field
	 * @Route("/{route}/{field}/{order}", name="sort_field", methods="GET")
	 */
	public function sortField(
		EntityManagerInterface $entityManager,
		$route,
		$field,
		$order
	): Response
	{
		if ($order !== 'asc' && $order !== 'desc') {
			return $this->redirectToRoute($route);
		}

		$routeSort = $this->repository->findOneBy([
			'route' => $route,
			'userId' => $this->getUser()->getId(),
			'field' => $field,
		]);
		
		if (!$routeSort) {
			$routeSort = new $this->routeSortEntity();
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