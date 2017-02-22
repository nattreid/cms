<?php

namespace NAttreid\Cms\Model\Configuration;
use Nextras\Orm\Entity\Entity;

/**
 * Configuration
 *
 * @property int $id {primary-proxy}
 * @property string $name {primary}
 * @property string $serializedValue
 * @property mixed|null $value {virtual}
 *
 * @author Attreid <attreid@gmail.com>
 */
class Configuration extends Entity
{

	protected function getterValue()
	{
		return unserialize($this->serializedValue);
	}

	protected function setterValue($value)
	{
		$this->serializedValue = serialize($value);
		return $value;
	}

}
