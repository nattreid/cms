<?php

declare(strict_types=1);

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
		return unserialize(base64_decode($this->serializedValue));
	}

	protected function setterValue($value)
	{
		$this->serializedValue = base64_encode(serialize($value));
		return $value;
	}

}
