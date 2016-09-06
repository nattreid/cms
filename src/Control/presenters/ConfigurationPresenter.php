<?php

namespace NAttreid\Crm\Control;

use NAttreid\Form\Form;
use NAttreid\TracyPlugin\Tracy;
use Nette\Utils\ArrayHash;

/**
 * Konfigurace webu
 *
 * @author Attreid <attreid@gmail.com>
 */
class ConfigurationPresenter extends CrmPresenter
{

	/** @var Tracy */
	private $tracy;

	public function __construct(Tracy $tracy)
	{
		parent::__construct();
		$this->tracy = $tracy;
	}

	/**
	 * Zobrazeni nastaveni
	 */
	public function renderDefault()
	{
		$form = $this['configurationForm'];
		/** @var $form Form */

		$this->addBreadcrumbLink('main.dockbar.settings.configuration');
		$form->setDefaults($this->configurator->fetchConfigurations());
	}

	public function handleDebug($on)
	{
		if ($this->isAjax()) {
			if ($on) {
				$this->tracy->enable();
			} else {
				$this->tracy->disable();
			}
			$this->redrawControl('configuration');
		} else {
			$this->terminate();
		}
	}

	/**
	 * Komponenta formulare nastaveni
	 * @return Form
	 */
	protected function createComponentConfigurationForm()
	{
		$form = $this->formFactory->create();
		$form->setAjaxRequest();

		$form->addGroup('main.settings.basic');
		$form->addImageUpload('logo', 'main.settings.logo', 'main.settings.deleteLogo')
			->setNamespace('crm')
			->setPreview();
		$form->addCheckbox('sendNewUserPassword', 'main.settings.sendNewUserPassword');
		$form->addCheckbox('sendChangePassword', 'main.settings.sendChangePassword');
		$form->addCheckbox('dockbarAdvanced', 'main.settings.dockbarAdvanced');
		$form->addSelectUntranslated('defaultLang', 'main.settings.defaultLang', $this->configurator->lang);
		$form->addMultiSelectUntranslated('allowedLang', 'main.settings.allowedLang', $this->configurator->lang)
			->setRequired();

		$form->addGroup('main.settings.development');
		if (!$this->tracy->isEnabled()) {
			$form->addLink('debugOn', 'main.settings.debugOn')
				->link($this->link('debug!', TRUE))
				->setAjaxRequest()
				->addClass('btn-success')
				->setAttribute('data-ajax-off', 'history');
		} else {
			$form->addLink('debugOff', 'main.settings.debugOff')
				->link($this->link('debug!', FALSE))
				->setAjaxRequest()
				->addClass('btn-danger')
				->setAttribute('data-ajax-off', 'history');
		}
		$form->addCheckbox('mailPanel', 'main.settings.mailPanel')
			->setDefaultValue($this->configurator->mailPanel);

		$form->addSubmit('save', 'form.save');

		$form->onSuccess[] = [$this, 'configurationFormSucseeded'];

		return $form;
	}

	/**
	 * Ulozeni nastaveni
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function configurationFormSucseeded(Form $form, $values)
	{
		$form->setValues($values);
		foreach ($values as $name => $value) {
			$this->configurator->$name = $value;
		}
		$this->flashNotifier->success('main.settings.settingsSaved');

		if ($this->isAjax()) {
			$this->redrawControl('configuration');
		} else {
			$this->redirect('default');
		}
	}

}
