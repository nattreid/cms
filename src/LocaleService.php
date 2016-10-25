<?php

namespace NAttreid\Crm;

use Kdyby\Translation\Translator;
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

	/** @var Translator */
	private $translator;

	/** @var int[] */
	private $currentId;

	public function __construct(Model $orm, IStorage $storage, AppManager $app, Translator $translator)
	{
		$this->orm = $orm;
		$this->cache = new Cache($storage, 'nattreid-crm-localeService');
		$this->translator = $translator;

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
		if ($result === null) {
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
		if ($result === null) {
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
				$locale->allowed = true;
			} else {
				$locale->allowed = false;
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

	/**
	 * @param $locale
	 * @return Locale
	 */
	public function get($locale)
	{
		return $this->orm->locales->getByLocale($locale);
	}

	/**
	 * @return int
	 */
	public function getCurrentLocaleId()
	{
		$locale = $this->translator->getLocale();
		if (!isset($this->currentId[$locale])) {
			$row = $this->get($locale);
			$this->currentId[$locale] = $row ? $row->id : $this->getDefaultLocaleId();
		}

		return $this->currentId[$locale];
	}
}