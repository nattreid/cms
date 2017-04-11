<?php

declare(strict_types=1);

namespace NAttreid\Cms\Model\Configuration;

use NAttreid\Cms\Model\Mapper;
use NAttreid\Orm\Structure\Table;

/**
 * Configuration Mapper
 *
 * @author Attreid <attreid@gmail.com>
 */
class ConfigurationMapper extends Mapper
{

	protected function createTable(Table $table)
	{
		$table->addPrimaryKey('name')
			->varChar(100);
		$table->addColumn('serializedValue')
			->varChar(500);
	}

}
