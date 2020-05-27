<?php

declare(strict_types=1);

namespace NAttreid\Cms\Configurator;

use NAttreid\AppManager\AppManager;
use NAttreid\Cms\Model\Configuration\Configuration;
use NAttreid\Cms\Model\Orm;
use NAttreid\Utils\Strings;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\InvalidArgumentException;
use Nextras\Orm\Model\Model;

/**
 * Nastaveni aplikace
 *
 * @author Attreid <attreid@gmail.com>
 */
class Configurator implements IConfigurator
{
	private $default = [
		'sendNewUserPassword' => true,
		'sendChangePassword' => true,
		'dockbarAdvanced' => false,
		'mailPanel' => false
	];

	/** @var bool */
	private $disabledCrm;

	private $tag = 'cache/configuration';

	/** @var Orm */
	private $orm;

	/** @var Cache */
	private $cache;

	public function __construct(bool $disabledCrm, Model $orm, IStorage $storage, AppManager $app)
	{
		$this->orm = $orm;
		$this->disabledCrm = $disabledCrm;
		$this->cache = new Cache($storage, 'nattreid-cms-configurator');
		$app->onInvalidateCache[] = [$this, 'cleanCache'];
		$this->orm->configuration->onFlush[] = function ($persisted, $removed) {
			if (!empty($persisted) || !empty($removed)) {
				$this->cleanCache();
			}
		};
	}

	/**
	 * Prida vychozi hodnotu
	 * @param string $property
	 * @param mixed $value
	 */
	public function addDefault(string $property, $value): void
	{
		$this->default[$property] = $value;
	}

	/**
	 * smaze cache
	 */
	public function cleanCache(): void
	{
		$this->cache->clean([
			Cache::TAGS => [$this->tag]
		]);
	}

	public function __get(string $name)
	{
		if ($name === 'disabledCrm') {
			return $this->disabledCrm;
		}
		if (Strings::contains($name, '->')) {
			list($name, $variable) = explode('->', $name);
			return $this->get($name)->$variable ?? false;
		} else {
			return $this->get($name);
		}
	}

	private function get($name)
	{
		$result = $this->cache->load($name);
		if ($result === null) {
			$result = $this->cache->save($name, function () use ($name) {
				$configuration = $this->orm->configuration->get($name);
				if ($configuration) {
					return $configuration->value;
				} else {
					if (isset($this->default[$name])) {
						return $this->$name = $this->default[$name];
					}
				}
				return false;
			}, [
				Cache::TAGS => [$this->tag]
			]);
		}
		return $result;
	}

	public function __set(string $name, $value)
	{
		if ($name === 'disabledCrm') {
			throw new InvalidArgumentException();
		}
		$configuration = $this->orm->configuration->getById($name);
		if ($configuration === null) {
			$configuration = new Configuration;
		}
		$configuration->name = $name;
		$configuration->value = $value;
		$this->orm->persistAndFlush($configuration);
	}

	public function __unset(string $name): void
	{
		$configuration = $this->orm->configuration->getById($name);
		if ($configuration) {
			$this->orm->remove($configuration);
			$this->orm->flush();

		}
	}

	public function fetchConfigurations(): array
	{
		$conf = $this->orm->configuration->findAll()->fetchPairs('name', 'value');
		return array_merge($this->default, $conf);
	}
}
