<?php

declare(strict_types=1);

namespace NAttreid\Cms\Model;

/**
 * Mapper
 *
 * @author Attreid <attreid@gmail.com>
 */
abstract class Mapper extends \NAttreid\Orm\Mapper
{

	public function getTablePrefix(): string
	{
		return '_';
	}

}
