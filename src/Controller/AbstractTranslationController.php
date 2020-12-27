<?php

namespace SamFreeze\SymfonyCrudBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractTranslationController extends Controller
{

	function __construct(
		TranslatorInterface $translator,
		$title,
		$route,
		$path
	) {
		$this->translator = $translator;
		$this->title = $title;
		$this->route = $route;
		$this->path = $path;
	}

	public function getPath(): String
    {
		return $this->path;
	}

	public function getRoute(): String
    {
		return $this->route;
	}

	public function getTitle(): String
    {
		return $this->title;
	}

	/**
     * @Route("/", name="index", methods="GET|POST")
     */
    public function index(Request $request, TranslatorInterface $translator): Response
    {
		// file path
		$locale = $translator->getLocale();
		$title = $this->getTitle();
		$path = $this->getPath();
        $filePath = "$path/messages.$locale.yml";

		$groups = [];
	
		foreach (Yaml::parseFile($filePath) as $key => $value) {
			$explodedKey = explode('.', $key);

			if (count($explodedKey) > 1) {
				$group = $explodedKey[0];
				array_push($groups, $group);
			}
			
		}

		return $this->render("$title/index.html.twig", ['groups' => array_unique($groups)]);
	}

	/**
     * @Route("/new", name="new", methods="GET|POST")
     */
    public function new(Request $request, TranslatorInterface $translator): Response
    {
		$locale = $translator->getLocale();
		$path = $this->getPath();
		$route = $this->getRoute();
		$title = $this->getTitle();

		// build form by group
		$formBuilder = $this->createFormBuilder();
		
		$formBuilder->add('group', TextType::class, [
			'required' => false
		]);
		$formBuilder->add('key', TextType::class);
		$formBuilder->add('value', TextareaType::class);
		
        $form = $formBuilder->getForm();
        
        $form->handleRequest($request);
		
        // submitted form
		if ($form->isSubmitted()) {
			
			if ($form->isValid()) {
				$data = $form->getData();
				$group = $data['group'];
				$key = $data['key'];
				$value = $data['value'];

				// file path
				$filePath = "$path/messages.$locale.yml";
				$translations = Yaml::parseFile($filePath);

				if ($group) {
					$translations["$group.$key"] = $value;
				} else {
					$translations[$key] = $value;
				}

				// read and write new yaml
				$yaml = Yaml::dump($translations);

				file_put_contents($filePath, $yaml);
				
				// clear translation cache
				$fileSystem = new Filesystem();
				$cacheDir = $this->get('kernel')->getCacheDir();
				$fileSystem->remove("$cacheDir/$path");
				
				$this->addFlash('notice', $translator->trans('admin.saved', [], 'SymfonyCrudBundle'));
				return $this->redirectToRoute("{$route}index");
			} else {
				foreach ($form->getErrors() as $error) {
					$this->addFlash('error', $error->getMessage());
				}
			}
		}
		
        return $this->render("$title/edit.html.twig", ['form' => $form->createView()]);
    }

    /**
     * @Route("/edit", name="edit", methods="GET|POST")
     */
    public function edit(Request $request, TranslatorInterface $translator): Response
    {
    	// file path
		$locale = $translator->getLocale();
		$path = $this->getPath();
		$route = $this->getRoute();
		$title = $this->getTitle();
        $filePath = "$path/messages.$locale.yml";
        
		$translations = [];
		$groupTranslations = [];

		$group = $request->query->get('id');
	
		foreach (Yaml::parseFile($filePath) as $key => $value) {
			$explodedKey = explode('.', $key);
			
			$translations[$key] = $value;

			if ((empty($group) && count($explodedKey) == 1)
				|| (!empty($group) && count($explodedKey) > 1) && $explodedKey[0] == $group) {
				$groupTranslations[str_replace( '.', '_', $key)] = $value;
			}
		}
	
		// build form by group
		$formBuilder = $this->createFormBuilder($groupTranslations);
		
		foreach ($groupTranslations as $key => $value) {
			$formBuilder->add($key, TextareaType::class);
		}
		
        $form = $formBuilder->getForm();
        
        $form->handleRequest($request);
		
        // submitted form
		if ($form->isSubmitted()) {
			
			if ($form->isValid()) {
				foreach ($form->getData() as $key => $value) {
					$translations[str_replace('_', '.', $key)] = $value;
				}
				
				// write new yaml
				$yaml = Yaml::dump($translations);
				file_put_contents($filePath, $yaml);
				
				// clear translation cache
				$fileSystem = new Filesystem();
				$cacheDir = $this->get('kernel')->getCacheDir();
				$fileSystem->remove("$cacheDir/$path");
				
				$this->addFlash('notice', $translator->trans('admin.saved', [], 'SymfonyCrudBundle'));
				return $this->redirectToRoute("{$route}index");
			} else {
				foreach ($form->getErrors() as $error) {
					$this->addFlash('error', $error->getMessage());
				}
			}
        }
		
        return $this->render("$title/edit.html.twig", ['form' => $form->createView()]);
    }
}