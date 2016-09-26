<?php

namespace NAttreid\Crm;

use NAttreid\AppManager\AppManager;
use NAttreid\Crm\Model\Locale;
use NAttreid\Crm\Model\Orm;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\SmartObject;
use Nextras\Orm\Model\Model;

/**
 * LocaleService
 *
 * @property string $default nastaveni defaultniho jazyka
 * @property array $allowed povolene jazyky
 *
 * @author Attreid <attreid@gmail.com>
 */
class LocaleService
{
	use SmartObject;

	private $tag = 'cache/localeService';

	/** @var Orm */
	private $orm;
	/** @var Cache */
	private $cache;

	public function __construct(Model $orm, IStorage $storage, AppManager $app)
	{
		$this->orm = $orm;
		$this->cache = new Cache($storage, 'nattreid-crm-localeService');
		$app->onInvalidateCache[] = [$this, 'cleanCache'];
		$this->orm->locales->onFlush[] = function ($persisted, $removed) {
			if (!empty($persisted) || !empty($removed)) {
				$this->cleanCache();
			}
		};
	}

	/**
	 * Smaze cache
	 */
	public function cleanCache()
	{
		$this->cache->clean([
			Cache::TAGS => [$this->tag]
		]);
	}

	/**
	 * @return string
	 */
	public function getDefault()
	{
		$key = 'default';
		$result = $this->cache->load($key);
		if ($result === NULL) {
			$result = $this->cache->save($key, function () {
				return $this->orm->locales->getDefault()->name;
			}, [
				Cache::TAGS => [$this->tag]
			]);
		}
		return $result;
	}

	/**
	 * @return int
	 */
	public function getDefaultLocaleId()
	{
		return $this->orm->locales->getDefault()->id;
	}

	/**
	 * @return int
	 */
	public function getAllowedLocalesId()
	{
		return $this->orm->locales->findAllowed()->fetchPairs('id', 'id');
	}

	/**
	 * @return array
	 */
	public function getAllowed()
	{
		$key = 'allowed';
		$result = $this->cache->load($key);
		if ($result === NULL) {
			$result = $this->cache->save($key, function () {
				return $this->orm->locales->fetchAllowed();
			}, [
				Cache::TAGS => [$this->tag]
			]);
		}
		return $result;
	}

	/**
	 * Nastavi vychozi jazyk
	 * @param $localeId
	 */
	public function setDefault($localeId)
	{
		$this->orm->locales->getById($localeId)->setDefault();
	}

	/**
	 * Nastavi povolene jazyky
	 * @param array $allowed
	 */
	public function setAllowed(array $allowed)
	{
		$locales = $this->orm->locales->findAll();
		foreach ($locales as $locale) {
			/* @var $locale Locale */
			if (in_array($locale->id, $allowed)) {
				$locale->allowed = TRUE;
			} else {
				$locale->allowed = FALSE;
			}
			$this->orm->persist($locale);
		}
		$this->orm->flush();
	}

	/**
	 * @return array
	 */
	public function fetch()
	{
		return $this->orm->locales->fetchPairsById();
	}
}