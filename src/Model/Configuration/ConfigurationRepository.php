<?php

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
	 * @return Configuration
	 */
	public function get($name)
	{
		return $this->getBy(['name' => $name]);
	}

}
