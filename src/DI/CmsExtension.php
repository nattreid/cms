<?php

declare(strict_types=1);

namespace NAttreid\Cms\DI;

use IPub\FlashMessages\FlashNotifier;
use NAttreid\Cms\Configurator\Configurator;
use NAttreid\Cms\Control\BasePresenter;
use NAttreid\Cms\Control\CmsPresenter;
use NAttreid\Cms\Control\Dockbar;
use NAttreid\Cms\Control\FileManagerPresenter;
use NAttreid\Cms\Control\IDockbarFactory;
use NAttreid\Cms\Control\InfoPresenter;
use NAttreid\Cms\Control\IPermissionListFactory;
use NAttreid\Cms\Control\ModulePresenter;
use NAttreid\Cms\Control\PermissionList;
use NAttreid\Cms\Control\ProfilePresenter;
use NAttreid\Cms\Control\SignPresenter;
use NAttreid\Cms\Control\UsersPresenter;
use NAttreid\Cms\Factories\DataGridFactory;
use NAttreid\Cms\Factories\FormFactory;
use NAttreid\Cms\Factories\ICmsMenuFactory;
use NAttreid\Cms\Factories\LoaderFactory;
use NAttreid\Cms\LocaleService;
use NAttreid\Cms\Mailing\Mailer;
use NAttreid\Cms\Routing\Router;
use NAttreid\FileManager\FileManager;
use NAttreid\FileManager\IFileManagerFactory;
use NAttreid\Menu\Menu\Menu;
use NAttreid\Routing\RouterFactory;
use NAttreid\Security\Authenticator\Authenticator;
use NAttreid\TracyPlugin\Tracy;
use Nette\DI\CompilerExtension;
use Nette\DI\Helpers;
use Nette\DI\MissingServiceException;
use Nette\DI\ServiceDefinition;
use Nette\InvalidStateException;
use Nette\Reflection\ClassType;
use Nette\Utils\Finder;
use Nette\Utils\Strings;
use SplFileInfo;
use WebLoader\FileNotFoundException;

/**
 * Rozsireni
 *
 * @author Attreid <attreid@gmail.com>
 */
class CmsExtension extends CompilerExtension
{
	use ExtensionTranslatorTrait;

	/** @var string */
	private $wwwDir;

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->loadFromFile(__DIR__ . '/default.neon'), $this->config);

		if ($config['front'] === null) {
			throw new InvalidStateException("Cms: 'front' does not set in config.neon");
		}
		if ($config['tracy']['cookie'] === null) {
			throw new InvalidStateException("Cms: 'tracy.cookie' does not set in config.neon");
		}

		$this->wwwDir = $config['wwwDir'] = Helpers::expand($config['wwwDir'], $builder->parameters);
		$config['fileManagerDir'] = Helpers::expand($config['fileManagerDir'], $builder->parameters);
		$config['layout'] = Helpers::expand($config['layout'], $builder->parameters);
		$config['tracy']['mailPath'] = Helpers::expand($config['tracy']['mailPath'], $builder->parameters);

		$builder->addDefinition($this->prefix('dockbar'))
			->setImplement(IDockbarFactory::class)
			->setFactory(Dockbar::class)
			->setArguments([$config['permissions'], $config['namespace'], $config['front']]);

		$builder->addDefinition($this->prefix('permissionList'))
			->setImplement(IPermissionListFactory::class)
			->setFactory(PermissionList::class);

		$builder->addDefinition($this->prefix('fileManagerFactory'))
			->setImplement(IFileManagerFactory::class)
			->setFactory(FileManager::class);

		$builder->addDefinition($this->prefix('router'))
			->setClass(Router::class)
			->setArguments([$config['namespace'], $config['url']]);

		$builder->addDefinition($this->prefix('configurator'))
			->setClass(Configurator::class);

		$builder->addDefinition($this->prefix('formFactory'))
			->setClass(FormFactory::class);

		$builder->addDefinition($this->prefix('dataGridFactory'))
			->setClass(DataGridFactory::class);

		$builder->addDefinition($this->prefix('localeService'))
			->setClass(LocaleService::class);

		$this->setLoader($config);
		$this->setPresenters($config);
		$this->setMenu($config);
		$this->setMailing($config);
		$this->setTracy($config);
	}

	private function setLoader(array $config)
	{
		$builder = $this->getContainerBuilder();

		$jsFilters = $this->createFilterServices($config['jsFilters'] ?? [], 'jsFilter');
		$cssFilters = $this->createFilterServices($config['cssFilters'] ?? [], 'cssFilter');

		$loader = $builder->addDefinition($this->prefix('loaderFactory'))
			->setClass(LoaderFactory::class)
			->setArguments([$config['wwwDir'], $jsFilters, $cssFilters])
			->addSetup('addFile', ['css/cms.boundled.min.css'])
			->addSetup('addFile', ['js/cms.boundled.min.js'])
			->addSetup('addFile', ['js/i18n/cms.cs.min.js', 'cs']);

		if (!empty($config['assets'])) {
			$this->addLoaderFiles($loader, $config['assets']);
		}
	}

	/**
	 * @param ServiceDefinition $loader
	 * @param array $assets
	 */
	private function addLoaderFiles(ServiceDefinition $loader, array $assets)
	{
		foreach ($assets as $file) {
			if (is_array($file) && isset($file['files']) && (isset($file['in']) || isset($file['from']))) {
				$this->addLoaderFileByFinder($loader, $file);
			} elseif (is_array($file)) {
				$this->addLoaderFile($loader, $file);
			} elseif (is_string($file)) {
				$this->checkFileExists($file);
				$loader->addSetup('addFile', [$file]);
			}
		}
	}

	/**
	 * @param ServiceDefinition $loader
	 * @param string[] $file
	 */
	private function addLoaderFileByFinder(ServiceDefinition $loader, array $file)
	{
		$finder = Finder::findFiles($file['files']);

		if (isset($file['exclude'])) {
			$finder->exclude($file['exclude']);
		}

		if (isset($file['in'])) {
			$finder->in($file['in']);
		} else {
			$finder->from($file['from']);
		}

		$foundFilesList = [];
		foreach ($finder as $foundFile) {
			/* @var $foundFile SplFileInfo */
			$foundFilesList[] = $foundFile->getPathname();
		}

		natsort($foundFilesList);

		foreach ($foundFilesList as $foundFilePathname) {
			$loader->addSetup('addFile', [$foundFilePathname]);
		}
	}

	/**
	 * @param ServiceDefinition $loader
	 * @param string[] $file
	 */
	private function addLoaderFile(ServiceDefinition $loader, array $file)
	{
		$name = $file[0];
		$locale = isset($file['locale']) ? $file['locale'] : null;
		$remote = isset($file['remote']) ? (bool) $file['remote'] : false;
		$this->checkFileExists($name);
		if ($remote) {
			$loader->addSetup('addRemoteFile', [$name, $locale]);
		} else {
			$loader->addSetup('addFile', [$name, $locale]);
		}
	}

	/**
	 * @param string $file
	 * @throws FileNotFoundException
	 */
	private function checkFileExists(string $file)
	{
		if (!file_exists($file)) {
			if (!file_exists($this->wwwDir . $file)) {
				throw new FileNotFoundException(sprintf("Neither '%s' was found", $file));
			}
		}
	}

	private function setPresenters(array $config)
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('profile'))
			->setClass(ProfilePresenter::class)
			->setArguments([$config['minPasswordLength']]);

		$builder->addDefinition($this->prefix('fileManager'))
			->setClass(FileManagerPresenter::class)
			->setArguments([$config['fileManagerDir']]);

		$builder->addDefinition($this->prefix('info'))
			->setClass(InfoPresenter::class)
			->setArguments([$config['infoRefresh']]);

		$builder->addDefinition($this->prefix('users'))
			->setClass(UsersPresenter::class)
			->setArguments([$config['passwordChars'], $config['minPasswordLength']]);

		$builder->addDefinition($this->prefix('sign'))
			->setClass(SignPresenter::class)
			->setArguments([$config['loginExpiration'], $config['sessionExpiration'], $config['minPasswordLength']]);
	}

	private function setMenu(array $config)
	{
		$builder = $this->getContainerBuilder();

		$menu = $builder->addDefinition($this->prefix('menu'))
			->setImplement(ICmsMenuFactory::class)
			->setFactory(Menu::class);

		if (!empty($config['menu'])) {
			$menu->addSetup('addMenu', [
				$config['menu']
			]);
		}
	}

	private function setMailing(array $config)
	{
		$builder = $this->getContainerBuilder();

		$rc = new ClassType(Mailer::class);
		$dir = dirname($rc->getFileName());
		$builder->addDefinition($this->prefix('mailer'))
			->setClass(Mailer::class)
			->setArguments([$config['sender'], [], $dir]);
	}

	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->loadFromFile(__DIR__ . '/default.neon'), $this->config);

		$namespace = Strings::firstLower($config['namespace']);

		$this->setRouting($config);
		$this->setTranslation(__DIR__ . '/../lang/', [
			'ublaboo_datagrid',
			'form',
			'cms',
			'security',
			'dockbar',
			'default'
		]);
		$this->setFlashMessages();
		$this->setLayout($config);
		$this->setModule($config, $namespace);

		$authenticator = $builder->getByType(Authenticator::class);
		$builder->getDefinition($authenticator)
			->addSetup('addMapping', [$namespace, '']);
	}

	public function afterCompile(\Nette\PhpGenerator\ClassType $class)
	{
		$initialize = $class->methods['initialize'];
		if (class_exists('Tracy\Debugger')) {
			$initialize->addBody('$this->getService(?)->run();', [$this->prefix('tracyPlugin')]);
		}
	}

	private function setRouting(array $config)
	{
		$builder = $this->getContainerBuilder();
		$routerFactory = $builder->getByType(RouterFactory::class);
		try {
			$builder->getDefinition($routerFactory)
				->addSetup('addRouter', ['@' . $this->prefix('router'), RouterFactory::PRIORITY_APP])
				->addSetup('setLocale', ['@' . $this->prefix('localeService') . '::default', '@' . $this->prefix('localeService') . '::allowed']);
		} catch (MissingServiceException $ex) {
			throw new MissingServiceException("Missing extension 'nattreid/routing'");
		}

		$presenterFactory = $builder->getDefinition('application.presenterFactory')
			->addSetup('setMapping', [
				[$config['namespace'] => 'NAttreid\Cms\Control\*Presenter']
			]);

		$router = $builder->getByType(Router::class);
		if (!empty($config['menu'])) {
			foreach ($config['menu'] as $module => $arr) {
				$name = Strings::firstUpper($module);

				$builder->getDefinition($router)
					->addSetup('addModule', [$module]);

				$presenterFactory->addSetup('setMapping', [
					[$name => preg_replace('/\*/', $name, $config['moduleMapping'], 1)]
				]);
			}
		}
	}

	private function setModule(array $config, string $namespace)
	{
		foreach ($this->findByType(BasePresenter::class) as $def) {
			$def->addSetup('setModule', [$config['namespace'], $namespace]);
		}
	}

	private function setLayout(array $config)
	{
		if ($config['layout'] !== null) {
			foreach ($this->findByType(CmsPresenter::class) as $def) {
				$def->addSetup('setLayout', [$config['layout']]);
			}
			foreach ($this->findByType(ModulePresenter::class) as $def) {
				$def->addSetup('setLayout', [$config['layout']]);
			}
		} else {
			foreach ($this->findByType(ModulePresenter::class) as $def) {
				$def->addSetup('setLayout', [__DIR__ . '/../Control/presenters/templates/@layout.latte']);
			}
		}
	}

	private function setTracy(array $config)
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('tracyPlugin'))
			->setClass(Tracy::class)
			->setArguments([$config['tracy']['cookie']])
			->addSetup('setMail', [$config['tracy']['mailPath'], '@' . $this->prefix('configurator') . '::mailPanel']);
	}

	private function setFlashMessages()
	{
		$builder = $this->getContainerBuilder();
		try {
			$flash = $builder->getByType(FlashNotifier::class);
			$builder->getDefinition($flash);
		} catch (MissingServiceException $ex) {
			throw new MissingServiceException("Missing extension 'nattreid/flash-messages'");
		}
	}

	/**
	 *
	 * @param string $type
	 * @return ServiceDefinition[]
	 */
	private function findByType(string $type): array
	{
		$type = ltrim($type, '\\');
		return array_filter($this->getContainerBuilder()->getDefinitions(), function (ServiceDefinition $def) use ($type) {
			return is_a($def->getClass(), $type, true) || is_a($def->getImplement(), $type, true);
		});
	}

	/**
	 * @param string[] $filters
	 * @param string $filter
	 * @return string[]
	 * @return string[]
	 */
	private function createFilterServices(array $filters, string $filter): array
	{
		$builder = $this->getContainerBuilder();
		$result = [];
		$counter = 1;
		foreach ($filters as $class) {
			$name = $this->prefix($filter . $counter++);
			$builder->addDefinition($name)
				->setClass($class);
			$result[] = '@' . $name;
		}
		return $result;
	}
}