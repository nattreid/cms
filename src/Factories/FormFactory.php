<?php

namespace NAttreid\Crm\Factories;

use Kdyby\Translation\Translator;
use NAttreid\Form\Form;
use Nette\ComponentModel\IContainer;
use Nette\SmartObject;
use Nextras\Forms\Rendering\Bs3FormRenderer;

/**
 * Tovarna na formular
 *
 * @author Attreid <attreid@gmail.com>
 */
class FormFactory
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
	 * @return Form
	 */
	public function create(IContainer $parent = NULL, $name = NULL)
	{
		$form = new Form($parent, $name);

		$form->setTranslator($this->translator);
		$form->setRenderer(new Bs3FormRenderer);

		return $form;
	}

}
