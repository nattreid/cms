<?php

namespace NAttreid\Crm\Model;

/**
 * Configuration Mapper
 *
 * @author Attreid <attreid@gmail.com>
 */
class ConfigurationMapper extends Mapper
{

	protected function createTable(\NAttreid\Orm\Structure\Table $table)
	{
		$table->addPrimaryKey('name')
			->varChar(100);
		$table->addColumn('serializedValue')
			->varChar(255);
	}

}
