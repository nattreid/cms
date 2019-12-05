<?php

declare(strict_types=1);

namespace NAttreid\Cms\Factories;

use Kdyby\Translation\Translator;
use Nette\ComponentModel\IContainer;
use Nette\SmartObject;
use Ublaboo\DataGrid\DataGrid;

/**
 * Tovaran datagridu
 *
 * @author Attreid <attreid@gmail.com>
 */
class DataGridFactory
{
	use SmartObject;

	/** @var array */
	private $config;

	/** @var Translator */
	private $translator;

	public function __construct(array $config, Translator $translator)
	{
		$this->translator = $translator;
		$this->config = $config;
	}

	/**
	 * @param IContainer $parent
	 * @param string $name
	 * @return DataGrid
	 */
	public function create(IContainer $parent = null, string $name = null): DataGrid
	{
		$grid = new DataGrid($parent, $name);

		$grid->setTranslator($this->translator);
		$grid->setItemsPerPageList($this->config['perPage']['list'], $this->config['perPage']['all']);
		$grid->setDefaultPerPage($this->config['perPage']['list'][0]);

		return $grid;
	}

}
