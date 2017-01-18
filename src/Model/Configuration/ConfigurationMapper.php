<?php

namespace NAttreid\Cms\Model;

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
			->varChar();
	}

}
