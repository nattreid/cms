<?php

declare(strict_types = 1);

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

	public static function getEntityClassNames()
	{
		return [Configuration::class];
	}

	/**
	 * Vrati hodnotu
	 * @param string $name
	 * @return Configuration|false
	 */
	public function get(string $name)
	{
		return $this->getBy(['name' => $name]);
	}

}
