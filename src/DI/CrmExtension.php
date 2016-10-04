<?php

namespace NAttreid\Crm\DI;

use IPub\FlashMessages\FlashNotifier;
use Kdyby\Translation\Translator;
use NAttreid\Crm\Configurator\Configurator;
use NAttreid\Crm\Control\BasePresenter;
use NAttreid\Crm\Control\CrmPresenter;
use NAttreid\Crm\Control\Dockbar;
use NAttreid\Crm\Control\FileManagerPresenter;
use NAttreid\Crm\Control\IDockbarFactory;
use NAttreid\Crm\Control\InfoPresenter;
use NAttreid\Crm\Control\ModulePresenter;
use NAttreid\Crm\Control\ProfilePresenter;
use NAttreid\Crm\Control\SignPresenter;
use NAttreid\Crm\Control\UsersPresenter;
use NAttreid\Crm\Factories\DataGridFactory;
use NAttreid\Crm\Factories\FormFactory;
use NAttreid\Crm\ICrmMenuFactory;
use NAttreid\Crm\LoaderFactory;
use NAttreid\Crm\LocaleService;
use NAttreid\Crm\Mailing\Mailer;
use NAttreid\Crm\Routing\Router;
use NAttreid\Filemanager\FileManager;
use NAttreid\Filemanager\IFileManagerFactory;
use NAttreid\Menu\Menu\Menu;
use NAttreid\Routing\RouterFactory;
use NAttreid\Security\Authenticator;
use NAttreid\Security\Authenticator\MainAuthenticator;
use NAttreid\TracyPlugin\Tracy;
use Nette\DI\CompilerExtension;
use Nette\DI\Helpers;
use Nette\DI\MissingServiceException;
use Nette\DI\ServiceDefinition;
use Nette\DI\Statement;
use Nette\InvalidStateException;
use Nette\Reflection\ClassType;
use Nette\Utils\Finder;
use Nette\Utils\Strings;
use WebLoader\FileNotFoundException;

/**
 * Rozsireni
 *
 * @author Attreid <attreid@gmail.com>
 */
class CrmExtension extends CompilerExtension
{

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->loadFromFile(__DIR__ . '/default.neon'), $this->config);

		if ($config['front'] === null) {
			throw new InvalidStateException("Crm: 'front' does not set in config.neon");
		}

		$config['wwwDir'] = Helpers::expand($config['wwwDir'], $builder->parameters);
		$config['fileManagerDir'] = Helpers::expand($config['fileManagerDir'], $builder->parameters);
		$config['layout'] = Helpers::expand($config['layout'], $builder->parameters);

		$builder->addDefinition($this->prefix('dockbar'))
			->setImplement(IDockbarFactory::class)
			->setFactory(Dockbar::class)
			->setArguments([$config['permissions'], $config['namespace'], $config['front']]);

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

		$builder->addDefinition($this->prefix('authenticator'))
			->setClass(MainAuthenticator::class)
			->setAutowired(false);

		$builder->addDefinition($this->prefix('localeService'))
			->setClass(LocaleService::class);

		$this->setLoader($config);
		$this->setPresenters($config);
		$this->setMenu($config);
		$this->setMailing($config);
	}

	private function setLoader($config)
	{
		$builder = $this->getContainerBuilder();

		$jsFilters = $this->createFilterServices($config['jsFilters'], 'jsFilter');
		$cssFilters = $this->createFilterServices($config['cssFilters'], 'cssFilter');

		$loader = $builder->addDefinition($this->prefix('loaderFactory'))
			->setClass(LoaderFactory::class)
			->setArguments([$config['wwwDir'], $jsFilters, $cssFilters])
			->addSetup('addFile', ['css/crm.boundled.min.css'])
			->addSetup('addFile', ['js/crm.boundled.min.js'])
			->addSetup('addFile', ['js/i18n/crm.cs.min.js', 'cs']);

		if (!empty($config['assets'])) {
			foreach ($this->findFiles($config['assets']) as $file => $locale) {
				$loader->addSetup('addFile', [$file, $locale]);
			}
		}
	}

	private function setPresenters($config)
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

	private function setMenu($config)
	{
		$builder = $this->getContainerBuilder();

		$menu = $builder->addDefinition($this->prefix('menu'))
			->setImplement(ICrmMenuFactory::class)
			->setFactory(Menu::class);

		if (!empty($config['menu'])) {
			$menu->addSetup('addMenu', [
				$config['menu']
			]);
		}
	}

	private function setMailing($config)
	{
		$builder = $this->getContainerBuilder();

		$rc = new ClassType(Mailer::class);
		$dir = dirname($rc->getFileName());
		$builder->addDefinition($this->prefix('mailer'))
			->setClass(Mailer::class)
			->setArguments([$config['sender'], $dir]);
	}

	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->loadFromFile(__DIR__ . '/default.neon'), $this->config);

		$namespace = Strings::firstLower($config['namespace']);

		$this->setRouting($config);
		$this->setTranslation();
		$this->setTracy();
		$this->setFlash();
		$this->setLayout($config);
		$this->setModule($config, $namespace);

		$authenticator = $builder->getByType(Authenticator::class);
		$builder->getDefinition($authenticator)
			->addSetup('add', [$namespace, $builder->getDefinition($this->prefix('authenticator'))]);
	}

	private function setRouting($config)
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
				[$config['namespace'] => 'NAttreid\Crm\Control\*Presenter']
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

	private function setModule($config, $namespace)
	{
		foreach ($this->findByType(BasePresenter::class) as $def) {
			$def->addSetup('setModule', [$config['namespace'], $namespace]);
		}
	}

	private function setLayout($config)
	{
		if ($config['layout'] !== null) {
			foreach ($this->findByType(CrmPresenter::class) as $def) {
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

	private function setTranslation()
	{
		$builder = $this->getContainerBuilder();
		try {
			$translator = $builder->getByType(Translator::class);
			$def = $builder->getDefinition($translator);
			$setup = [
				new Statement('addResource', ['neon', __DIR__ . '/../lang/ublaboo_datagrid.cs_CZ.neon', 'cs_CZ', 'ublaboo_datagrid']),
				new Statement('addResource', ['neon', __DIR__ . '/../lang/ublaboo_datagrid.en_US.neon', 'en_US', 'ublaboo_datagrid']),
				new Statement('addResource', ['neon', __DIR__ . '/../lang/form.cs_CZ.neon', 'cs_CZ', 'form']),
				new Statement('addResource', ['neon', __DIR__ . '/../lang/form.en_US.neon', 'en_US', 'form']),
				new Statement('addResource', ['neon', __DIR__ . '/../lang/mailing.cs_CZ.neon', 'cs_CZ', 'mailing']),
				new Statement('addResource', ['neon', __DIR__ . '/../lang/mailing.en_US.neon', 'en_US', 'mailing']),
				new Statement('addResource', ['neon', __DIR__ . '/../lang/crm.cs_CZ.neon', 'cs_CZ', 'crm']),
				new Statement('addResource', ['neon', __DIR__ . '/../lang/crm.en_US.neon', 'en_US', 'crm']),
				new Statement('addResource', ['neon', __DIR__ . '/../lang/dockbar.cs_CZ.neon', 'cs_CZ', 'dockbar']),
				new Statement('addResource', ['neon', __DIR__ . '/../lang/dockbar.en_US.neon', 'en_US', 'dockbar']),
				new Statement('addResource', ['neon', __DIR__ . '/../lang/default.cs_CZ.neon', 'cs_CZ', 'default']),
				new Statement('addResource', ['neon', __DIR__ . '/../lang/default.en_US.neon', 'en_US', 'default'])
			];
			$def->setSetup(array_merge($def->getSetup(), $setup));
		} catch (MissingServiceException $ex) {
			throw new MissingServiceException("Missing extension 'kdyby/translation'");
		}
	}

	private function setTracy()
	{
		$builder = $this->getContainerBuilder();
		try {
			$tracy = $builder->getByType(Tracy::class);
			$builder->getDefinition($tracy)
				->addSetup('enableMail', ['@' . $this->prefix('configurator') . '::mailPanel']);
		} catch (MissingServiceException $ex) {
			throw new MissingServiceException("Missing extension 'nattreid/tracyplugin'");
		}
	}

	private function setFlash()
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
	private function findByType($type)
	{
		$type = ltrim($type, '\\');
		return array_filter($this->getContainerBuilder()->getDefinitions(), function (ServiceDefinition $def) use ($type) {
			return is_a($def->getClass(), $type, true) || is_a($def->getImplement(), $type, true);
		});
	}

	private function createFilterServices($filters, $filter)
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

	/**
	 * @param array $filesConfig
	 * @return array
	 */
	private function findFiles(array $filesConfig)
	{
		$normalizedFiles = array();

		foreach ($filesConfig as $file) {
			// finder support
			if (is_array($file) && isset($file['files']) && (isset($file['in']) || isset($file['from']))) {
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
					/* @var $foundFile \SplFileInfo */
					$foundFilesList[] = $foundFile->getPathname();
				}

				natsort($foundFilesList);

				foreach ($foundFilesList as $foundFilePathname) {
					$normalizedFiles[$foundFilePathname] = null;
				}
			} elseif (is_array($file)) {
				$this->checkFileExists($file[0]);
				$normalizedFiles[$file[0]] = $file[1];
			} else {
				$this->checkFileExists($file);
				$normalizedFiles[$file] = null;
			}
		}

		return $normalizedFiles;
	}

	/**
	 * @param string $file
	 * @throws FileNotFoundException
	 */
	protected function checkFileExists($file)
	{
		if (!file_exists($file)) {
			throw new FileNotFoundException(sprintf("Neither '%s' was found", $file));
		}
	}
}