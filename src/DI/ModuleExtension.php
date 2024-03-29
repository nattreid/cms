<?php

declare(strict_types=1);

namespace NAttreid\Cms\DI;

use NAttreid\Cms\Factories\ICmsMenuFactory;
use NAttreid\Cms\Factories\LoaderFactory;
use NAttreid\Cms\Routing\Router;
use Nette\DI\CompilerExtension;
use Nette\DI\MissingServiceException;
use Nette\Utils\Strings;

/**
 * Rozsireni modulu CMS
 *
 * @author Attreid <attreid@gmail.com>
 */
abstract class ModuleExtension extends CompilerExtension
{

	use ExtensionTranslatorTrait;

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

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		$this->setRouting();
		$this->setTranslation($this->dir . '/../lang/', [
			$this->namespace
		]);
		$this->setMenu();

		$uName = Strings::firstUpper($this->namespace);

		$builder->getDefinition('application.presenterFactory')
			->addSetup('setMapping', [
				[$uName => $this->package . $uName . '\Presenters\*Presenter']
			]);
	}

	private function setRouting(): void
	{
		$builder = $this->getContainerBuilder();

		$router = $builder->getByType(Router::class);
		try {
			$builder->getDefinition($router)
				->addSetup('addModule', [$this->namespace]);
		} catch (MissingServiceException $ex) {
			throw new MissingServiceException("Missing extension 'nattreid/cms'");
		}
	}

	private function setMenu(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->loadFromFile($this->dir . '/default.neon'), $this->config);

		$items = [
			$this->namespace => [
					'link' => $config['link']
				] + $config['menu']
		];

		$menu = $builder->getByType(ICmsMenuFactory::class);
		$builder->getDefinition($menu)
			->getResultDefinition()
			->addSetup('addMenu', [$items, null, $config['position']]);

	}

	/**
	 * Prida soubor do loaderu
	 * @param string $file
	 * @param string $locale
	 */
	protected function addLoaderFile(string $file, string $locale = null): void
	{
		if ($this->loader === null) {
			$builder = $this->getContainerBuilder();

			$loader = $builder->getByType(LoaderFactory::class);
			$this->loader = $builder->getDefinition($loader);
		}
		$this->loader->addSetup('addFile', [$file, $locale]);
	}

}
