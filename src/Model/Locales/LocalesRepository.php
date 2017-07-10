<?php

declare(strict_types=1);

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

	public static function getEntityClassNames(): array
	{
		return [Locale::class];
	}

	/**
	 * Vrati vychozi lokalizaci
	 * @return Locale
	 */
	public function getDefault(): Locale
	{
		return $this->getBy(['default' => 1]);
	}

	/**
	 * @param string $locale
	 * @return Locale|null
	 */
	public function getByLocale(?string $locale): ?Locale
	{
		return $this->getBy(['name' => $locale]);
	}

	/**
	 * Vrati povolene jazyky
	 * @return ICollection|Locale[]
	 */
	public function findAllowed(): ICollection
	{
		return $this->findBy(['allowed' => 1]);
	}

	/**
	 * Vrati povolene jazyky
	 * @return array
	 */
	public function fetchAllowed(): array
	{
		return $this->findAllowed()->fetchPairs('id', 'name');
	}

}
