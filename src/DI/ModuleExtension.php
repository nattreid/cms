<?php

namespace NAttreid\Crm\DI;

use Kdyby\Translation\Translator;
use NAttreid\Crm\LoaderFactory;
use NAttreid\Crm\Routing\Router;
use Nette\DI\Statement;
use Nette\Utils\Strings;

/**
 * Rozsireni modulu crm
 *
 * @author Attreid <attreid@gmail.com>
 */
abstract class ModuleExtension extends \Nette\DI\CompilerExtension
{

	/**
	 * Nazev modulu
	 * @var string
	 */
	protected $namespace;

	/**
	 * Cesta k DI slozce
	 * @var string
	 */
	protected $dir;

	/**
	 * Nazev namespace modulu
	 * @var string
	 */
	protected $package;

	/** @var LoaderFactory */
	private $loader;

	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		$this->setRouting();
		$this->setTranslation();
		$this->setMenu();

		$uName = Strings::firstUpper($this->namespace);

		$builder->getDefinition('application.presenterFactory')
			->addSetup('setMapping', [
				[$uName => $this->package . $uName . '\Presenters\*Presenter']
			]);
	}

	private function setRouting()
	{
		$builder = $this->getContainerBuilder();

		$router = $builder->getByType(Router::class);
		try {
			$builder->getDefinition($router)
				->addSetup('addModule', [$this->namespace]);
		} catch (\Nette\DI\MissingServiceException $ex) {
			throw new \Nette\DI\MissingServiceException("Missing extension 'nattreid/crm'");
		}
	}

	private function setTranslation()
	{
		$builder = $this->getContainerBuilder();
		try {
			$translator = $builder->getByType(Translator::class);
			$def = $builder->getDefinition($translator);
			$setup = [
				new Statement('addResource', ['neon', $this->dir . '/../lang/' . $this->namespace . '.cs_CZ.neon', 'cs_CZ', $this->namespace]),
				new Statement('addResource', ['neon', $this->dir . '/../lang/' . $this->namespace . '.en_US.neon', 'en_US', $this->namespace])
			];
			$def->setSetup(array_merge($def->getSetup(), $setup));
		} catch (\Nette\DI\MissingServiceException $ex) {
			throw new \Nette\DI\MissingServiceException("Missing extension 'kdyby/translation'");
		}
	}

	private function setMenu()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->loadFromFile($this->dir . '/default.neon'), $this->config);

		$menu = [
			$this->namespace => [
					'link' => $config['link']
				] + $config['menu']
		];

		foreach ($builder->findByTag('crm.menu') as $service => $attr) {
			$builder->getDefinition($service)
				->addSetup('addMenu', [$menu, NULL, $config['position']]);
		}
	}

	/**
	 * Prida soubor do loaderu
	 * @param string $file
	 * @param string $locale
	 */
	protected function addLoaderFile($file, $locale = NULL)
	{
		if ($this->loader === NULL) {
			$builder = $this->getContainerBuilder();

			$loader = $builder->getByType(LoaderFactory::class);
			$this->loader = $builder->getDefinition($loader);
		}
		$this->loader->addSetup('addFile', [$file, $locale]);
	}

}
