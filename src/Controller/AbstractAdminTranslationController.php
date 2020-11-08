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

abstract class AbstractAdminTranslationController extends Controller
{

	/**
     * @Route("/", name="index", methods="GET|POST")
     */
    public function index(Request $request, TranslatorInterface $translator): Response
    {
		// file path
    	$locale = $translator->getLocale();
        $path = "translations/messages.$locale.yml";

		$groups = [];
	
		foreach (Yaml::parseFile($path) as $key => $value) {
			$explodedKey = explode('.', $key);

			if (count($explodedKey) > 1) {
				$group = $explodedKey[0];
				array_push($groups, $group);
			}
			
		}

		return $this->render('translation/index.html.twig', ['groups' => array_unique($groups)]);
	}

	/**
     * @Route("/new", name="new", methods="GET|POST")
     */
    public function new(Request $request, TranslatorInterface $translator): Response
    {
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
				$locale = $translator->getLocale();
				$path = "translations/messages.$locale.yml";
				$translations = Yaml::parseFile($path);

				if ($group) {
					$translations["$group.$key"] = $value;
				} else {
					$translations[$key] = $value;
				}

				// read and write new yaml
				$yaml = Yaml::dump($translations);

				file_put_contents($path, $yaml);
				
				// clear translation cache
				$fileSystem = new Filesystem();
				$cacheDir = $this->get('kernel')->getCacheDir();
				$fileSystem->remove("$cacheDir/translations");
				
				$this->addFlash('notice', $translator->trans('rest.saved'));
				return $this->redirectToRoute("admin_translation_index");
			} else {
				foreach ($form->getErrors() as $error) {
					$this->addFlash('error', $error->getMessage());
				}
			}
        }
		
        return $this->render('translation/edit.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/edit", name="edit", methods="GET|POST")
     */
    public function edit(Request $request, TranslatorInterface $translator): Response
    {
    	// file path
    	$locale = $translator->getLocale();
        $path = "translations/messages.$locale.yml";
        
		$translations = [];
		$groupTranslations = [];

		$group = $request->query->get('id');
	
		foreach (Yaml::parseFile($path) as $key => $value) {
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
				file_put_contents($path, $yaml);
				
				// clear translation cache
				$fileSystem = new Filesystem();
				$cacheDir = $this->get('kernel')->getCacheDir();
				$fileSystem->remove("$cacheDir/translations");
				
				$this->addFlash('notice', $translator->trans('rest.saved'));
				return $this->redirectToRoute("admin_translation_index");
			} else {
				foreach ($form->getErrors() as $error) {
					$this->addFlash('error', $error->getMessage());
				}
			}
        }
		
        return $this->render('translation/edit.html.twig', ['form' => $form->createView()]);
    }
}