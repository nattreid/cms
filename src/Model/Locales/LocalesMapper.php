<?php

declare(strict_types = 1);

namespace NAttreid\Cms\Model\Locale;

use NAttreid\Cms\Model\Mapper;
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
			->varChar(5)
			->setUnique();
		$table->addColumn('default')
			->bool()
			->setDefault(0)
			->setKey();
		$table->addColumn('allowed')
			->bool()
			->setDefault(1);

		$this->afterCreateTable[] = function () {
			$this->insert([
				[
					'id' => 1,
					'name' => 'en',
					'default' => 1,
					'allowed' => 1
				], [
					'id' => 2,
					'name' => 'cs',
					'default' => 0,
					'allowed' => 1
				]
			]);
		};
	}
}
