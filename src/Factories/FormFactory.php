<?php

namespace NAttreid\Crm\Factories;

use NAttreid\Form\Form,
    Kdyby\Translation\Translator,
    Nette\ComponentModel\IContainer;

/**
 * Tovarna na formular
 *
 * @author Attreid <attreid@gmail.com>
 */
class FormFactory {

    use \Nette\SmartObject;

    /** @var Translator */
    private $translator;

    public function __construct(Translator $translator) {
        $this->translator = $translator;
    }

    /** @return Form */
    public function create(IContainer $parent = NULL, $name = NULL) {
        $form = new Form($parent, $name);
        
        $form->setTranslator($this->translator);
        $form->setRenderer(new \Nextras\Forms\Rendering\Bs3FormRenderer);
        
        return $form;
    }

}
