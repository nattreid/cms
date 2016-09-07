<?php

namespace NAttreid\Crm\DI;

use IPub\FlashMessages\FlashNotifier;
use Kdyby\Translation\Translator;
use NAttreid\Crm\Control\BasePresenter;
use NAttreid\Crm\Control\CrmPresenter;
use NAttreid\Crm\Control\ExtensionPresenter;
use NAttreid\Crm\Control\ModulePresenter;
use NAttreid\Crm\Mailing\Mailer;
use NAttreid\Routing\RouterFactory;
use NAttreid\Security\Authenticator;
use NAttreid\TracyPlugin\Tracy;
use Nette\DI\ServiceDefinition;
use Nette\DI\Statement;
use Nette\Utils\Finder;
use Nette\Utils\Strings;

/**
 * Rozsireni
 *
 * @author Attreid <attreid@gmail.com>
 */
class CrmExtension extends \Nette\DI\CompilerExtension
{

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->loadFromFile(__DIR__ . '/default.neon'), $this->config);

		if ($config['front'] === NULL) {
			throw new \Nette\InvalidStateException("Crm: 'front' does not set in config.neon");
		}

		$config['wwwDir'] = \Nette\DI\Helpers::expand($config['wwwDir'], $builder->parameters);
		$config['fileManagerDir'] = \Nette\DI\Helpers::expand($config['fileManagerDir'], $builder->parameters);
		$config['layout'] = \Nette\DI\Helpers::expand($config['layout'], $builder->parameters);

		$builder->addDefinition($this->prefix('dockbar'))
			->setImplement(\NAttreid\Crm\Control\IDockbarFactory::class)
			->setFactory(\NAttreid\Crm\Control\Dockbar::class)
			->setArguments([$config['permissions'], $config['namespace'], $config['front']]);

		$builder->addDefinition($this->prefix('fileManagerFactory'))
			->setImplement(\NAttreid\Filemanager\IFileManagerFactory::class)
			->setFactory(\NAttreid\Filemanager\FileManager::class);

		$builder->addDefinition($this->prefix('router'))
			->setClass(\NAttreid\Crm\Routing\Router::class)
			->setArguments([$config['namespace'], $config['url'], $config['secured']]);

		$builder->addDefinition($this->prefix('configurator'))
			->setClass(\NAttreid\Crm\Configurator::class)
			->setArguments([$config['locales']]);;

		$builder->addDefinition($this->prefix('formFactory'))
			->setClass(\NAttreid\Crm\Factories\FormFactory::class);

		$builder->addDefinition($this->prefix('dataGridFactory'))
			->setClass(\NAttreid\Crm\Factories\DataGridFactory::class);

		$builder->addDefinition($this->prefix('authenticator'))
			->setClass(\NAttreid\Security\Authenticator\MainAuthenticator::class)
			->setAutowired(FALSE);

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
			->setClass(\NAttreid\Crm\LoaderFactory::class)
			->setArguments([$config['wwwDir'], $jsFilters, $cssFilters])
			->addSetup('addFile', ['css/crm.boundled.min.css'])
			->addSetup('addFile', ['js/crm.boundled.min.js'])
			->addSetup('addFile', ['js/i18n/crm.cs.min.js', 'cs']);

		if (!empty($config['assets'])) {
			foreach ($this->findFiles($config['assets']) as $file) {
				$loader->addSetup('addFile', [$file]);
			}
		}
	}

	private function setPresenters($config)
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('profile'))
			->setClass(\NAttreid\Crm\Control\ProfilePresenter::class)
			->setArguments([$config['minPasswordLength']]);

		$builder->addDefinition($this->prefix('fileManager'))
			->setClass(\NAttreid\Crm\Control\FileManagerPresenter::class)
			->setArguments([$config['fileManagerDir']]);

		$builder->addDefinition($this->prefix('info'))
			->setClass(\NAttreid\Crm\Control\InfoPresenter::class)
			->setArguments([$config['infoRefresh']]);

		$builder->addDefinition($this->prefix('users'))
			->setClass(\NAttreid\Crm\Control\UsersPresenter::class)
			->setArguments([$config['passwordChars'], $config['minPasswordLength']]);

		$builder->addDefinition($this->prefix('sign'))
			->setClass(\NAttreid\Crm\Control\SignPresenter::class)
			->setArguments([$config['loginExpiration'], $config['sessionExpiration'], $config['minPasswordLength']]);
	}

	private function setMenu($config)
	{
		$builder = $this->getContainerBuilder();

		$extension = Strings::firstLower($config['namespace']);
		$builder->addDefinition($this->prefix('menu'))
			->setImplement(\NAttreid\Menu\IMenuFactory::class)
			->setFactory(\NAttreid\Menu\Menu::class)
			->addTag('crm.menu')
			->addSetup('setMenu', [
				[$extension . 'Ext' => $config['menu']]
			]);
	}

	private function setMailing($config)
	{
		$builder = $this->getContainerBuilder();

		$rc = new \Nette\Reflection\ClassType(Mailer::class);
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

		$this->setRouting();
		$this->setTranslation();
		$this->setTracy();
		$this->setFlash();
		$this->setLayout($config);
		$this->setModule($config, $namespace);
		$this->setCrmModule();

		$builder->getDefinition('application.presenterFactory')
			->addSetup('setMapping', [
				[$config['namespace'] => 'NAttreid\Crm\Control\*Presenter']
			]);

		$authenticator = $builder->getByType(Authenticator::class);
		$builder->getDefinition($authenticator)
			->addSetup('add', [$namespace, $builder->getDefinition($this->prefix('authenticator'))]);
	}

	private function setRouting()
	{
		$builder = $this->getContainerBuilder();
		$router = $builder->getByType(RouterFactory::class);
		try {
			$builder->getDefinition($router)
				->addSetup('addRouter', ['@' . $this->prefix('router'), RouterFactory::PRIORITY_APP])
				->addSetup('setLocale', ['@' . $this->prefix('configurator') . '::defaultLocale', '@' . $this->prefix('configurator') . '::allowedLocales']);
		} catch (\Nette\DI\MissingServiceException $ex) {
			throw new \Nette\DI\MissingServiceException("Missing extension 'nattreid/routing'");
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
		if ($config['layout'] !== NULL) {
			foreach ($this->findByType(CrmPresenter::class) as $def) {
				$def->addSetup('setLayout', [$config['layout']]);
			}
			foreach ($this->findByType(ExtensionPresenter::class) as $def) {
				$def->addSetup('setLayout', [$config['layout']]);
			}
		} else {
			foreach ($this->findByType(ExtensionPresenter::class) as $def) {
				$def->addSetup('setLayout', [__DIR__ . '/../Control/presenters/templates/@layout.latte']);
			}
		}
	}

	private function setCrmModule()
	{
		foreach ($this->findByType(ModulePresenter::class) as $def) {
			$class = $def->getClass();

			$m = Strings::matchAll($class, '#\\\\(\w+)\\\\Presenters#');

			$module = end($m)[1];
			if (Strings::endsWith($module, 'Module')) {
				$module = substr($module, 0, -6);
			}
			$def->addSetup('setCrmModule', [Strings::firstLower($module)]);
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
				new Statement('addResource', ['neon', __DIR__ . '/../lang/main.cs_CZ.neon', 'cs_CZ', 'main']),
				new Statement('addResource', ['neon', __DIR__ . '/../lang/main.en_US.neon', 'en_US', 'main']),
				new Statement('addResource', ['neon', __DIR__ . '/../lang/default.cs_CZ.neon', 'cs_CZ', 'default']),
				new Statement('addResource', ['neon', __DIR__ . '/../lang/default.en_US.neon', 'en_US', 'default'])
			];
			$def->setSetup(array_merge($def->getSetup(), $setup));
		} catch (\Nette\DI\MissingServiceException $ex) {
			throw new \Nette\DI\MissingServiceException("Missing extension 'kdyby/translation'");
		}
	}

	private function setTracy()
	{
		$builder = $this->getContainerBuilder();
		try {
			$tracy = $builder->getByType(Tracy::class);
			$builder->getDefinition($tracy)
				->addSetup('enableMail', ['@' . $this->prefix('configurator') . '::mailPanel']);
		} catch (\Nette\DI\MissingServiceException $ex) {
			throw new \Nette\DI\MissingServiceException("Missing extension 'nattreid/tracyplugin'");
		}
	}

	private function setFlash()
	{
		$builder = $this->getContainerBuilder();
		try {
			$flash = $builder->getByType(FlashNotifier::class);
			$builder->getDefinition($flash);
		} catch (\Nette\DI\MissingServiceException $ex) {
			throw new \Nette\DI\MissingServiceException("Missing extension 'nattreid/flash-messages'");
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
			return is_a($def->getClass(), $type, TRUE) || is_a($def->getImplement(), $type, TRUE);
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
					$foundFilesList[] = $foundFile->getPathname();
					/* @var $foundFile \SplFileInfo */
				}

				natsort($foundFilesList);

				foreach ($foundFilesList as $foundFilePathname) {
					$normalizedFiles[] = $foundFilePathname;
				}
			} else {
				$this->checkFileExists($file);
				$normalizedFiles[] = $file;
			}
		}

		return $normalizedFiles;
	}

	/**
	 * @param string $file
	 * @throws \WebLoader\FileNotFoundException
	 */
	protected function checkFileExists($file)
	{
		if (!file_exists($file)) {
			throw new \WebLoader\FileNotFoundException(sprintf("Neither '%s' was found", $file));
		}
	}

}
