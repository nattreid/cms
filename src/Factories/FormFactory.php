<?php

namespace NAttreid\Crm\Factories;

use Nette\ComponentModel\IContainer;
use Nextras\Forms\Rendering\Bs3FormRenderer;

/**
 * Tovarna na formular
 *
 * @author Attreid <attreid@gmail.com>
 */
class FormFactory extends \NAttreid\Form\FormFactory
{
	public function create(IContainer $parent = null, $name = null)
	{
		$form = parent::create($parent, $name);

		$form->setRenderer(new Bs3FormRenderer);

		return $form;
	}


}
