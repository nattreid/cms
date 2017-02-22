<?php

namespace NAttreid\Cms\Model\Locale;

use NAttreid\Orm\Repository;
use Nextras\Orm\Collection\ICollection;

/**
 * Locales Repository
 *
 * @method Locale getById($primaryValue)
 *
 * @author Attreid <attreid@gmail.com>
 */
class LocalesRepository extends Repository
{

	public static function getEntityClassNames()
	{
		return [Locale::class];
	}

	/**
	 * Vrati vychozi lokalizaci
	 * @return Locale
	 */
	public function getDefault()
	{
		return $this->getBy(['default' => 1]);
	}

	/**
	 * @param $locale
	 * @return Locale
	 */
	public function getByLocale($locale)
	{
		return $this->getBy(['name' => $locale]);
	}

	/**
	 * Vrati povolene jazyky
	 * @return ICollection|Locale[]
	 */
	public function findAllowed()
	{
		return $this->findBy(['allowed' => 1]);
	}

	/**
	 * Vrati povolene jazyky
	 * @return array
	 */
	public function fetchAllowed()
	{
		return $this->findAllowed()->fetchPairs('id', 'name');
	}

}
