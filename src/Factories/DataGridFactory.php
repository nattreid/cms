<?php

namespace NAttreid\Crm\Factories;

use Kdyby\Translation\Translator;
use Nette\ComponentModel\IContainer;
use Ublaboo\DataGrid\DataGrid;

/**
 * Tovaran datagridu
 *
 * @author Attreid <attreid@gmail.com>
 */
class DataGridFactory
{

	use \Nette\SmartObject;

	/** @var Translator */
	private $translator;

	public function __construct(Translator $translator)
	{
		$this->translator = $translator;
	}

	/** @return DataGrid */
	public function create(IContainer $parent = NULL, $name = NULL)
	{
		$grid = new DataGrid($parent, $name);

		$grid->setTranslator($this->translator);
		$grid->setDefaultPerPage(50);
		$grid->setRememberState(FALSE);

		return $grid;
	}

}
