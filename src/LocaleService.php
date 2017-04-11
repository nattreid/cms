<?php

declare(strict_types=1);

namespace NAttreid\Cms;

use Kdyby\Translation\Translator;
use NAttreid\AppManager\AppManager;
use NAttreid\Cms\Model\Locale\Locale;
use NAttreid\Cms\Model\Orm;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\SmartObject;
use Nextras\Orm\Model\Model;

/**
 * LocaleService
 *
 * @property string $default nastaveni defaultniho jazyka
 * @property string[] $allowed povolene jazyky
 * @property-read int[] $allowedLocaleIds
 * @property-read int $defaultLocaleId
 * @property-read int $currentLocaleId
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
		$this->cache = new Cache($storage, 'nattreid-cms-localeService');
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
	protected function getDefault(): string
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
	protected function getDefaultLocaleId(): int
	{
		return $this->orm->locales->getDefault()->id;
	}

	/**
	 * @return int[]
	 */
	protected function getAllowedLocaleIds(): array
	{
		return $this->orm->locales->findAllowed()->fetchPairs('id', 'id');
	}

	/**
	 * @return array
	 */
	protected function getAllowed(): array
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
	 * @param int $localeId
	 */
	protected function setDefault(int $localeId)
	{
		$this->orm->locales->getById($localeId)->setDefault();
	}

	/**
	 * Nastavi povolene jazyky
	 * @param int[] $allowed
	 */
	protected function setAllowed(array $allowed)
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
	public function fetch(): array
	{
		return $this->orm->locales->fetchPairsById();
	}

	/**
	 * @param string $locale
	 * @return Locale|null
	 */
	public function get(string $locale)
	{
		return $this->orm->locales->getByLocale($locale);
	}

	/**
	 * @param int $id
	 * @return Locale|null
	 */
	public function getById($id)
	{
		return $this->orm->locales->getById($id);
	}

	/**
	 * @return int
	 */
	protected function getCurrentLocaleId(): int
	{
		$locale = $this->translator->getLocale();
		if (!isset($this->currentId[$locale])) {
			$row = $this->get($locale);
			$this->currentId[$locale] = $row ? $row->id : $this->getDefaultLocaleId();
		}

		return $this->currentId[$locale];
	}
}