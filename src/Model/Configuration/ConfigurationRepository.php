<?php

declare(strict_types=1);

namespace NAttreid\Cms\Model\Configuration;

use NAttreid\Orm\Repository;

/**
 * Configuration Repository
 *
 * @method Configuration getById($primaryValue)
 *
 * @author Attreid <attreid@gmail.com>
 */
class ConfigurationRepository extends Repository
{

	public static function getEntityClassNames(): array
	{
		return [Configuration::class];
	}

	/**
	 * Vrati hodnotu
	 * @param string $name
	 * @return Configuration|null
	 */
	public function get(string $name): ?Configuration
	{
		return $this->getBy(['name' => $name]);
	}

}
