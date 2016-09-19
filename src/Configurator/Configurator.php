<?php

namespace NAttreid\Crm\Configurator;

use NAttreid\AppManager\AppManager;
use NAttreid\Crm\Model\Configuration;
use NAttreid\Crm\Model\Orm;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nextras\Orm\Model\Model;

/**
 * Nastaveni aplikace
 *
 * @author Attreid <attreid@gmail.com>
 */
class Configurator implements IConfigurator
{
	private $default = [
		'sendNewUserPassword' => TRUE,
		'sendChangePassword' => TRUE,
		'dockbarAdvanced' => FALSE,
		'mailPanel' => FALSE
	];

	private $tag = 'cache/configuration';

	private $locales;

	/** @var Orm */
	private $orm;

	/** @var Cache */
	private $cache;

	public function __construct(array $locales, Model $orm, IStorage $storage, AppManager $app)
	{
		$this->prepareLocales($locales);
		$this->orm = $orm;
		$this->cache = new Cache($storage, 'nattreid-crm-configurator');
		$app->onInvalidateCache[] = [$this, 'cleanCache'];
		$this->orm->configuration->onFlush[] = function ($persisted, $removed) {
			if (!empty($persisted) || !empty($removed)) {
				$this->cleanCache();
			}
		};
	}

	public function addDefault($property, $value)
	{
		$this->default[$property] = $value;
	}

	public function cleanCache()
	{
		$this->cache->clean([
			Cache::TAGS => [$this->tag]
		]);
	}

	public function __get($name)
	{
		$key = 'cache_configuration_' . $name;

		$result = $this->cache->load($key);
		if ($result === NULL) {
			$result = $this->cache->save($key, function () use ($name) {
				/* @var $configuration Configuration */
				$configuration = $this->orm->configuration->get($name);
				if ($configuration) {
					return $configuration->value;
				} else {
					if (isset($this->default[$name])) {
						return $this->$name = $this->default[$name];
					}
				}
				return FALSE;
			}, [
				Cache::TAGS => [$this->tag]
			]);
		}
		return $result;
	}

	public function __set($name, $value)
	{
		$configuration = $this->orm->configuration->getById($name);
		if ($configuration === NULL) {
			$configuration = new Configuration;
		}
		$configuration->name = $name;
		$configuration->value = $value;
		$this->orm->persistAndFlush($configuration);
	}

	public function fetchConfigurations()
	{
		$conf = $this->orm->configuration->findAll()->fetchPairs('name', 'value');
		return array_merge($this->default, $conf);
	}

	public function fetchLocales()
	{
		return $this->locales;
	}

	private function prepareLocales(array $locales)
	{
		$this->default['defaultLocale'] = $locales[0];
		$this->default['allowedLocales'] = $locales;
		foreach ($locales as $locale) {
			$this->locales[$locale] = $locale;
		}
	}

}
