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

	/** @var Orm */
	private $orm;

	/** @var Cache */
	private $cache;

	public function __construct(Model $orm, IStorage $storage, AppManager $app)
	{
		$this->orm = $orm;
		$this->cache = new Cache($storage, 'nattreid-crm-configurator');
		$app->onInvalidateCache[] = [$this, 'cleanCache'];
		$this->orm->configuration->onFlush[] = function ($persisted, $removed) {
			if (!empty($persisted) || !empty($removed)) {
				$this->cleanCache();
			}
		};
	}

	/**
	 * Prida vychozi hodnotu
	 * @param $property
	 * @param $value
	 */
	public function addDefault($property, $value)
	{
		$this->default[$property] = $value;
	}

	/**
	 * smaze cache
	 */
	public function cleanCache()
	{
		$this->cache->clean([
			Cache::TAGS => [$this->tag]
		]);
	}

	public function __get($name)
	{
		$result = $this->cache->load($name);
		if ($result === NULL) {
			$result = $this->cache->save($name, function () use ($name) {
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
}
