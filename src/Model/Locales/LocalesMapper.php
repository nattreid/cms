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
		$table->addPrimaryKey('id')
			->int()
			->setAutoIncrement();
		$table->addColumn('name')
			->varChar();
		$table->addColumn('default')
			->boolean()
			->setDefault(0)
			->setKey();
		$table->addColumn('allowed')
			->boolean()
			->setDefault(1);
	}

	protected function loadDefaultData()
	{
		$this->insert([
			'name' => 'en',
			'default' => 1,
			'allowed' => 1
		]);
		$this->insert([
			'name' => 'cs',
			'default' => 0,
			'allowed' => 1
		]);
	}


}
