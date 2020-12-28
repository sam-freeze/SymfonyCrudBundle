<?php

namespace SamFreeze\SymfonyCrudBundle\Controller;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use SamFreeze\SymfonyCrudBundle\Repository\AbstractRoutePaginationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractPaginationController extends Controller {

	protected $translator;
	
	protected $repository;
	
    function __construct(
		TranslatorInterface $translator,
		AbstractRoutePaginationRepository $repository
	) {
		$this->translator = $translator;
		$this->repository = $repository;
	}

	/**
	 * hook to get new entity
	 */
	abstract function getNewEntity();

	/**
	 * sort
	 * @Route("/{route}/{field}/{value}", name="paginate", methods="GET")
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
			$routePagination = $this->getNewEntity();
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