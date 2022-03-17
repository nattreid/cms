<?php

declare(strict_types=1);

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

	protected function createTable(Table $table): void
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

		$this->onCreateTable[] = function () {
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
				], [
					'id' => 3,
					'name' => 'de',
					'default' => 0,
					'allowed' => 1
				], [
					'id' => 4,
					'name' => 'sk',
					'default' => 0,
					'allowed' => 1
				], [
					'id' => 5,
					'name' => 'pl',
					'default' => 0,
					'allowed' => 1
				], [
					'id' => 6,
					'name' => 'es',
					'default' => 0,
					'allowed' => 1
				], [
					'id' => 7,
					'name' => 'hu',
					'default' => 0,
					'allowed' => 1
				], [
					'id' => 8,
					'name' => 'ro',
					'default' => 0,
					'allowed' => 1
				], [
					'id' => 9,
					'name' => 'tr',
					'default' => 0,
					'allowed' => 1
				], [
					'id' => 10,
					'name' => 'ru',
					'default' => 0,
					'allowed' => 1
				], [
					'id' => 11,
					'name' => 'he',
					'default' => 0,
					'allowed' => 1
				], [
					'id' => 12,
					'name' => 'no',
					'default' => 0,
					'allowed' => 1
				], [
					'id' => 13,
					'name' => 'sv',
					'default' => 0,
					'allowed' => 1
				], [
					'id' => 14,
					'name' => 'jp',
					'default' => 0,
					'allowed' => 1
				], [
					'id' => 15,
					'name' => 'fr',
					'default' => 0,
					'allowed' => 1
				], [
					'id' => 16,
					'name' => 'da',
					'default' => 0,
					'allowed' => 1
				]
			]);
		};
	}

	public function unsetDefault(): void
	{
		$this->connection->query('UPDATE %table SET %set', $this->getTableName(), [
			'default' => 0
		]);
	}
}
