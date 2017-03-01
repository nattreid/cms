<?php

declare(strict_types = 1);

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

	/** @var Translator */
	private $translator;

	public function __construct(Translator $translator)
	{
		$this->translator = $translator;
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
		$grid->setDefaultPerPage(50);

		return $grid;
	}

}
