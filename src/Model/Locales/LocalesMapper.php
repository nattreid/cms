<?php

namespace NAttreid\Crm\Model;

use NAttreid\Orm\Structure\Table;

/**
 * Locales Mapper
 *
 * @author Attreid <attreid@gmail.com>
 */
class LocalesMapper extends Mapper
{

	protected function createTable(Table $table)
	{
		$table->setDefaultDataFile(__DIR__ . '/locale.sql');

		$table->addPrimaryKey('id')
			->int()
			->setAutoIncrement();
		$table->addColumn('name')
			->varChar()
			->setUnique();
		$table->addColumn('default')
			->boolean()
			->setDefault(0)
			->setKey();
		$table->addColumn('allowed')
			->boolean()
			->setDefault(1);
	}
}
