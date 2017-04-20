<?php

declare(strict_types=1);

namespace NAttreid\Cms\Factories;

use NAttreid\Form\Factories\Factory;
use NAttreid\Form\Form;
use NAttreid\Form\Rendering\HorizontalFormRenderer;
use Nette\ComponentModel\IContainer;

/**
 * Tovarna na formular
 *
 * @author Attreid <attreid@gmail.com>
 */
class FormFactory extends Factory
{
	public function create(IContainer $parent = null, string $name = null): Form
	{
		$form = parent::create($parent, $name);
		$form->setRenderer(new HorizontalFormRenderer);
		return $form;
	}


}
